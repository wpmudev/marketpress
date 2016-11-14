<?php

class MP_Multisite {

	/**
	 * Refers to the current multisite build
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	var $build = 3;

	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Multisite();
		}

		return self::$_instance;
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		if ( ! is_plugin_active_for_network( mp_get_plugin_slug() ) ) {
			return;
		}
		
		$this->maybe_install();
		//we will need to register a post type use for index
		if ( mp_get_network_setting( 'global_cart' ) ) {
			mp_cart()->is_global = true;

			add_filter( 'mp_product/url', array( &$this, 'product_url' ), 10, 2 );
			add_action( 'switch_blog', array( &$this, 'refresh_autoloaded_options' ) );
			add_action( 'mp/cart/before_calculate_shipping', array( &$this, 'load_shipping_plugins' ) );
			add_action( 'mp_order/get_cart', array( &$this, 'maybe_show_cart_global' ), 10, 2 );
		}
		add_filter( 'rewrite_rules_array', array( &$this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( &$this, 'add_query_vars' ) );

		add_filter( 'mp_gateway_api/get_gateways', array( &$this, 'get_gateways' ) );
		
		$settings = get_site_option( 'mp_network_settings', array() );
		if ( ( isset($settings['main_blog']) && mp_is_main_site() ) || isset($settings['main_blog']) && !$settings['main_blog'] ) {
			//shortcode
			add_shortcode( 'mp_list_global_products', array( &$this, 'mp_list_global_products_sc' ) );
			add_shortcode( 'mp_global_categories_list', array( &$this, 'mp_global_categories_list_sc' ) );
			add_shortcode( 'mp_global_tag_cloud', array( &$this, 'mp_global_tag_cloud_sc' ) );
		}
		
		//filter global product list
		add_action( 'wp_ajax_mp_global_update_product_list', array( &$this, 'filter_products' ) );
		add_action( 'wp_ajax_nopriv_mp_global_update_product_list', array( &$this, 'filter_products' ) );
		//for indexer
		//index products
		add_action( 'wp_insert_post', array( &$this, 'save_post' ), 10, 3 );
		add_action( 'untrashed_post', array( &$this, 'untrash_post' ) );
		add_action( 'trashed_post', array( &$this, 'delete_product' ) );
		add_action( 'after_delete_post', array( &$this, 'delete_product' ) );
		add_action( 'mp_checkout/product_sale', array( &$this, 'record_sale' ), 10, 2 );

		add_filter( 'the_content', array( &$this, 'taxonomy_output' ) );

		add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts' ), 11 );

		add_action( 'wpmu_new_blog', array( &$this, 'wpmu_new_blog' ) );
		add_action( 'admin_init', array( &$this, 'redirect_to_wizard_subsite' ) );
	}

	public function redirect_to_wizard_subsite() {
		if ( ! is_admin() ) {
			return;
		}

		if ( get_current_blog_id() == 1 ) {
			return;
		}

		if ( get_option( 'mp_subsite_need_redirect', 0 ) == 0 ) {
			return;
		}

		$screen = mp_get_current_screen();

		if ( $screen->id == 'store-settings_page_store-setup-wizard' ) {
			//user already inside this first time, return
			update_option( 'mp_subsite_need_redirect', 0 );
			return;
		}

		$ids  = array(
			'product',
			'edit-product',
			'edit-mp_order',
			'toplevel_page_store-settings'
		);
		$base = 'store-settings_page';
		if ( ( in_array( $screen->id, $ids ) || strpos( $screen->id, $base ) === 0 ) ) {
			update_option( 'mp_subsite_need_redirect', 0 );
			wp_redirect( admin_url( 'admin.php?page=store-setup-wizard' ) );
			exit;
		}
	}

	public function wpmu_new_blog( $blog_id ) {
		switch_to_blog( $blog_id );
		update_option( 'mp_subsite_need_redirect', 1 );
		restore_current_blog();
	}

	public function load_scripts() {
		$terms    = mp_global_get_terms( 'product_category' );
		$cat_urls = array();
		foreach ( $terms as $term ) {
			$cat_urls[ $term->term_id ] = mp_global_taxonomy_url( $term->slug, 'product_category' );
		}
		wp_localize_script( 'mp-frontend', 'mp_global', array(
			'cat_urls' => $cat_urls,
			'cat_url'  => get_permalink( mp_get_network_setting( 'pages->networks_categories' ) )
		) );
	}

	public function taxonomy_output( $content ) {
		if ( ! in_the_loop() ) {
			return $content;
		}

		remove_filter( 'the_content', array( &$this, 'taxonomy_output' ) );

		$type     = '';
		$taxonomy = '';
		if ( get_the_ID() == mp_get_network_setting( 'pages->network_categories' ) ) {
			$type     = 'mp_global_category';
			$taxonomy = 'product_category';
		} elseif ( get_the_ID() == mp_get_network_setting( 'pages->network_tags' ) ) {
			$type     = 'mp_global_tag';
			$taxonomy = 'product_tag';
		}

		if ( ! empty( $type ) ) {
			$slug = get_query_var( $type );
			if ( $slug ) {
				$content = do_shortcode( '[mp_list_global_products]' );
			}
		}

		return $content;
	}

	public function add_rewrite_rules( $rewrite_rules ) {
		$new_rules = array();

		if ( $post_id = mp_get_network_setting( 'pages->network_categories' ) ) {
			$uri                                           = get_page_uri( $post_id );
			$new_rules[ $uri . '/([^/]+)/page/([^/]*)/?' ] = 'index.php?pagename=' . $uri . '&mp_global_category=$matches[1]&paged=$matches[2]';
			$new_rules[ $uri . '/([^/]+)/?' ]              = 'index.php?pagename=' . $uri . '&mp_global_category=$matches[1]';
		}

		if ( $post_id = mp_get_network_setting( 'pages->network_tags' ) ) {
			$uri                                           = get_page_uri( $post_id );
			$new_rules[ $uri . '/([^/]+)/page/([^/]*)/?' ] = 'index.php?pagename=' . $uri . '&mp_global_tag=$matches[1]&paged=$matches[2]';
			$new_rules[ $uri . '/([^/]+)/?' ]              = 'index.php?pagename=' . $uri . '&mp_global_tag=$matches[1]';
		}

		return $new_rules + $rewrite_rules;
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'mp_global_category';
		$vars[] = 'mp_global_tag';

		return $vars;
	}

	/**
	 *
	 */
	public function filter_products() {
		$page      = mp_get_post_value( 'page', 1 );
		$widget_id = mp_get_post_value( 'widget_id', - 1 );
		list( $order_by, $order ) = explode( '-', mp_get_post_value( 'order' ) );
		$category = mp_get_post_value( 'product_category', null ) > 0 ? mp_get_post_value( 'product_category' ) : null;
		echo mp_global_list_products( array(
			'page'      => $page,
			'order_by'  => trim( $order_by ),
			'order'     => trim( $order ),
			'widget_id' => $widget_id,
			'category'  => $category
		) );
		die;
	}

