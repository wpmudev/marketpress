<?php
if( !defined('MP_EMAIL_USE_BILLIG_NAME')) define('MP_EMAIL_USE_BILLIG_NAME', false);

if ( ! function_exists( 'mp' ) ) :

	/**
	 * Returns the Marketpress instance
	 *
	 * @since 3.0
	 * @return object
	 */
	function mp() {
		return Marketpress::get_instance();
	}

endif;

if ( ! function_exists( 'mp_search_array' ) ) :

	function mp_search_array( $array, $key, $value ) {
		$results = array();

		if ( is_array( $array ) ) {
			if ( isset( $array[ $key ] ) && $array[ $key ] == $value ) {
				$results[] = $array;
			}
			foreach ( $array as $subarray ) {
				$results = array_merge( $results, mp_search_array( $subarray, $key, $value ) );
			}
		}

		return $results;
	}

endif;

if ( ! function_exists( 'mp_get_api_timeout' ) ) :

	/**
	 * Get the number of seconds before an API call should timeout
	 *
	 * @since 3.0
	 * @access protected
	 *
	 * @param string $key Optional, a unique key for the timeout.
	 *
	 * @return int
	 */
	function mp_get_api_timeout( $key = '' ) {
		/**
		 * Filter the api timeout
		 *
		 * @since 3.0
		 *
		 * @param int $timeout The current timeout value.
		 */
		$timeout = apply_filters( 'mp_api_timeout', 30 );
		$timeout = apply_filters( 'mp_api_timeout/' . $key, $timeout );

		return (int) $timeout;
	}

endif;


