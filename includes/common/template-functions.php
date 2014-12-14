<?php
	
if ( ! function_exists( '_mp_order_status_overview' ) ) :
	/**
	 * Display the order status overview html.
	 *
	 * @since 3.0
	 * @return string
	 */
	function _mp_order_status_overview() {		
		$history = mp_get_order_history();
		$page = max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) );
		$per_page = get_option( 'posts_per_page' );
		$offset = ($page - 1) * $per_page;
		$total_pages = ceil( count( $history ) / $per_page );
		$html = '
			<div id="mp-order-history">';
		
		if ( count( $history ) > 0 )  {
			$history = array_slice( $history, $offset, $per_page );
			$html .= '
				<h2>' . __( 'Order History', 'mp' ) . '</h2>';
			
			foreach ( $history as $timestamp => $order ) {
				$order = new MP_Order( $order['id'] );
				$html .= $order->header( false );
			}
			
			if ( $total_pages > 1 ) {
				$big = 99999999;
				$html .= '<div id="mp-order-history-pagination">';
				$html .= paginate_links( array(
					'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'current' => $page,
					'total' => $total_pages,
				) );
				$html .= '</div>';
			}
		}
		
		$html .= '
			</div>';
		
		return $html;
	}
endif;

if ( ! function_exists( '_mp_products_html' ) ) :
	/**
	 * Display products according to preference
	 *
	 * @since 3.0
	 * @access public
	 * @param string $view Either "grid" or "list".
	 * @param WP_Query $custom_query A WP_Query object.
	 * @return string
	 */
	function _mp_products_html( $view, $custom_query ) {
		$html = '';
		$per_row = (int) mp_get_setting( 'per_row' );
		$width = round( 100 / $per_row, 1 ) . '%';
		$column = 1;
		
		//get image width
		if ( mp_get_setting( 'list_img_size' ) == 'custom' ) {
			$img_width = mp_get_setting( 'list_img_width' ) . 'px';
		} else {
			$size = mp_get_setting( 'list_img_size' );
			$img_width = get_option( $size . '_size_w' ) . 'px';
		}
		
		while ( $custom_query->have_posts() ) : $custom_query->the_post();
			$product = new MP_Product();
			
			$align = null;
			if ( 'list' == mp_get_setting( 'list_view' ) ) {
				$align = mp_get_setting( 'image_alignment_list' );
			}
			$img = $product->image( false, 'list', null, $align );
			
			$excerpt = mp_get_setting( 'show_excerpt' ) ? '<div class="mp_excerpt">' . $product->excerpt() . '</div>' : '';
			$mp_product_list_content = apply_filters( 'mp_product_list_content', $excerpt, $product->ID );
	
			$pinit = $product->pinit_button( 'all_view' );

			$class = array();
			$class[] = ( strlen( $img ) > 0 ) ? 'mp_thumbnail' : '';
			$class[] = ( strlen( $excerpt ) > 0 ) ? 'mp_excerpt' : '';
			$class[] = ( $product->has_variations() ) ? 'mp_price_variations' : '';
			$class[] = ( $product->on_sale() ) ? 'mp_on_sale' : '';
			
			if ( 'grid' == $view ) {
				if ( $column == 1 ) {
					$class[] = 'first';
					$html .= '<div class="mp_grid_row">';
					$column ++;
				} elseif ( $column == $per_row ) {
					$class[] = 'last';
					$column = 1;
				}
			}
			
			$class = array_filter( $class, create_function( '$s', 'return ( ! empty( $s ) );' ) );

			$html .= '
				<div itemscope itemtype="http://schema.org/Product" class="hentry mp_one_tile ' . implode( $class, ' ' ) . '"' . (( 'grid' == $view ) ? ' style="width: ' . $width . '"' : '') . '>
					<div class="mp_one_product clearfix"' . (( 'grid' == $view ) ? ' style="width:' . $img_width . '"' : '') . '>
						<div class="mp_product_detail">
							' . $img . '
							<div class="mp_product_content">
								' . $pinit .'
								<h3 class="mp_product_name entry-title" itemprop="name">
									<a href="' . $product->url( false ) . '">' . $product->title( false ) . '</a>
								</h3>'
								. $mp_product_list_content . '
							</div>
						</div>

						<div class="mp_price_buy">
							' . $product->display_price( false ) . '
							' . $product->buy_button( false, 'list' ) . '
							' . apply_filters( 'mp_product_list_meta', '', $product->ID ) . '
						</div>
						
						<div style="display:none">
							<span class="entry-title">' . $product->title( false ) . '</span> was last modified:
							<time class="updated">' . get_the_time( 'Y-m-d\TG:i' ) . '</time> by
							<span class="author vcard"><span class="fn">' . get_the_author_meta( 'display_name' ) . '</span></span>
						</div>
					</div>
				</div>';
			
			if ( $column == 1 && $view == 'grid' ) {
				$html .= '</div><!-- END .mp_grid_row -->';				
			}
		endwhile;
		
		if ( $column != 1 && $view == 'grid' ) {
			$html .= '</div><!-- END .mp_grid_row -->';
		}

		if ( $view == 'grid' ) {
			$html .= ( $custom_query->found_posts > 0 ) ? '<div class="clear"></div>' : '';
		}
		
		wp_reset_postdata();
		
		/**
		 * Filter the product list html content
		 *
		 * @since 3.0
		 * @param string $html.
		 * @param WP_Query $custom_query.
		 */
		return apply_filters( "_mp_products_html_{$view}", $html, $custom_query );
	}
endif;

if ( ! function_exists( '_mp_products_html_list' ) ) :
	/**
	 * Display product list in list layout
	 *
	 * @since 3.0
	 * @param WP_Query $custom_query
	 * @return string
	 */
	function _mp_products_html_list( $custom_query ) {
		return _mp_products_html( 'list', $custom_query );
	}
endif;

if ( ! function_exists('_mp_products_html_grid')) :
	/**
	 * Display product list in grid layout
	 *
	 * @since 3.0
	 * @param WP_Query $custom_query
	 * @return string
	 */
	function _mp_products_html_grid( $custom_query ) {
		return _mp_products_html( 'grid', $custom_query );
	}
endif;

