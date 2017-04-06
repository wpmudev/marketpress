<?php

class MP_Pages_Admin {
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
			self::$_instance = new MP_Pages_Admin();
		}
		return self::$_instance;
	}

	/**
	 * Init edit-page metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_page_settings_metabox();
	}

	/**
	 * Init the
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_page_settings_metabox() {
		if ( ! current_user_can('manage_store_settings') ) {
			// Only admins can set store pages
			return;
		}

		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-store-pages-metabox',
			'post_type' => 'page',
			'title' => __('Store Page Settings', 'mp'),
			'context' => 'side',
		));

		$options = array(
			'none' => __('None', 'mp'),
			'store' => __('Store Base', 'mp'),
			'products' => __('Products List', 'mp'),
			'cart' => __('Shopping Cart', 'mp'),
			'checkout' => __('Checkout Page', 'mp'),
			'order_status' => __('Order Status', 'mp'),
		);

		if ( is_multisite() && mp_is_main_site() && is_super_admin() ) {
			$options['network_store_page'] = __('Network Store Base', 'mp');
		}

		$metabox->add_field('select', array(
			'name' => 'mp_store_page',
			'desc' => __('You can choose to make this page one of the following core store pages.', 'mp'),
			'default_value' => 'none',
			'options' => $options,
		));
	}

	/**
	 * Save the store_page field value
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field/save_value/store_page
	 * @uses $wpdb
	 */
	public function save_store_page_value( $value, $post_id, $field ) {
		global $wpdb;

		// Delete existing meta keys from db that have the same value
		//$wpdb->delete($wpdb->postmeta, array('meta_key' => '_mp_store_page', 'meta_value' => $value));

		update_post_meta($post_id, '_mp_store_page', $value);
		if ( in_array( $value, array( 'network_store_page', 'network_categories', 'network_tags' ) ) ) {
			mp_update_network_setting("pages->$value", $post_id);
		} else {
			mp_update_setting("pages->$value", $post_id);
		}

		return null;
	}

	/**
	 * Get the store_page field value
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field/get_value/store_page
	 */
	public function get_store_page_value( $value, $post_id, $raw, $field ) {
		$meta_value = get_post_meta($post_id, '_mp_store_page', true);

		if ( $meta_value !== '' ) {
			return $meta_value;
		}

		return null;
	}

	/**
	 * Constructor
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action('init', array(&$this, 'init_metaboxes'));
		add_filter('wpmudev_field/save_value/mp_store_page', array(&$this, 'save_store_page_value'), 10, 3);
		add_filter('wpmudev_field/before_get_value/mp_store_page', array(&$this, 'get_store_page_value'), 10, 4);
	}
}

MP_Pages_Admin::get_instance();