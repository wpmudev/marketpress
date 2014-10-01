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
	 * Creates a store page and sets it to a draft state
	 *
	 * @since 3.0
	 * @access public
	 */
	public function create_store_page() {
		check_admin_referer('mp_create_store_page');
		
		$type = mp_get_get_value('type');
		$args = array();
		$defaults = array(
			'post_status' => 'publish',
			'post_type' => 'page',
		);
		
		switch ( $type ) {
			case 'store' :
				$args = array(
					'post_title' => __('Store', 'mp'),
					'post_content' => __("Welcome to our online store! Feel free to browse around:\n\n[mp_store_navigation]\n\nCheck out our most popular products:\n\n[mp_popular_products]\n\nBrowse by category:\n\n[mp_list_categories]\n\nBrowse by tag:\n\n[mp_tag_cloud]", 'mp'),
				);
			break;
			
			case 'network_store_page' :
				$args = array(
					'post_title' => __('Global Store', 'mp'),
					'post_content' => __("Welcome to our market place!\n\nCheck out our network of products:\n\n[mp_list_global_products]\n\nBrowse by category:\n\n[mp_global_categories_list]\n\nBrowse by tag:\n\n[mp_global_tag_cloud]", 'mp'),
				);
			break;
			
			case 'products' :
				$args = array(
					'post_title' => __('Products', 'mp'),
					'post_content' => '[mp_list_products]',
					'post_parent' => mp_get_setting('pages->store', 0),
				);
			break;
			
			case 'cart' :
				$args = array(
					'post_title' => __('Cart', 'mp'),
					'post_content' => '[mp_cart]',
					'post_parent' => mp_get_setting('pages->store', 0)
				);
			break;
			
			case 'checkout' :
				$args = array(
					'post_title' => __('Checkout', 'mp'),
					'post_content' => '[mp_cart]',
					'post_parent' => mp_get_setting('pages->store', 0)
				);
			break;
			
			case 'order_status' :
				$args = array(
					'post_title' => __('Order Status', 'mp'),
					'post_content' => '[mp_order_status]',
					'post_parent' => mp_get_setting('pages->store', 0)
				);
			break;
		}
		
		$post_id = wp_insert_post(array_merge($defaults, $args));
		MP_Pages_Admin::get_instance()->save_store_page_value($type, $post_id, false);
		wp_send_json_success(array(
			'post_id' => $post_id,
			'select2_value' => $post_id . '->' . get_the_title($post_id),
			'button_html' => '<a target="_blank" class="button mp-edit-page-button" href="' . add_query_arg(array(
				'post' => $post_id,
				'action' => 'edit',
			), get_admin_url(null, 'post.php')) . '">' . __('Edit Page', 'mp') . '</a>',
		));
	}
	
	/**
	 * Bulk edit products
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_bulk_edit_product
	 */
	public function bulk_edit_products() {
		if ( ! wp_verify_nonce(mp_get_post_value('nonce'), 'bulk_edit_products') ) {
			die;
		}
		
		$post_ids = mp_get_post_value('post_ids');
		$price = mp_get_post_value('price', '');
		$sale_price = mp_get_post_value('sale_price', '');
		
		if ( ! is_array($post_ids) ) {
			die;
		}
		
		foreach ( $post_ids as $post_id ) {
			update_post_meta($post_id, 'regular_price', $price);
			update_post_meta($post_id, 'sale_price_amount', $sale_price);
		}
		
		die;
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action('wp_ajax_mp_create_store_page', array(&$this, 'create_store_page'));
		add_action('wp_ajax_mp_bulk_edit_products', array(&$this, 'bulk_edit_products'));
	}
}

MP_Ajax::get_instance();