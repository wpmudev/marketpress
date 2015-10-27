<?php

class MP_Prosites_Addon {
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
			self::$_instance = new MP_Prosites_Addon();
		}

		return self::$_instance;
	}

	/**
	 * Update pro levels keys when upgrading to 3.0+
	 *
	 * @since 3.0
	 * @access protected
	 *
	 * @param array $pro_levels The current pro levels from < 3.0.
	 */
	protected function _update_pro_levels( $pro_levels ) {
		$settings = get_site_option( 'mp_network_settings', array() );

		foreach ( $pro_levels as $gateway => $level ) {
			$settings['allowed_gateways'][ $gateway ] = 'psts_level_' . $level;
		}

		unset( $settings['gateways_pro_level'] );

		update_site_option( 'mp_network_settings', $settings );
	}

	/**
	 * Filter the list of gateways depending on pro sites level
	 *
	 * @since 3.0
	 * @access public
	 */
	public function get_gateways( $gateways ) {
		if ( is_multisite() && ! is_network_admin() ) {
			foreach ( $gateways as $code => $gateway ) {
				$level = str_replace( 'psts_level_', '', mp_get_network_setting( 'allowed_gateways->' . $code, '' ) );
				if ( $level != 'full' && ! is_pro_site( false, $level ) ) {
					unset( $gateways[ $code ] );
				}
			}
		}

		return $gateways;
	}

	/**
	 * Filter the theme list
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_get_theme_list
	 */
	public function get_theme_list( $theme_list, $allowed_themes ) {
		if ( is_multisite() && ! is_network_admin() ) {
			foreach ( $theme_list as $key => $theme ) {
				if ( $permissions = mp_arr_get_value( $key, $allowed_themes ) ) {
					$level = str_replace( 'psts_level_', '', $permissions );

					if ( $permissions != 'full' || ! is_pro_site( false, $level ) ) {
						unset( $theme_list[ $key ] );
					}
				}
			}
		}

		return $theme_list;
	}

	/**
	 * Runs when the addon is enabled
	 *
	 * @since 3.0
	 * @access public
	 */
	public function on_enable() {
		if ( $pro_levels = mp_get_network_setting( 'gateways_pro_level' ) ) {
			$this->_update_pro_levels( $pro_levels );
		}
	}

	/**
	 * Add pro sites levels to permissions select dropdown for gateways and themes
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_admin_multisite/theme_permissions_options, mp_admin_multisite/gateway_permissions_options
	 */
	public function permissions_options( $opts ) {
		$levels         = get_site_option( 'psts_levels' );
		$options_levels = array();

		if ( is_array( $levels ) ) {
			foreach ( $levels as $level => $value ) {
				$options_levels[ 'psts_level_' . $level ] = $level . ':' . $value['name'];
			}
		}

		$opts['supporter'] = array(
			'group_name' => __( 'Pro Site Level', 'mp' ),
			'options'    => $options_levels,
		);

		return $opts;
	}

	public function disable_active_deactive_ability( $status, $addon ) {
		if ( $addon->class == 'MP_Prosites_Addon' ) {
			$status = __( "Enabled", "mp" );
		}

		return $status;
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action( 'mp_addons/enable/MP_Prosites_Addon', array( &$this, 'on_enable' ) );
		add_filter( 'mp_admin_multisite/theme_permissions_options', array( &$this, 'permissions_options' ) );
		add_filter( 'mp_admin_multisite/gateway_permissions_options', array( &$this, 'permissions_options' ) );
		add_filter( 'mp_gateway_api/get_gateways', array( &$this, 'get_gateways' ) );
		add_filter( 'mp_get_theme_list', array( &$this, 'get_theme_list' ), 10, 2 );
		add_filter( 'mp_addon_status_column_data', array( &$this, 'disable_active_deactive_ability' ), 10, 2 );
	}
}

MP_Prosites_Addon::get_instance();