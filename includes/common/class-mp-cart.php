<?php

/**
 * Handles all aspects of checking out and adding/remove items from the user's cart
 *
 * @since 3.0
 *
 * @package MarketPress
 * @subpackage MP_Cart
 */

class MP_Cart {
	/**
	 * Whether there are any checkout errors
	 *
	 * @since 3.0
	 * @access public
	 * @var bool
	 */
	static $checkout_error = false;
	 
	/**
	 * Sets a checkout error
	 *
	 * @since 3.0
	 * @access public
	 * @param string $msg The error message to display.
	 * @param string $context The context of the message - set to the name of the field for field-specific errors or to "checkout" for a global errors.
	 */
	public static function set_checkout_error( $msg, $context = 'checkout' ) {
		$msg = str_replace('"', '\"', $msg); // Prevent double quotes from causing errors.
		$content = 'return "<div class=\"mp_checkout_error\">' . $msg . '</div>";';
		add_action('mp_checkout_error_' . $context, create_function('', $content));
		self::$checkout_error = true;
	}
	
	/**
	 * Sets the cart updateed message
	 *
	 * @since 3.0
	 * @access public
	 * @param string $msg The message to display.
	 */	
	public static function set_update_message( $msg ) {
		$content = 'return "<div id=\"mp_cart_updated_msg\">' . $msg . '</div>";';
		add_filter('mp_cart_updated_msg', create_function('', $content));
	}
	
	/**
	 * Gets a user's checkout fields (e.g. billing/shipping info)
	 *
	 * @since 3.0
	 * @access public
	 * @param int $user_id
	 * @return array
	 */
	public static function get_user_checkout_fields( $user_id = null ) {
		if ( is_null($user_id) ) {
			$user_id = wp_get_current_user()->ID;
		}
		
		$fields = array('email', 'name', 'address1', 'address2', 'city', 'state', 'zip', 'country', 'phone');
		$meta = array(
			'mp_billing_info' => get_user_meta($user_id, 'mp_billing_info', true),
			'mp_shipping_info' => get_user_meta($user_id, 'mp_shipping_info', true),
		);
		$groups = array('mp_billing_info', 'mp_shipping_info');
		$return = array();
		
		foreach ( $groups as $group ) {
			foreach ( $fields as $field ) {
				$return[$group][$field] = ($sess_value = mp_get_session_value("{$group}->{$field}")) ? $sess_value : (( $meta_value = mp_arr_get_value($field, $group) ) ? $meta_value : '');
				
				if ( $return[$group]['country'] ) {
					$return[$group]['country'] = mp_get_setting('base_country');
				}
			}
		}
		
		return $return;
	}
}