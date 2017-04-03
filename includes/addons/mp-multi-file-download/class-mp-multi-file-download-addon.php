<?php
/**
 * Addon to allow for multiple files for digital products
 * It adds and option for uploading more than one file per product
 *
 * @author Paul Kevin
 *
 * @since 3.2.4
 * @class MP_Multi_File_Download_Addon
 */

class MP_Multi_File_Download_Addon {

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
			self::$_instance = new MP_Multi_File_Download_Addon();
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
		//Set the file type in for the product
		add_filter( 'mp_product_file_url_type', array( &$this, 'file_type' ), 99, 1 );
    }


	/**
	* Set the file type when Addon is enabled
	*
	* @since 3.2.4
	* @param String $type - The current file type
	*
	* @return String
	*/
	public function file_type( $type ){
		return 'file_list';
	}
}

if ( ! function_exists( 'mp_multi_file_download_addon' ) ) :

	/**
	* Get the MP_Multi_File_Download_Addon instance
	*
	* @since 3.2.4
	* @return MP_Multi_File_Download_Addon
	*/
	function mp_multi_file_download_addon() {

		return MP_Multi_File_Download_Addon::get_instance();
	}


endif;

//Instantiate the class
mp_multi_file_download_addon();
?>