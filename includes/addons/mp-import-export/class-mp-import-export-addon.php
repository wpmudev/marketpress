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
		var_dump( 'asdsad' );
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
