<?php
	
class MP_Coupons {
	/**
	 * Get applied coupons from session
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public static function get_applied() {
		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			$coupons = mp_get_session_value("mp_cart_coupons->{$blog_id}");
		} else {
			$coupons = mp_get_session_value('mp_cart_coupons');
		}
		
		return $coupons;
	}
}