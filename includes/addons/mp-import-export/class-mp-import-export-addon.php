<?php

class MP_Import_Export_Addon {

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
			self::$_instance = new MP_Import_Export_Addon();
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

		require_once mp_plugin_dir( 'includes/addons/mp-import-export/mp-import-export-helpers.php' );
		require_once mp_plugin_dir( 'includes/addons/mp-import-export/class-mp-import.php' );
		require_once mp_plugin_dir( 'includes/addons/mp-import-export/class-mp-export.php' );
		
	}

}

MP_Import_Export_Addon::get_instance();

if ( ! function_exists( 'mp_import_export_addon' ) ) :

	/**
	 * Get the MP_Import_Export instance
	 *
	 * @since 3.0
	 * @return MP_Coupons
	 */
	function mp_import_export_addon() {
		return MP_Import_Export_Addon::get_instance();
	}


endif;