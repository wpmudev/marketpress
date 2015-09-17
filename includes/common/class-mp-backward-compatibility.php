<?php

/**
 * This class is uses for deal with old marketpress theme like frame market & grid market
 * @author: Hoang Ngo
 */
class MP_Backward_Compatibility {
	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Backward_Compatibility();
		}

		return self::$_instance;
	}

	private function __construct() {
		//we will not use the mp_product of this theme, as it will prevent output
		add_filter( 'mp_single_product_template', array( &$this, 'prevent_old_mp_theme_file' ) );
	}

	public function prevent_old_mp_theme_file( $template ) {
		if ( ! empty( $template ) ) {
			$theme      = wp_get_theme();
			$old_themes = array(
				'GridMarket'
			);
			apply_filters( 'mp_backward_compatibility/old_themes', $old_themes );

			if ( in_array( $theme->Name, $old_themes ) ) {
				if ( pathinfo( $template, PATHINFO_FILENAME ) == 'mp_product' ) {
					$template = '';
				}
			}
		}

		return $template;
	}
}

MP_Backward_Compatibility::get_instance();