	/**
	 * @since 3.0
	 * @access public
	 */
	public function register_post_type() {
		register_post_type( 'mp_ms_indexer', array(
			'public'             => false,
			'show_ui'            => false,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'rewrite'            => false,
			'query_var'          => false,
			'supports'           => array(),
		) );
	}

	/**
	 * This function use for the hook mp_checkout/product_sale, we will need to update the sales count of index
	 *
	 * @param MP_Product $item
	 * @param $paid
	 *
	 * @since 3.0
	 * @access public
	 */
	public function record_sale( MP_Product $item, $paid ) {

	}

	/**
	 * @param $post_id
	 *
	 * @since 3.0
	 * @access public
	 */
	public function untrash_post( $post_id ) {
		$this->add_index( get_current_blog_id(), get_post( $post_id ) );
		$post = get_post( $post_id );
		$this->index_product_terms( get_current_blog_id(), $post );
	}

	/**
	 * @param $post_id
	 *
	 * @since 3.0
	 * @access public
	 */
	public function delete_product( $post_id ) {
		$this->delete_index( get_current_blog_id(), $post_id );
	}

	/**
	 * @param $post_id
	 * @param $post
	 * @param $update
	 *
	 * @since 3.0
	 */
	public function save_post( $post_id, $post, $update ) {
		if ( $post->post_type != MP_Product::get_post_type() ) {
			return;
		}

		if ( ! $update ) {
			//this is new product added, create new index
			$this->add_index( get_current_blog_id(), $post );
		} else {
			//find the indexer id
			$this->update_index( get_current_blog_id(), $post );
		}
		//update the terms
		$this->index_product_terms( get_current_blog_id(), $post );
	}