if ( ! function_exists( 'mp_filter_email' ) ) :

	/**
	 * Replace short codes in email messages with dynamic content
	 *
	 * @since 3.0
	 * @uses $blog_id
	 *
	 * @param MP_Order $order An MP_Order object.
	 * @param string $text The email text including short codes.
	 * @param bool $escape Optional, whether to escape string or not. Defaults to false.
	 */
	function mp_filter_email( $order, $text, $escape = false ) {
		global $blog_id;
		$bid = ( is_multisite() ) ? $blog_id : 1;

		// Currency
		$currency = $order->get_meta( 'currency', '' );

		// Cart
		$cart  = $order->get_cart();
		$items = $cart->get_items_as_objects();

		// Order info
		if ( count( $items ) > 0 ) {
			$order_info = '<table width="100%">
							<tr>
							<th align="left">' . __( 'Item', 'mp' ) . '</th>
							<th align="left">' . __( 'Sku', 'mp' ) . '</th>
							<th align="right">' . __( 'Qty', 'mp' ) . '</th>
							<th align="right">' . __( 'Price', 'mp' ) . '</th>
							<th align="right">' . __( 'Total', 'mp' ) . '</th>
							</tr>';

			foreach ( $items as $item ) {
				$price = ( $item->get_price( 'lowest' ) * $item->qty );

				//show download link if set
				$download_link = false;
				if ( $order->post_status != 'order_received' && ( $download_url = $item->download_url( $order->get_id(), false ) ) ) {
					
					//Handle multiple files
					if( is_array( $download_url ) ){
						//If we have more than one product file, we loop and add each to a new line
						foreach ( $download_url as $key => $value ){
							$download_link .= '<a target="_blank" href="' . $value . '">' . sprintf( __( 'Download %1$s', 'mp' ),( $key+1 ) ) . '</a><br/>';
						}
						
					} else {
						$download_link = '<a href="' . $download_url . '">' . __( 'Download', 'mp' ) . '</a>';
					}
				}

				$order_info .= "<tr>
								<td>" . $item->title( false ) . ( ( $download_link ) ? '<br />' . $download_link : '' ) . '</td>
								<td>' . $item->get_meta( 'sku', '&mdash;' ) . '</td>
								<td align="right">' . number_format_i18n( $item->qty ) . '</td>
								<td align="right">' . mp_format_currency( $currency, $item->get_price( 'lowest' ) ) . '</td>
								<td align="right">' . mp_format_currency( $currency, $price ) . '</td>
								</tr>' . "\n";
			}

			$order_info .= "</table><br /><br />";
		}


		// Coupon lines
		if ( $coupons = $order->get_meta( 'mp_discount_info' ) ) {
			$order_info .= "<strong>" . __( 'Coupons:', 'mp' ) . "</strong>";

			foreach ( $coupons as $code => $discount ) {
				$order_info .= $code . "\t" . mp_format_currency( $currency, $discount ) . "<br />\n";
			}
		}

		// Shipping line
		if ( $shipping_total = $order->get_meta( 'mp_shipping_total' ) ) {
			if( ! mp_cart()->is_download_only()  ) {
				$order_info .= '<strong>' . __( 'Shipping:', 'mp' ) . '</strong> ' . ( ( 0 == $shipping_total ) ? __( 'FREE', 'mp' ) : mp_format_currency( $currency, $shipping_total ) ) . "<br />\n";
			}
		}

		// Tax line
		if ( $tax_total = $order->get_meta( 'mp_tax_total' ) ) {
			$order_info .= '<strong>' . esc_html( mp_get_setting( 'tax->label', __( 'Taxes', 'mp' ) ) ) . ':</strong> ' . mp_format_currency( $currency, $tax_total ) . "<br />\n";
		}

		// Total line
		if ( $order_total = $order->get_meta( 'mp_order_total' ) ) {
			$order_info .= '<strong>' . __( 'Order Total:', 'mp' ) . '</strong> ' . mp_format_currency( $currency, $order_total ) . "<br />\n";
		}

		// Cart
		$cart = $order->get_cart();

		// Shipping/Billing Info
		$types                 = array(
			'billing'  => __( 'Billing Address', 'mp' ),
			'shipping' => __( 'Shipping Address', 'mp' )
		);
		$shipping_billing_info = '<table width="100%"><tr>';
		if ( $cart->is_download_only() || mp_get_setting( 'shipping->method' ) == 'none' ) {
			$shipping_billing_info .= '<td align="left"><h3>' . __( 'Shipping', 'mp' ) . '</h3>' . __( 'No shipping required for this order.', 'mp' ) . '</td>';
			$type = array( 'billing' => __( 'Billing', 'mp' ) );
		}
		$all_countries = mp_countries();
		foreach ( $types as $type => $label ) {
			$states = mp_get_states( $order->get_meta( "mp_{$type}_info->country" ) );
			$city = $order->get_meta( "mp_{$type}_info->city" );
			$zip = $order->get_meta( "mp_{$type}_info->zip" );

			if( $type != "shipping" || !$cart->is_download_only() ) {
				
				$shipping_billing_info .= '<td><strong>' . $label . '</strong><br /><br />' . "\n";
				$shipping_billing_info .= $order->get_name( $type ) . "<br />\n";
				$shipping_billing_info .= $order->get_meta( "mp_{$type}_info->company_name" ) . "<br />\n";
				$shipping_billing_info .= $order->get_meta( "mp_{$type}_info->address1" ) . "<br />\n";

				if ( $order->get_meta( "mp_{$type}_info->address2" ) ) {
					$shipping_billing_info .= $order->get_meta( "mp_{$type}_info->address2" ) . "<br />\n";
				}

				if( ( ( $state = $order->get_meta( "mp_{$type}_info->state", '' ) ) && is_array( $states ) && isset( $states[$state] ) ) ){
					$state = $states[$state];
				}

				if( ( ( $country = $order->get_meta( "mp_{$type}_info->country", '' ) ) && is_array( $all_countries ) && isset( $all_countries[$country] ) ) ){
					$country = $all_countries[$country];
				}				
				
				if( ! empty( $city ) && ! empty( $state ) &&  ! empty( $zip ) && ! empty( $country ) ) {
					$shipping_billing_info .= $order->get_meta( "mp_{$type}_info->city" ) . ', ' . $state . ' ' . $order->get_meta( "mp_{$type}_info->zip" ) . ' ' . $country . "<br /><br />\n";
				}
				
				$shipping_billing_info .= $order->get_meta( "mp_{$type}_info->email" ) . "<br />\n";

				if ( $order->get_meta( "mp_{$type}_info->phone" ) ) {
					$shipping_billing_info .= $order->get_meta( "mp_{$type}_info->phone" ) . "<br />\n";
				}

				$shipping_billing_info .= '</td>';
			}
		}

		$shipping_billing_info .= '</tr></table><br /><br />';
		
		$custom_carriers = mp_get_setting( 'shipping->custom_method', array() );
		$method = $order->get_meta( 'mp_shipping_info->method' );
		
		if( isset( $custom_carriers[ $method ] ) && !empty( $custom_carriers[ $method ] ) ) {
			$carrier = $custom_carriers[ $method ];
		} else {
			$carrier = $method;
		}

		// If actually shipped show method, else customer's shipping choice.
		if ( $order->get_meta( 'mp_shipping_info->method' ) && $order->get_meta( 'mp_shipping_info->method' != 'other' ) ) {
			$shipping_billing_info .= '<strong>' . __( 'Shipping Method:', 'mp' ) . '</strong> ' . $carrier;
			// If using calculated shipping, show the carrier and shipping option selected
		} elseif ( $order->get_meta( 'mp_shipping_info->shipping_sub_option' ) &&  !is_array( $order->get_meta( 'mp_shipping_info->shipping_option' ) ) ) {
			$shipping_billing_info .= '<strong>' . __( 'Shipping Method:', 'mp' ) . '</strong> ' . strtoupper( $order->get_meta( 'mp_shipping_info->shipping_option' ) ) . ' ' . $order->get_meta( 'mp_shipping_info->shipping_sub_option' );
		} else {
			$shipping_billing_info .= '<strong>' . __( 'Shipping Method:', 'mp' ) . '</strong> ' . $carrier;
		}

		if ( $order->get_meta( 'mp_shipping_info->tracking_num' ) ) {
			$shipping_billing_info .= "<br /><strong>" . __( 'Tracking Number:', 'mp' ) . '</strong> ' . $order->get_meta( 'mp_shipping_info->tracking_num' );
		}

		// Special Instructions
		if ( $order->get_meta( 'mp_shipping_info->special_instructions' ) ) {
			$shipping_billing_info .= "<br /><strong>" . __( 'Special Instructions:', 'mp' ) . ':</strong>' . wordwrap( $order->get_meta( 'mp_shipping_info->special_instructions' ) );
		}

		$order_notes = '';
		if ( $order->get_meta( 'mp_order_notes' ) ) {
			$order_notes = '<strong>' . __( 'Order Notes:', 'mp' ) . ':</strong>' . wordwrap( $order->get_meta( 'mp_order_notes' ) ) . "<br />\n";
		}

		// Payment Info
		$payment_info = '<strong>' . __( 'Payment Method:', 'mp' ) . '</strong> ' . $order->get_meta( 'mp_payment_info->gateway_public_name' ) . "<br />\n";

		if ( $order->get_meta( 'mp_payment_info->method' ) ) {
			$payment_info .= '<strong>' . __( 'Payment Type:', 'mp' ) . '</strong> ' . $order->get_meta( 'mp_payment_info->method' ) . "<br />\n";
		}

		if ( $order->get_meta( 'mp_payment_info->transaction_id' ) ) {
			$payment_info .= '<strong>' . __( 'Transaction ID:', 'mp' ) . '</strong> ' . $order->get_meta( 'mp_payment_info->transaction_id' ) . "<br />\n";
		}

		$payment_info .= '<strong>' . __( 'Payment Total:', 'mp' ) . '</strong> ' . mp_format_currency( $currency, $order->get_meta( 'mp_payment_info->total' ) ) . "<br /><br />\n";

		if ( $order->post_status == 'order_paid' || $order->post_status == 'order_shipped' ) {
			$payment_info .= __( 'Your payment for this order is complete.', 'mp' );
		} else {
			$payment_info .= __( 'Your payment for this order is not yet complete. Here is the latest status:', 'mp' ) . "\n";
			$statuses = $order->get_meta( 'mp_payment_info->status' );
			krsort( $statuses ); //sort with latest status at the top
			$status    = reset( $statuses );
			$timestamp = key( $statuses );
			$payment_info .= mp_format_date( $timestamp ) . ': ' . $status;
		}

		// Total
		$order_total = mp_format_currency( $currency, $order->get_meta( 'mp_payment_info->total' ) );

		// Tracking URL
		$tracking_url = $order->tracking_url( false );

		$customer_name = MP_EMAIL_USE_BILLIG_NAME ? $order->get_meta( 'mp_billing_info->first_name' ) . ' ' . $order->get_meta( 'mp_billing_info->last_name' ) : $order->get_meta( 'mp_shipping_info->first_name' ) . ' ' . $order->get_meta( 'mp_shipping_info->last_name' );

		// If we don't have shipping name (for example on digital download only orders), lets use the name on the billing info
		if( empty( $customer_name ) ) $customer_name = trim( $order->get_meta( 'mp_billing_info->first_name' ) . ' ' . $order->get_meta( 'mp_billing_info->last_name' ) );

		// Setup search/replace
		$search_replace = array(
			'CUSTOMERNAME' => $customer_name,
			'ORDERID'      => $order->get_id(),
			'ORDERINFOSKU' => $order_info,
			'ORDERINFO'    => $order_info,
			'SHIPPINGINFO' => $shipping_billing_info,
			'PAYMENTINFO'  => $payment_info,
			'TOTAL'        => $order_total,
			'TRACKINGURL'  => $tracking_url,
			'ORDERNOTES'   => $order_notes,
		);

		// Escape for sprintf() if required
		if ( $escape ) {
			$search_replace = array_map( create_function( '$a', 'return str_replace("%","%%",$a);' ), $search_replace );
		}

		// Replace codes
		$text = str_replace( array_keys( $search_replace ), array_values( $search_replace ), $text );

		return $text;
	}

