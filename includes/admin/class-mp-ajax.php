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
	 * Gets a page slug from a given page id
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_get_page_slug
	 */
	public function get_page_slug() {
		if ( wp_verify_nonce(mp_get_post_value('nonce'), 'mp_get_page_slug') && ($page_id = mp_get_post_value('page_id')) ) {
			wp_send_json_success(get_permalink($page_id));
		}
		
		wp_send_json_error();
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action('wp_ajax_mp_get_page_slug', array(&$this, 'get_page_slug'));
	}
}

MP_Ajax::get_instance();