	/**
	 * This is use for find an index
	 *
	 * @param $blog_id
	 * @param $product_id
	 *
	 * @return mixed
	 * @since 3.0
	 */
	public function find_index( $blog_id, $product_id ) {
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}mp_products WHERE post_id=%d AND blog_id=%d", $product_id, $blog_id );

		return $wpdb->get_row( $sql );
	}

	/**
	 * @param $blog_id
	 * @param $post
	 *
	 * @return false|int
	 */
	public function add_index( $blog_id, $post ) {
		global $wpdb;
		$blog_public  = get_blog_status( $blog_id, 'public' );
		$product      = new MP_Product( $post->ID );
		$product_data = array(
			'site_id'           => $wpdb->siteid,
			'blog_id'           => $blog_id,
			'blog_public'       => $blog_public,
			'post_id'           => $post->ID,
			'post_author'       => $post->post_author,
			'post_title'        => $post->post_title,
			'post_content'      => strip_shortcodes( $post->post_content ),
			'post_permalink'    => $product->url( false ),
			'post_date'         => $post->post_date,
			'post_date_gmt'     => $post->post_date_gmt,
			'post_modified'     => $post->post_modified,
			'post_modified_gmt' => $post->post_modified_gmt,
			'post_status'       => $post->post_status,
			'price'             => $product->get_price( 'lowest' ),
			'sales_count'       => $product->get_meta( 'mp_sales_count' )
		);
		$wpdb->insert( $wpdb->base_prefix . 'mp_products', $product_data );
		$index_id = $wpdb->insert_id;
		return $index_id;
	}

	public function update_index( $blog_id, $post ) {
		global $wpdb;
		$blog_public = get_blog_status( $blog_id, 'public' );
		$product     = new MP_Product( $post->ID );
		$index       = $this->find_index( $blog_id, $post->ID );

		if ( ! $index ) {
			return false;
		}
		$product_data = array(
			'site_id'           => $wpdb->siteid,
			'blog_id'           => $blog_id,
			'blog_public'       => $blog_public,
			'post_id'           => $post->ID,
			'post_author'       => $post->post_author,
			'post_title'        => $post->post_title,
			'post_content'      => strip_shortcodes( $post->post_content ),
			'post_permalink'    => $product->url( false ),
			'post_date'         => $post->post_date,
			'post_date_gmt'     => $post->post_date_gmt,
			'post_modified'     => $post->post_modified,
			'post_modified_gmt' => $post->post_modified_gmt,
			'post_status'       => $post->post_status,
			'price'             => $product->get_price( 'lowest' ),
			'sales_count'       => $product->get_meta( 'mp_sales_count' )
		);
		unset( $product_data['site_id'] );
		unset( $product_data['blog_id'] );
		unset( $product_data['post_id'] );

		$wpdb->update( $wpdb->base_prefix . 'mp_products', $product_data, array(
			'post_id' => $post->ID,
			'blog_id' => $blog_id
		) );

		return $index->id;
	}

	public function index_product_terms( $blog_id, $post ) {
		global $wpdb;

		$indexer = $this->find_index( $blog_id, $post->ID );
		if ( ! $indexer ) {
			return;
		}

		$index_id = $indexer->id;

		$terms      = wp_get_object_terms( $post->ID, array( 'product_category', 'product_tag' ) );
		$while_list = array();
		foreach ( $terms as $term ) {
			//check if the term exist
			$exist = mp_global_term_exist( $term->slug, $term->taxonomy );
			if ( ! is_object( $exist ) ) {
				//term not exists, just create
				$wpdb->insert( $wpdb->base_prefix . 'mp_terms', array(
					'name' => $term->name,
					'slug' => $term->slug,
					'type' => $term->taxonomy
				) );
				$term_id = $wpdb->insert_id;
			} else {
				$term_id = $exist->term_id;
			}

			$sql    = $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->base_prefix}mp_term_relationships WHERE post_id = %d AND blog_id = %d AND term_id=%d",
				$index_id, $blog_id, $term_id
			);
			$linked = $wpdb->get_var( $sql );
			if ( ! $linked ) {
				$wpdb->insert( $wpdb->base_prefix . 'mp_term_relationships', array(
					'post_id' => $index_id,
					'blog_id' => $blog_id,
					'term_id' => $term_id
				) );
			}

			$while_list[] = "'$term_id'";
		}

		if ( empty( $while_list ) ) {
			$while_list[] = - 1;
		}

		$while_list = implode( ',', $while_list );

		$sql = "DELETE FROM {$wpdb->base_prefix}mp_term_relationships WHERE post_id = $index_id AND term_id NOT IN ($while_list)";
		$wpdb->query( $sql );
	}

	public function delete_index( $blog_id, $product_id ) {
		global $wpdb;

		$sql = $wpdb->prepare( "DELETE p.*, r.* FROM {$wpdb->base_prefix}mp_products p LEFT JOIN {$wpdb->base_prefix}mp_term_relationships r ON p.id = r.post_id WHERE p.site_id = {$wpdb->siteid} AND p.blog_id = {$blog_id} AND p.post_id = %d", $product_id );
		$wpdb->query( $sql );

		$sql_r = $wpdb->prepare( "DELETE FROM {$wpdb->base_prefix}mp_term_relationships WHERE post_id = %d and blog_id = %d", $product_id, $blog_id );		
		$wpdb->query( $sql_r );
	}

	public function count() {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM " . $wpdb->base_prefix . "mp_products";

		return $wpdb->get_var( $sql );
	}

	/**
	 * Loop through all the blogs, we will store all the products/categories/tags of the blog
	 * to global table.
	 * After store all to the table, started to create relations
	 */
	public function index_content() {
		$this->maybe_create_ms_tables();
		//Delete all records on mp_terms table to fix issue with deleted categories / tags still exist
		$this->truncate_index_table();
		$blogs = wp_get_sites();
		$count = 0;
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			$tmp = new WP_Query( array(
				'post_type'   => MP_Product::get_post_type(),
				'nopaging'    => true,
				'post_status' => 'publish'
			) );
			global $wpdb;

			$blog_archived = get_blog_status( $wpdb->blogid, 'archived' );
			$blog_mature   = get_blog_status( $wpdb->blogid, 'mature' );
			$blog_spam     = get_blog_status( $wpdb->blogid, 'spam' );
			$blog_deleted  = get_blog_status( $wpdb->blogid, 'deleted' );
			if ( $tmp->post_count > 0 ) {
				foreach ( $tmp->posts as $post ) {
					if ( $post->post_status != 'published' || $blog_archived || $blog_deleted || $blog_mature || $blog_spam ) {
						//todo delete index
					}

					if ( $index = $this->find_index( $blog['blog_id'], $post->ID ) ) {
						$index_id = $this->update_index( $blog['blog_id'], $post );
					} else {
						$index_id = $this->add_index( $blog['blog_id'], $post );
					}

					//product indexed, now taxonomies & terms
					$this->index_product_terms( $blog['blog_id'], $post );
					$count ++;
				}
			}
		}

		return array(
			'count' => $count
		);
	}

	/**
	 * This is use for index the products within network
	 *
	 * @return array
	 *
	 * @since 3.0
	 * @access public
	 * @deprecated
	 */
	public function _index_content() {
		_deprecated_function( 'deprecated from 3.0.0.3', '3.0.0.3' );
		//build an index with the whole site

		$categories = array();
		$tags       = array();
		$blogs      = wp_get_sites();
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			$tmp = new WP_Query( array(
				'post_type'   => MP_Product::get_post_type(),
				'nopaging'    => true,
				'post_status' => 'publish'
			) );
			if ( $tmp->post_count > 0 ) {
				foreach ( $tmp->posts as $post ) {
					$product = new MP_Product( $post->ID );
					$data[]  = array(
						'blog_id'        => $blog['blog_id'],
						'post'           => $post->to_array(),
						'regular_price'  => $product->get_price( 'lowest' ),
						'mp_sales_count' => $product->get_meta( 'mp_sales_count' ),
					);
				}
			}
			//now we need to process the taxonomy
			$cats = get_terms( 'product_category', array(
				//only get parent
				'hierarchical' => false
			) );
			foreach ( $cats as $cat ) {
				$categories[] = array(
					'blog_id' => $blog['blog_id'],
					'term_id' => $cat->term_id,
					'name'    => $cat->name,
					'slug'    => $cat->slug,
					'count'   => $cat->count
				);
			}

			$ts = get_terms( 'product_tag', array(
				//only get parent
				'hierarchical' => false
			) );
			foreach ( $ts as $tag ) {
				$tags[] = array(
					'blog_id' => $blog['blog_id'],
					'term_id' => $tag->term_id,
					'name'    => $tag->name,
					'slug'    => $tag->slug,
					'count'   => $tag->count
				);
			}
		}

		switch_to_blog( 1 );
		//got the index, we will need to drop the old index for new
		$indexer = new WP_Query( array(
			'post_type' => 'mp_ms_indexer',
			'nopaging'  => true,
			'fields'    => 'ids'
		) );

		if ( $indexer->post_count > 0 ) {
			foreach ( $indexer->posts as $p_id ) {
				//after the wp_delete_post, it auto switch back to original blog, so we need to switch to 1
				switch_to_blog( 1 );
				wp_delete_post( $p_id, true );
			}
		}

		//stared to import
		foreach ( $data as $row ) {
			//import the post first
			$args = $row['post'];

			$id = wp_insert_post( array(
				'post_title'    => $args['post_title'],
				'post_type'     => 'mp_ms_indexer',
				'post_status'   => 'publish',
				'post_date'     => $args['post_date'],
				'post_date_gmt' => $args['post_date_gmt']
			) );
			update_post_meta( $id, 'blog_id', $row['blog_id'] );
			update_post_meta( $id, 'post_id', $args['ID'] );
			update_post_meta( $id, 'regular_price', $row['regular_price'] );
			update_post_meta( $id, 'mp_sales_count', $row['mp_sales_count'] );
		}
		//products done, now we process the tax
		update_site_option( 'mp_product_category', $categories );
		update_site_option( 'mp_product_tag', $tags );

		return array(
			'count' => count( $data )
		);
	}

	/**
	 * @param $atts
	 *
	 * @return string
	 */
	function mp_global_tag_cloud_sc( $atts ) {
		return mp_global_taxonomy_list( 'product_tag', $atts, false );
	}

	/**
	 * @param $atts
	 *
	 * @return string
	 * @since 3.0
	 */
	function mp_global_categories_list_sc( $atts ) {
		return mp_global_taxonomy_list( 'product_category', $atts, false );
	}

	/**
	 * @param $atts
	 *
	 * @return string
	 */
	function mp_list_global_products_sc( $atts ) {
		$atts['echo'] = false;
		if ( $var = get_query_var( 'mp_global_category' ) ) {
			$atts['category'] = $var;
		}
		if ( $var = get_query_var( 'mp_global_tag' ) ) {
			$atts['tag'] = $var;
		}
		$args = shortcode_atts( mp()->defaults['list_products'], $atts );

		return mp_global_list_products( $args );
	}

	/**
	 * @param $cart
	 * @param MP_Order $order
	 *
	 * @return mixed
	 */
	public function maybe_show_cart_global( $cart, MP_Order $order ) {
		//order not exist
		if ( ! $order->exists() || is_admin() ) {
			return $cart;
		}
		$id                 = $order->get_id();
		$global_order_index = get_site_option( 'mp_global_order_index', array() );

		if ( isset( $global_order_index[ $id ] ) ) {
			return $global_order_index[ $id ];
		}

		return $cart;
	}

	/**
	 * @since 3.0
	 */
	public function load_shipping_plugins() {
		/**
		 * Shipping plugin will load in very first runtime, and only for the single site. So in global cart,
		 * sometime it won't load the necessary, we need to check and load it
		 */
		MP_Shipping_API::load_active_plugins( true );
	}

	/**
	 * Drop old multisite tables
	 *
	 * @since 3.0
	 * @access public
	 * @global $wpdb
	 */
	public function drop_old_ms_tables() {
		global $wpdb;

		$table1 = $wpdb->base_prefix . 'mp_products';
		$table2 = $wpdb->base_prefix . 'mp_terms';
		$table3 = $wpdb->base_prefix . 'mp_term_relationships';

		$wpdb->query( "DROP TABLE IF EXISTS $table1, $table2, $table3" );
	}
	
	/**
	 * Truncate index table
	 *
	 * @since 3.0
	 * @access public
	 * @global $wpdb
	 */
	public function truncate_index_table() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->base_prefix}mp_terms WHERE type = 'product_category' OR type = 'product_tag' " );
	}

	/**
	 * Filter out gateways that aren't allowed according to network admin settings
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_gateway_api/get_gateways
	 */
	public function get_gateways( $gateways ) {
		if ( ! is_network_admin() ) {
			if ( mp_get_network_setting( 'global_cart' ) ) {
				$code = mp_get_network_setting( 'global_gateway' );
				if ( ! empty( $code ) ) {
					$gateways = array( $code => $gateways[ $code ] );
				} else {
					//case no gateway picked in the admin
					//todo show info to admin
					$gateways = array();
				}
			} else {
				$allowed                = mp_get_network_setting( 'allowed_gateways' );
				$allowed['free_orders'] = 'full';//Always allow and activate it automatically later if needed

				if ( is_array( $allowed ) ) {
					foreach ( $gateways as $code => $gateway ) {
						if ( isset( $allowed[ $code ] ) && 'none' == $allowed[ $code ] ) {
							unset( $gateways[ $code ] );
						}
					}
				}
			}
		}

		return $gateways;
	}

	/**
	 * Check to see if install sequence needs to be run
	 *
	 * @since 3.0
	 * @access public
	 */
	public function maybe_install() {
		$build = (int) get_site_option( 'mp_network_build', 1 );

		//check if installed
		if ( $this->build === $build ) {
			return;
		}

		//$this->drop_old_ms_tables();
		$this->maybe_create_ms_tables();


		update_site_option( 'mp_network_build', $this->build );
	}

	function maybe_create_ms_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table_product = $wpdb->base_prefix . 'mp_products';
		if ( ! $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_product ) ) == $table_product ) {
			$table_1 = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}mp_products` (
								`id` bigint(20) unsigned NOT NULL auto_increment,
								`site_id` bigint(20),
								`blog_id` bigint(20),
								`blog_public` int(2),
								`post_id` bigint(20),
								`post_author` bigint(20) unsigned NOT NULL DEFAULT '0',
								`post_title` text NOT NULL,
								`post_content` longtext NOT NULL,
								`post_excerpt` longtext NOT NULL,
								`post_permalink` text NOT NULL,
								`post_status` varchar(20) NOT NULL,
								`post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
								`post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
								`post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
								`post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
								`price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
								`sales_count` bigint(20) unsigned NOT NULL DEFAULT '0',
								PRIMARY KEY	 (`id`)
							) ENGINE=MyISAM	 DEFAULT CHARSET=utf8;";
			dbDelta( $table_1 );
		}
		$table_terms = $wpdb->base_prefix . 'mp_terms';
		if ( ! $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_terms ) ) == $table_terms ) {
			$table_2 = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}mp_terms` (
								`term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
								`name` varchar(200) NOT NULL DEFAULT '',
								`slug` varchar(200) NOT NULL DEFAULT '',
								`type` varchar(20) NOT NULL DEFAULT 'product_category',
								`count` bigint(10) NOT NULL DEFAULT '0',
								PRIMARY KEY (`term_id`),
								KEY `name` (`name`)
							) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
			dbDelta( $table_2 );
		}

		$table_relations = $wpdb->base_prefix . 'mp_term_relationships';
		if ( ! $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_relations ) ) == $table_relations ) {
			$table_3 = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}mp_term_relationships` (
								`post_id` bigint(20) unsigned NOT NULL,
								`blog_id` bigint(20) unsigned NOT NULL,
								`term_id` bigint(20) unsigned NOT NULL,
								`public`  boolean NOT NULL DEFAULT 1,
								PRIMARY KEY ( `post_id` , `term_id` ),
								KEY (`term_id`)
							) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
			dbDelta( $table_3 );
		}
	}

	/**
	 * Add/update network settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function ms_settings() {
		$settings = get_site_option( 'mp_network_settings', array() );

		$default_settings = array(
			'global_cart'      => 0,
			'allowed_gateways' => array(),
			'global_gateway'   => 'paypal_express',
			'allowed_themes'   => array(
				'default' => 'full',
			),
		);

		if ( ! class_exists( 'MP_Gateway_API' ) ) {
			require_once mp_plugin_dir( 'includes/common/payment-gateways/class-mp-gateway-api.php' );
		}

		$gateways = MP_Gateway_API::get_gateways();
		foreach ( $gateways as $code => $gateway ) {
			$access = ( $gateway->plugin_name != 'paypal_express' ) ? 'none' : 'full';
			mp_push_to_array( $default_settings, "allowed_gateways->{$code}", $access );
		}

		$new_settings = array_replace_recursive( $default_settings, $settings );

		update_site_option( 'mp_network_settings', $new_settings );
	}

	/**
	 * Make sure product post types are indexed by Post Indexer
	 *
	 * @since 3.0
	 * @access public
	 */
	public function post_indexer_set_post_types() {
		$pi_post_types = (array) get_site_option( 'postindexer_globalposttypes', array( 'post' ) );
		$changed       = false;

		foreach ( mp()->post_types as $post_type ) {
			if ( ! in_array( $post_type, $pi_post_types ) ) {
				$pi_post_types[] = $post_type;
				$changed         = true;
			}
		}

		if ( $changed ) {
			update_site_option( 'postindexer_globalposttypes', $pi_post_types );
		}
	}

	/**
	 * Get the correct product url when global cart is enabled
	 *
	 * When using switch_to_blog $wp_rewrite permastructs don't get updated so
	 * this is required to get the correct product url.
	 *
	 * See https://core.trac.wordpress.org/ticket/20861 for more info.
	 *
	 * @since 3.0
	 * @access public
	 */
	public function product_url( $url, $product ) {
		if ( $product->is_variation() && $product->get_parent() !== false ) {
			$url = trailingslashit( mp_store_page_url( 'products', false ) . $product->get_parent()->post_name ) . 'variation/' . $product->ID;
		} else {
			$url = trailingslashit( mp_store_page_url( 'products', false ) . $product->post_name );
		}
		return $url ;
	}

	/**
	 * Reload MP settings after switching blogs
	 *
	 * When using switch_to_blog auto-loaded options aren't refreshed which causes
	 * mp_settings to not update accordingly which affects things like tax and
	 * shipping rates.
	 *
	 * @since 3.0
	 * @access public
	 * @action switch_blog
	 */
	public function refresh_autoloaded_options() {
		wp_load_alloptions();
	}

}

MP_Multisite::get_instance();