endif;

if ( ! function_exists( 'mp_quote_it' ) ) :

	/**
	 * Wrap string in single or double quotes
	 *
	 * @since 3.0
	 *
	 * @param string $text The text to wrap.
	 * @param string $type Optional, either "single" or "double". Defaults to "double".
	 *
	 * @return string
	 */
	function mp_quote_it( $text, $type = 'double' ) {
		$char = '"';
		if ( $type == 'single' ) {
			$char = "'";
		}

		return $char . $text . $char;
	}

endif;

if ( ! function_exists( 'mp_delete_from_array' ) ) :

	/**
	 * Deletes a key from a given array
	 *
	 * @since 1.0
	 *
	 * @param array $array The array to work with.
	 * @param string $key_string
	 */
	function mp_delete_from_array( &$array, $key_string ) {
		$keys   = explode( '->', $key_string );
		$branch = &$array;

		while ( count( $keys ) ) {
			$key = array_shift( $keys );

			if ( ! is_array( $branch ) ) {
				return false;
			}

			$branch = &$branch[ $key ];
		}

		unset( $branch );
	}

endif;

if ( ! function_exists( 'mp_push_to_array' ) ) :

	/**
	 * Pushes a value to a given array with given key
	 *
	 * @since 1.0
	 *
	 * @param array $array The array to work with.
	 * @param string $key_string
	 * @param mixed $value
	 */
	function mp_push_to_array( &$array, $key_string, $value ) {
		$keys   = explode( '->', $key_string );
		$branch = &$array;

		while ( count( $keys ) ) {
			$key = array_shift( $keys );

			if ( ! is_array( $branch ) ) {
				$branch = array();
			}

			$branch = &$branch[ $key ];
		}

		$branch = $value;
	}

endif;

if ( ! function_exists( 'mp_cart' ) ) :

	/**
	 * Get the MP_Cart instance
	 *
	 * @since 3.0
	 */
	function mp_cart() {
		if ( ! class_exists( 'MP_Cart' ) ) {
			require_once mp_plugin_dir( 'includes/common/class-mp-cart.php' );
		}

		return MP_Cart::get_instance();
	}

endif;

if ( ! function_exists( 'mp_checkout' ) ) :

	/**
	 * Get the MP_Checkout instance
	 *
	 * @since 3.0
	 */
	function mp_checkout() {
		if ( ! class_exists( 'MP_Checkout' ) ) {
			require_once mp_plugin_dir( 'includes/public/class-mp-checkout.php' );
		}

		return MP_Checkout::get_instance();
	}

endif;