if ( ! function_exists( 'mp_before_tax_price' ) ) :
	/**
	 * Get the price before taxes
	 *
	 * @since 3.0
	 * @access public
	 */
	function before_tax_price( $tax_price, $product_id = null ) {
		//if tax inclusve pricing is turned off just return given price
		if ( ! mp_get_setting( 'tax->tax_inclusive' ) ) {
			return $tax_price;
		}

		$product = new MP_Product( $product_id );
		if ( $product->exists() && ($tax_rate = $product->get_meta( 'special_tax_rate' )) ){
			$rate = $tax_rate;
		} else {
			if	( 'CA' == mp_get_setting( 'tax->base_country' ) ) {
				$rate = mp_get_setting( 'tax->canada_rate->' . mp_get_setting( 'base_province' ) );
			} else {
				$rate = mp_get_setting( 'tax->rate' );
			}
		}

		return $tax_price / ($rate + 1);
	}
endif;

if ( ! function_exists('mp_buy_button') ) :
	/**
	 * Display the buy or add to cart button
	 *
	 * @param bool $echo Optional, whether to echo
	 * @param string $context Options are list or single
	 * @param int $post_id The post_id for the product. Optional if in the loop.
	 */
	function mp_buy_button( $echo = true, $context = 'list', $post_id = NULL ) {
		_deprecated_function('mp_buy_button', '3.0', 'MP_Product::buy_button');
		
		$product = new MP_Product($post_id);
		$button = $product->buy_button(false, $context);
		
		if ( $echo ) {
			echo $button;
		} else {
			return $button;
		}
	}
endif;

if ( ! function_exists('mp_cart_link') ) :
	/**
	 * Display the current shopping cart link. If global cart is on reflects global location
	 * @since 3.0
	 * @param bool $echo Optional, whether to echo. Defaults to true.
	 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
	 * @param string $link_text Optional, text to show in link.
	 */
	function mp_cart_link( $echo = true, $url = false, $link_text = false ) {
		if ( mp_cart()->is_global && ! mp_is_main_site() ) {
			switch_to_blog(MP_ROOT_BLOG);
			$link = get_permalink(mp_get_setting('pages->cart'));
			restore_current_blog();
		} else {
			$link = get_permalink(mp_get_setting('pages->cart'));
		}

		if ( ! $url ) {
			$text = ( $link_text ) ? $link_text : __('Shopping Cart', 'mp');
			$link = '<a href="' . $link . '" class="mp_cart_link">' . $text . '</a>';
		}

		/**
		 * Filter the cart link
		 *
		 * @since 3.0
		 * @param string $link The current link.
		 * @param bool $echo Optional, whether to echo. Defaults to true.
		 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
		 * @param string $link_text Optional, text to show in link.
		 */
		$link = apply_filters('mp_cart_link', $link, $echo, $url, $link_text);

		if ( $echo ) {
			echo $link;
		} else {
			return $link;
		}
	}
endif;

if ( ! function_exists('mp_checkout_step_url') ) :
	/**
	 * Get the current shopping cart link with checkout step
	 *
	 * @since 3.0
	 * @param string $checkoutstep. Possible values: checkout-edit, shipping, checkout, confirm-checkout, confirmation
	 */
	function mp_checkout_step_url( $checkout_step ) {
		return ( is_admin() ) ? '' : apply_filters('mp_checkout_step_url', mp_cart_link(false, true) . trailingslashit($checkout_step), $checkout_step);
	}
endif;

if ( ! function_exists( 'mp_create_store_page' ) ) :
	/**
	 * Create a store page
	 *
	 * @since 3.0
	 * @param string $type The type of page to create.
	 * @return int $post_id The ID of the newly created page.
	 */
	function mp_create_store_page( $type ) {
		$args = array();
		$defaults = array(
			'post_status' => 'publish',
			'post_type' => 'page',
		);
		
		switch ( $type ) {
			case 'store' :
				$args = array(
					'post_title' => __( 'Store', 'mp' ),
					'post_content' => __( "Welcome to our online store! Feel free to browse around:\n\n[mp_store_navigation]\n\nCheck out our most popular products:\n\n[mp_popular_products]\n\nBrowse by category:\n\n[mp_list_categories]\n\nBrowse by tag:\n\n[mp_tag_cloud]", 'mp' ),
				);
			break;
			
			case 'network_store_page' :
				$args = array(
					'post_title' => __( 'Global Store', 'mp' ),
					'post_content' => __( "Welcome to our market place!\n\nCheck out our network of products:\n\n[mp_list_global_products]\n\nBrowse by category:\n\n[mp_global_categories_list]\n\nBrowse by tag:\n\n[mp_global_tag_cloud]", 'mp' ),
				);
			break;
			
			case 'products' :
				$args = array(
					'post_title' => __( 'Products', 'mp' ),
					'post_content' => '[mp_list_products]',
					'post_parent' => mp_get_setting( 'pages->store', 0 ),
				);
			break;
			
			case 'cart' :
				$args = array(
					'post_title' => __( 'Cart', 'mp' ),
					'post_content' => '[mp_cart]',
					'post_parent' => mp_get_setting( 'pages->store', 0 ),
				);
			break;
			
			case 'checkout' :
				$args = array(
					'post_title' => __( 'Checkout', 'mp' ),
					'post_content' => '[mp_checkout]',
					'post_parent' => mp_get_setting( 'pages->store', 0 )
				);
			break;
			
			case 'order_status' :
				$args = array(
					'post_title' => __( 'Order Status', 'mp' ),
					'post_content' => "[mp_order_lookup_form]\n<h2>" . __( 'Order Search', 'mp' ) . "</h2>\n<p>" . __( 'If you have your order ID you can look it up using the form below.', 'mp' ) . "</p>[/mp_order_lookup_form]\n[mp_order_status]\n",
					'post_parent' => mp_get_setting( 'pages->store', 0 )
				);
			break;
		}
		
		$post_id = wp_insert_post( array_merge( $defaults, $args ) );
		MP_Pages_Admin::get_instance()->save_store_page_value( $type, $post_id, false );
		
		return $post_id;
	}
endif;

