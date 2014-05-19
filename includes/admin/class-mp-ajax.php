<?php

class MP_Ajax {
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
			self::$_instance = new MP_Ajax();
		}
		return self::$_instance;
	}
	
	/**
	 * Updates a user's preference
	 *
	 * @since 3.0
	 * @access public
	 */
	public function update_user_preference() {
		check_ajax_referer('mp-update-user-preference', 'mp_update_user_preference_nonce');
		mp()->update_user_preference(get_current_user_id(), $_POST['key'], $_POST['value']);
	}

	/**
	 * Maybe override the value returned by get_option('mp_settings')
	 *
	 * Used when running ajax requests and you need to change some settings
	 * temporarily while you run some other functions that use
	 * get_option('mp_settings), but you don't want to actually save the settings
	 *
	 * @since 3.0
	 * @access public
	 * @param string $value
	 * @return mixed
	 */	
	public function maybe_override_settings( $value ) {
		return wp_cache_get('mp_settings', 'mp');
	}
			
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		//update a user's preference
		add_action('wp_ajax_mp-update-user-preference', array(&$this, 'update_user_preference'));
		//allow overriding of value returned by get_option('mp_settings'))
		add_filter('pre_option_mp_settings', array(&$this, 'maybe_override_settings'));
	}
}

MP_Ajax::get_instance();