if ( ! function_exists( 'mp_countries' ) ) :

	/**
	 * Gets the whole country list
	 *
	 * @since 3.0
	 * @return array
	 */
	function mp_countries() {
		$countries = mp()->countries;

		/**
		 * Filter the all countries list
		 *
		 * @since 3.0
		 *
		 * @param array $countries The default countries.
		 */
		return apply_filters( 'mp_countries', $countries );		
	}

endif;


if ( ! function_exists( 'mp_country_list' ) ) :

	/**
	 * Gets the country list without the popular countries
	 *
	 * @since 3.0
	 * @return array
	 */
	function mp_country_list() {
		$sorted    = array();
		$countries = mp_countries();

		foreach ( $countries as $code => $country ) {
			if ( ! in_array( $code, mp()->popular_countries ) ) {
				$sorted[ $code ] = $country;
			}
		}

		return $sorted;
	}

endif;

if ( ! function_exists( 'mp_popular_country_list' ) ) :

	/**
	 * Gets the popular country list
	 *
	 * @since 3.0
	 * @return array
	 */
	function mp_popular_country_list() {
		$sorted    = array();
		$countries = mp()->popular_countries;

		/**
		 * Filter the popular countries list
		 *
		 * @since 3.0
		 *
		 * @param array $countries The default popular countries.
		 */
		$countries = apply_filters( 'mp_popular_country_list', $countries );

		foreach ( $countries as $code => $country ) {
			$sorted[ $code ] = $country;
		}

		asort( $sorted );

		return $sorted;
	}

endif;

if ( ! function_exists( 'mp_get_states' ) ) :

	/**
	 * Gets the states/regions/provinces for a given country
	 *
	 * @since 3.0
	 *
	 * @param string $country The country code.
	 *
	 * @return array
	 */
	function mp_get_states( $country ) {
		$list = array();
		$property = $country.'_provinces';
		if ( property_exists( mp(), $property ) ) {
			$list = mp()->$property;
		}

		/**
		 * Filter the state/province list
		 *
		 * @since 3.0
		 *
		 * @param array $list The current state/province list.
		 * @param string $country The current country.
		 */

		return apply_filters( 'mp_get_states', $list, $country );
	}

endif;

if ( ! function_exists( 'mp_get_theme_list' ) ) :

	/**
	 * Get a list of MP themes
	 *
	 * @since 3.0
	 * @access public
	 */
	function mp_get_theme_list() {
		$theme_list = array();
		$theme_dirs = array( mp_plugin_dir( 'ui/themes' ), WP_CONTENT_DIR . '/marketpress-styles/' );

		foreach ( $theme_dirs as $theme_dir ) {
			$themes = mp_get_dir_files( $theme_dir, 'css' );

			if ( ! $themes ) {
				continue;
			}

			$allowed_themes = $themes;
			if ( is_multisite() && ! is_network_admin() ) {
				$allowed_themes = mp_get_network_setting( 'allowed_themes' );
			}

			foreach ( $themes as $theme ) {
				$theme_data = get_file_data( $theme, array( 'name' => 'MarketPress Style' ) );
				$key        = basename( $theme, '.css' );

				if ( $name = mp_arr_get_value( 'name', $theme_data ) ) {
					if ( is_multisite() && is_network_admin() ) {
						$theme_list[ $key ] = array( 'path' => $theme, 'name' => $name );
					} else {
						$theme_list[ $key ] = $name;
					}
				}
			}
		}

		asort( $theme_list );

		/**
		 * Filter the theme list
		 *
		 * @since 3.0
		 *
		 * @param array $theme_list An array of themes.
		 * @param array $allowed_theme An array of allowed themes.
		 */

		return apply_filters( 'mp_get_theme_list', $theme_list, $allowed_themes );
	}

endif;

if ( ! function_exists( 'mp_is_valid_zip' ) ) :

	/**
	 * Check if zipcode is valid
	 *
	 * @since 3.0
	 *
	 * @param string $zip
	 * @param string $country
	 */
	function mp_is_valid_zip( $zip, $country ) {
		if ( mp_arr_get_value( $country, mp()->countries_no_postcode ) ) {
			//given country doesn't use post codes so zip is always valid
			return true;
		}

		if ( empty( $zip ) ) {
            
            //Doesn't matter if empty for download only carts
			if( mp_cart()->is_download_only() ){
				return true;
			}
			//no post code provided
			return false;
		}

		if ( strlen( $zip ) < 3 ) {
			//post code is too short - see http://wp.mu/8wg
			return false;
		}

		return true;
	}

endif;

if ( ! function_exists( 'mp_get_dir_files' ) ) :

	/**
	 * Get all files from a given directory
	 *
	 * @since 3.0
	 *
	 * @param string $dir The full path of the directory
	 * @param string $ext Get only files with a given extension. Set to NULL to get all files.
	 *
	 * @return array or false if no files exist
	 */
	function mp_get_dir_files( $dir, $ext = 'php' ) {
		$myfiles = array();

		if ( ! is_null( $ext ) ) {
			$ext = '.' . $ext;
		}

		if ( false === file_exists( $dir ) ) {
			return false;
		}

		$dir   = trailingslashit( $dir );
		$files = glob( $dir . '*' . $ext );
		$files = array_filter( $files, create_function( '$filepath', 'return is_readable($filepath);' ) );

		return ( empty( $files ) ) ? false : $files;
	}

endif;

if ( ! function_exists( 'mp_include_dir' ) ) :

	/**
	 * Includes all files in a given directory
	 *
	 * @since 3.0
	 *
	 * @param string $dir The directory to work with
	 * @param string $ext Only include files with this extension
	 */
	function mp_include_dir( $dir, $ext = 'php' ) {
		if ( false === ( $files = mp_get_dir_files( $dir, $ext ) ) ) {
			return false;
		}

		foreach ( $files as $file ) {
			include_once $file;
		}
	}