if ( ! function_exists('mp_format_currency') ) :
	/**
	 * Formats currency
	 *
	 * @since 3.0
	 *
	 * @param string $currency The currency code to use for formatting (defaults to value set in currency settings)
	 * @param float $amount The amount to format
	 * @return string
	 */
	function mp_format_currency( $currency = '', $amount = false ) {
		$currencies = apply_filters('mp_currencies', mp()->currencies);
		
		if ( empty($currency) ) {
			$currency = mp_get_setting('currency', 'USD');
		}
		
		// get the currency symbol
		if ( $symbol = mp_arr_get_value("$currency->1", $currencies) ) {
			// if many symbols are found, rebuild the full symbol
			$symbols = array_map('trim', explode(', ', $symbol));
			if ( is_array($symbols) ) {
				$symbol = '';
				foreach ($symbols as $temp) {
					$symbol .= '&#x'.$temp.';';
				}
			} else {
				$symbol = '&#x'.$symbol.';';
			}
		}

		/**
		 * Filter the currency symbol used to format curency
		 *
		 * @since 3.0
		 * @param string $symbol
		 * @param string $currency
		 */
		$symbol = apply_filters('mp_format_currency_symbol', $symbol, $currency);

		//check decimal option
		if ( mp_get_setting('curr_decimal') === '0' ) {
			$decimal_place = 0;
			$zero = '0';
		} else {
			$decimal_place = 2;
			$zero = '0.00';
		}
		
		//handle negative numbers
		$negative_symbol = '';
		if ( $amount < 0 ) {
			$negative_symbol = '-';
			$amount = abs($amount);
		}

		//format currency amount according to preference
		if ( $amount ) {
			if ( mp_get_setting('curr_symbol_position') == 1 || ! mp_get_setting('curr_symbol_position') )
				return $negative_symbol . $symbol . number_format_i18n($amount, $decimal_place);
			
			if ( mp_get_setting('curr_symbol_position') == 2 )
				return $negative_symbol . $symbol . ' ' . number_format_i18n($amount, $decimal_place);
			
			if ( mp_get_setting('curr_symbol_position') == 3 )
				return number_format_i18n($amount, $decimal_place) . $symbol;
				
			if ( mp_get_setting('curr_symbol_position') == 4 )
				return number_format_i18n($amount, $decimal_place) . ' ' . $symbol;
		} else if ( $amount === false ) {
			return $symbol;
		} else {
			if ( mp_get_setting('curr_symbol_position') == 1 || ! mp_get_setting('curr_symbol_position') )
				return $symbol . $zero;
			
			if ( mp_get_setting('curr_symbol_position') == 2 )
				return $symbol . ' ' . $zero;
			
			if ( mp_get_setting('curr_symbol_position') == 3 )
				return $zero . $symbol;
			
			if ( mp_get_setting('curr_symbol_position') == 4 )
				return $zero . ' ' . $symbol;
		}
		
		/**
		 * Filter the formatted currency
		 *
		 * @since 3.0
		 * @param string $currency
		 * @param string $symbol
		 * @param float $amount
		 */
		return apply_filters('mp_format_currency', $currency, $symbol, $amount);
	}
endif;

if ( ! function_exists( 'mp_format_date' ) ) :
	/**
	 * Format a date according to settings
	 *
	 * @since 3.0
	 * @param int $timestamp
	 * @param bool $date_only Optional, whether to return just the date part or include the time as well. Defaults to include time.
	 */
	function mp_format_date( $timestamp, $date_only = false ) {
		$format = get_option( 'date_format' );
		if ( ! $date_only ) {
			$format .= ' - ' . get_option( 'time_format' );
		}
		
		return date_i18n( $format, $timestamp );
	}
endif;

if ( ! function_exists('mp_get_current_user_zipcode') ) :
	/**
	 * Get the current user's zipcode
	 *
	 * @since 3.0
	 * @access public
	 * @return string The zipcode. False, if no zipcode could be retrieved.
	 */
	function mp_get_current_user_zipcode() {
		$user = wp_get_current_user();
		$address = $user->get('mp_shipping_info');
		$zipcode = false;
		
		if ( is_array($address) ) {
			// Try to get from usermeta
			$zipcode = mp_arr_get_value('zip', $address);
		}
		
		if ( false === $zipcode ) {
			// Try to get from cookie
			$zipcode = mp_get_cookie_value('zip');
		}
		
		return $zipcode;
	}
endif;

if ( ! function_exists('mp_get_user_address') ) :
	/**
	 * Get full user address
	 *
	 * @since 3.0
	 * @param string $what Either shipping or billing.
	 * @param WP_User/int $user Optional, an WP_User object or a user ID. Defaults to the current user.
	 * @return array False, on error.
	 */
	function mp_get_user_address( $what, $user = null ) {
		if ( is_null($user) ) {
			$user = wp_get_current_user();
		} elseif ( ! $user instanceof WP_User && false === ($user = get_user_by('id', $user)) ) {
			return false;
		}
		
		$data = mp_get_session_value("mp_{$what}_info");
		
		if ( empty($data) ) {
			$data = $user->get("mp_{$what}_info");
		}
		
		return $data;
	}
endif;

if ( ! function_exists('mp_list_payment_options') ) :
	/**
	 * List available payment options (if there is more than one)
	 *
	 * @since 3.0
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	function mp_list_payment_options( $echo = true ) {
		$gateways = MP_Gateway_API::get_active_gateways();
		$html = '';
		
		$options = array();
		foreach ( $gateways as $code => $gateway ) {
			$options[$code] = $gateway->public_name;
		}
		
		/**
		 * Filter the options array before formatting to html
		 *
		 * @since 3.0
		 * @param array $options
		 */
		$options = (array) apply_filters('mp_payment_options_array', $options);
		
		$index = 0;
		foreach ( $options as $code => $label ) {
			$checked = '';
			if ( $selected = mp_get_session_value('mp_payment_method') ) {
				if ( $selected == $code ) {
					$checked = ' checked';
				}
			} elseif ( $index == 0 ) {
				$checked = ' checked';
			}
			
			$input_id = 'mp-gateway-option-' . $code;
			$html .= '
				<label class="mp-checkout-option-label" for="' . $input_id . '"' . (( count( $options) == 1 ) ? ' style="display:none"' : '') . '>
					<input id="' . $input_id . '" type="radio" name="payment_method" value="' . $code . '"' . $checked . ' autocomplete="off" />
					<span></span>' . $label . '
				</label>';
			
			$index ++;
		}
		
		/**
		 * Filter the payment options html
		 *
		 * @since 3.0
		 * @param string $html The current html.
		 */
		$html = apply_filters('mp_list_payment_options', $html);
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
endif;

