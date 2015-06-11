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

		if ( mp_get_network_setting( 'global_cart' ) && mp_is_post_indexer_installed() ) {
			mp_cart()->is_global = true;
			$this->post_indexer_set_post_types();

			add_filter( 'mp_product/url', array( &$this, 'product_url' ), 10, 2 );
			add_action( 'switch_blog', array( &$this, 'refresh_autoloaded_options' ) );
		}

		add_filter( 'mp_gateway_api/get_gateways', array( &$this, 'get_gateways' ) );
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

		$table1	 = $wpdb->base_prefix . 'mp_products';
		$table2	 = $wpdb->base_prefix . 'mp_terms';
		$table3	 = $wpdb->base_prefix . 'mp_term_relationships';

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
		if ( !is_network_admin() ) {
			if ( mp_cart()->is_global ) {
				$code		 = mp_get_network_setting( 'global_gateway' );
				$gateways	 = array( $code => $gateways[ $code ] );
			} else {
				$allowed				 = mp_get_network_setting( 'allowed_gateways' );
				$allowed[ 'free_orders' ]	 = 'full';//Always allow and activate it automatically later if needed
				if ( is_array( $allowed ) ) {
					foreach ( $gateways as $code => $gateway ) {
						if ( 'full' != $allowed[ $code ] ) {
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
			'global_cart'		 => 0,
			'allowed_gateways'	 => array(),
			'global_gateway'	 => 'paypal_express',
			'allowed_themes'	 => array(
				'default' => 'full',
			),
		);

		if ( !class_exists( 'MP_Gateway_API' ) ) {
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
		$pi_post_types	 = (array) get_site_option( 'postindexer_globalposttypes', array( 'post' ) );
		$changed		 = false;

		foreach ( mp()->post_types as $post_type ) {
			if ( !in_array( $post_type, $pi_post_types ) ) {
				$pi_post_types[] = $post_type;
				$changed		 = true;
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