endif;

if ( ! function_exists( 'mp_get_current_screen' ) ) :

	/**
	 * Safely gets the $current_screen object even before the current_screen hook is fired
	 *
	 * @since 3.0
	 * @uses $current_screen, $hook_suffix, $pagenow, $taxnow, $typenow
	 * @return object
	 */
	function mp_get_current_screen() {
		global $current_screen, $hook_suffix, $pagenow, $taxnow, $typenow;

		if ( empty( $current_screen ) ) {
			//set current screen (not normally available here) - this code is derived from wp-admin/admin.php
			require_once ABSPATH . 'wp-admin/includes/screen.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			if ( isset( $_GET['page'] ) ) {
				$plugin_page = wp_unslash( $_GET['page'] );
				$plugin_page = plugin_basename( $plugin_page );
			}

			if ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) {
				$typenow = $_REQUEST['post_type'];
			} else {
				$typenow = '';
			}

			if ( isset( $_REQUEST['taxonomy'] ) && taxonomy_exists( $_REQUEST['taxonomy'] ) ) {
				$taxnow = $_REQUEST['taxonomy'];
			} else {
				$taxnow = '';
			}

			if ( isset( $plugin_page ) ) {
				if ( ! empty( $typenow ) ) {
					$the_parent = $pagenow . '?post_type=' . $typenow;
				} else {
					$the_parent = $pagenow;
				}

				if ( ! $page_hook = get_plugin_page_hook( $plugin_page, $the_parent ) ) {
					$page_hook = get_plugin_page_hook( $plugin_page, $plugin_page );
					// backwards compatibility for plugins using add_management_page
					if ( empty( $page_hook ) && 'edit.php' == $pagenow && '' != get_plugin_page_hook( $plugin_page, 'tools.php' ) ) {
						// There could be plugin specific params on the URL, so we need the whole query string
						if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
							$query_string = $_SERVER['QUERY_STRING'];
						} else {
							$query_string = 'page=' . $plugin_page;
						}

						wp_redirect( admin_url( 'tools.php?' . $query_string ) );
						exit;
					}
				}

				unset( $the_parent );
			}

			$hook_suffix = '';
			if ( isset( $page_hook ) ) {
				$hook_suffix = $page_hook;
			} else if ( isset( $plugin_page ) ) {
				$hook_suffix = $plugin_page;
			} else if ( isset( $pagenow ) ) {
				$hook_suffix = $pagenow;
			}

			set_current_screen();
		}

		return get_current_screen();
	}

endif;

if ( ! function_exists( 'mp_admin' ) ) :

	/**
	 * Returns the MP_Admin instance
	 *
	 * @since 3.0
	 * @return object
	 */
	function mp_admin() {
		if ( ! class_exists( 'MP_Admin' ) ) {
			require_once mp_plugin_dir( 'includes/admin/class-mp-admin.php' );
		}

		return MP_Admin::get_instance();
	}

endif;

if ( ! function_exists( 'mp_public' ) ) :

	/**
	 * Returns the MP_Public instance
	 *
	 * @since 3.0
	 * @return object
	 */
	function mp_public() {
		if ( ! class_exists( 'MP_Pubic' ) ) {
			require_once mp_plugin_dir( 'includes/public/class-mp-public.php' );
		}

		return MP_Public::get_instance();
	}

endif;

if ( ! function_exists( 'mp_doing_ajax' ) ) :

	/**
	 * Checks if an ajax action is currently being executed
	 *
	 * @since 3.0
	 * @uses DOING_AJAX
	 *
	 * @param string $action Optional, the ajax action to check.
	 *
	 * @return bool
	 */
	function mp_doing_ajax( $action = null ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ( is_null( $action ) || $action == mp_get_request_value( 'action' ) ) ) {
			return true;
		}

		return false;
	}

endif;

if ( ! function_exists( 'mp_doing_autosave' ) ) :

	/**
	 * Checks if an autosave action is currently being executed
	 *
	 * @since 3.0
	 * @uses DOING_AUTOSAVE
	 * @return bool
	 */
	function mp_doing_autosave() {
		return ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE );
	}

endif;


if ( ! function_exists( 'array_replace_recursive' ) ) :

	/**
	 * Recursively replace one array with another. Provides compatibility for PHP version < 5.3
	 *
	 * @since 3.0
	 *
	 * @param array $array
	 * @param array $array1 The values from this array will overwrite the values from $array
	 *
	 * @return array
	 */
	function array_replace_recursive() {
		// handle the arguments, merge one by one
		$args  = func_get_args();
		$array = $args[0];

		if ( ! is_array( $array ) ) {
			return $array;
		}

		for ( $i = 1; $i < count( $args ); $i ++ ) {
			if ( is_array( $args[ $i ] ) ) {
				$array = recurse( $array, $args[ $i ] );
			}
		}

		return $array;
	}

	function recurse( $array, $array1 ) {
		foreach ( $array1 as $key => $value ) {
			// create new key in $array, if it is empty or not an array
			if ( ! isset( $array[ $key ] ) || ( isset( $array[ $key ] ) && ! is_array( $array[ $key ] ) ) ) {
				$array[ $key ] = array();
			}

			// overwrite the value in the base array
			if ( is_array( $value ) ) {
				$value = recurse( $array[ $key ], $value );
			}

			$array[ $key ] = $value;
		}

		return $array;
	}

endif;

