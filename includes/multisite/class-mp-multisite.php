<?php

class MP_Multisite {

	/**
	 * Refers to the current multisite build
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	var $build = 2;

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
		$this->maybe_install();
		//we will need to register a post type use for index
		add_action( 'init', array( &$this, 'register_post_type' ) );
		if ( mp_get_network_setting( 'global_cart' ) ) {
			mp_cart()->is_global = true;

			add_filter( 'mp_product/url', array( &$this, 'product_url' ), 10, 2 );
			add_action( 'switch_blog', array( &$this, 'refresh_autoloaded_options' ) );
			add_action( 'mp/cart/before_calculate_shipping', array( &$this, 'load_shipping_plugins' ) );
			add_action( 'mp_order/get_cart', array( &$this, 'maybe_show_cart_global' ), 10, 2 );
		}

		add_filter( 'mp_gateway_api/get_gateways', array( &$this, 'get_gateways' ) );
		//shortcode
		add_shortcode( 'mp_list_global_products', array( &$this, 'mp_list_global_products_sc' ) );
		add_shortcode( 'mp_global_categories_list', array( &$this, 'mp_global_categories_list_sc' ) );
		add_shortcode( 'mp_global_tag_cloud', array( &$this, 'mp_global_tag_cloud_sc' ) );
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
	}

	public function filter_products() {
		$page = mp_get_post_value( 'page', 1 );
		list( $order_by, $order ) = explode( '-', mp_get_post_value( 'order' ) );
		echo mp_global_list_products( array(
			'page'     => $page,
			'order_by' => trim( $order_by ),
			'order'    => trim( $order ),
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
		$current_blog_id = get_current_blog_id();
		$index           = $this->find_index( get_current_blog_id(), $item->ID );
		if ( is_object( $index ) ) {
			//we need to update the sale count
			$sale_count = $item->get_meta( 'mp_sales_count' );
			//turn to the index
			switch_to_blog( 1 );
			update_post_meta( $index->ID, 'mp_sales_count', $sale_count );
			//back to the site
			switch_to_blog( $current_blog_id );
		}
	}

	/**
	 * @param $post_id
	 *
	 * @since 3.0
	 * @access public
	 */
	public function untrash_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return;
		}

		if ( $post->post_type != MP_Product::get_post_type() ) {
			return;
		}
		//we will need to re-add the index
		if ( $post->post_status == 'publish' ) {
			$exist = is_object( $this->find_index( get_current_blog_id(), $post_id ) );
			if ( ! $exist ) {
				$this->add_index( get_current_blog_id(), $post_id );
			}
		}
	}

	/**
	 * @param $post_id
	 *
	 * @since 3.0
	 * @access public
	 */
	public function delete_product( $post_id ) {
		$post = get_post( $post_id );
		if ( ! is_object( $post ) ) {
			return;
		}

		if ( $post->post_type != MP_Product::get_post_type() ) {
			return;
		}
		$current_blog_id = get_current_blog_id();
		$exist           = $this->find_index( $current_blog_id, $post_id );
		if ( is_object( $exist ) ) {
			switch_to_blog( 1 );
			wp_delete_post( $exist->ID, true );
		}
	}

	/**
	 * @param $post_id
	 * @param $post
	 * @param $update
	 *
	 * @since 3.0
	 */
	public function save_post( $post_id, $post, $update ) {
		// If this is just a revision, don't send the email.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( $post->post_type != MP_Product::get_post_type() ) {
			return;
		}

		if ( $post->post_status == 'publish' ) {
			//we only index publish product
			$exist = is_object( $this->find_index( get_current_blog_id(), $post_id ) );
			if ( ! $exist ) {
				$this->add_index( get_current_blog_id(), $post_id );
			}
		}
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
		switch_to_blog( 1 );
		$query = new WP_Query( array(
			'post_type'   => 'mp_ms_indexer',
			'post_status' => 'publish',
			'meta_query'  => array(
				array(
					'key'   => 'blog_id',
					'value' => $blog_id,
				),
				array(
					'key'   => 'post_id',
					'value' => $product_id,
				),
			),
		) );
		switch_to_blog( $blog_id );

		return $query->post_count > 0 ? $query->posts[0] : null;
	}

	/**
	 * Add an index base on product id & blog id
	 *
	 * @param $blog_id
	 * @param $product_id
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_index( $blog_id, $product_id ) {
		//gather data
		$post    = get_post( $product_id );
		$product = new MP_Product( $post->ID );
		$metas   = array(
			'blog_id'        => $blog_id,
			'post_id'        => $post->ID,
			'regular_price'  => $product->get_price( 'lowest' ),
			'mp_sales_count' => $product->get_meta( 'mp_sales_count' ),
		);

		//start to insert index
		switch_to_blog( 1 );
		$id = wp_insert_post( array(
			'post_title'    => $post->post_title,
			'post_type'     => 'mp_ms_indexer',
			'post_status'   => 'publish',
			'post_date'     => $post->post_date,
			'post_date_gmt' => $post->post_date_gmt
		) );

		foreach ( $metas as $key => $val ) {
			update_post_meta( $id, $key, $val );
		}
		switch_to_blog( $blog_id );
	}

	/**
	 * This is use for index the products within network
	 *
	 * @return array
	 *
	 * @since 3.0
	 * @access public
	 */
	public function index_content() {
		//build an index with the whole site
		$data       = array();
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
		$args         = shortcode_atts( mp()->defaults['list_products'], $atts );
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
						if ( isset( $allowed[ $code ] ) && 'full' != $allowed[ $code ] ) {
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

		$this->drop_old_ms_tables();

		update_site_option( 'mp_network_build', $this->build );
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
		return trailingslashit( mp_store_page_url( 'products', false ) . $product->post_name );
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
