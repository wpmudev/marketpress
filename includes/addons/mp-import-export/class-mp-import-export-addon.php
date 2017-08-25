<?php
/**
 * Add-on to import/export of products.
 *
 * @author Anton Vanyukov
 *
 * @since 3.2.6
 * @class MP_Import_Export_Addon
 */

class MP_Import_Export_Addon {
	/**
	 * Store single instance of the class.
	 *
	 * @since  3.2.6
	 * @access private
	 * @var    MP_Import_Export_Addon|object|null
	 */
	private static $_instance = null;

	/**
	 * Get the single instance of the class.
	 *
	 * @since  3.2.6
	 * @return MP_Import_Export_Addon|object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Import_Export_Addon();
		}

		return self::$_instance;
	}

	/**
	 * MP_Import_Export_Addon constructor.
	 *
	 * @since  3.2.6
	 * @access private
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_mp_export_products', array( $this, 'export_products' ) );
	}

	/**
	 * Add sumbmenu items to admin menu.
	 *
	 * @since  3.2.6
	 * @action admin_menu
	 */
	public function add_menu_item() {
		add_submenu_page(
			'edit.php?post_type=' . MP_Product::get_post_type(),
			__( 'Export Products', 'mp' ),
			__( 'Export Products', 'mp' ),
			apply_filters( 'mp_store_settings_cap', 'manage_store_settings' ),
			'mp_export',
			array( $this, 'display_page' )
		);
	}

	/**
	 * Render view.
	 *
	 * @since 3.2.6
	 * @callback add_submenu_page
	 */
	public function display_page() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_store_settings' ) ) {
			return;
		}

		include_once mp_plugin_dir( 'includes/addons/mp-import-export/templates/export.php' );
	}

	/**
	 * Enqueue styles.
	 *
	 * @since  3.2.6
	 * @action admin_enqueue_scripts
	 * @param  string $hook  Contains page slug.
	 */
	public function enqueue_scripts( $hook ) {
		// If not on export page, do not enqueue styles.
		if ( 'product_page_mp_export' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'mp-export-js',
			mp_plugin_url( 'includes/addons/mp-import-export/ui/js/script.js' ),
			array( 'jquery', 'mp-select2' ),
			MP_VERSION
		);

		wp_localize_script( 'mp-export-js', 'mp_vars', array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'mp_export_products' ),
			'all_products' => __( 'Export all products', 'mp' ),
		));

		wp_enqueue_style(
			'mp-export-css',
			mp_plugin_url( 'includes/addons/mp-import-export/ui/css/style.css' ),
			false,
			MP_VERSION
		);
	}

	/**
	 * Export products.
	 *
	 * @since  3.2.6
	 * @action wp_ajax_mp_export_products
	 */
	public function export_products() {
		check_ajax_referer( 'mp_export_products' );

		wp_die();
	}
}

if ( ! function_exists( 'mp_import_export_addon' ) ) {
	/**
	 * Function to init the class.
	 *
	 * @since  3.2.6
	 * @return MP_Import_Export_Addon|object
	 */
	function mp_import_export_addon() {
		return MP_Import_Export_Addon::get_instance();
	}
}

// Init the class.
mp_import_export_addon();