if ( ! function_exists( 'debug_to_console' ) ) :

	/**
	 * Send a log to the browser console
	 *
	 * @since 3.0
	 * @access public
	 */
	function debug_to_console( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			echo "<script>if ( typeof(window.console) !== 'undefined' ) console.log('PHP: " . json_encode( $data ) . "');</script>";
		} else {
			echo "<script>if ( typeof(window.console) !== 'undefined' ) console.log('PHP: " . $data . "');</script>";
		}
	}

endif;

if ( ! function_exists( 'mp_arr_get_value' ) ) :

	/**
	 * Safely retrieve a value from an array
	 *
	 * @since 3.0
	 * @uses mp_arr_search()
	 *
	 * @param string $key (e.g. key1->key2->key3)
	 * @param array $array The $array to work with
	 * @param mixed $default The default value to return if $key is not found within $array
	 *
	 * @return mixed
	 */
	function mp_arr_get_value( $key, $array, $default = false ) {
		$keys  = explode( '->', $key );
		$keys  = array_map( 'trim', $keys );
		$value = mp_arr_search( $array, $key );

		return ( is_null( $value ) ) ? $default : $value;
	}

endif;


if ( ! function_exists( 'mp_get_cookie_value' ) ) :

	/**
	 * Safely retreives a value from the $_COOKIE array
	 *
	 * @since 3.0
	 * @uses mp_arr_get_value()
	 *
	 * @param string $key (e.g. key1->key2->key3)
	 * @param mixed $default The default value to return if $key is not found within $array
	 *
	 * @return mixed
	 */
	function mp_get_cookie_value( $key, $default = false ) {
		return stripslashes_deep( mp_arr_get_value( $key, $_COOKIE, $default ) );
	}

endif;

if ( ! function_exists( 'mp_get_get_value' ) ) :

	/**
	 * Safely retreives a value from the $_GET array
	 *
	 * @since 3.0
	 * @uses mp_arr_get_value()
	 *
	 * @param string $key (e.g. key1->key2->key3)
	 * @param mixed $default The default value to return if $key is not found within $array
	 *
	 * @return mixed
	 */
	function mp_get_get_value( $key, $default = false ) {
		return mp_arr_get_value( $key, $_GET, $default );
	}

endif;

if ( ! function_exists( 'mp_get_post_value' ) ) :

	/**
	 * Safely retreives a value from the $_POST array
	 *
	 * @since 3.0
	 * @uses mp_arr_get_value()
	 *
	 * @param string $key (e.g. key1->key2->key3)
	 * @param mixed $default The default value to return if $key is not found within $array
	 *
	 * @return mixed
	 */
	function mp_get_post_value( $key, $default = false ) {
		return mp_arr_get_value( $key, $_POST, $default );
	}

endif;

if ( ! function_exists( 'mp_get_request_value' ) ) :

	/**
	 * Safely retreives a value from the $_REQUEST array
	 *
	 * @since 3.0
	 * @uses mp_arr_get_value()
	 *
	 * @param string $key (e.g. key1->key2->key3)
	 * @param mixed $default The default value to return if $key is not found within $array
	 *
	 * @return mixed
	 */
	function mp_get_request_value( $key, $default = false ) {
		return mp_arr_get_value( $key, $_REQUEST, $default );
	}

endif;


if ( ! function_exists( 'mp_get_session_value' ) ) :

	/**
	 * Safely retreives a value from the $_SESSION array
	 *
	 * NOTE: this function is designed to only be used on cart and checkout pages.
	 * Use them any where else and they will not work as the session is only started
	 * on these pages!
	 *
	 * @since 3.0
	 * @uses mp_arr_get_value()
	 *
	 * @param string $key (e.g. key1->key2->key3)
	 * @param mixed $default The default value to return if $key is not found within $array
	 *
	 * @return mixed
	 */
	function mp_get_session_value( $key, $default = false ) {
		mp_public()->start_session();

		return mp_arr_get_value( $key, $_SESSION, $default );
	}

endif;

if ( ! function_exists( 'mp_get_setting' ) ) :
	/*
	 * Safely retrieves a setting
	 *
	 * An easy way to get to our settings array without undefined indexes
	 *
	 * @since 3.0
	 * @uses mp_arr_search()
	 *
	 * @param string $key A setting key, or -> separated list of keys to go multiple levels into an array
	 * @param mixed $default Returns when setting is not set
	 * @return mixed
	 */

	function mp_get_setting( $key, $default = null ) {

		$settings = get_option( 'mp_settings' );

		$keys    = explode( '->', $key );
		$keys    = array_map( 'trim', $keys );
		$setting = mp_arr_get_value( $key, $settings, $default );

		return apply_filters( 'mp_setting_' . implode( '', $keys ), $setting, $default );
	}

endif;

if ( ! function_exists( 'mp_get_network_setting' ) ) :
	/*
	 * Safely retrieves a network setting
	 *
	 * An easy way to get to our settings array without undefined indexes
	 *
	 * @since 3.0
	 * @uses mp_arr_search()
	 *
	 * @param string $key A setting key, or -> separated list of keys to go multiple levels into an array
	 * @param mixed $default Returns when setting is not set
	 * @return mixed
	 */

	function mp_get_network_setting( $key, $default = null ) {
		$settings = wp_cache_get( 'network_settings', 'marketpress' );
		if ( ! $settings ) {
			$settings = get_site_option( 'mp_network_settings', $default, false );
			wp_cache_set( 'network_settings', $settings, 'marketpress' );
		}

		$keys    = explode( '->', $key );
		$keys    = array_map( 'trim', $keys );
		$setting = mp_arr_get_value( $key, $settings, $default );

		return apply_filters( 'mp_network_setting_' . implode( '', $keys ), $setting, $default );
	}

