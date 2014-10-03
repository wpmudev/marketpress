<?php

if ( ! function_exists('_mp_products_html_grid')) :
	/**
	 * Display product list in grid layout
	 *
	 * @since 3.0
	 * @param WP_Query $custom_query
	 * @return string
	 */
	function _mp_products_html_grid( $custom_query ) {
		$html = '';
		
		//get image width
		if ( mp_get_setting('list_img_size') == 'custom' ) {
			$width = mp_get_setting('list_img_width');
		} else {
			$size = mp_get_setting('list_img_size');
			$width = get_option($size . "_size_w");
		}

		$inline_style = ! ( mp_get_setting('store_theme') == 'none' || current_theme_supports('mp_style') );

		while ( $custom_query->have_posts() ) : $custom_query->the_post();
			$product = new MP_Product();
			$img = $product->image(false, 'list');
			$excerpt = mp_get_setting('show_excerpt') ? '<p class="mp_excerpt">' . $product->excerpt($product->post_excerpt, $product->post_content, '') . '</p>' : '';
			$mp_product_list_content = apply_filters('mp_product_list_content', $excerpt, $product->ID);
	
			$pinit = $product->pinit_button('all_view');

			$class = array();
			$class[] = strlen($img) > 0 ? 'mp_thumbnail' : '';
			$class[] = strlen($excerpt) > 0 ? 'mp_excerpt' : '';
			$class[] = ( $product->has_variations() ) ? 'mp_price_variations' : '';

			$html .= '
				<div itemscope itemtype="http://schema.org/Product" class="hentry mp_one_tile ' . implode($class, ' ') . '">
					<div class="mp_one_product"' . ($inline_style ? ' style="width: ' . $width . 'px;"' : '') . '>
						<div class="mp_product_detail"' . ($inline_style ? ' style="width: ' . $width . 'px;"' : '') . '>
							' . $img . '
							' . $pinit .'
							<h3 class="mp_product_name entry-title" itemprop="name">
								<a href="' . get_permalink($product->ID) . '">' . $product->post_title . '</a>
							</h3>
						
							<div>' . $mp_product_list_content . '</div>
						</div>

						<div class="mp_price_buy"' . ($inline_style ? ' style="width: ' . $width . 'px;"' : '') . '>
							' . $product->get_price() . '
							' . $product->buy_button(false, 'list') . '
							' . apply_filters('mp_product_list_meta', '', $product->ID) . '
						</div>
						
						<div style="display:none" >
							<span class="entry-title">' . get_the_title() . '</span> was last modified:
							<time class="updated">' . get_the_time('Y-m-d\TG:i') . '</time> by
							<span class="author vcard"><span class="fn">' . get_the_author_meta('display_name') . '</span></span>
						</div>
					</div>
				</div>';
		endwhile;

		$html .= ($custom_query->found_posts > 0) ? '<div class="clear"></div>' : '';
		
		wp_reset_postdata();
		
		return apply_filters('_mp_products_html_grid', $html, $custom_query);
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
	 * @param bool $echo Optional, whether to echo. Defaults to true
	 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
	 * @param string $link_text Optional, text to show in link.
	 */
	function mp_cart_link( $echo = true, $url = false, $link_text = '' ) {
		if ( mp()->global_cart && ! mp_is_main_site() ) {
			switch_to_blog(MP_ROOT_BLOG);
			$link = get_permalink(mp_get_setting('pages->checkout'));
			restore_current_blog();
		} else {
			$link = get_permalink(mp_get_setting('pages->checkout'));
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
			$content .= $layout_type == 'grid' ? _mp_products_html_grid($custom_query) : _mp_products_html_list($custom_query);
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
		return $product->excerpt($excerpt, $content, $excerpt_more);	
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