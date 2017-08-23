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
	 * @access public
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
		add_action( 'admin_menu', array( $this, 'add_menu_item' ), 9 );
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
			'edit.php?action=mp_export'
		);
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