if ( ! function_exists( 'mp_list_plugin_shipping_options' ) ) :
	/**
	 * Display an array of shipping plugin shipping options as html
	 *
	 * @since 3.0
	 * @param MP_Shipping_API $plugin A shipping plugin object.
	 * @param bool $echo Optional, whether to echo or return. Defaults to return.
	 * @return string
	 */
	function mp_list_plugin_shipping_options( $plugin, $echo = false ) {
		if ( ! $plugin instanceof MP_Shipping_API ) {
			trigger_error( $plugin . ' is not an instance of MP_Shipping_API', E_USER_ERROR );
		}
		
		$what =  ( mp_get_post_value( 'enable_shipping_address' ) ) ? 'shipping' : 'billing';
		$shipping_option = mp_get_session_value( 'mp_shipping_info->shipping_option' );
		$shipping_sub_option = mp_get_session_value( 'mp_shipping_info->shipping_sub_option' );
		
		$address1 = mp_get_user_address_part( 'address1', $what );
		$address2 = mp_get_user_address_part( 'address2', $what );
		$city = mp_get_user_address_part( 'city', $what );
		$state = mp_get_user_address_part( 'state', $what );
		$zip = mp_get_user_address_part( 'zip', $what );
		$country = mp_get_user_address_part( 'country', $what );
		
		$items = mp_cart()->get_items();
		$options = $plugin->shipping_options( $items, $address1, $address2, $city, $state, $zip, $country );
		
		$html = '';
		foreach ( (array) $options as $method => $label ) {
			$input_id = 'mp-shipping-option-' . $plugin->plugin_name . '-' . sanitize_title( $method );
			$checked = ( $plugin->plugin_name == $shipping_option && $method == $shipping_sub_option ) ? ' checked' : '';
			$html .= '
				<label class="mp-checkout-option-label" for="' . $input_id . '">
					<input
						id="' . $input_id . '"
						type="radio"
						name="shipping_method"
						value="' . $plugin->plugin_name . '->' . $method . '"
						autocomplete="off"
						data-rule-required="true"
						data-msg-required="' . __( 'Please choose a shipping method', 'mp' ) . '"' .
						$checked . ' />
					<span></span>' . $label . '
				</label>';
		}
		
		/**
		 * Filter the shipping options list html
		 *
		 * @since 3.0
		 * @param string $html Current html.
		 * @param array $options An array of shipping options.
		 */
		$html = apply_filters( 'mp_list_shipping_options', $html, $options );
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
endif;

if ( ! function_exists('mp_get_user_address_part') ) :
	/**
	 * Get user address part
	 *
	 * @since 3.0
	 * @param string $what What to get (e.g. address1, address2, etc)
	 * @param string $type Either shipping or billing.
	 * @param WP_User/int $user Optional, an WP_User object or a user ID. Defaults to the current user.
	 * @return string
	 */
	function mp_get_user_address_part( $what, $type, $user = null ) {
		if ( is_null( $user ) ) {
			$user = wp_get_current_user();
		} elseif ( ! $user instanceof WP_User && false === ($user = get_user_by( 'id', $user )) ) {
			return false;
		}
		
		$meta = $user->get( "mp_{$type}_info" );
		
		if ( 'first_name' == $what || 'last_name' == $what ) {
			$name = mp_get_session_value( "mp_shipping_info->name", mp_arr_get_value( 'name', $meta, '' ) );
			$name_parts = explode( ' ', $name );
			
			if ( 'first_name' == $what ) {
				return mp_arr_get_value( '0', $name_parts, '' );
			} else {
				return mp_arr_get_value( '1', $name_parts, '' );
			}
		} else {
			return mp_get_session_value( "mp_shipping_info->{$what}", mp_arr_get_value( $what, $meta, '' ) );
		}
	}
endif;

if ( ! function_exists( 'mp_get_states' ) ) :
	/**
	 * Get an array of states/provinces for a given country
	 *
	 * @since 3.0
	 * @access public
	 * @param string $country A country code.
	 * @return string
	 */
	function mp_get_states( $country ) {
		$list = false;
		switch ( $country ) {
			case 'US' :
				$list = mp()->usa_states;
			break;
			
			case 'CA' :
				$list = mp()->canadian_provinces;
			break;
			
			case 'AU' :
				$list = mp()->australian_states;
			break;
		}	

		/**
		 * Filter the state/province list
		 *
		 * @since 3.0
		 * @param array $list The current state/province list.
		 * @param string $country The current country.
		 */
		return apply_filters( 'mp_get_states', $list, $country );
	}
endif;

if ( ! function_exists('mp_get_image_size') ) :
	/**
	 * Get the image size per presentation settings
	 *
	 * @since 3.0
	 * @param string $view Either "single" or "list".
	 * @return array
	 */
	function mp_get_image_size( $view ) {
		$prefix = ( $view == 'single' ) ? 'product' : 'list';
		$size = mp_get_setting($prefix . '_img_size');
		
		if ( $size == 'custom' ) {
			$size = array(
				'label' => 'custom',
				'width' => intval(mp_get_setting($prefix . '_img_size_custom->width')),
				'height' => intval(mp_get_setting($prefix . '_img_size_custom->height')),
			);
		} else {
			$size = array(
				'label' => $size,
				'width' => get_option($size . '_size_w'),
				'height' => get_option($size . 'size_h'),
			);
		}
		
		return $size;
	}
endif;

if ( ! function_exists( 'mp_get_order_history' ) ) :
	/**
	 * Get order history for a given user
	 *
	 * @since 3.0
	 * @param int $user_id The ID of the user to retrieve order history for.
	 * @return array
	 */
	function mp_get_order_history( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		
		if ( is_multisite() ) {
			global $blog_id;
			$key = 'mp_order_history_' . $blog_id;
		} else {
			$key = 'mp_order_history';
		}
		
		if ( $user_id ) {
			$orders = (array) get_user_meta( $user_id, $key, true );
		} else {
			$orders = (array) mp_get_cookie_value( $key, array() );
		}
		
		/**
		 * Filter the user's order history
		 *
		 * @since 3.0
		 * @param array $orders The current array of orders.
		 * @param int $user_id The user's ID.
		 */
		$orders = (array) apply_filters( 'mp_get_order_history', $orders, $user_id );
		
		// Put orders in reverse chronological order
		krsort( $orders );
		
		return $orders;
	}
endif;

if ( ! function_exists('mp_store_page_uri') ) {
	/**
	 * Get a store page uri
	 *
	 * @since 3.0
	 * @param string $page The page to get the uri for.
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	function mp_store_page_uri( $page, $echo = true ) {
		$url = '';
		if ( $post_id = mp_get_setting("pages->{$page}") ) {
			$url = get_page_uri($post_id);
		}
		
		if ( $echo ) {
			echo $url;
		} else {
			return $url;
		}
	}
}

if ( ! function_exists('mp_store_page_url') ) {
	/**
	 * Get a store page url
	 *
	 * @since 3.0
	 * @param string $page The page to get the URL for.
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	function mp_store_page_url( $page, $echo = true ) {
		$url = '';
		if ( $post_id = mp_get_setting("pages->{$page}") ) {
			$url = get_permalink($post_id);
		}
		
		if ( $echo ) {
			echo $url;
		} else {
			return $url;
		}
	}
}

if ( ! function_exists('mp_is_shop_page') ) :
	/**
	 * Check if current page is a shop page
	 *
	 * @since 3.0
	 * @param array/string $page The specific page to check - e.g. "cart".
	 * @return bool
	 */
	function mp_is_shop_page( $page = null ) {
		return MP_Public::get_instance()->is_store_page($page);
	}
endif;

if ( ! function_exists('mp_list_products') ) :
	/**
	 * Display a list of products according to preference
	 *
	 * @since 3.0
	 * @param bool $echo Optional, whether to echo or return
	 * @param bool $paginate Optional, whether to paginate
	 * @param int $page Optional, The page number to display in the product list if $paginate is set to true.
	 * @param int $per_page Optional, How many products to display in the product list if $paginate is set to true.
	 * @param string $order_by Optional, What field to order products by. Can be: title, date, ID, author, price, sales, rand
	 * @param string $order Optional, Direction to order products by. Can be: DESC, ASC
	 * @param string $category Optional, limit to a product category
	 * @param string $tag Optional, limit to a product tag
	 * @param bool $list_view Optional, show as list. Default to presentation settings
	 * @param bool $filters Optional, show filters	 
	 */
	function mp_list_products() {
		// Init args
		$func_args = func_get_args();
		$args = mp_parse_args( $func_args, mp()->defaults['list_products'] );
		$args['nopaging'] = false;
		
		// Init query params
		$query = array(
			'post_type' => MP_Product::get_post_type(),
			'post_status' => 'publish',
		);
		
		// Setup taxonomy query
		$tax_query = array();
		if ( ! is_null($args['category']) || ! is_null($args['tag']) ) {
			if ( ! is_null($args['category']) ) {
				$tax_query[] = array(
					'taxonomy' => 'product_category',
					'field' => 'slug',
					'terms' => sanitize_title($args['category']),
				);
			}
	
			if ( ! is_null($args['tag']) ) {
				$tax_query[] = array(
					'taxonomy' => 'product_tag',
					'field' => 'slug',
					'terms' => sanitize_title($args['tag']),
				);
			}
		} elseif ( get_query_var('taxonomy') == 'product_category' ) {
			$tax_query[] = array(
				'taxonomy' => 'product_category',
				'field' => 'slug',
				'terms' => get_query_var('term'),
			);
		} elseif ( get_query_var('taxonomy') == 'product_tag' ) {
			$tax_query[] = array(
				'taxonomy' => 'product_tag',
				'field' => 'slug',
				'terms' => get_query_var('term'),
			);
		}
		
		if ( count($tax_query) > 1 ) {
			$query['tax_query'] = array_merge(array('relation' => 'AND'), $tax_query);
		} elseif ( count($tax_query) == 1 ) {
			$query['tax_query'] = $tax_query;
		}

		// Setup pagination
		if ( (! is_null($args['paginate']) && ! $args['paginate']) || (is_null($args['paginate']) && ! mp_get_setting('paginate')) ) {
			$query['nopaging'] = $args['nopaging'] = true;
		} else {
			// Figure out per page
			if ( ! is_null($args['per_page']) ) {
				$query['posts_per_page'] = intval($args['per_page']);
			} else {
				$query['posts_per_page'] = intval(mp_get_setting('per_page'));
			}
			
			// Figure out page
			if ( ! is_null($args['page']) ) {
				$query['paged'] = intval($args['page']);
			} elseif ( get_query_var('paged') != '' ) {
				$query['paged'] = $args['page'] = intval(get_query_var('paged'));
			}
	
			// Get order by
			if ( ! is_null($args['order_by']) ) {
				if ( 'price' == $args['order_by'] ) {
					$query['meta_key'] = 'regular_price';
					$query['orderby'] = 'meta_value_num';
				} else if ( 'sales' == $args['order_by'] ) {
					$query['meta_key'] = 'mp_sales_count';
					$query['orderby'] = 'meta_value_num';
				} else {
					$query['orderby'] = $args['order_by'];
				}
			} elseif ( 'price' == mp_get_setting('order_by') ) {
				$query['meta_key'] = 'regular_price';
				$query['orderby'] = 'meta_value_num';
			} elseif ( 'sales' == mp_get_setting('order_by') ) {
				$query['meta_key'] = 'mp_sales_count';
				$query['orderby'] = 'meta_value_num';
			} else {
				$query['orderby'] = mp_get_setting('order_by');
			}
		}
		
		// Get order direction
		$query['order'] = mp_get_setting('order');
		if ( ! is_null($args['order']) ) {
			$query['order'] = $args['order'];
		}
		
		// The Query
		$custom_query = new WP_Query($query);

		// Get layout type
		$layout_type = mp_get_setting('list_view');
		if ( ! is_null($args['list_view']) ) {
			$layout_type = $args['list_view'] ? 'list' : 'grid';
		}
		
		// Build content
		$content = '';
		
		if ( ! mp_doing_ajax() ) {
			$per_page = ( is_null($args['per_page']) ) ? null : $args['per_page'];
			$content .= ( (is_null($args['filters']) && 1 == mp_get_setting('show_filters')) || $args['filters'] ) ? mp_products_filter(false, $per_page, $custom_query) : mp_products_filter(true, $per_page, $custom_query);
		}
		
		$content .= '<div id="mp_product_list" class="clearfix hfeed mp_' . $layout_type . '">';
	
		if ( $last = $custom_query->post_count ) {
			$content .= $layout_type == 'grid' ? _mp_products_html_grid($custom_query) : _mp_products_html_list($custom_query);
		} else {
			$content .= '<div id="mp_no_products">' . apply_filters('mp_product_list_none', __('No Products', 'mp')) . '</div>';
		}
	
		$content .= '</div>';

		$content .= ( ! $args['nopaging'] ) ? mp_products_nav( false, $custom_query ) : '';
		
		/**
		 * Filter product list html
		 *
		 * @since 3.0
		 * @param string $content The current html content.
		 * @param array $args The arguments passed to mp_list_products
		 */
		$content = apply_filters( 'mp_list_products', $content, $args );
	
		if ( $args['echo'] ) {
			echo $content;
		} else {
			return $content;
		}
	}
endif;

if ( ! function_exists( 'mp_order_lookup_form' ) ) :
	/**
	 * Display a form for looking up orders
	 *
	 * @since 3.0
	 * @param array $args {
	 * 		Optional, an array of arguments.
	 *
	 * 		@type bool $echo Optional, whether to echo or return. Defaults to echo.
	 *		@type string $content Optional, the content to display before the form.
	 * }
	 */
	function mp_order_lookup_form( $args = array() ) {
		$_args = array_replace_recursive( array(
			'echo' => true,
			'content' => '',
		), $args );
		
		extract( $_args );
		
		if ( get_query_var( 'mp_order_id' ) ) {
			return '';
		}
		
		$form = '
			<form id="mp-order-lookup-form" method="post" action="' . admin_url( 'admin-ajax.php?action=mp_lookup_order' ) . '">
				<div class="mp-order-lookup-form-content">' . $content . '</div>
				<input type="text" id="mp-order-id-input" name="order_id" placeholder="' . __( 'Order ID', 'mp' ) . '" /> <button type="submit" class="mp-button">' . __( 'Look Up', 'mp' ) . '</button>
			</form>';
			
		/**
		 * Filter the order lookup form html
		 *
		 * @since 3.0
		 * @param string $form The form HTML.
		 * @param array $args Any arguments passed to the function.
		 */
		$form = apply_filters( 'mp_order_lookup_form', $form, $args );
			
		if ( $echo ) {
			echo $form;
		} else {
			return $form;
		}
	}
endif;

if ( ! function_exists( 'mp_order_status' ) ) :
	/**
	 * Display the order status page html
	 *
	 * @since 3.0
	 * @param array $args {
	 *		Optional, an array of arguments.
	 *
	 *		@type bool $echo Optional, whether to echo or return. Defaults to echo.
	 *		@type string $order_id Optional, the specific order ID to show. If empty, defaults to order status overview page.
	 * }
	 */
	function mp_order_status( $args ) {
		$args = array_replace_recursive( array(
			'echo' => false,
			'order_id' => get_query_var( 'mp_order_id', null ),
		), $args );
		
		extract( $args );
		
		$html = '';
		if ( is_null( $order_id ) ) {
			$html .= _mp_order_status_overview();
		} else {
			$order = new MP_Order( $order_id );
			if ( $order->exists() ) {
				$html .= $order->details( false );
			} else {
				$html .= __( 'Oops! We couldn\'t locate any orders matching that order number. Please verify the order number and try again.', 'mp' );
				$html .= _mp_order_status_overview();
			}
		}
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
endif;

if ( ! function_exists('mp_pinit_button') ) :
	/**
	 * Pinterest PinIt button
	 *
	 * @param int $product_id
	 * @param string $context
	 * @param bool $echo
	 */
	function mp_pinit_button( $product_id = NULL, $context = 'single_view', $echo = false ) {
		_deprecated_function('mp_pinit_button', '3.0', 'MP_Product::pinit_button()');
		
		$product = new MP_Product($product_id);
		$snippet = $product->pinit_button($context, false);
		
		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
	}
endif;

if ( ! function_exists( 'mp_popular_products' ) ) :
/**
 * Displays a list of popular products ordered by sales.
 *
 * @since 3.0
 * @param bool $echo Optional, whether to echo or return
 * @param int $num Optional, max number of products to display. Defaults to 5
 */
function mp_popular_products( $echo = true, $num = 5 ) {
		$num = (int) $num;
		$custom_query = new WP_Query( array(
			'post_type' => MP_Product::get_post_type(),
			'post_status' => 'publish',
			'posts_per_page' => $num,
			'meta_query' => array(
				array(
					'key' => 'mp_sales_count',
					'value' => '0',
					'compare' => '>',
					'type' => 'NUMERIC',
				),
			),
			'orderby' => 'meta_value_num',
			'meta_key' => 'mp_sales_count',
			'order' => 'DESC',
		) );
		
		$content = '
			<ul id="mp_popular_products">';
		
		if ( $custom_query->have_posts() ) {
			while ( $custom_query->have_posts() ) : $custom_query->the_post();
				$content .= '
				<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
			endwhile;
			wp_reset_postdata();
		} else {
				$content .= '
				<li>' . __( 'No Products', 'mp' ) . '</li>';
		}
		
		$content .= '
			</ul>';
		
		/**
		 * Filter the popular products html
		 *
		 * @since 3.0
		 * @param string $content The current HTML markup.
		 * @param int $num The number of products to display.
		 */
		$content = apply_filters( 'mp_popular_products', $content, $num );
		
		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
}
endif;


if ( ! function_exists('mp_product') ) {
	/*
	 * Displays a single product according to preference
	 * 
	 * @param bool $echo Optional, whether to echo or return
	 * @param int $product_id the ID of the product to display. Optional if in the loop.
	 * @param bool $title Whether to display the title
	 * @param bool/string $content Whether and what type of content to display. Options are false, 'full', or 'excerpt'. Default 'full'
	 * @param bool/string $image Whether and what context of image size to display. Options are false, 'single', or 'list'. Default 'single'
	 * @param bool $meta Whether to display the product meta
	 */
	function mp_product( $echo = true, $product_id = null, $title = true, $content = 'full', $image = 'single', $meta = true ) {
		if ( function_exists('icl_object_id') ) {
			$product_id = icl_object_id($product_id, MP_Product::get_post_type(), false);	
		}
				
		$product = new MP_Product($product_id);
		$form_id = 'mp_buy_form_' . $product_id;
		
		$variation = false;
		if ( $variation_id = get_query_var('mp_variation_id') ) {
			$variation = new MP_Product($variation_id);
			if ( ! $variation->exists() ) {
				$variation = false;
			}
		}
		
		$return = '
			<div id="mp_single_product" itemscope itemtype="http://schema.org/Product">
				<span style="display:none" class="date updated">' . get_the_time($product->ID) . '</span>'; // mp_product_class(false, 'mp_product', $post->ID)
		
		if ( $title) {
			$return .= '
				<h1 itemprop="name" class="mp_product_name entry-title"><a href="' . $product->url(false) . '">' . $product->title(false) . '</a></h1>';
		}

		if ( $meta ) {
			$return .= '
				<div class="mp_product_meta">';
				
			// Price
			$return .= ( $variation ) ? $variation->display_price(false) : $product->display_price(false);
			
			// Button
			$selected_atts = array();
			if ( $variation ) {
				$atts = $variation->get_attributes();
				foreach ( $atts as $slug => $att ) {
					$selected_atts[$slug] = key($att['terms']);
				}
			}
			$return .= $product->buy_button(false, 'single', $selected_atts);
			
			$return .= '
				</div>';
		}

		$return .= $product->content_tab_labels(false);
		
		if ( ! empty($content) ) {
			$return .= '
				<div id="mp-product-overview" class="mp_product_content clearfix">';
				
			if ( $image ) {
				$return .= ( $variation ) ? $variation->image(false, $image) : $product->image(false, $image);
			}
			
			$return .= '
					<div itemprop="description" class="mp_product_content_text">';
								
			if ( $content == 'excerpt' ) {
				$return .= ( $variation ) ? $variation->excerpt() : $product->excerpt();
			} else {
				$return .= ( $variation ) ? $variation->content(false) : $product->content(false);
			}
						
			$return .= '
					</div>
				</div>';
		}
		// Remove overview tab as it's already been manually output above
		array_shift( $product->content_tabs ); 
		
		foreach ( $product->content_tabs as $slug => $label ) {
			switch ( $slug ) {
				case 'mp-related-products' :
					if ( mp_get_setting( 'related_products->show' ) ) {
						$return .= '<div id="mp-related-products" class="mp_product_content clearfix" style="display:none">' . $product->related_products() . ' </div>';
					}
				break;
				
				default :
					/**
					 * Filter the content tab html
					 *
					 * @since 3.0
					 * @param string
					 * @param string $slug The tab slug.
					 */
					$tab = apply_filters( 'mp_content_tab_html', '', $slug );
					
					$return .= '<div id="' . esc_attr( $slug ) . '" class="mp_product_content clearfix" style="display:none">' . $tab . '</div>';
				break;
			}
		}

		$return .= '
			</div>';
		
		/**
		 * Filter the product html
		 *
		 * @since 3.0
		 * @param string $return The current product html.
		 * @param int $product->ID The product's ID.
		 * @param bool $title Whether to display the title.
		 * @param bool/string $content Whether and what type of content to display. Options are false, 'full', or 'excerpt'. Default 'full'.
		 * @param bool/string $image Whether and what context of image size to display. Options are false, 'single', or 'list'. Default 'single'.
		 * @param bool $meta Whether to display the product meta.
		 */
		$return = apply_filters( 'mp_product', $return, $product->ID, $title, $content, $image, $meta );

		if ( $echo ) {
			echo $return;
		} else {
			return $return;
		}
	}
}

if ( ! function_exists('mp_product_excerpt') ) :
	/**
	 * Replaces wp_trim_excerpt in MP custom loops
	 *
	 * @param string $excerpt
	 * @param string $content
	 * @param int $product_id
	 * @param string $excerpt_more
	 * @return string
	 */
	function mp_product_excerpt( $excerpt, $content, $product_id, $excerpt_more = null ) {
		_deprecated_function('mp_product_excerpt', '3.0', 'MP_Product::excerpt()');
		$product = new MP_Product($product_id);
		return $product->excerpt($excerpt_more, $excerpt, $content);	
	}
endif;

if ( ! function_exists('mp_product_image') ) :
	/*
	 * Get the product image
	 *
	 * @param bool $echo Optional, whether to echo
	 * @param string $context Options are list, single, or widget
	 * @param int $post_id The post_id for the product. Optional if in the loop
	 * @param int $size An optional width/height for the image if contect is widget
	 * @param string $align The alignment of the image. Defaults to settings.
	 */
	function mp_product_image( $echo = true, $context = 'list', $post_id = NULL, $size = NULL, $align = NULL ) {
		_deprecated_function('mp_product_image', '3.0', 'MP_Product::image()');
		
		$product = new MP_Product($post_id);
		$image = MP_Product::image(false, $context, $size, $align);
		
		if ( $echo ) {
			echo $image;
		} else {
			return $image;
		}
	}
endif;

if ( ! function_exists( 'mp_products_nav' ) ) :
	/**
	 * Get the current product list/grid navigation
	 *
	 * @param bool $echo Optional, whether to echo. Defaults to true
	 * @param WP_Query object $custom_query
	 */
	function mp_products_nav( $echo = true, $custom_query ) {
		$html = '';
		
		if ( $custom_query->max_num_pages > 1 ) {
			$big = 999999999;
			
			$html = '
				<div id="mp_product_nav">
					<div id="mp_product_nav_inner" class="clearfix">';
			
			$html .= paginate_links( array(
				'base' => '%_%',
				'format' => '?paged=%#%',
				'total' => $custom_query->max_num_pages,
				'current' => max( 1, $custom_query->get( 'paged' ) ),
				'prev_text' => __( 'Prev', 'mp' ),
				'next_text' => __( 'Next', 'mp' ),
			) );
			
			$html .= '
					</div>
				</div>';
		}
		
		/**
		 * Filter the products nav html
		 *
		 * @since 3.0
		 * @param string $html
		 * @param WP_Query $custom_query
		 */
		$html = apply_filters( 'mp_products_nav', $html, $custom_query );
	
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
endif;

if ( ! function_exists('mp_products_filter') ) :
	/**
	 * Display product filters
	 *
	 * @since 3.0
	 * @param bool $hidden Are the filters hidden or visible?
	 * @param int $per_page The number of posts per page
	 * @param WP_Query $query
	 * @return string
	 */
	function mp_products_filter( $hidden = false, $per_page = null, $query = null ) {
		$default = '-1';
		if ( $query instanceof WP_Query && $query->get('taxonomy') == 'product_category' ) {
			$term = get_term_by( 'slug', $query->get( 'term' ), $query->get( 'taxonomy' ) );
			$default = $term->term_id;
		} elseif ( 'product_category' == get_query_var('taxonomy') ) {
			$term = get_queried_object(); //must do this for number tags
			$default = $term->term_id;
		}
	
		$terms = wp_dropdown_categories(array(
			'name' => 'product_category',
			'class' => 'mp_select2',
			'id' => 'mp-product-category',
			'taxonomy' => 'product_category',
			'show_option_none' => __('Show All', 'mp'),
			'show_count' => 1,
			'orderby' => 'name',
			'selected' => $default,
			'echo' => 0,
			'hierarchical' => true
		));
	
		$current_order = strtolower($query->get('order_by') . '-' . $query->get('order'));
		$options = array(
				array('0', '', __('Default', 'mp')),
				array('date', 'desc', __('Release Date', 'mp')),
				array('title', 'asc', __('Name', 'mp')),
				array('price', 'asc', __('Price (Low to High)', 'mp')),
				array('price', 'desc', __('Price (High to Low)', 'mp')),
				array('sales', 'desc', __('Popularity', 'mp'))
		);
		$options_html = '';
		foreach ($options as $k => $t) {
			$value = $t[0] . '-' . $t[1];
			$options_html .= '<option value="' . $value . '" ' . selected($value, $current_order, false) . '>' . $t[2] . '</option>';
		}
	
		$return = '
			<a name="mp-product-list-top"></a>
			<div class="mp_list_filter"' . (( $hidden ) ? ' style="display:none"' : '') . '>
				<form id="mp_product_list_refine" name="mp_product_list_refine" class="mp_product_list_refine clearfix" method="get">
						<div class="one_filter" data-placeholder="' . __('Product Category', 'mp') . '">
							<label for="mp-product-category">' . __('Category', 'mp') . '</label><br />
							' . $terms . '
						</div>
	
						<div class="one_filter">
							<label for="mp-sort-order">' . __('Order By', 'mp') . '</label><br />
							<select id="mp-sort-order" class="mp_select2" name="order" data-placeholder="' . __('Product Category', 'mp') . '">
								' . $options_html . '
							</select>
						</div>' .
						
						(( is_null($per_page) ) ? '' : '<input type="hidden" name="per_page" value="' . $per_page . '" />') . '
						<input type="hidden" name="page" value="' . max( get_query_var( 'paged' ), 1 ) . '" />
				</form>
			</div>';
	
		return apply_filters('mp_products_filter', $return);
	}
endif;

if ( ! function_exists('mp_province_field') ) :
/*
 * Display state/province dropdown field
 *
 * @param string $country two-digit country code
 * @param string $selected state code form value to be shown/selected
 */
function mp_province_field( $country = 'US', $selected = null ) {
	_deprecated_function('mp_province_field', '3.0', 'MP_Checkout::province_field');
}
endif;

if ( ! function_exists('mp_related_products') ) :
	/**
	 * Get related products
	 *
	 * @since 3.0
	 * @param int $product_id.
	 * @param string $relate_by Optional, how to relate the products - either by category, tag, or both.
	 * @param bool $echo Echo or return.
	 * @param int $limit. Optional The number of products we want to retrieve.
	 * @param bool $simple_list Optional, whether to show the related products based on the "list_view" setting or as a simple unordered list.
	 */
	function mp_related_products() {
		_deprecated_function('mp_related_products', '3.0', 'MP_Product::related_products()');
		
		$defaults = array(
			'product_id' => null,
			'echo' => false,
			'relate_by' => mp_get_setting('related_products->relate_by'),
			'limit' => mp_get_setting('related_products->show_limit'),
			'simple_list' => mp_get_setting('related_products->simple_list'),
		);
		$args = array_replace_recursive($defaults, array_combine(array_keys($defaults), func_get_args()));
		$html = '';
		
		if ( ! is_null($args['product_id']) ) {
			$product = new MP_Product($args['product_id']);
		
			if ( $product->exists() ) {
				$args['echo'] = false;
				$html .= $product->related_products($args);
			}
		}
		
		if ( $args['echo'] ) {
			echo $html;
		} else {
			return $html;
		}
	}
endif;

if ( ! function_exists( 'mp_get_store_email' ) ) :
	/**
	 * Get the store admin email address
	 *
	 * @since 3.0
	 * @return string
	 */
	function mp_get_store_email() {
		return ( $email = mp_get_setting( 'store_email' ) ) ? $email : get_option( 'admin_email' );
	}
endif;

if ( ! function_exists( 'mp_send_email' ) ) :
	/**
	 * Send an email
	 *
	 * @since 3.0
	 * @param string $email The email address to send to.
	 * @param string $subject The subject of the email.
	 * @param string $msg The email message.
	 * @return bool
	 */
	function mp_send_email( $email, $subject, $msg ) {
		//remove any other filters
		remove_all_filters( 'wp_mail_from' );
		remove_all_filters( 'wp_mail_from_name' );

		//convert all tabs to their approriate html markup
		$msg = str_replace( array( "\t" ), array( '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' ), $msg );
		
		// set headers
		$headers = array(
			'From' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . '<' . mp_get_store_email() . '>',
			'Content-Type' => 'text/html; charset=UTF-8',
		);
		
		$header = '';
		foreach ( $headers as $key => $val ) {
			$header .= "{$key}: {$val}\r\n";
		}
						
		return wp_mail( $email, $subject, $msg, $header );		
	}
endif;