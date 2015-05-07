<?php

class MP_Public {
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
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Public();
		}
		return self::$_instance;
	}
	
	/**
	 * Constructor
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		$this->includes();
		
		add_filter( 'get_post_metadata', array( &$this, 'remove_product_post_thumbnail' ), 999, 4 );
		add_action( 'wp_enqueue_scripts', array( &$this, 'frontend_styles_scripts' ) );
		add_filter( 'comments_open', array( &$this, 'disable_comments_on_store_pages' ), 10, 2 );
		add_action( 'wp', array( &$this, 'maybe_start_session' ) );
		add_action( 'wp_footer', array( &$this, 'create_account_lightbox_html' ), 20 );
		
		// Template Stuff
		add_filter( 'taxonomy_template', array( &$this, 'load_taxonomy_template' ) );
		add_filter( 'single_template', array( &$this, 'load_single_product_template' ) );
		add_filter( 'page_template', array( &$this, 'load_page_template' ) );
		
		//Downloads
		add_action( 'pre_get_posts', array(&$this, 'include_out_of_stock_products_for_downloads') );
		add_filter( 'posts_results', array(&$this, 'set_publish_status_for_out_of_stock_product_downloads'), 10, 2 );
		add_action( 'template_redirect', array(&$this, 'maybe_serve_download') );
	}
	
	/**
	 * Output the html for the "create account" lightbox
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_footer
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	public function create_account_lightbox_html( $echo = true ) {
		if ( is_user_logged_in() ) {
			// Bail - user is logged in (e.g. already has an account)
			return false;
		}
		
		if ( 'wp_footer' == current_filter() ) {
			$echo = true;
		}
		
		$html = '
			<div style="display:none">
				<div id="mp-create-account-lightbox" class="entry-content">
					<h2>' . __( 'Create Account', 'mp' ) . '</h2>
					<form action="' . admin_url( 'admin-ajax.php?action=mp_create_account' ) . '" method="post">' .
						wp_nonce_field( 'mp_create_account', 'mp_create_account_nonce', true, false ) . '
						<div class="mp-form-row" class="clearfix">
							<label for="mp-create-account-name-first">' . __( 'First Name:', 'mp' ) . '<span class="mp-field-required">*</span></label>
							<div><input id="mp-create-account-name-first" type="text" name="name_first" data-rule-required="true" /></div>
						</div>
						<div class="mp-form-row" class="clearfix">
							<label for="mp-create-account-name-last">' . __( 'Last Name:', 'mp' ) . '<span class="mp-field-required">*</span></label>
							<div><input id="mp-create-account-name-last" type="text" name="name_last" data-rule-required="true" /></div>
						</div>
						<div class="mp-form-row" class="clearfix">
							<label for="mp-create-account-email">' . __( 'Email:', 'mp' ) . '<span class="mp-field-required">*</span></label>
							<div><input id="mp-create-account-email" type="email" name="email" data-rule-required="true" data-rule-email="true" data-rule-remote="' . admin_url( 'admin-ajax.php?action=mp_check_if_email_exists' ) . '" data-msg-remote="' . __( 'An account with this email address already exists', 'mp' ) . '" /></div>
						</div>
						<div class="mp-form-row">
							<label for="mp-create-account-password1">' . __( 'Password:', 'mp' ) . '<span class="mp-field-required">*</span></label>
							<div><input id="mp-create-account-password1" type="password" name="password1" data-rule-required="true" /></div>
						</div>
						<div class="mp-form-row">
							<label for="mp-create-account-password2">' . __( 'Re-enter Password:', 'mp' ) . '<span class="mp-field-required">*</span></label>
							<div><input id="mp-create-account-password2" type="password" name="password2" data-rule-required="true" data-rule-equalTo="#mp-create-account-password1" data-msg-equalTo="' . __( 'Passwords do not match!', 'mp' ) . '" /></div>
						</div>
						<div class="mp-form-row">
							<button type="submit">' . __( 'Create Account', 'mp' ) . '</button>
						</div>
					</form>
				</div>
			</div>';
			
		/**
		 * Filter the "create account" lightbox html
		 *
		 * @since 3.0
		 * @param string $html The current html.
		 */
		$html = apply_filters( 'mp_public/create_account_lightbox_html', $html );
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

	/**
	 * Safely start session
	 *
	 * @since 3.0
	 * @access protected
	 */
	public function start_session() {
		$sess_id = session_id();
		if ( empty($sess_id) ) {
			@session_start();
		}
	}
		
	/**
	 * Disable comments on store pages
	 *
	 * @since 3.0
	 * @access public
	 */
	public function disable_comments_on_store_pages( $open, $post_id ) {
		if ( get_post_type($post_id) == MP_Product::get_post_type() || get_post_meta($post_id, '_mp_store_page', true) !== '' ) {
			$open = false;
		}
		
		return $open;
	}

	/**
	 * Hide the single product title
	 *
	 * @since 3.0
	 * @access public
	 * @filter the_title
	 * @return string
	 */
	public function hide_single_product_title( $title ) {
		if ( in_the_loop() && is_main_query() ) {
			$title = '';
		}
		
		return $title;
	}
	
	/**
	 * Check if the current page is a store page
	 *
	 * @since 3.0
	 * @param string $page The specific page to check - e.g. "cart".
	 * @return bool
	 */
	function is_store_page( $page = null ) {
		if ( is_null($page) ) {
			return ( get_post_meta(get_the_ID(), '_mp_store_page', true) !== '' || is_singular(MP_Product::get_post_type()) || is_tax(array('product_category', 'product_tag')) );
		} else {
			$page = (array) $page;
			return ( in_array(get_post_meta(get_the_ID(), '_mp_store_page', true), $page) );
		}
	}
	
	/**
	 * Include files
	 *
	 * @since 3.0
	 * @access public
	 */
	public function includes() {
		require_once mp_plugin_dir('includes/public/class-mp-checkout.php');
		require_once mp_plugin_dir('includes/public/class-mp-short-codes.php');
	}

	/**
	 * Modify query object to allow drafts for single products
	 *
	 * If a product is set to out_of_stock status then the user won't be
	 * able to download their files.
	 *
	 * @since 2.9.5.8
	 * @access public
	 * @action pre_get_posts
	 */
	public function include_out_of_stock_products_for_downloads( $query ) {
		if ( MP_Product::get_post_type() == $query->get( 'post_type' ) && $query->get( MP_Product::get_post_type() ) && ($order = mp_get_get_value( 'orderid' ) ) ) {
			$query->set( 'post_status', array( 'out_of_stock', 'publish' ) );
		}
	}
	
	/**
	 * Enqueue frontend styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function frontend_styles_scripts() {
		if ( ! $this->is_store_page() ) {
			return;
		}
		
		// CSS
		wp_register_style( 'jquery-ui', mp_plugin_url( 'ui/css/jquery-ui.min.css' ), false, MP_VERSION );
		wp_enqueue_style( 'mp-frontend', mp_plugin_url( 'ui/css/frontend.css' ), array( 'jquery-ui' ), MP_VERSION );
		wp_enqueue_style( 'mp-theme', mp_plugin_url( 'ui/themes/' . mp_get_setting( 'store_theme' ) . '.css' ), array( 'mp-frontend' ), MP_VERSION );
		wp_enqueue_style( 'select2', mp_plugin_url( 'ui/select2/select2.css'), false, MP_VERSION );
		
		// JS
		wp_register_script( 'hover-intent', mp_plugin_url( 'ui/js/hoverintent.min.js'), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'select2', mp_plugin_url( 'ui/select2/select2.min.js'), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'colorbox', mp_plugin_url( 'ui/js/jquery.colorbox-min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_enqueue_script( 'mp-frontend', mp_plugin_url( 'ui/js/frontend.js'), array( 'jquery-ui-tooltip', 'colorbox', 'hover-intent', 'select2' ), MP_VERSION, true );
		
		// Get product category links
		$terms = get_terms( 'product_category'  );
		$cats = array();
		foreach ( $terms as $term ) {
			$cats[ $term->term_id ] = get_term_link( $term );
		}
		
		// Localize js
		wp_localize_script( 'mp-frontend', 'mp_i18n', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'loadingImage' => mp_plugin_url( 'ui/images/loading.gif' ),
			'productsURL' => mp_store_page_url( 'products', false ),
			'productCats' => $cats,
		) );
	}
	
	/**
	 * Load template for a store page
	 *
	 * @since 3.0
	 * @access public
	 * @filter page_template
	 * @uses $post
	 */
	public function load_page_template( $template ) {
		global $post;
		
		if ( mp_get_setting('pages->store') == $post->ID ) {
			$template = locate_template(array('mp_store.php'));		
		} elseif ( mp_get_setting('pages->product') == $post->ID ) {
			$template = locate_template(array('mp_productlist.php'));
		} elseif ( mp_get_setting('pages->cart') == $post->ID ) {
			$template = locate_template(array('mp_cart.php'));
		} elseif ( mp_get_setting('pages->checkout') == $post->ID ) {
			$template = locate_template(array('mp_checkout.php', 'mp_cart.php'));
		} elseif ( mp_get_setting('pages->order_status') == $post->ID ) {
			$template = locate_template(array('mp_orderstatus.php'));
		}
		
		return $template;
	}
	
	/**
	 * Load template for a single product
	 *
	 * @since 3.0
	 * @access public
	 * @filter single_template
	 * @uses $post, $wp_query
	 */
	public function load_single_product_template( $template ) {
		global $post, $wp_query;
		
		if ( get_post_type() == MP_Product::get_post_type() ) {
			$template = locate_template(array(
				"mp_product-{$post->post_name}.php",
				"mp_product-{$post->ID}.php",
				"mp_product.php",
			));
			
			if ( $template === '' ) {
				$ok = true;
				
				if ( $variation_id = get_query_var('mp_variation_id') ) {
					$variation = new MP_Product($variation_id);
					
					// Make sure variation actually exists, otherwise trigger a 404 error
					if ( ! $variation->exists() ) {
						$ok = false;
						$wp_query->set_404();
						$template = locate_template(array(
							'404.php',
							'index.php',
						));
					}
				}
				
				if ( $ok ) {
					add_filter('the_title', array(&$this, 'hide_single_product_title'));
					add_filter('the_content', array(&$this, 'single_product_content'));
				}
			}
		}
		
		return $template;
	}
		
	/**
	 * Load page template for product_category and product_tag
	 *
	 * We don't want to use the default taxonomy template as this doesn't provide
	 * enough flexibility to correctly display product layouts, etc
	 *
	 * @since 3.0
	 * @access public
	 * @filter taxonomy_template
	 * @uses $wp_query
	 */
	public function load_taxonomy_template( $template ) {
		global $wp_query;
		
		switch ( get_query_var('taxonomy') ) {
			case 'product_category' :
			case 'product_tag' :
				$term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
			
			case 'product_category' :
				$template = locate_template(array(
					'mp_category-' . get_query_var('taxonomy') . '.php',
					'mp_category-' . $term->term_id . '.php',
					'mp_category.php',
					'mp_taxonomy.php',
					'taxonomy-product_category-' . get_query_var('term') . '.php',
					'taxonomy-product_category.php',
					'mp_productlist.php',
					'page.php',
				));
			break;
			
			case 'product_tag' :
				$template = locate_template(array(
					'mp_tag-' . get_query_var('taxonomy') . '.php',
					'mp_tag-' . $term->term_id . '.php',
					'mp_tag.php',
					'mp_taxonomy.php',
					'taxonomy-product_tag-' . get_query_var('term') . '.php',
					'taxonomy-product_tag.php',
					'mp_productlist.php',
					'page.php',
				));
			break;
		}
		
		if ( strpos($template, 'page.php') !== false ) {
			// Hide edit-post links
			add_filter('edit_post_link', create_function('', 'return "";'));
			// Filter output of the_title()
			add_filter('the_title', array(&$this, 'taxonomy_title'));
			// Filter output of the_content()
			add_filter('the_content', array(&$this, 'taxonomy_content'));
			// Only show the first post	
			$wp_query->post_count = 1;
		}
		
		return $template;
	}

	/**
	 * Maybe serve a download
	 *
	 * @since 2.9.5.8
	 * @access public
	 * @action template_redirect
	 */
	function maybe_serve_download() {
		if ( MP_Product::get_post_type() == get_query_var( 'post_type' ) && get_query_var( MP_Product::get_post_type() ) && ( $order = mp_get_get_value( 'orderid' ) ) ) {
			$product_id = ( $variation_id = get_query_var( 'mp_variation_id' ) ) ? $variation_id : get_queried_object_id();
			$this->serve_download( $product_id );
		}
	}
	
	/**
	 * Maybe start the session
	 *
	 * @since 3.0
	 * @access public
	 * @action init
	 */
	public function maybe_start_session() {
		if ( ! mp_is_shop_page( 'checkout' ) && ! mp_is_shop_page( 'cart' ) ) {
			return;
		}
		
		$this->start_session();
	}
	
	/**
	 * Hide the post thumbnail on single product, product category and product tag templates
	 *
	 * @since 3.0
	 * @access public
	 * @filter get_post_metadata
	 */
	public function remove_product_post_thumbnail( $content, $post_id, $meta_key, $single ) {
		if ( (is_singular( MP_Product::get_post_type() ) || is_tax( array( 'product_category', 'product_tax' ) )) && is_main_query() && in_the_loop() && $meta_key == '_thumbnail_id' ) {
			return false;
		}
		
		return $content;
	}

	/**
	 * Serve a file download
	 *
	 * @since 3.0
	 * @access public
	 */
	function serve_download( $product_id ) {
		$order_id = mp_get_get_value( 'orderid' );
		if ( ! $order_id ) {
			return false;
		}

		//get the order
		$order = new MP_Order( $order_id );
		if ( ! $order->exists() ) {
			wp_die( __( 'Sorry, the link is invalid for this download.', 'mp' ) );
		}
		
		//check that order is paid
		if ( $order->post_status == 'order_received' ) {
			wp_die( __( 'Sorry, your order has been marked as unpaid.', 'mp' ) );
		}

		//get the product object
		$product = new MP_Product( $product_id );

		//get the cart object
		$cart = $order->get_cart();
		
		$url = $product->get_meta( 'file_url' );

		//get download count
		$download_count = mp_arr_get_value( $product_id, $cart->download_count );
		
		if ( false === $download_count ) {
			$cart->download_count[ $product_id ] = 0;
		}
		
		$download_count = (int) $download_count;
		
		//check for too many downloads
		$max_downloads = mp_get_setting( 'max_downloads', 5 );
		if ( $download_count >= $max_downloads ) {
			wp_die( sprintf( __( 'Sorry, our records show you\'ve downloaded this file %d out of %d times allowed. Please contact us if you still need help.', 'mp' ), $download_count, $max_downloads ) );
		}

		/**
		 * Triggered when a file is served for download
		 *
		 * @since 3.0
		 * @param string $url The url of the file being served.
		 * @param MP_Order $order The order object associated with the file
		 * @param int $download_count The number of times the file has been downloaded.
		 */
		do_action( 'mp_serve_download', $url, $order, $download_count );

		/* if large downloads have been enabled just redirect to the actual file for download
		instead of trying to mask the file name */
		if ( mp_get_setting( 'use_alt_download_method' ) || MP_LARGE_DOWNLOADS === true ) {
			//record the download attempt
			$cart->download_count[ $product_id ] += 1;
			$order->update_meta( 'mp_cart_info', $cart );
			
			wp_redirect( $url );
			exit;
		}
		
		set_time_limit( 0 ); //try to prevent script from timing out

		//create unique filename
		$ext = ltrim( strrchr( basename( $url ), '.' ), '.' );
		$filename = sanitize_file_name( strtolower( get_the_title( $product_id ) ) . '.' . $ext );

		$dirs = wp_upload_dir();
		$location = str_replace( $dirs['baseurl'], $dirs['basedir'], $url );
		if ( file_exists( $location ) ) {
			// File is in our server
			$tmp = $location;
			$not_delete = true;
		} else {
			// File is remote so we need to download it first
			require_once ABSPATH . '/wp-admin/includes/file.php';
			
			//don't verify ssl connections
			add_filter( 'https_local_ssl_verify', create_function( '$ssl_verify', 'return false;' ) );
			add_filter( 'https_ssl_verify', create_function( '$ssl_verify', 'return false;' ) );

			$tmp = download_url( $url ); //we download the url so we can serve it via php, completely obfuscating original source

			if ( is_wp_error( $tmp ) ) {
				@unlink( $tmp );
				trigger_error( "MarketPress was unable to download the file $url for serving as download: " . $tmp->get_error_message(), E_USER_WARNING );
				wp_die( __( 'Whoops, there was a problem loading up this file for your download. Please contact us for help.', 'mp' ) );
			}
		}

		if ( file_exists( $tmp ) ) {
		 	$chunksize = (8 * 1024); //number of bytes per chunk
			$buffer = '';
			$filesize = filesize( $tmp );
			$length = $filesize;
			list( $fileext, $filetype ) = wp_check_filetype( $tmp );
			
			if ( empty( $filetype ) ) {
				$filetype = 'application/octet-stream';
			}
			
			ob_clean(); //kills any buffers set by other plugins
			
			if( isset( $_SERVER['HTTP_RANGE'] ) ) {
				//partial download headers
				preg_match( '/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches );
				$offset = intval( $matches[1] );
				$length = intval( $matches[2] ) - $offset;
				$fhandle = fopen( $filePath, 'r' );
				fseek( $fhandle, $offset ); // seek to the requested offset, this is 0 if it's not a partial content request
				$data = fread( $fhandle, $length );
				fclose( $fhandle );
				header( 'HTTP/1.1 206 Partial Content' );
				header( 'Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $filesize );
			}
			
			header( 'Accept-Ranges: bytes' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: ' . $filetype );
			header( 'Content-Disposition: attachment;filename="' . $filename . '"' );
			header( 'Expires: -1' );
			header( 'Cache-Control: public, must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . $filesize );
			
			if ( $filesize > $chunksize ) {
				$handle = fopen( $tmp, 'rb' );
				
				if ( $handle === false ) {
					trigger_error( "MarketPress was unable to read the file $tmp for serving as download.", E_USER_WARNING );
					return false;
				}
				
				while ( ! feof( $handle ) && ( connection_status() === CONNECTION_NORMAL ) ) {
					$buffer = fread( $handle, $chunksize );
					echo $buffer;
				}
				
				ob_end_flush();
				fclose( $handle );
			} else {
				ob_clean();
				flush();
				readfile( $tmp );
			}

			if ( ! $not_delete ) {
				@unlink( $tmp );
			}
		}

		//record download attempt
		$cart->download_count[ $product_id ] += 1;
		$order->update_meta( 'mp_cart_info', $cart );
		
		exit;
	}

	/**
	 * Force post status to publish for single products that are in out_of_stock status
	 *
	 * By default, WP won't allow access to single posts that are in out_of_stock
	 * status which will prevent users from downloading files they purchased.
	 *
	 * @since 2.9.5.8
	 * @access public
	 * @filter posts_results
	 */
	function set_publish_status_for_out_of_stock_product_downloads( $posts, $query ) {
		if ( MP_Product::get_post_type() == $query->get( 'post_type' ) && $query->get( MP_Product::get_post_type() ) && ($order = mp_get_get_value( 'orderid' ) ) ) {
			$posts[0]->post_status = 'publish';
		}
		
		return $posts;
	}
	
	/**
	 * Filter the content for a single product
	 *
	 * @since 3.0
	 * @access public
	 * @filter the_content
	 * @return string
	 */
	public function single_product_content( $content ) {
		if ( is_main_query() && in_the_loop() ) {
			remove_filter('get_post_metadata', array(&$this, 'remove_product_post_thumbnail'), 999, 4);
			remove_filter('the_content', array(&$this, 'single_product_content'));
			
			$show_img = ( mp_get_setting('show_img') ) ? 'single' : false;
			return mp_product(false, null, true, 'full', $show_img);
		}
		
		return $content;
	}
	
	/**
	 * Change the title output for product_category and product_tag archives
	 *
	 * @since 3.0
	 * @access public
	 * @filter the_title
	 */
	public function taxonomy_title( $title ) {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $title;
		}
		
		$tax = get_taxonomy( get_query_var( 'taxonomy' ) );
		$tax_labels = get_taxonomy_labels( $tax );
		$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ) );
		$title = $tax_labels->singular_name . ': ' . $term->name;
		
		/**
		 * Filter the taxonomy title for product category/tag templates
		 *
		 * @since 3.0
		 * @param string $title A title.
		 * @param Object $tax A taxonomy object.
		 * @param Object $term A term object.
		 */
		$title = apply_filters( 'mp_taxonomy_title', $title, $tax, $term );
		
		return $title;
	}
	
	/**
	 * Change the content for product_category and product_tag archives
	 *
	 * @since 3.0
	 * @access public
	 * @filter the_content
	 */
	public function taxonomy_content( $content ) {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		
		// don't remove post thumbnails from products
		remove_filter( 'get_post_metadata', array( &$this, 'remove_product_post_thumbnail' ), 999 );
		
		return mp_list_products();
	}
}

MP_Public::get_instance();