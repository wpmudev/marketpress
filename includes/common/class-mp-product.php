<?php

class MP_Product {
	/**
	 * Get the internal post type for products
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function get_post_type() {
		return mp_get_setting('product_post_type', 'product');
	}
}