endif;


if ( ! function_exists( 'mp_arr_search' ) ) :

	/**
	 * Searches an array multidimensional array for a specific path (if it exists)
	 *
	 * @since 3.0
	 *
	 * @param array $array The array we want to search
	 * @param string $path The path we want to check for (e.g. key1->key2->key3 = $array[key1][key2][key3])
	 *
	 * @return mixed
	 */
	function mp_arr_search( $array, $path ) {
		$keys = explode( '->', $path );
		$keys = array_map( 'trim', $keys );

		for ( $i = $array; ( $key = array_shift( $keys ) ) !== null; $i = $i[ $key ] ) {
			if ( ! isset( $i[ $key ] ) ) {
				return null;
			}
		}

		return $i;
	}

endif;

if ( ! function_exists( 'mp_register_addon' ) ) :

	/**
	 * Wrapper function for MP_Addons::register()
	 *
	 * @since 3.0
	 *
	 * @param array $args
	 */
	function mp_register_addon( $args = array() ) {
		MP_Addons::get_instance()->register( $args );
	}

endif;

if ( ! function_exists( 'mp_update_setting' ) ) :

	/**
	 * Update a specific setting
	 *
	 * @since 3.0
	 *
	 * @param string $key The key to update
	 * @param mixed $value The value to use
	 *
	 * @return bool
	 */
	function mp_update_setting( $key, $value ) {
		$settings = get_option( 'mp_settings' );
		mp_push_to_array( $settings, $key, $value );

		return update_option( 'mp_settings', $settings );
	}

endif;

if ( ! function_exists( 'mp_update_session_value' ) ) :

	/**
	 * Update a session variable
	 *
	 * NOTE: this function is designed to only be used on cart and checkout pages.
	 * Use them any where else and they will not work as the session is only started
	 * on these pages!
	 *
	 * @since 3.0
	 *
	 * @param string $key The key to update
	 * @param mixed $value The value to use
	 */
	function mp_update_session_value( $key, $value ) {
		mp_public()->start_session();
		mp_push_to_array( $_SESSION, $key, $value );
	}

endif;


if ( ! function_exists( 'mp_update_network_setting' ) ) :

	/**
	 * Update a specific network setting
	 *
	 * @since 3.0
	 *
	 * @param string $key The key to update
	 * @param mixed $value The value to use
	 *
	 * @return bool
	 */
	function mp_update_network_setting( $key, $value ) {
		$settings = get_site_option( 'mp_network_settings' );
		mp_push_to_array( $settings, $key, $value );

		return update_site_option( 'mp_network_settings', $settings );
	}

endif;

if ( ! function_exists( 'mp_parse_args' ) ) :

	/**
	 * Convert old style arguments (broken out into variables) into an associative array
	 *
	 * @param mixed $args
	 * @param array $defaults
	 *
	 * @return array
	 */
	function mp_parse_args( $args, $defaults ) {
		if ( ! isset( $args[0] ) ) {
			return $defaults;
		}

		if ( ( isset( $args[0] ) && is_array( $args[0] ) ) || ( isset( $args[0] ) && ! is_numeric( $args[0] ) && ! is_bool( $args[0] ) ) ) {
			return array_replace_recursive( $defaults, $args[0] );
		}

		$tmp_args = array();
		foreach ( $defaults as $key => $value ) {
			$val              = array_shift( $args );
			$tmp_args[ $key ] = ! is_null( $val ) ? $val : $value;
		}

		return $tmp_args;
	}

endif;
if ( ! function_exists( 'mp_plugin_url' ) ) :

	/**
	 * Returns a url with given path relative to the plugin's root
	 *
	 * @since 3.0
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	function mp_plugin_url( $path = '' ) {
		return mp()->plugin_url( $path );
	}

endif;

if ( ! function_exists( 'mp_plugin_dir' ) ) :

	/**
	 * Returns a path with given path relative to the plugin's root
	 *
	 * @since 3.0
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	function mp_plugin_dir( $path = '' ) {
		return mp()->plugin_dir( $path );
	}

endif;

if ( ! function_exists( 'mp_array_map_recursive' ) ) :

	/**
	 * Execute a give function on each element in a given array
	 *
	 * @param string $func The function name to execute
	 * @param array $array The array to execute on
	 *
	 * @return array
	 */
	function mp_array_map_recursive( $func, $array ) {
		foreach ( $array as $key => $val ) {
			$array[ $key ] = ( is_array( $array[ $key ] ) ) ? mp_array_map_recursive( $func, $array[ $key ] ) : $func( $val );
		}

		return $array;
	}

endif;

if ( ! function_exists( 'mp_array_to_attributes' ) ) :

	/**
	 * Convert an array of attributes to an html string
	 *
	 * @since 3.0
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	function mp_array_to_attributes( $attributes ) {
		$atts = '';
		foreach ( $attributes as $key => $val ) {
			$atts .= ' ' . $key . '="' . esc_attr( $val ) . '"';
		}

		return $atts;
	}

endif;

if ( ! function_exists( 'mp_is_main_site' ) ) :

	/**
	 * Checks if the current blog is the main site
	 *
	 * @since 3.0
	 * @uses $wpdb
	 */
	function mp_is_main_site() {
		global $wpdb;

		if ( MP_ROOT_BLOG !== false ) {
			return $wpdb->blogid == MP_ROOT_BLOG;
		} else {
			return is_main_site();
		}
	}

