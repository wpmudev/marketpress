<?php

if ( ! function_exists('mp_cart_link') ) :
	/**
	 * Display the current shopping cart link. If global cart is on reflects global location
	 * @since 3.0
	 * @param bool $echo Optional, whether to echo. Defaults to true
	 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
	 * @param string $link_text Optional, text to show in link.
	 */
	function mp_cart_link( $echo = true, $url = false, $link_text = '' ) {
		if ( mp()->global_cart && mp_is_main_site() ) {
			switch_to_blog(MP_ROOT_BLOG);
			$link = home_url(mp_get_setting('slugs->store') . '/' . mp_get_setting('slugs->cart') . '/');
			restore_current_blog();
		} else {
			$link = home_url(mp_get_setting('slugs->store') . '/' . mp_get_setting('slugs->cart') . '/');
		}

		if ( ! $url ) {
			$text = ($link_text) ? $link_text : __('Shopping Cart', 'mp');
			$link = '<a href="' . $link . '" class="mp_cart_link">' . $text . '</a>';
		}

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
		return apply_filters('mp_checkout_step_url', mp_cart_link(false, true) . trailingslashit($checkout_step), $checkout_step);
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

		//format currency amount according to preference
		if ( $amount ) {
			if ( mp_get_setting('curr_symbol_position') == 1 || ! mp_get_setting('curr_symbol_position') )
				return $symbol . number_format_i18n($amount, $decimal_place);
			
			if ( mp_get_setting('curr_symbol_position') == 2 )
				return $symbol . ' ' . number_format_i18n($amount, $decimal_place);
			
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
		$args = array_replace_recursive(mp()->defaults['list_products'], func_get_args());
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
				'terms' => $wp_query->get('term'),
			);
		} elseif ( get_query_var('taxonomy') == 'product_tag' ) {
			$tax_query[] = array(
				'taxonomy' => 'product_tag',
				'field' => 'slug',
				'terms' => $wp_query->get('term'),
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
					$query['meta_key'] = 'mp_price_sort';
					$query['orderby'] = 'meta_value_num';
				} else if ( 'sales' == $args['order_by'] ) {
					$query['meta_key'] = 'mp_sales_count';
					$query['orderby'] = 'meta_value_num';
				} else {
					$query['orderby'] = $args['order_by'];
				}
			} elseif ( 'price' == mp_get_setting('order_by') ) {
				$query['meta_key'] = 'mp_price_sort';
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
		
		$content .= '<div id="mp_product_list" class="hfeed mp_' . $layout_type . '">';
	
		if ( $last = $custom_query->post_count ) {
			//$content .= $layout_type == 'grid' ? _mp_products_html_grid($custom_query) : _mp_products_html_list($custom_query);
		} else {
			$content .= '<div id="mp_no_products">' . apply_filters('mp_product_list_none', __('No Products', 'mp')) . '</div>';
		}
	
		$content .= '</div>';
		//$content .= ( ! $args['nopaging'] ) ? mp_products_nav(false, $custom_query) : '';
		
		$content = apply_filters('mp_list_products', $content, $args);
	
		if ( $args['echo'] ) {
			echo $content;
		} else {
			return $content;
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
			$default = $query->get('taxonomy');
		} elseif ( 'product_category' == get_query_var('taxonomy') ) {
			$term = get_queried_object(); //must do this for number tags
			$default = $term->term_id;
		}
	
		$terms = wp_dropdown_categories(array(
			'name' => 'product_category',
			'id' => 'product-category',
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
				<form name="mp_product_list_refine" class="mp_product_list_refine" method="get">
						<div class="one_filter">
							<span>' . __('Category', 'mp') . '</span>
							' . $terms . '
						</div>
	
						<div class="one_filter">
							<span>' . __('Order By', 'mp') . '</span>
							<select name="order">
								' . $options_html . '
							</select>
						</div>' .
						
						(( is_null($per_page) ) ? '' : '<input type="hidden" name="per_page" value="' . $per_page . '" />') . '
				</form>
			</div>';
	
		return apply_filters('mp_products_filter', $return);
	}
endif;