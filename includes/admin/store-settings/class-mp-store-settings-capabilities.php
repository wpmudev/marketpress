<?php

class MP_Store_Settings_Capabilities {
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
			self::$_instance = new MP_Store_Settings_Capabilities();
		}
		return self::$_instance;
	}
	
	/**
	 * Init metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		if ( ! function_exists('get_editable_roles') ) {
			require_once ABSPATH . '/wp-admin/includes/user.php';
		}
		
		$roles = get_editable_roles();
		$caps = mp_get_store_caps();
		
		if( empty( $roles ) ) return;
		
		foreach ( $roles as $role_name => $role ) {
			if ( $role_name == 'administrator' ) {
				continue;
			}
			
			$metabox = new WPMUDEV_Metabox(array(
				'id' => 'mp-settings-capabilities-' . $role_name,
				'page_slugs' => array('store-settings-capabilities'),
				'title' => sprintf(__('%s Capabilities', 'mp'), $role['name']),
				'option_name' => 'mp_settings',
			));
			$metabox->add_field('checkbox_group', array(
				'name' => "caps[$role_name]",
				'label' => array( 'text' => __( 'Capabilities', 'mp' ) ),
				'options' => $caps,
				'width' => '33.3%',
			));
		}
	}
	
	/**
	 * Update user caps
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_metabox/after_all_settings_metaboxes_saved/store-settings-capabilities
	 */
	public function save_user_caps() {
		$roles = get_editable_roles();
		$caps = mp_get_setting('caps');
		$all_caps = mp_get_store_caps();
		
		foreach ( $roles as $role_name => $role ) {
			if ( $role_name == 'administrator' ) {
				continue;
			}
			
			$role = get_role($role_name);
			foreach ( $all_caps as $cap ) {
				if ( mp_arr_get_value("$role_name->$cap", $caps) == 1 ) {
					$role->add_cap($cap);
				} else {
					$role->remove_cap($cap);
				}
			}
		}
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action('init', array(&$this, 'init_metaboxes'));
		add_action('wpmudev_metabox/after_all_settings_metaboxes_saved/store-settings-capabilities', array(&$this, 'save_user_caps'));
	}
}

MP_Store_Settings_Capabilities::get_instance();