endif;

if ( ! function_exists( 'mp_is_post_indexer_installed' ) ) :

	/**
	 * Check if Post Indexer plugin is installed
	 *
	 * @since 3.0
	 * @return bool
	 */
	function mp_is_post_indexer_installed() {
		return ( defined( 'POST_INDEXER_PLUGIN_DIR' ) );
	}

endif;

if ( ! function_exists( 'mp_root_blog_id' ) ) :

	/**
	 * Get the root blog id
	 *
	 * @since 3.0
	 * @uses $current_site
	 */
	function mp_root_blog_id() {
		global $current_site;

		if ( MP_ROOT_BLOG !== false ) {
			return MP_ROOT_BLOG;
		} else {
			return $current_site->blog_id;
		}
	}

endif;

if ( ! function_exists( 'mp_get_store_caps' ) ) :

	/**
	 * Get store capabilities
	 *
	 * @since 3.0
	 * @return array
	 */
	function mp_get_store_caps() {
		if ( $store_caps = wp_cache_get( 'store_caps', 'marketpress' ) ) {
			return $store_caps;
		}

		$store_caps = array( 'manage_store_settings' => 'manage_store_settings' );
		$taxonomies = array( 'product_category', 'product_tag' );
		$pts        = array( 'product', 'mp_product', 'product_coupon', 'mp_order' );

		foreach ( $taxonomies as $tax_slug ) {
			if ( ! taxonomy_exists( $tax_slug ) ) {
				continue;
			}

			$tax = get_taxonomy( $tax_slug );
			foreach ( $tax->cap as $cap ) {
				$store_caps[ $cap ] = $cap;
			}
		}

		foreach ( $pts as $pt_slug ) {
			if ( ! post_type_exists( $pt_slug ) ) {
				continue;
			}

			$pt = get_post_type_object( $pt_slug );
			foreach ( $pt->cap as $cap ) {
				if( $cap == "read" ) {
					continue;
				}
				
				$store_caps[ $cap ] = $cap;
			}
		}

		wp_cache_set( 'store_caps', $store_caps, 'marketpress' );

		return $store_caps;
	}

endif;

if ( ! function_exists( 'mp_get_single_site_cart' ) ) {
	function mp_get_single_site_cart() {
		$items = mp_cart()->get_all_items();
		$model = new MP_Cart( false );
		$model->set_id( get_current_blog_id() );
		$items = isset( $items[ get_current_blog_id() ] ) ? $items[ get_current_blog_id() ] : array();
		foreach ( $items as $pid => $qty ) {
			$model->add_item( $pid, $qty );
		}

		return $model;
	}
}

if ( ! function_exists( 'mp_resize_image' ) ) {
	/**
	 * @param $image_id
	 * @param $image_url
	 * @param string $size
	 *
	 * @return mixed|void
	 */
	function mp_resize_image( $image_id, $image_url, $size = 'thumbnail' ) {
		$img_path = get_attached_file($image_id);

		$image = wp_get_image_editor( $img_path );

		if ( ! is_wp_error( $image ) ) {
			if ( is_array( $size ) ) {
				$size_data = $size;
			} else {
				// Get the image sizes from options
				$size_data = array(
					get_option( $size . '_size_w' ),
					get_option( $size . '_size_h' ),
				);
			}
			//build the path name, and try to check if
			$filename_data = pathinfo( $image_url );
			$upload_dir    = wp_upload_dir();
			$image_path    = $upload_dir['path'] . '/' . $filename_data['filename'] . '-' . $size_data[0] . 'x' . $size_data[1] . '.' . $filename_data['extension'];
			$image_url     = str_replace( $upload_dir['path'], $upload_dir['url'], $image_path );
			if ( file_exists( $image_path ) ) {
				//we will check the time of this image
				$cache_time = apply_filters( 'mp_image_resize_cache_duration', 3 );
				if ( strtotime( '+' . $cache_time . ' days', filemtime( $image_path ) ) <= time() ) {
					unlink( $image_path );
				}
			}

			if ( ! file_exists( $image_path ) ) {
				$is_crop = false;
				if ( $size == 'thumbnail' ) {
					$is_crop = array( 'left', 'top' );
				}
				$is_crop = apply_filters( 'mp_image_crop_position', $is_crop, $image, $image_path, $image_url, $size );
				$image->resize( $size_data[0], $size_data[1], $is_crop );
				$image->save( $image_path );
			}

			$image = apply_filters( 'image_downsize', array(
				$image_url,
				$size_data[0],
				$size_data[1]
			), $image_id, array( $size_data[0], $size_data[1] ) );

			return apply_filters( 'mp_image_after_resize', $image );
		}

		return false;
	}
}

if ( ! function_exists( 'mp_get_the_thumbnail' ) ) {

}

if (! function_exists( 'mp_array_column' ) ) {
    function mp_array_column( array $input, $columnKey, $indexKey = null ) {
    	
    	if( function_exists( 'array_column' ) ){
    		return array_column( $input, $columnKey, $indexKey );
    	}

        $array = array();
        foreach ( $input as $value ) {
            if ( ! isset( $value[$columnKey] ) ) {
                return false;
            }
            if ( is_null( $indexKey ) ) {
                $array[] = $value[$columnKey];
            }
            else {
                if ( ! isset( $value[$indexKey] ) ) {
                    return false;
                }
                if ( ! is_scalar( $value[$indexKey] ) ) {
                    return false;
                }
                $array[$value[$indexKey]] = $value[$columnKey];
            }
        }
        return $array;
    }
}
