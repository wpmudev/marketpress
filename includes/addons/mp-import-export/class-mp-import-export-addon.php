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
	 * Refers to the build of the addon
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	public $build = 1;

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
		require_once mp_plugin_dir( 'includes/addons/mp-import-export/class-mp-import.php' );
		require_once mp_plugin_dir( 'includes/addons/mp-import-export/class-mp-export.php' );
		
		// $this->_install();
	}

	/**
	 * Install
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _install() {
		// $db_build = mp_get_setting( 'coupons->build', 0 );

		// if ( $this->build == $db_build ) {
		// 	return;
		// }

		// if ( false === get_option( 'mp_coupons' ) ) {
		// 	add_option( 'mp_coupons', array() );
		// }

		// if ( $db_build < 1 ) {
		// 	$this->_update_coupon_schema();
		// }

		// mp_update_setting( 'coupons->build', $this->build );
	}

	/**
	 * Prints applicable CSS
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_print_styles
	 */
	public function print_css() {
		if ( get_current_screen()->post_type != 'product_coupon' ) {
			return;
		}
		?>
		<style type="text/css">
			#misc-publishing-actions,
			#minor-publishing-actions {
				display: none;
			}

			input#title,
			.row-title {
				text-transform: uppercase;
			}

			.tablenav .actions {
				display: none;
			}

			.tablenav .bulkactions {
				display: block;
			}

			th.manage-column {
				width: 20%;
			}
		</style>
		<?php

	}

	/**
	 * Prints applicable javascript
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_print_footer_scripts
	 */
	public function print_js() {
		if ( get_current_screen()->id != 'mp_coupon' ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('#menu-posts-product, #menu-posts-product > a, #menu-posts-mp_product, #menu-posts-mp_product > a')
					.addClass('wp-menu-open wp-has-current-submenu')
					.find('a[href="edit.php?post_type=mp_coupon"]').parent().addClass('current');
			});
		</script>
		<?php

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