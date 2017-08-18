<?php

if ( ! function_exists( 'mp_get_product_class' ) ) :

	/**
	 * Retrieve the list of classes for the product as an array.
	 *
	 * The class names are add are many. If the post is a sticky, then the 'sticky'
	 * class name. The class 'hentry' is always added to each post. For each
	 * category, the class will be added with 'category-' with category slug is
	 * added. The tags are the same way as the categories with 'tag-' before the tag
	 * slug. All classes are passed through the filter, 'post_class' with the list
	 * of classes, followed by $class parameter value, with the post ID as the last
	 * parameter.
	 *
	 *
	 * @param string|array $class One or more classes to add to the class list.
	 * @param int $post_id The post_id for the product. Optional if in the loop
	 *
	 * @return array Array of classes.
	 */
	function mp_get_product_class( $class = '', $post_id = null ) {
		global $id;
		$post_id = ( null === $post_id ) ? $id : $post_id;

		$post = get_post( $post_id );

		$classes = array();

		if ( empty( $post ) ) {
			return $classes;
		}

		$classes[] = 'product-' . $post->ID;
		$classes[] = $post->post_type;
		$classes[] = 'type-' . $post->post_type;

		// sticky for Sticky Posts
		if ( is_sticky( $post->ID ) ) {
			$classes[] = 'sticky';
		}

		// hentry for hAtom compliace
		$classes[] = 'hentry';

		// Categories
		$categories = get_the_terms( $post->ID, "product_category" );
		foreach ( (array) $categories as $cat ) {
			if ( empty( $cat->slug ) || ! isset( $cat->cat_ID ) ) {
				continue;
			}
			$classes[] = 'category-' . sanitize_html_class( $cat->slug, $cat->cat_ID );
		}

		// Tags
		$tags = get_the_terms( $post->ID, "product_tag" );
		foreach ( (array) $tags as $tag ) {
			if ( empty( $tag->slug ) ) {
				continue;
			}
			$classes[] = 'tag-' . sanitize_html_class( $tag->slug, $tag->term_id );
		}

		if ( ! empty( $class ) ) {
			if ( ! is_array( $class ) ) {
				$class = preg_split( '#\s+#', $class );
			}
			$classes = array_merge( $classes, $class );
		}

		$classes = array_map( 'esc_attr', $classes );

		return apply_filters( 'mp_get_product_class', $classes, $class, $post_id );
	}

endif;

if ( ! function_exists( 'mp_product_class' ) ) :

	/**
	 * Display the classes for the product div.
	 *
	 * @param bool $echo Whether to echo class.
	 * @param string|array $class One or more classes to add to the class list.
	 * @param int $post_id The post_id for the product. Optional if in the loop
	 */
	function mp_product_class( $echo = true, $class = '', $post_id = null ) {
		// Separates classes with a single space, collates classes for post DIV
		$content = 'class="' . join( ' ', mp_get_product_class( $class, $post_id ) ) . '"';

		$content = apply_filters( 'mp_product_class', $content, $class, $post_id );

		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
	}

endif;

if ( ! function_exists( 'mp_product_sku' ) ) :
	/*
	 * function mp_product_sku
	 *
	 * @param bool $echo default true
	 * @param int $post_id The post_id of the product. Optional if in the loop
	 * @param string $seperator The seperator to put between skus, default ', '
	 *
	 * Returns or echos html of variation SKUs
	 */

	function mp_product_sku( $echo = true, $post_id = null ) {
		global $id, $mp;
		$post_id = ( null === $post_id ) ? $id : $post_id;

		$sku  = get_post_meta( $post_id, "sku", true );
		$html = '<span class="mp_product_skus">' . $sku . '</span>';

		$html = apply_filters( 'mp_product_skus', $html, $post_id, $sku );

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

endif;


if ( ! function_exists( 'mp_product_price' ) ) :
	/*
	 * Displays the product price (and sale price)
	 *
	 * @param bool $echo Optional, whether to echo
	 * @param int $post_id The post_id for the product. Optional if in the loop
	 * @param sting $label A label to prepend to the price. Defaults to "Price: "
	 */

	function mp_product_price( $echo = true, $post_id = null, $label = true ) {
		global $id, $mp;

		$price_html = '';

		$product = new MP_Product( $post_id );

		$label = ( $label === true ) ? __( 'Price: ', 'mp' ) : $label; //should be empty from 3.0

		$price_html .= $product->display_price( false );

		$price_html = apply_filters( 'mp_product_price_html', $price_html, $post_id, $label, $product->display_price( false ) );

		if ( $echo ) {
			echo $price_html;
		} else {
			return $price_html;
		}
	}

endif;

if ( ! function_exists( 'mp_store_navigation' ) ) :

	/**
	 * Echos the current store navigation links.
	 *
	 * @param bool $echo Optional, whether to echo. Defaults to true
	 */
	function mp_store_navigation( $echo = true ) {
		//navigation
		if ( ! mp_get_setting( 'disable_cart' ) ) {
			$nav = '<ul class="mp_store_navigation"><li class="page_item"><a href="' . mp_products_link( false, true ) . '" title="' . __( 'Products', 'mp' ) . '">' . __( 'Products', 'mp' ) . '</a></li>';
			$nav .= '<li class="page_item"><a href="' . mp_cart_link( false, true ) . '" title="' . __( 'Shopping Cart', 'mp' ) . '">' . __( 'Shopping Cart', 'mp' ) . '</a></li>';
			$nav .= '<li class="page_item"><a href="' . mp_orderstatus_link( false, true ) . '" title="' . __( 'Order Status', 'mp' ) . '">' . __( 'Order Status', 'mp' ) . '</a></li>
</ul>';
		} else {
			$nav = '<ul class="mp_store_navigation">
<li class="page_item"><a href="' . mp_products_link( false, true ) . '" title="' . __( 'Products', 'mp' ) . '">' . __( 'Products', 'mp' ) . '</a></li>
</ul>';
		}

		$nav = apply_filters( 'mp_store_navigation', $nav );

		if ( $echo ) {
			echo $nav;
		} else {
			return $nav;
		}
	}

endif;

if ( ! function_exists( 'mp_orderstatus_link' ) ) :

	/**
	 * Echos the current order status link.
	 *
	 * @param bool $echo Optional, whether to echo. Defaults to true
	 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
	 * @param string $link_text Optional, text to show in link.
	 * @param string $order Optional, the order id to append to the link
	 */
	function mp_orderstatus_link( $echo = true, $url = false, $link_text = '', $order_id = '' ) {
		$link = get_permalink( mp_get_setting( 'pages->order_status' ) );
		if ( ! $url ) {
			$text = ( $link_text ) ? $link_text : __( 'Check Order Status', 'mp' );
			$link = '<a href="' . $link . '" class="mp_orderstatus_link">' . $text . '</a>';
		}

		$link = apply_filters( 'mp_orderstatus_link', $link, $echo, $url, $link_text );

		if ( $echo ) {
			echo $link;
		} else {
			return $link;
		}
	}

endif;

if ( ! function_exists( 'mp_products_link' ) ) :

	/**
	 * Echos the current product list link.
	 *
	 * @param bool $echo Optional, whether to echo. Defaults to true
	 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
	 * @param string $link_text Optional, text to show in link.
	 */
	function mp_products_link( $echo = true, $url = false, $link_text = '' ) {
		$link = get_permalink( mp_get_setting( 'pages->products' ) );

		if ( ! $url ) {
			$text = ( $link_text ) ? $link_text : __( 'View Products', 'mp' );
			$link = '<a href="' . $link . '" class="mp_products_link">' . $text . '</a>';
		}

		$link = apply_filters( 'mp_products_link', $link, $echo, $url, $link_text );

		if ( $echo ) {
			echo $link;
		} else {
			return $link;
		}
	}

endif;

if ( ! function_exists( 'mp_store_link' ) ) :

	/**
	 * Echos the current store link.
	 *
	 * @param bool $echo Optional, whether to echo. Defaults to true
	 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
	 * @param string $link_text Optional, text to show in link.
	 */
	function mp_store_link( $echo = true, $url = false, $link_text = '' ) {
		$link = get_permalink( mp_get_setting( 'pages->store' ) );

		if ( ! $url ) {
			$text = ( $link_text ) ? $link_text : __( 'Visit Store', 'mp' );
			$link = '<a href="' . $link . '" class="mp_store_link">' . $text . '</a>';
		}

		$link = apply_filters( 'mp_store_link', $link, $echo, $url, $link_text );

		if ( $echo ) {
			echo $link;
		} else {
			return $link;
		}
	}

endif;

/**
 * Display or retrieve the HTML dropdown list of product categories.
 *
 * The list of arguments is below:
 *         'show_option_all' (string) - Text to display for showing all categories.
 *         'show_option_none' (string) - Text to display for showing no categories.
 *         'orderby' (string) default is 'ID' - What column to use for ordering the
 * categories.
 *         'order' (string) default is 'ASC' - What direction to order categories.
 *         'show_last_update' (bool|int) default is 0 - See {@link get_categories()}
 *         'show_count' (bool|int) default is 0 - Whether to show how many posts are
 * in the category.
 *         'hide_empty' (bool|int) default is 1 - Whether to hide categories that
 * don't have any posts attached to them.
 *         'child_of' (int) default is 0 - See {@link get_categories()}.
 *         'exclude' (string) - See {@link get_categories()}.
 *         'depth' (int) - The max depth.
 *         'tab_index' (int) - Tab index for select element.
 *         'name' (string) - The name attribute value for select element.
 *         'id' (string) - The ID attribute value for select element. Defaults to name if omitted.
 *         'class' (string) - The class attribute value for select element.
 *         'selected' (int) - Which category ID is selected.
 *         'taxonomy' (string) - The name of the taxonomy to retrieve. Defaults to category.
 *
 * The 'hierarchical' argument, which is disabled by default, will override the
 * depth argument, unless it is true. When the argument is false, it will
 * display all of the categories. When it is enabled it will use the value in
 * the 'depth' argument.
 *
 *
 * @param bool $echo Optional. Whether or not to echo.
 * @param string|array $args Optional. Override default arguments.
 */
if ( ! function_exists( 'mp_dropdown_categories' ) ) {

	function mp_dropdown_categories( $echo = true, $args = '' ) {
		$args['taxonomy']    = 'product_category';
		$args['echo']        = false;
		$args['id']          = 'mp_category_dropdown';
		$args['value_field'] = 'slug';

		$dropdown = wp_dropdown_categories( $args );
		$dropdown .= '<script type="text/javascript">
	var dropdown = document.getElementById("mp_category_dropdown");
	function onCatChange() {
			location.href = "' . get_home_url() . '/?product_category="+dropdown.options[dropdown.selectedIndex].value;
	}
	dropdown.onchange = onCatChange;
</script>';
		//get_category_link

		$dropdown = apply_filters( 'mp_dropdown_categories', $dropdown, $args );

		if ( $echo ) {
			echo $dropdown;
		} else {
			return $dropdown;
		}
	}

}

if ( ! function_exists( 'mp_main_site_id' ) ) {

	function mp_main_site_id() {
		global $current_site;
		if ( MP_ROOT_BLOG !== false ) {
			return MP_ROOT_BLOG;
		} else {
			return $current_site->blog_id;
		}
	}

}

if ( ! function_exists( 'mp_number_format' ) ) {

	function mp_number_format( $amount, $decimal_place, $force_basic = false ) {

		if ( (int) ( $amount ) == (float) $amount ) {
			$int_decimals = 0;
		} else {
			$int_decimals = 2;
		}

		$curr_decimal = mp_get_setting( 'curr_decimal', 1 );
		if( is_multisite() && mp_cart()->is_global ){
			$curr_decimal = mp_get_network_setting( 'global_curr_decimal' ) == 'off' ? 0 : 1;
		}
		$price_format = ( is_multisite() && mp_cart()->is_global ) ? mp_get_network_setting( 'global_price_format' ) : mp_get_setting( 'price_format' );

		if ( $curr_decimal == 1 ) {
			$int_decimals = 2;
		} else {
			$int_decimals = 0;
		}

		$decimals = apply_filters( 'mp_number_format_decimals', $int_decimals );

		if ( $force_basic ) {
			$formatted = number_format( $amount, $int_decimals, $dec_point = ".", $thousands_sep = "" );
		} else {
			switch ( $price_format ) {
				case 'us' :
					$formatted = number_format( $amount, $decimals, $dec_point = ".", $thousands_sep = "," );
					break;

				case 'eu' :
					$formatted = number_format( $amount, $decimals, $dec_point = ",", $thousands_sep = "." );
					break;

				case 'frc' :
					$formatted = number_format( $amount, $decimals, $dec_point = ",", $thousands_sep = "&nbsp;" );
					break;

				case 'frd' :
					$formatted = number_format( $amount, $decimals, $dec_point = ".", $thousands_sep = "&nbsp;" );
					break;

				default ://us
					$formatted = number_format( $amount, $decimals, $dec_point = ".", $thousands_sep = "," );
			}
		}

		return $formatted;
	}

}

if ( ! function_exists( 'mp_cart_widget' ) ) :

	/**
	 * Display a cart widget.
	 *
	 * The 'title' argument will accept a string and defaults is empty.
	 * The 'custom_text' argument will accept a string and defaults is empty.
	 *
	 * @param bool $echo Optional. Whether or not to echo.
	 * @param array|string $args Optional. Override default arguments.
	 */
	function mp_cart_widget( $echo = true, $args = array() ) {

		$mini_cart    = '';
		$title        = '';
		$custom_text  = '';
		$cart_content = '';

		$mini_cart .= apply_filters( 'mp_cart_widget_before', '<div class="mp_cart_widget">' );

		if ( ! empty( $args['title'] ) ) {
			$title .= '<div class="mp_cart_widget_title">';
			$title .= $args['title'];
			$title .= '</div><!-- end .mp_cart_widget_title -->';
		};

		$mini_cart .= apply_filters( 'mp_cart_widget_title', $title );

		if ( ! empty( $args['custom_text'] ) ) {
			$custom_text .= '<div class="mp_cart_widget_custom_text">';
			$custom_text .= $args['custom_text'];
			$custom_text .= '</div><!-- end .mp_cart_widget_custom_text -->';
		};

		$mini_cart .= apply_filters( 'mp_cart_widget_custom_text', $custom_text );

		$cart_content .= '<div class="mp_cart_widget_content">';
		$cart_content .= MP_Cart::get_instance()->cart_products_html( 'widget', $args['show_product_image'], $args['show_product_qty'], $args['show_product_price'] );
		$cart_content .= '</div><!-- end .mp_cart_widget_content -->';

		$mini_cart .= apply_filters( 'mp_cart_widget_content', $cart_content );

		$mini_cart .= apply_filters( 'mp_cart_widget_after', '</div><!-- end .mp_cart_widget -->' );

		if ( $echo ) {
			echo $mini_cart;
		} else {
			return $mini_cart;
		}
	}

endif;

if ( ! function_exists( 'mp_tag_cloud' ) ) :

	/**
	 * Display product tag cloud.
	 *
	 * The text size is set by the 'smallest' and 'largest' arguments, which will
	 * use the 'unit' argument value for the CSS text size unit. The 'format'
	 * argument can be 'flat' (default), 'list', or 'array'. The flat value for the
	 * 'format' argument will separate tags with spaces. The list value for the
	 * 'format' argument will format the tags in a UL HTML list. The array value for
	 * the 'format' argument will return in PHP array type format.
	 *
	 * The 'orderby' argument will accept 'name' or 'count' and defaults to 'name'.
	 * The 'order' is the direction to sort, defaults to 'ASC' and can be 'DESC'.
	 *
	 * The 'number' argument is how many tags to return. By default, the limit will
	 * be to return the top 45 tags in the tag cloud list.
	 *
	 * The 'topic_count_text_callback' argument is a function, which, given the count
	 * of the posts     with that tag, returns a text for the tooltip of the tag link.
	 *
	 * The 'exclude' and 'include' arguments are used for the {@link get_tags()}
	 * function. Only one should be used, because only one will be used and the
	 * other ignored, if they are both set.
	 *
	 * @param bool $echo Optional. Whether or not to echo.
	 * @param array|string $args Optional. Override default arguments.
	 */
	function mp_tag_cloud( $echo = true, $args = array() ) {

		$args['echo']     = false;
		$args['taxonomy'] = 'product_tag';

		$cloud = '<div id="mp-tag-cloud">' . wp_tag_cloud( $args ) . '</div><!-- end mp-tag-cloud -->';

		$cloud = apply_filters( 'mp_tag_cloud', $cloud, $args );

		if ( $echo ) {
			echo $cloud;
		} else {
			return $cloud;
		}
	}

endif;

if ( ! function_exists( '_mp_order_status_overview' ) ) :

	/**
	 * Display the order status overview html.
	 *
	 * @since 3.0
	 * @return string
	 */
	function _mp_order_status_overview() {
		$history        = array_filter( mp_get_order_history() );
		$page           = get_query_var( 'mp_status_pagenumber', 1 );
		$per_page_value = mp_get_setting( 'per_page_order_history' );
		$per_page       = isset( $per_page_value ) ? $per_page_value : get_option( 'posts_per_page' );
		$offset         = ( $page - 1 ) * $per_page;
		$total_pages    = ceil( count( $history ) / $per_page );
		$html           = '
			<!-- Order History -->
			<section id="mp-order-history" class="mp_orders mp_orders-list">';

		if ( count( $history ) > 0 ) {
			$history = array_slice( $history, $offset, $per_page );
			$html .= '
				<h2 class="mp_title">' . __( 'Order History', 'mp' ) . '</h2>' .
			         '<div class="mp_order_details">';
			foreach ( $history as $timestamp => $order ) {
				$order = new MP_Order( $order['id'] );
				$html .= '<div class="mp_order">';
				$html .= $order->header( false );
				$html .= '</div><!-- end mo_order -->';
			}

			$html .= '</div><!-- end mp_order_details -->';

			if ( $total_pages > 1 ) {
				$big = 99999999;
				$html .= '<nav class="mp_listings_nav">';
				$html .= paginate_links( array(
					'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'current' => $page,
					'total'   => $total_pages,
				) );
				$html .= '</nav>';
			}
		}

		$html .= '
			</section><!-- end mp-order-history -->';

		return $html;
	}

endif;

if ( ! function_exists( '_mp_products_html' ) ) :

	/**
	 * Display products according to preference
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $view Either "grid" or "list".
	 * @param WP_Query $custom_query A WP_Query object.
	 *
	 * @return string
	 */
	function _mp_products_html( $view, $custom_query, $related_products = false ) {

		$html = '';
		if ( $related_products ) {
			$per_row = mp_get_setting( 'related_products->per_row', 3 );
		} else {
			$per_row = (int) mp_get_setting( 'per_row', 3 );
		}

		$width  = round( 100 / $per_row, 1 ) . '%';
		$column = 1;

//get image width
		if ( mp_get_setting( 'list_img_size' ) == 'custom' ) {
			$img_width = mp_get_setting( 'list_img_width' ) . 'px';
		} else {
			$size      = mp_get_setting( 'list_img_size' );
			$img_width = get_option( $size . '_size_w' ) . 'px';
		}

		while ( $custom_query->have_posts() ) : $custom_query->the_post();
			$product = new MP_Product();

			$align = null;
			if ( 'list' == mp_get_setting( 'list_view' ) ) {
				$align = mp_get_setting( 'image_alignment_list' );
			}

			$img = $product->image( false, 'list', null, $align, true );

			$excerpt                 = mp_get_setting( 'show_excerpts' ) ? '<div class="mp_product_excerpt"><p>' . $product->excerpt() . '</div></p><!-- end mp_product_excerpt -->' : '';
			$mp_product_list_content = apply_filters( 'mp_product_list_content', $excerpt, $product->ID );

			$pinit   = $product->pinit_button( 'all_view' );
			$fb      = $product->facebook_like_button( 'all_view' );
			$twitter = $product->twitter_button( 'all_view' );

			$class   = array();
			$class[] = ( strlen( $img ) > 0 ) ? 'mp_thumbnail' : '';
			$class[] = ( strlen( $excerpt ) > 0 ) ? 'mp_excerpt' : '';
			$class[] = ( $product->has_variations() ) ? 'mp_price_variations' : '';
			$class[] = ( $product->on_sale() ) ? 'mp_on_sale' : '';

			if ( 'grid' == $view ) {
				if ( $column == 1 ) {
					$class[] = 'first';
					$html .= '<div class="mp_products_items">';
					$column ++;
				} elseif ( $column == $per_row ) {
					$class[] = 'last';
					$column  = 1;
				} else {
					$column ++;
				}
			}

			$class = array_filter( $class, create_function( '$s', 'return ( ! empty( $s ) );' ) );

			$image_alignment = mp_get_setting( 'image_alignment_list' );

			$align_class = ( $view == 'list' ) ? ' mp_product-image-' . ( ! empty( $image_alignment ) ? $image_alignment : 'alignleft' ) : '';

			$html .= '
				<div class="mp_product_item' . ( ( 'grid' == $view ) ? ' mp_product_item-col-' . $per_row : '' ) . '">
					<div itemscope itemtype="http://schema.org/Product" class="mp_product' . ( ( strlen( $img ) > 0 ) ? ' mp_product-has-image' . $align_class : '' ) . ' ' . implode( $class, ' ' ) . '">
					
						<div class="mp_product_images">
							' . $img . '
						</div><!-- end mp_product_images -->
						
						<div class="mp_product_details">
 
							<div class="mp_product_meta">
								<h3 class="mp_product_name entry-title" itemprop="name">
	 								<a href="' . $product->url( false ) . '">' . $product->title( false ) . '</a>
	 							</h3>
								' . $product->display_price( false ) . '
 								' . $mp_product_list_content . '
 								
 								<div class="mp_social_shares">
									' . $pinit . '
									' . $fb . '
									' . $twitter . '
								</div><!-- end mp_social_shares -->
 								
							</div><!-- end mp_product_meta -->

							<div class="mp_product_callout">
								' . $product->buy_button( false, 'list', array(), true ) . '
								' . apply_filters( 'mp_product_list_meta', '', $product->ID ) . '
							</div><!-- end mp_product_callout -->
							
 						</div><!-- end mp_product_details -->
	
						<div style="display:none">
							<span class="entry-title">' . $product->title( false ) . '</span> was last modified:
							<time class="updated">' . get_the_time( 'Y-m-d\TG:i' ) . '</time> by
							<span class="author vcard"><span class="fn">' . get_the_author_meta( 'display_name' ) . '</span></span>
						</div>
						
					</div><!-- end mp_product -->
				</div><!-- end mp_product_item -->';

			if ( $column == 1 && $view == 'grid' ) {
				$html .= '</div><!-- end mp_products_items -->';
			}
		endwhile;

		if ( $column != 1 && $view == 'grid' ) {
			$html .= '</div><!-- end mp_products_items -->';
		}

		/* if ( $view == 'grid' ) {
		  $html .= ( $custom_query->found_posts > 0 ) ? '<div class="clear"></div>' : '';
		  } */

		wp_reset_postdata();

		/**
		 * Filter the product list html content
		 *
		 * @since 3.0
		 *
		 * @param string $html .
		 * @param WP_Query $custom_query .
		 */

		return apply_filters( "_mp_products_html_{$view}", $html, $custom_query );
	}

endif;

if ( ! function_exists( '_mp_products_html_list' ) ) :

	/**
	 * Display product list in list layout
	 *
	 * @since 3.0
	 *
	 * @param WP_Query $custom_query
	 *
	 * @return string
	 */
	function _mp_products_html_list( $custom_query ) {
		return _mp_products_html( 'list', $custom_query );
	}

endif;

if ( ! function_exists( '_mp_products_html_grid' ) ) :

	/**
	 * Display product list in grid layout
	 *
	 * @since 3.0
	 *
	 * @param WP_Query $custom_query
	 *
	 * @return string
	 */
	function _mp_products_html_grid( $custom_query, $relate_products = false ) {
		return _mp_products_html( 'grid', $custom_query, $relate_products );
	}

endif;

if ( ! function_exists( 'mp_before_tax_price' ) ) :

	/**
	 * Get the price before taxes
	 *
	 * @since 3.0
	 *
	 * @param float $tax_price The price including tax.
	 * @param float $rate Optional. The tax rate applied to the price.
	 */
	function mp_before_tax_price( $tax_price, $rate = null ) {
		if ( ! mp_get_setting( 'tax->tax_inclusive' ) ) {
// tax inclusve pricing is turned off - just return tax price
			return $tax_price;
		}

		if ( is_null( $rate ) ) {
			$rate = mp_tax_rate();
		}

		return $tax_price / ( floatval( $rate ) + 1 );
	}

endif;

if ( ! function_exists( 'mp_buy_button' ) ) :

	/**
	 * Display the buy or add to cart button
	 *
	 * @param bool $echo Optional, whether to echo
	 * @param string $context Options are list or single
	 * @param int $post_id The post_id for the product. Optional if in the loop.
	 */
	function mp_buy_button( $echo = true, $context = 'list', $product_id = null ) {
		//_deprecated_function( 'mp_buy_button', '3.0', 'MP_Product::buy_button' );

		$product = new MP_Product( $product_id );
		if ( ! $product->exists() ) {
			return;
		}

		$button = $product->buy_button( false, $context, array(), true, true );

		if ( $echo ) {
			echo $button;
		} else {
			return $button;
		}
	}

endif;

if ( ! function_exists( 'mp_cart_link' ) ) :

	/**
	 * Display the current shopping cart link. If global cart is on reflects global location
	 * @since 3.0
	 *
	 * @param bool $echo Optional, whether to echo. Defaults to true.
	 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
	 * @param string $link_text Optional, text to show in link.
	 */
	function mp_cart_link( $echo = true, $url = false, $link_text = false ) {
		if ( mp_cart()->is_global && ! mp_is_main_site() ) {
			switch_to_blog( MP_ROOT_BLOG );
			$link = get_permalink( mp_get_setting( 'pages->cart' ) );
			restore_current_blog();
		} else {
			$link = get_permalink( mp_get_setting( 'pages->cart' ) );
		}

		if ( ! $url ) {
			$text = ( $link_text ) ? $link_text : __( 'Shopping Cart', 'mp' );
			$link = '<a href="' . $link . '" class="mp_cart_link">' . $text . '</a>';
		}

		/**
		 * Filter the cart link
		 *
		 * @since 3.0
		 *
		 * @param string $link The current link.
		 * @param bool $echo Optional, whether to echo. Defaults to true.
		 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
		 * @param string $link_text Optional, text to show in link.
		 */
		$link = apply_filters( 'mp_cart_link', $link, $echo, $url, $link_text );

		if ( $echo ) {
			echo $link;
		} else {
			return $link;
		}
	}

endif;

if ( ! function_exists( 'mp_checkout_step_url' ) ) :

	/**
	 * Get the current shopping cart link with checkout step
	 *
	 * @since 3.0
	 *
	 * @param string $checkoutstep . Possible values: checkout-edit, shipping, checkout, confirm-checkout, confirmation
	 */
	function mp_checkout_step_url( $checkout_step ) {
		return ( is_admin() ) ? '' : apply_filters( 'mp_checkout_step_url', mp_cart_link( false, true ) . trailingslashit( $checkout_step ), $checkout_step );
	}

endif;

if ( ! function_exists( 'mp_create_store_page' ) ) :

	/**
	 * Create a store page
	 *
	 * @since 3.0
	 *
	 * @param string $type The type of page to create.
	 *
	 * @return int $post_id The ID of the newly created page.
	 */
	function mp_create_store_page( $type ) {
		$args     = array();
		$defaults = array(
			'post_status' => 'publish',
			'post_type'   => 'page',
		);

		switch ( $type ) {
			case 'store' :
				$args = array(
					'post_title'   => __( 'Store', 'mp' ),
					'post_content' => __( "Welcome to our online store! Feel free to browse around:\n\n[mp_store_navigation]\n\nCheck out our most popular products:\n\n[mp_popular_products]\n\nBrowse by category:\n\n[mp_list_categories]\n\nBrowse by tag:\n\n[mp_tag_cloud]", 'mp' ),
				);
				break;

			case 'network_store_page' :
				$args = array(
					'post_title'     => __( 'Global Store', 'mp' ),
					'post_content'   => __( "Welcome to our market place!\n\nCheck out our network of products:\n\n[mp_list_global_products]\n\nBrowse by category:\n\n[mp_global_categories_list]\n\nBrowse by tag:\n\n[mp_global_tag_cloud]", 'mp' ),
					'comment_status' => 'closed'
				);
				break;
			case 'network_categories' :
				$args = array(
					'post_title'     => __( 'Category', 'mp' ),
					'post_content'   => __( "[mp_global_categories_list]", 'mp' ),
					'comment_status' => 'closed',
					'post_parent'    => mp_get_network_setting( 'pages->network_store_page', 0 ),
				);
				break;
			case 'network_tags' :
				$args = array(
					'post_title'     => __( 'Tag', 'mp' ),
					'post_content'   => __( "[mp_global_tag_cloud]", 'mp' ),
					'comment_status' => 'closed',
					'post_parent'    => mp_get_network_setting( 'pages->network_store_page', 0 ),
				);
				break;
			case 'products' :
				$args = array(
					'post_title'   => __( 'Products', 'mp' ),
					'post_content' => '[mp_list_products]',
					'post_parent'  => mp_get_setting( 'pages->store', 0 ),
				);
				break;

			case 'cart' :
				$args = array(
					'post_title'   => __( 'Cart', 'mp' ),
					'post_content' => '[mp_cart]',
					'post_parent'  => mp_get_setting( 'pages->store', 0 ),
				);
				break;

			case 'checkout' :
				$args = array(
					'post_title'   => __( 'Checkout', 'mp' ),
					'post_content' => '[mp_checkout]',
					'post_parent'  => mp_get_setting( 'pages->store', 0 )
				);
				break;

			case 'order_status' :
				$args = array(
					'post_title'   => __( 'Order Status', 'mp' ),
					'post_content' => "[mp_order_lookup_form]<h2>" . __( 'Order Search', 'mp' ) . "</h2><p>" . __( 'If you have your order ID you can look it up using the form below.', 'mp' ) . "</p>[/mp_order_lookup_form][mp_order_status]",
					'post_parent'  => mp_get_setting( 'pages->store', 0 )
				);
				break;
		}

		$post_id = wp_insert_post( array_merge( $defaults, $args ) );
		MP_Pages_Admin::get_instance()->save_store_page_value( $type, $post_id, false );

		return $post_id;
	}

endif;

if ( ! function_exists( 'mp_display_currency' ) ) :

	/**
	 * Format a number as currency without the symbol
	 *
	 * @since 3.0
	 *
	 * @param float $amount The amount to format.
	 * @param int $dec_places Optional, the number of decimal places to show.
	 *
	 * @return string
	 */
	function mp_display_currency( $amount, $dec_places = null ) {
		$amount = (float) $amount;

		if ( is_null( $dec_places ) ) {
			$dec_places = 2;
			if ( $amount == (int) $amount ) {
				$dec_places = 0;
			}
		}

		return number_format( $amount, $dec_places, '.', '' );
	}

endif;

if ( ! function_exists( 'mp_format_currency' ) ) :

	/**
	 * Formats currency
	 *
	 * @since 3.0
	 *
	 * @param string $currency The currency code to use for formatting (defaults to value set in currency settings)
	 * @param float $amount The amount to format
	 *
	 * @return string
	 */
	function mp_format_currency(
		$currency = '', $amount = false, $price_class = '', $currency_class = '',
		$price_holder_arguments = array(), $force_basic = false
	) {

		$currencies = mp()->currencies;

		if ( empty( $currency ) ) {
			$currency = ( is_multisite() && mp_cart()->is_global ) ? mp_get_network_setting( 'global_currency', 'USD' ) : mp_get_setting( 'currency', 'USD' );
		}
		
		$curr_symbol_position = mp_get_setting( 'curr_symbol_position' );

		// If multisite use global currency symbol
		if( is_multisite() && mp_cart()->is_global ) {
			$global_currency = mp_get_network_setting( 'global_curr_symbol_position' );
			
			// Check if we have global currency symbol
			if( ! empty( $global_currency ) ) {
				$curr_symbol_position = $global_currency;
			}
		}

// get the currency symbol
		if ( $symbol = mp_arr_get_value( "$currency->1", $currencies ) ) {
// if many symbols are found, rebuild the full symbol
			$symbols = array_map( 'trim', explode( ', ', $symbol ) );
			if ( is_array( $symbols ) ) {
				$symbol = '';
				foreach ( $symbols as $temp ) {
					$symbol .= '&#x' . $temp . ';';
				}
			} else {
				$symbol = '&#x' . $symbol . ';';
			}
		}

		/**
		 * Filter the currency symbol used to format curency
		 *
		 * @since 3.0
		 *
		 * @param string $symbol
		 * @param string $currency
		 */
		$symbol = apply_filters( 'mp_format_currency_symbol', $symbol, $currency );

//check decimal option
		if ( (int) ( $amount ) == (float) $amount ) {
			$decimal_place = 0;
		} else {
			$decimal_place = 2;
		}

//handle negative numbers
		$negative_symbol = '';

		if ( $amount < 0 ) {
			$negative_symbol = '-';
			$amount          = abs( $amount );
		}

		if ( $amount === false ) {
// just return symbol
			$formatted = $symbol;
		} else {
// just in case so number_format_i18n doesn't throw an error if $amount is string instead of double
			$amount                        = (float) $amount;
			$price_holder_arguments_string = '';

			if ( is_array( $price_holder_arguments ) && count( $price_holder_arguments ) > 0 ) {
				foreach ( $price_holder_arguments as $argument_name => $argument_value ) {
					$price_holder_arguments_string .= ' ' . esc_attr( $argument_name ) . '="' . esc_attr( $argument_value ) . '" ';
				}
			} else {
				$price_holder_arguments_string = '';
			}

			if ( ! empty( $price_class ) ) {
				$price_pre  = '<span class="' . esc_attr( $price_class ) . '" ' . $price_holder_arguments_string . '>';
				$price_post = '</span>';
			} else {
				$price_pre  = '';
				$price_post = '';
			}

			if ( ! empty( $currency_class ) ) {
				$currency_pre  = '<span class="' . esc_attr( $currency_class ) . '">';
				$currency_post = '</span>';
			} else {
				$currency_pre  = '';
				$currency_post = '';
			}

			switch ( $curr_symbol_position ) {
				case 1 :
					$formatted = $negative_symbol . $currency_pre . $symbol . $currency_post . $price_pre . mp_number_format( $amount, $decimal_place, $force_basic ) . $price_post;
					break;

				case 2 :
					$formatted = $negative_symbol . $currency_pre . $symbol . $currency_post . '&nbsp;' . $price_pre . mp_number_format( $amount, $decimal_place, $force_basic ) . $price_post;
					break;

				case 3 :
					$formatted = $price_pre . mp_number_format( $amount, $decimal_place, $force_basic ) . $price_post . $currency_pre . $symbol . $currency_post;
					break;

				case 4 :
					$formatted = $price_pre . mp_number_format( $amount, $decimal_place, $force_basic ) . $price_post . '&nbsp;' . $currency_pre . $symbol . $currency_post;
					break;
			}
		}

		/**
		 * Filter the formatted currency
		 *
		 * @since 3.0
		 *
		 * @param string $formatted
		 * @param string $currency
		 * @param string $symbol
		 * @param float $amount
		 */

		return apply_filters( 'mp_format_currency', $formatted, $currency, $symbol, $amount );
	}

endif;


if ( ! function_exists( 'mp_format_date' ) ) :

	/**
	 * Format a date according to settings
	 *
	 * @since 3.0
	 *
	 * @param int $timestamp
	 * @param bool $date_only Optional, whether to return just the date part or include the time as well. Defaults to include time.
	 */
	function mp_format_date( $timestamp, $date_only = false ) {
		$format = get_option( 'date_format' );
		if ( ! $date_only ) {
			$format .= ' - ' . get_option( 'time_format' );
		}

		$date = get_date_from_gmt( date( 'Y-m-d H:i:s', $timestamp ), $format );

		return $date;
		//return date_i18n( $format, $timestamp);
	}

endif;

if ( ! function_exists( 'mp_get_current_user_zipcode' ) ) :

	/**
	 * Get the current user's zipcode
	 *
	 * @since 3.0
	 * @access public
	 * @return string The zipcode. False, if no zipcode could be retrieved.
	 */
	function mp_get_current_user_zipcode() {
		$user    = wp_get_current_user();
		$address = $user->get( 'mp_shipping_info' );
		$zipcode = false;

		if ( is_array( $address ) ) {
// Try to get from usermeta
			$zipcode = mp_arr_get_value( 'zip', $address );
		}

		if ( false === $zipcode ) {
// Try to get from cookie
			$zipcode = mp_get_cookie_value( 'zip' );
		}

		return $zipcode;
	}

endif;

if ( ! function_exists( 'mp_get_current_user_city' ) ) :

	/**
	 * Get the current user's zipcode
	 *
	 * @since 3.0
	 * @access public
	 * @return string The zipcode. False, if no zipcode could be retrieved.
	 */
	function mp_get_current_user_city() {
		$user    = wp_get_current_user();
		$address = $user->get( 'mp_shipping_info' );
		$city    = false;

		if ( is_array( $address ) ) {
// Try to get from usermeta
			$city = mp_arr_get_value( 'city', $address );
		}

		if ( false === $city ) {
// Try to get from cookie
			$city = mp_get_cookie_value( 'city' );
		}

		return $city;
	}

endif;

if ( ! function_exists( 'mp_get_user_address' ) ) :

	/**
	 * Get full user address
	 *
	 * @since 3.0
	 *
	 * @param string $what Either shipping or billing.
	 * @param WP_User /int $user Optional, an WP_User object or a user ID. Defaults to the current user.
	 *
	 * @return array False, on error.
	 */
	function mp_get_user_address( $what, $user = null ) {
		if ( is_null( $user ) ) {
			$user = wp_get_current_user();
		} elseif ( ! $user instanceof WP_User && false === ( $user = get_user_by( 'id', $user ) ) ) {
			return false;
		}

		if ( $_data = mp_get_session_value( "mp_{$what}_info" ) ) {
			$data = $_data;
		} elseif ( empty( $data ) && is_user_logged_in() ) {
			$data = $user->get( "mp_{$what}_info" );
		} else {
			return false;
		}

		/* if ( !empty( $data ) ) {
		  foreach ( $data as $k => $v ) {
		  switch ( $k ) {
		  case 'shipping_option' :
		  case 'shipping_sub_option' :
		  case 'shipping_cost' :
		  unset( $data[ $k ] );
		  break;
		  }
		  }
		  } */

		return $data;
	}

endif;

if ( ! function_exists( 'mp_list_categories' ) ) :

	/**
	 * Display or retrieve the HTML list of product categories.
	 *
	 * The list of arguments is below:
	 *         'show_option_all' (string) - Text to display for showing all categories.
	 *         'orderby' (string) default is 'ID' - What column to use for ordering the
	 * categories.
	 *         'order' (string) default is 'ASC' - What direction to order categories.
	 *         'show_last_update' (bool|int) default is 0 - See {@link
	 * walk_category_dropdown_tree()}
	 *         'show_count' (bool|int) default is 0 - Whether to show how many posts are
	 * in the category.
	 *         'hide_empty' (bool|int) default is 1 - Whether to hide categories that
	 * don't have any posts attached to them.
	 *         'use_desc_for_title' (bool|int) default is 1 - Whether to use the
	 * description instead of the category title.
	 *         'feed' - See {@link get_categories()}.
	 *         'feed_type' - See {@link get_categories()}.
	 *         'feed_image' - See {@link get_categories()}.
	 *         'child_of' (int) default is 0 - See {@link get_categories()}.
	 *         'exclude' (string) - See {@link get_categories()}.
	 *         'exclude_tree' (string) - See {@link get_categories()}.
	 *         'current_category' (int) - See {@link get_categories()}.
	 *         'hierarchical' (bool) - See {@link get_categories()}.
	 *         'title_li' (string) - See {@link get_categories()}.
	 *         'depth' (int) - The max depth.
	 *
	 * @param bool $echo Optional. Whether or not to echo.
	 * @param string|array $args Optional. Override default arguments.
	 */
	function mp_list_categories( $echo = true, $args = array() ) {
		$args['taxonomy'] = 'product_category';
		$args['echo']     = false;

		$list = '<ul id="mp_category_list">' . wp_list_categories( $args ) . '</ul>';

		/**
		 * Filter the category list html
		 *
		 * @since 3.0
		 *
		 * @param string $list
		 * @param array $args
		 */
		$list = apply_filters( 'mp_list_categories', $list, $args );

		if ( $echo ) {
			echo $list;
		} else {
			return $list;
		}
	}

endif;

if ( ! function_exists( 'mp_list_payment_options' ) ) :

	/**
	 * List available payment options (if there is more than one)
	 *
	 * @since 3.0
	 *
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	function mp_list_payment_options( $echo = true ) {

		$gateways = MP_Gateway_API::get_active_gateways();
		$html     = '';

		$cart  = MP_Cart::get_instance();
		$total = $cart->total( false );

		$options = array();
		foreach ( $gateways as $code => $gateway ) {
			if ( $total == 0 ) {
				if ( $code == 'free_orders' ) {
					$options[ $code ] = $gateway->public_name;
				} else {
					//do not list other payment gateways
				}
			} else {
				if ( $code !== 'free_orders' ) {//do not list free orders if total is > 0
					$options[ $code ] = $gateway->public_name;
				}
			}
		}
		/**
		 * Filter the options array before formatting to html
		 *
		 * @since 3.0
		 *
		 * @param array $options
		 */
		$options = (array) apply_filters( 'mp_payment_options_array', $options );

		$index = 0;
		foreach ( $options as $code => $label ) {
			$checked = '';
			if ( $selected = mp_get_session_value( 'mp_payment_method' ) ) {
				if ( $selected == $code ) {
					$checked = ' checked';
				}
			} elseif ( $index == 0 ) {
				$checked = ' checked';
			}

			$input_id = 'mp-gateway-option-' . $code;
			$html .= '
				<label class="mp_form_label mp_form_label-checkout-option" for="' . $input_id . '"' . ( ( count( $options ) == 1 ) ? ' style="display:none"' : '' ) . '>
					<input
						data-mp-use-confirmation-step="' . ( ( $gateways[ $code ]->use_confirmation_step ) ? 'true' : 'false' ) . '"
						id="' . $input_id . '"
						type="radio"
						name="payment_method"
						value="' . $code . '"' . $checked . '
						autocomplete="off">
					<span></span>' . $label . '
				</label>';

			$index ++;
		}

		/**
		 * Filter the payment options html
		 *
		 * @since 3.0
		 *
		 * @param string $html The current html.
		 */
		$html = apply_filters( 'mp_list_payment_options', $html );

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
	 *
	 * @param MP_Shipping_API $plugin A shipping plugin object.
	 * @param bool $echo Optional, whether to echo or return. Defaults to return.
	 *
	 * @return string
	 */
	function mp_list_plugin_shipping_options( $plugin, $echo = false ) {
		if ( ! $plugin instanceof MP_Shipping_API ) {
			trigger_error( $plugin . ' is not an instance of MP_Shipping_API', E_USER_ERROR );
		}

		$what                = ( mp_get_post_value( 'enable_shipping_address' ) ) ? 'shipping' : 'billing';
		$shipping_option     = mp_get_session_value( 'mp_shipping_info->shipping_option' );
		$shipping_sub_option = mp_get_session_value( 'mp_shipping_info->shipping_sub_option' );

		$address1 = mp_get_user_address_part( 'address1', $what );
		$address2 = mp_get_user_address_part( 'address2', $what );
		$city     = mp_get_user_address_part( 'city', $what );
		$state    = mp_get_user_address_part( 'state', $what );
		$zip      = mp_get_user_address_part( 'zip', $what );
		$country  = mp_get_user_address_part( 'country', $what );

		$cart    = mp_cart();
		$options = $plugin->shipping_options( $cart, $address1, $address2, $city, $state, $zip, $country );

		$html = '';
		foreach ( (array) $options as $method => $label ) {
			$input_id   = 'mp-shipping-option-' . $plugin->plugin_name . '-' . sanitize_title( $method );
			$checked    = ( $plugin->plugin_name == $shipping_option && $method == $shipping_sub_option ) ? ' checked' : '';
			$input_name = ( mp_cart()->is_global ) ? 'shipping_method[' . mp_cart()->get_blog_id() . ']' : 'shipping_method';
			$html .= '
				<label class="mp_form_label mp_form_label-checkout-option" for="' . $input_id . '">
					<input
						id="' . $input_id . '"
						type="radio"
						name="' . $input_name . '"
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
		 *
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

if ( ! function_exists( 'mp_get_user_address_part' ) ) :

	/**
	 * Get user address part
	 *
	 * @since 3.0
	 *
	 * @param string $what What to get (e.g. address1, address2, etc)
	 * @param string $type Either shipping or billing.
	 * @param WP_User /int $user Optional, an WP_User object or a user ID. Defaults to the current user.
	 *
	 * @return string
	 */
	function mp_get_user_address_part( $what, $type, $user = null ) {

		if ( is_null( $user ) ) {
			$user = wp_get_current_user();
		} elseif ( ! $user instanceof WP_User && false === ( $user = get_user_by( 'id', $user ) ) ) {
			return false;
		}

		$meta = $user->get( "mp_{$type}_info" );

		if ( 'first_name' == $what || 'last_name' == $what ) {
			if ( 'first_name' == $what ) {
				$first_name = mp_get_session_value( "mp_" . $type . "_info->first_name", mp_arr_get_value( 'first_name', $meta, '' ) );
				if ( ! empty( $first_name ) ) {
					return $first_name;
				}
			} else {
				$last_name = mp_get_session_value( "mp_" . $type . "_info->last_name", mp_arr_get_value( 'last_name', $meta, '' ) );
				if ( ! empty( $last_name ) ) {
					return $last_name;
				}
			}

			$name       = mp_get_session_value( "mp_" . $type . "_info->name", $user->display_name );
			$name_parts = explode( ' ', $name );

			if ( 'first_name' == $what ) {
				return mp_arr_get_value( '0', $name_parts, '' );
			} else {
				return mp_arr_get_value( '1', $name_parts, '' );
			}

		} elseif ( 'email' == $what ) {
			$email = mp_get_session_value( "mp_" . $type . "_info->{$what}", mp_arr_get_value( $what, $meta, '' ) );
			if ( ! empty( $email ) ) {
				return $email;
			} else {
				return $user->user_email;
			}
		} else {
			//echo 'type:'.$type;
			return mp_get_session_value( "mp_" . $type . "_info->{$what}", mp_arr_get_value( $what, $meta, '' ) );
		}
	}

endif;

if ( ! function_exists( 'mp_get_states' ) ) :

	/**
	 * Get an array of states/provinces for a given country
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $country A country code.
	 *
	 * @return string
	 */
	function mp_get_states( $country ) {
		$list = false;
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

if ( ! function_exists( 'mp_get_image_size' ) ) :

	/**
	 * Get the image size per presentation settings
	 *
	 * @since 3.0
	 *
	 * @param string $view Either "single" or "list".
	 *
	 * @return array
	 */
	function mp_get_image_size( $view ) {
		$prefix = ( $view == 'single' ) ? 'product' : 'list';
		$size   = mp_get_setting( $prefix . '_img_size' );

		if ( $size == 'custom' ) {
			$size = array(
				'label'  => 'custom',
				'width'  => intval( mp_get_setting( $prefix . '_img_size_custom->width' ) ),
				'height' => intval( mp_get_setting( $prefix . '_img_size_custom->height' ) ),
			);
		} else {
			$size = array(
				'label'  => $size,
				'width'  => get_option( $size . '_size_w' ),
				'height' => get_option( $size . 'size_h' ),
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
	 *
	 * @param int $user_id The ID of the user to retrieve order history for.
	 *
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
			//cookie values is serialize format, we will have to unserialize
			$orders = maybe_unserialize( mp_get_cookie_value( $key, array() ) );
		}

		foreach ( $orders as $key => $order ) {
			if ( ! empty( $key ) ) {
				if ( ! empty( $order['id'] ) ) {
					$mp_order = get_post( $order['id'] );

					// if order is deleted or trashed, unset it
					if ( empty( $mp_order ) || 'trash' === $mp_order->post_status || 'auto-draft' === $mp_order->post_status ) {
						unset( $orders[ $key ] );
					}
				}
			} else {
				unset( $orders[ $key ] );
			}
		}

		/**
		 * Filter the user's order history
		 *
		 * @since 3.0
		 *
		 * @param array $orders The current array of orders.
		 * @param int $user_id The user's ID.
		 */
		$orders = (array) apply_filters( 'mp_get_order_history', $orders, $user_id );

// Put orders in reverse chronological order
		krsort( $orders );

		return $orders;
	}

endif;

if ( ! function_exists( 'mp_store_page_uri' ) ) {

	/**
	 * Get a store page uri
	 *
	 * @since 3.0
	 *
	 * @param string $page The page to get the uri for.
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	function mp_store_page_uri( $page, $echo = true ) {
		$url = $append = '';

		if ( $page == 'confirm_order' ) {
			$append = 'confirm/';
			$page   = 'checkout';
		}

		if ( $post_id = mp_get_setting( "pages->{$page}" ) ) {
			$url = trailingslashit( get_page_uri( $post_id ) ) . $append;
		}

		if ( $echo ) {
			echo $url;
		} else {
			return $url;
		}
	}

}

if ( ! function_exists( 'mp_store_page_url' ) ) :

	/**
	 * Get a store page url
	 *
	 * @since 3.0
	 *
	 * @param string $page The page to get the URL for.
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	function mp_store_page_url( $page, $echo = true ) {
		$url = $append = '';

		if ( $page == 'confirm_order' ) {
			$append = 'confirm/';
			$page   = 'checkout';
		}

		if ( $post_id = mp_get_setting( "pages->{$page}" ) ) {
			$url = trailingslashit( get_permalink( $post_id ) ) . $append;
		}

		if ( $echo ) {
			echo $url;
		} else {
			return $url;
		}
	}

endif;

if ( ! function_exists( 'mp_tax_rate' ) ) :

	/**
	 * Get the tax rate per settings
	 *
	 * @since 3.0
	 *
	 * @param bool $echo Optional, whether to echo or return. Defaults to return.
	 */
	function mp_tax_rate( $echo = false ) {
//get address
		$state    = mp_get_user_address_part( 'state', 'shipping' );
		$country  = mp_get_user_address_part( 'country', 'shipping' );
		$tax_rate = 0;

		if ( empty( $country ) ) {
			$country = mp_get_setting( 'base_country' );
		}

		if ( empty( $state ) ) {
			$state = mp_get_setting( 'base_province' );
		}
        
        /*
		switch ( $country ) {//mp_get_setting( 'base_country' )
			case 'US':
// USA taxes are only for orders delivered inside the state
				if ( $country == 'US' && $state == mp_get_setting( 'base_province' ) ) {
					$tax_rate = (float) mp_get_setting( 'tax->rate' );
				}
				break;

			case 'CA':
//Canada tax is for all orders in country, based on province shipped to. We're assuming the rate is a combination of GST/PST/etc.
				if ( $country == 'CA' && array_key_exists( $state, mp()->CA_provinces ) ) {
					if ( $_tax_rate = mp_get_setting( "tax->canada_rate->$state" ) ) {
						$tax_rate = (float) $_tax_rate;
					}
				}
				break;

			case 'AU':
//Australia taxes orders in country
				if ( $country == 'AU' ) {
					$tax_rate = (float) mp_get_setting( 'tax->rate' );
				}
				break;

			default:
//EU countries charge VAT within the EU
				if ( in_array( mp_get_setting( 'base_country' ), mp()->eu_countries ) ) {
					if ( in_array( $country, mp()->eu_countries ) ) {
						$tax_rate = (float) mp_get_setting( 'tax->rate' );
					}
				} else {
//all other countries use the tax outside preference
//if ( mp_get_setting( 'tax->tax_outside' ) || (!mp_get_setting( 'tax->tax_outside' ) && $country == mp_get_setting( 'base_country' )) ) {
					$tax_rate = (float) mp_get_setting( 'tax->rate' );
//}
				}
				break;
		}
        */
        
        $tax_rate = (float) mp_get_setting( 'tax->rate' );

		if ( empty( $tax_rate ) ) {
			$tax_rate = 0;
		}

		/**
		 * Filter the tax rate
		 *
		 * @since 3.0
		 *
		 * @param float
		 * @param float $tax_rate The current tax rate.
		 */
		$tax_rate = (float) apply_filters( 'mp_tax_rate', $tax_rate );

		if ( $echo ) {
			echo $tax_rate;
		} else {
			return $tax_rate;
		}
	}

endif;

if ( ! function_exists( 'mp_weight_label' ) ) :

	/**
	 * Display the appropriate weight label (kgs/lbs) according to settings
	 *
	 * @since 3.0
	 *
	 * @param int /float $val
	 *
	 * @return string
	 */
	function mp_weight_label( $val = null ) {
		$units = ( 'english' == mp_get_setting( 'shipping->system' ) ) ? 'lbs' : 'kgs';
		$html  = '<span class="mp-weight-label">' . ( ( ! is_null( $val ) ) ? $val : '' ) . '<span class="mp-units">' . $units . '</span>' . '</span>';

		/**
		 * Filter the weight label
		 *
		 * @since 3.0
		 *
		 * @param string $html
		 * @param int /float $val
		 * @param string $units Either "kgs" or "lbs".
		 */
		$html = apply_filters( 'mp_weight_label', $html, $val, $units );

		return $html;
	}

endif;

if ( ! function_exists( 'mp_dimension_label' ) ) :

	/**
	 * Display the appropriate dimension label (in/cm) according to settings
	 *
	 * @since 3.0
	 *
	 * @param int /float $val
	 */
	function mp_dimension_label( $val = null ) {
		$units = ( 'english' == mp_get_setting( 'shipping->system' ) ) ? 'in' : 'cm';
		$html  = '<span class="mp-dimension-label">' . ( ( ! is_null( $val ) ) ? $val : '' ) . '<span class="mp-units">' . $units . '</span>' . '</span>';

		/**
		 * Filter the dimension label
		 *
		 * @since 3.0
		 *
		 * @param string $html
		 * @param int /float $val
		 * @param string $units Either "in" or "cm".
		 */
		$html = apply_filters( 'mp_dimension_label', $html, $val, $units );

		return $html;
	}

endif;


if ( ! function_exists( 'mp_is_shop_page' ) ) :

	/**
	 * Check if current page is a shop page
	 *
	 * @since 3.0
	 *
	 * @param array /string $page The specific page to check - e.g. "cart".
	 *
	 * @return bool
	 */
	function mp_is_shop_page( $page = null ) {
		return ( is_admin() ) ? false : MP_Public::get_instance()->is_store_page( $page );
	}

endif;

if ( ! function_exists( 'mp_list_products' ) ) :

	/**
	 * Display a list of products according to preference
	 *
	 * @since 3.0
	 *
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
		$func_args        = func_get_args();
		$args             = mp_parse_args( $func_args, mp()->defaults['list_products'] );
		$args['nopaging'] = false;

// Init query params
		$query = array(
			'post_type'   => MP_Product::get_post_type(),
			'post_status' => 'publish',
		);

// Setup taxonomy query
		$tax_query = array();
		if ( ! is_null( $args['category'] ) || ! is_null( $args['tag'] ) ) {
			if ( ! is_null( $args['category'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'product_category',
					'field'    => 'slug',
					'terms'    => sanitize_title( $args['category'] ),
				);
			}

			if ( ! is_null( $args['tag'] ) ) {
				$tax_query[] = array(
					'taxonomy' => 'product_tag',
					'field'    => 'slug',
					'terms'    => sanitize_title( $args['tag'] ),
				);
			}
		} elseif ( get_query_var( 'taxonomy' ) == 'product_category' ) {
			$tax_query[] = array(
				'taxonomy' => 'product_category',
				'field'    => 'slug',
				'terms'    => get_query_var( 'term' ),
			);
		} elseif ( get_query_var( 'taxonomy' ) == 'product_tag' ) {
			$tax_query[] = array(
				'taxonomy' => 'product_tag',
				'field'    => 'slug',
				'terms'    => get_query_var( 'term' ),
			);
		}

		if ( count( $tax_query ) > 1 ) {
			$query['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query );
		} elseif ( count( $tax_query ) == 1 ) {
			$query['tax_query'] = $tax_query;
		}

// Setup pagination
		if ( ( ! is_null( $args['paginate'] ) && ! $args['paginate'] ) || ( is_null( $args['paginate'] ) && ! mp_get_setting( 'paginate' ) ) ) {
			$query['nopaging'] = $args['nopaging'] = true;
		} else {
// Figure out per page
			if ( ! is_null( $args['per_page'] ) ) {
				$query['posts_per_page'] = intval( $args['per_page'] );
			} else {
				//$query[ 'posts_per_page' ] = intval( $args[ 'per_page' ] );
				$query['posts_per_page'] = intval( mp_get_setting( 'per_page' ) );
			}

// Figure out page
			if ( ! is_null( $args['page'] ) ) {
				$query['paged'] = intval( $args['page'] );
			} elseif ( get_query_var( 'paged' ) != '' ) {
				$query['paged'] = $args['page'] = intval( get_query_var( 'paged' ) );
			} elseif ( get_query_var( 'page' ) != '' ) {
				$query['paged'] = $args['page'] = intval( get_query_var( 'page' ) );
			}

			/*//Get session values for order and order_by
			if ( session_id() == '' ) {
				if (version_compare(phpversion(), '5.4.0', '<')) {
				     if(session_id() == '') {
				        @session_start();
				     }
				 }
				 else
				 {
				    if (session_status() == PHP_SESSION_NONE) {
				        @session_start();
				    }
				 }
			}*/

			if( !isset( $_SESSION ) ){
				$_SESSION = array();
			}

			$order_by = isset( $_SESSION['mp_product_list_order_by'] ) ? $_SESSION['mp_product_list_order_by'] : '';
			$order    = isset( $_SESSION['mp_product_list_order'] ) ? $_SESSION['mp_product_list_order'] : '';

			if ( ! empty( $order_by ) && ! empty( $order ) ) {
				$query['orderby'] = $order_by;
				$query['order']   = $order;

				$args['order_by'] = $order_by;
				$args['order']    = $order;
			}

// Get order by
			if ( ! is_null( $args['order_by'] ) ) {
				if ( 'price' == $args['order_by'] ) {
					$query['meta_key'] = 'sort_price';
					$query['orderby']  = 'meta_value_num';
				} else if ( 'sales' == $args['order_by'] ) {
					$query['meta_key'] = 'mp_sales_count';
					$query['orderby']  = 'meta_value_num';
				} else {
					$query['orderby'] = $args['order_by'];
				}
			} elseif ( 'price' == mp_get_setting( 'order_by' ) ) {
				$query['meta_key'] = 'sort_price';
				$query['orderby']  = 'meta_value_num';
			} elseif ( 'sales' == mp_get_setting( 'order_by' ) ) {
				$query['meta_key'] = 'mp_sales_count';
				$query['orderby']  = 'meta_value_num';
			} else {
				$query['orderby'] = mp_get_setting( 'order_by' );
			}
		}

// Get order direction
		$query['order'] = mp_get_setting( 'order' );
		if ( ! is_null( $args['order'] ) ) {
			$query['order'] = $args['order'];
		}

// Filter by featured
		if ( (bool) $args['featured'] ) {
			$query['meta_query'][]     = array(
				'key'     => 'featured',
				'value'   => '1',
				'compare' => '=',
			);
		}

        if( mp_get_setting( 'inventory_remove' ) )
        {
            $query['meta_query'][] = array(
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => 'inventory_tracking',
                        'value'   => '0',
                        'compare' => '=',
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key'     => 'inv_out_of_stock_purchase',
                            'value'   => '1',
                            'compare' => '=',
                        ),
                        array(
                            'key'     => 'inventory',
                            'value'   => '0',
                            'compare' => '>',
                        )
                    )
                )
            );
        }

// The Query
		$custom_query = new WP_Query( $query );
// Get layout type
		$layout_type = mp_get_setting( 'list_view' );
		if ( ! is_null( $args['list_view'] ) ) {
			$layout_type = $args['list_view'] ? 'list' : 'grid';
		}

		//$layout_type_output = (( 'grid' == $layout_type ) ? 'mp_products-grid' : 'mp_products-list');
// Build content
		$content = '';

		if ( ! mp_doing_ajax() ) {
			$per_page = ( is_null( $args['per_page'] ) ) ? null : $args['per_page'];
			$content .= ( $args['filters'] || ( is_null( $args['filters'] ) && 1 != mp_get_setting( 'hide_products_filter' ) ) ) ? mp_products_filter( false, $per_page, $custom_query ) : '';
		}

		$content .= '<!-- MP Product List --><section id="mp-products" class="hfeed mp_products mp_products-' . $layout_type . '">';

		if ( $last = $custom_query->post_count ) {
			$content .= $layout_type == 'grid' ? _mp_products_html_grid( $custom_query ) : _mp_products_html_list( $custom_query );
		} else {
			$content .= '<div id="mp-products-empty">' . apply_filters( 'mp_product_list_none', __( 'No Products', 'mp' ) ) . '</div><!-- end mp-no-products -->';
		}

		$content .= '</section><!-- end mp-products -->';

		$content .= ( ! $args['nopaging'] ) ? mp_products_nav( false, $custom_query ) : '';

		/**
		 * Filter product list html
		 *
		 * @since 3.0
		 *
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
	 *
	 * @param array $args {
	 *        Optional, an array of arguments.
	 *
	 * @type bool $echo Optional, whether to echo or return. Defaults to echo.
	 * @type string $content Optional, the content to display before the form.
	 * }
	 */
	function mp_order_lookup_form( $args = array() ) {
		$_args = array_replace_recursive( array(
			'echo'    => true,
			'content' => '',
		), $args );

		extract( $_args );

		if ( get_query_var( 'mp_order_id' ) ) {
			return '';
		}

		$guest_email = '';
		
		//Add guest email field
		if( ! is_user_logged_in() ){
			$guest_email = '<br/><input type="text" class="mp_form_input" id="mp-guest_email-input" name="guest_email" placeholder="' . __( 'Guest Email', 'mp' ) . '">';
		}

		$form = '
			<form id="mp-order-lookup-form" class="mp_form mp_form-order-lookup" method="post" action="' . admin_url( 'admin-ajax.php?action=mp_lookup_order' ) . '">
				<div class="mp_form_content">' . $content . '</div>
				<div class="mp_form_group">
					<div class="mp_form_group_input">
						<input type="text" class="mp_form_input" id="mp-order-id-input" name="order_id" placeholder="' . __( 'Order ID', 'mp' ) . '">
						' . $guest_email . '
					</div>
					<div class="mp_form_group_btn">
				  		<button class="mp_button" type="submit">' . __( 'Look Up', 'mp' ) . '</button>
				  	</div>
			    </div>
			</form><!-- end mp-order-lookup-form -->';

		/**
		 * Filter the order lookup form html
		 *
		 * @since 3.0
		 *
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
	 *
	 * @param array $args {
	 *        Optional, an array of arguments.
	 *
	 * @type bool $echo Optional, whether to echo or return. Defaults to echo.
	 * @type string $order_id Optional, the specific order ID to show. If empty, defaults to order status overview page.
	 * }
	 */
	function mp_order_status( $args ) {
		$args = array_replace_recursive( array(
			'echo'     => false,
			'order_id' => get_query_var( 'mp_order_id', null ),
			'guest_email' => get_query_var( 'mp_guest_email', null ),
		), $args );

		extract( $args );

		$html = '';

		//check does user logged in
		if ( is_user_logged_in() ) {
			if ( is_null( $order_id ) ) {
				$html .= _mp_order_status_overview();
			} else {
				$order = new MP_Order( $order_id );
				if ( $order->exists() ) {
					//only owner and store admins can see
					if ( $order->post_author != get_current_user_id() && !current_user_can( apply_filters( 'mp_store_settings_cap', 'read_store_order' ) ) ) {
						$html .= __( 'Oops! We couldn\'t locate any orders matching that order number. Please verify the order number and try again.', 'mp' );
						$html .= _mp_order_status_overview();
					} else {
						$html .= $order->details( false );
					}
				} else {
					$html .= __( 'Oops! We couldn\'t locate any orders matching that order number. Please verify the order number and try again.', 'mp' );
					$html .= _mp_order_status_overview();
				}
			}
		} else {
			if ( ! is_null( $order_id ) ) {
				if( ! is_null ( $guest_email ) ) {
					// If email and order provided matches, show the order status page
					$order = new MP_Order( $order_id );
					if ( $order->exists() && ( md5( $order->get_meta( 'mp_billing_info->email', '' ) ) == $guest_email || md5( $order->get_meta( 'mp_shipping_info->email', '' ) ) == $guest_email ) ) {
						$html .= $order->details( false );
					} else {
						$html .= __( 'Oops! We couldn\'t locate any orders matching that order number. Please verify the order number and try again.', 'mp' );
					}
				} else {
					$html .= __( 'Oops! We couldn\'t locate any orders matching that order number. Please verify the order number and try again.', 'mp' );
				}
			}
		}

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}

endif;

if ( ! function_exists( 'mp_pinit_button' ) ) :

	/**
	 * Pinterest PinIt button
	 *
	 * @param int $product_id
	 * @param string $context
	 * @param bool $echo
	 */
	function mp_pinit_button( $product_id = null, $context = 'single_view', $echo = false ) {
		_deprecated_function( 'mp_pinit_button', '3.0', 'MP_Product::pinit_button()' );

		$product = new MP_Product( $product_id );
		$snippet = $product->pinit_button( $context, false );

		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
	}

endif;

if ( ! function_exists( 'mp_featured_products' ) ) :

	/**
	 * Displays a list of popular products ordered by sales.
	 *
	 * @since 3.0
	 *
	 * @uses mp_list_products()
	 *
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
	function mp_featured_products() {
		$func_args        = func_get_args();
		$args             = mp_parse_args( $func_args, mp()->defaults['list_products'] );
		$echo = $args['echo'];

		// force echo to false to get content from mp_list_products()
		// force featured to true to filter only featured in mp_list_products()
		$args['echo'] 	  = false;
		$args['nopaging'] = false;
		$args['featured'] = true;		
		$content = mp_list_products($args);

		/**
		 * Filter the featured products html
		 *
		 * @since 3.0
		 *
		 * @param string $content The current HTML markup.
		 * @param array $args mp_featured_products short code attributes.
		*/
		$content = apply_filters( 'mp_featured_products', $content, $args );


		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
	}

endif;

if ( ! function_exists( 'mp_popular_products' ) ) :

	/**
	 * Displays a list of popular products ordered by sales.
	 *
	 * @since 3.0
	 *
	 * @param bool $echo Optional, whether to echo or return
	 * @param int $num Optional, max number of products to display. Defaults to 5
	 */
	function mp_popular_products( $echo = true, $num = 5 ) {
		$num          = (int) $num;
		$custom_query = new WP_Query( array(
			'post_type'      => MP_Product::get_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => $num,
			'meta_query'     => array(
				array(
					'key'     => 'mp_sales_count',
					'value'   => '0',
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'mp_sales_count',
			'order'          => 'DESC',
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
		 *
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


if ( ! function_exists( 'mp_product' ) ) {
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

	function mp_product(
		$echo = true, $product_id = null, $title = true, $content = 'full', $image = 'single',
		$meta = true
	) {
		global $wp_query;

		if ( function_exists( 'icl_object_id' ) ) {
			$product_id = icl_object_id( $product_id, MP_Product::get_post_type(), false );
		}

		$product = new MP_Product( $product_id );
		if ( ! $product->exists() ) {
			return;
		}
		$form_id = 'mp_buy_form_' . $product_id;

		$variation = false;
		$pinit   = $product->pinit_button( 'single_view' );
		$fb      = $product->facebook_like_button( 'single_view' );
		$twitter = $product->twitter_button( 'single_view' );

		$display_description = ( ! empty( $content ) && (bool) $content );

		$has_image = false;
		if ( ! $product->has_variations() ) {
			$values = get_post_meta( $product->ID, 'mp_product_images', true );
			if ( $values ) {
				$has_image = true;
			}
		} else {
			$variation_id = intval( get_post_meta( $product->ID, 'default_variation', true ) );
			if( get_query_var( 'mp_variation_id' ) != '' && is_numeric( get_query_var( 'mp_variation_id' ) ) ) $variation_id = get_query_var( 'mp_variation_id' );

			if( is_numeric( $variation_id ) ){
				$variation = new MP_Product( $variation_id );
				if ( ! $variation->exists() ) {
					$variation = false;
				}
			}

			foreach ( $product->get_variation_ids() as $id ) {
				$post_thumbnail_id = get_post_thumbnail_id( $id );
				if ( $post_thumbnail_id ) {
					$has_image = true;
					break;
				}
			}

			// if ( ! $has_image ) {
			// 	$values = get_post_meta( $product->ID, 'mp_product_images', true );
			// 	if ( $values ) {
			// 		$has_image = true;
			// 	}
			// }
		}

		$image_alignment = mp_get_setting( 'image_alignment_single' );

		$return = '
			<!-- MP Single Product -->
			<section id="mp-single-product-' . $product->ID . '" class="mp-single-product" itemscope itemtype="http://schema.org/Product">
				<div class="mp_product mp_single_product' . ( $has_image ? ' mp_single_product-has-image mp_single_product-image-' . ( ! empty( $image_alignment ) ? $image_alignment : 'aligncenter' ) . '' : '' ) . ( $product->has_variations() ? ' mp_single_product-has-variations' : '' ) . '">';

		$values = get_post_meta( $product->ID, 'mp_product_images', true );

		if ( mp_get_setting( 'product_img_size' ) == 'custom' ) {
			$size = array(
				mp_get_setting( 'product_img_size_custom->width' ),
				mp_get_setting( 'product_img_size_custom->height' )
			);
		} else {
			$size = mp_get_setting( 'product_img_size' );
		}

		$lightbox_code = '';
		$show_lightbox = mp_get_setting( 'show_lightbox' );

		if ( $show_lightbox == 1 && mp_get_setting( 'disable_large_image' ) != 1 ) {
			$lightbox_code = "onSliderLoad: function(el) {
											el.lightGallery({
												selector: '#mp-product-gallery .lslide',
												thumbnail: true,
												zoom: true,
											});
										}";
		}
        
        $lightbox_code = apply_filters( 'mp_single_product_image_lightbox', $lightbox_code );

		$post = get_post();

		if ( $image && $has_image && ! post_password_required( $post ) ) {
			if ( ! $product->has_variations() ) {

				if ( $values ) {

					$return .= '<div class="mp_single_product_images">';

					$return .= "<script>
								jQuery(document).ready(function() {
									jQuery('#mp-product-gallery').lightSlider({
										gallery:true,
										item:1,
										loop:true,
										thumbItem:5,
										slideMargin:0,
										enableDrag: true,
										currentPagerPosition:'left',
										" . $lightbox_code . "
									});
								});
								</script>";

					$return .= '<ul id="mp-product-gallery" class="mp_product_gallery">';

					$values = explode( ',', $values );

					if ( $image != "single" ) {
						foreach ( $values as $value ) {

							if ( preg_match( '/http:|https:/', $value ) ) {
								$img_url = array( esc_url( $value ) );
							} else {
								//$img_url = wp_get_attachment_image_src( $value, $size );
								$original_image = wp_get_attachment_image_src( $value, 'full' );
								$img_url        = mp_resize_image( $value, $original_image[0], $size );
							}

							$return .= '<li data-thumb="' . $img_url[0] . '" data-src ="' . $original_image[0] . '"><img src="' . $img_url[0] . '"></li>';
						}
					} else {
						if ( ! empty( $values[0] ) ) {

							if ( preg_match( '/http:|https:/', $values[0] ) ) {
								$img_url = array( esc_url( $values[0] ) );
							} else {
								$original_image = wp_get_attachment_image_src( $values[0], 'full' );
								$img_url        = mp_resize_image( $values[0], $original_image[0], $size );
							}

							$return .= '<li data-thumb="' . $img_url[0] . '" data-src ="' . $original_image[0] . '"><img src="' . $img_url[0] . '"></li>';
						}
					}

					$return .= '</ul><!-- end mp_product_gallery -->';

					$return .= '</div><!-- end mp_single_product_images -->';
				}
			} else {
				$return .= '<div class="mp_single_product_images">';
				$return .= ( $variation ) ? $variation->image( false, 'single', $size, $image_alignment ) : $product->image( false, 'single', $size, $image_alignment );
				$return .= '</div><!-- end mp_single_product_images -->';
			}
		}

		if ( $image && ! post_password_required( $post ) ) {
			$return .= '<div class="mp_single_product_details">';

			$return .= '<span style="display:none" class="date updated">' . get_the_time( $product->ID ) . '</span>'; // mp_product_class(false, 'mp_product', $post->ID)
		}


        if ( $title ) {
                $return .= ' <h1 itemprop="name" class="mp_product_name entry-title"><a href="' . $product->url( false ) . '">' . $product->title( false ) . '</a></h1>';

		        // If post password required and it doesn't match the cookie.
		        if ( post_password_required( $post ) )
			        $return .= get_the_password_form( $post );
        }

        if ( $meta && ! post_password_required( $post ) ) {
                $return .= '<div class="mp_product_meta">';

			// Price
			$return .= ( $variation ) ? $variation->display_price( false ) : $product->display_price( false );

			if ( mp_get_setting( 'show_single_excerpt' ) == 1  && $display_description ) {
				// Excerpt
				if ( ! $variation ) {
					$return .= '<div class="mp_product_excerpt">';
					$return .= mp_get_the_excerpt( $product_id, apply_filters( 'mp_get_the_excerpt_length', 18 ) );
					$return .= '</div><!-- end mp_product_excerpt -->';
				} else {
					$return .= '<div class="mp_product_excerpt mp_product_excerpt-variation">';
					$return .= mp_get_the_excerpt( $variation_id, apply_filters( 'mp_get_the_excerpt_length', 18 ), true );
					$return .= '</div><!-- end mp_product_excerpt -->';
				}
			}

			if ( mp_get_setting( 'show_single_categories' ) == 1 ) {
				$return .= mp_category_list( $product_id, '<div class="mp_product_categories">' . __( 'Categorized in ', 'mp' ), ', ', '</div>' );
			}

			$return .= '</div><!-- end mp_product_meta-->';

			// Callout
			$return .= '<div class="mp_product_callout">';

			// Button
			$selected_atts = array();

			if ( $variation ) {
				$atts = $variation->get_attributes();
				foreach ( $atts as $slug => $att ) {
					$selected_atts[ $slug ] = key( $att['terms'] );
				}
			}

			$return .= $product->buy_button( false, 'single', $selected_atts );

			if ( mp_get_setting( 'show_single_tags' ) == 1 ) {
				$return .= mp_tag_list( $product_id, '<div class="mp_product_tags">' . __( 'Tagged in ', 'mp' ), ', ', '</div>' );
			}

			$return .= '</div><!-- end mp_product_callout-->';

			$return .= '<div class="mp_social_shares">';
			$return .= $pinit;
			$return .= $fb;
			$return .= $twitter;
			$return .= '</div><!-- end mp_social_shares -->';
		}

		if ( $image && ! post_password_required( $post ) ) {
			$return .= '</div><!-- end mp_single_product_details-->';
		}

		if ( ! post_password_required( $post ) ) {
			$return .= '<div class="mp_single_product_extra">';

			if ( ! $display_description && isset( $product->content_tabs['mp-product-overview'] ) ) {
				unset( $product->content_tabs['mp-product-overview'] );
			}

			$return .= $product->content_tab_labels( false );

			$index = 0;

			if ( $display_description ) {
				$return .= '
	<div id="mp-product-overview' . '-' . $product->ID . '" class="mp_product_tab_content mp_product_tab_content-overview mp_product_tab_content-current">';

				$return .= '
	<div itemprop="description" class="mp_product_tab_content_text">';

				if ( $content == 'excerpt' ) {
					$return .= ( $variation ) ? mp_get_the_excerpt( $variation_id, apply_filters( 'mp_get_the_excerpt_length', 18 ), true ) : $product->excerpt();
				} else {
					$product_description = ( ! $product->post_content && $variation ) ? $product->get_variation()->post_content : $product->post_content;
					$return              .= apply_filters( 'the_content', $product_description );
				}

				$return .= '
	</div><!-- end mp_product_tab_content_text -->
	</div><!-- end mp-product-overview -->';
				$index ++;
			}


			// Remove overview tab as it's already been manually output above
			unset( $product->content_tabs['mp-product-overview'] );

			$func_args = func_get_args();
			$args      = mp_parse_args( $func_args, mp()->defaults['list_products'] );

			foreach ( $product->content_tabs as $slug => $label ) {
				switch ( $slug ) {
					case 'mp-related-products' :
						$view = mp_get_setting( 'related_products->view' );
						if ( mp_get_setting( 'related_products->show' ) ) {
							$layout_type = mp_get_setting( 'list_view' );
							if ( ! is_null( $args['list_view'] ) ) {
								$layout_type = $args['list_view'] ? 'list' : 'grid';
							}
							$return .= '
							<div id="mp-related-products-' . $product->ID . '" class="' . ( ( $index == 0 ) ? 'mp_product_tab_content-current' : '' ) . ' mp-multiple-products mp_product_tab_content mp_product_tab_content-related-products">
								<div class="mp_product_tab_content_products mp_products mp_products-related ' . ( isset( $view ) ? 'mp_products-' . $view : 'mp_products-list' ) . '">' . $product->related_products() . ' </div>
							</div><!-- end mp-related-products -->';
						}
						break;

					default :
						/**
						 * Filter the content tab html
						 *
						 * @since 3.0
						 *
						 * @param string
						 * @param string $slug The tab slug.
						 */
						$tab = apply_filters( 'mp_content_tab_html', '', $slug );

						$return .= '
						<div id="' . esc_attr( $slug ) . '-' . $product->ID . '" class="' . ( ( $index == 0 ) ? 'mp_product_tab_content-current' : '' ) . ' mp_product_tab_content mp_product_tab_content-html" style="display:none">
							<div class="mp_product_tab_content_html">' . $tab . '</div><!-- end mp_product_tab_content_html -->
						</div><!-- end ' . esc_attr( $slug ) . ' -->';
						break;
				}
				$index ++;
			}
			$return .= '</div><!-- end mp_single_product_extra -->';
		}

		$return .= '
			
</div><!-- end mp_product/mp_single_product -->	
</section><!-- end mp-single-product -->';

		/**
		 * Filter the product html
		 *
		 * @since 3.0
		 *
		 * @param string $return The current product html.
		 * @param int $product ->ID The product's ID.
		 * @param bool $title Whether to display the title.
		 * @param bool /string $content Whether and what type of content to display. Options are false, 'full', or 'excerpt'. Default 'full'.
		 * @param bool /string $image Whether and what context of image size to display. Options are false, 'single', or 'list'. Default 'single'.
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

if ( ! function_exists( 'mp_product_excerpt' ) ) :

	/**
	 * Replaces wp_trim_excerpt in MP custom loops
	 *
	 * @param string $excerpt
	 * @param string $content
	 * @param int $product_id
	 * @param string $excerpt_more
	 *
	 * @return string
	 */
	function mp_product_excerpt( $excerpt, $content, $product_id, $excerpt_more = null ) {
		_deprecated_function( 'mp_product_excerpt', '3.0', 'MP_Product::excerpt()' );
		$product = new MP_Product( $product_id );

		return $product->excerpt( $excerpt_more, $excerpt, $content );
	}

endif;

if ( ! function_exists( 'mp_product_image' ) ) :
	/*
	 * Get the product image
	 *
	 * @param bool $echo Optional, whether to echo
	 * @param string $context Options are list, single, or widget
	 * @param int $post_id The post_id for the product. Optional if in the loop
	 * @param int $size An optional width/height for the image if contect is widget
	 * @param string $align The alignment of the image. Defaults to settings.
	 */

	function mp_product_image( $echo = true, $context = 'list', $post_id = null, $size = null, $align = null ) {
		_deprecated_function( 'mp_product_image', '3.0', 'MP_Product::image()' );

		$product = new MP_Product( $post_id );
		//$image	 = MP_Product::image( false, $context, $size, $align );
		$image = $product->image( false, $context, $size, $align );

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
				<nav class="mp_listings_nav">';

			/* $html .= paginate_links( array(
			  'base'		 => '%_%',
			  'format'	 => '?paged=%#%',
			  'total'		 => $custom_query->max_num_pages,
			  'current'	 => max( 1, $custom_query->get( 'paged' ) ),
			  'prev_text'	 => __( 'Prev', 'mp' ),
			  'next_text'	 => __( 'Next', 'mp' ),
			  ) ); */

			//echo 'current_page:'.$custom_query->get( 'paged' );

			$html .= paginate_links( array(
				'base'         => '?paged=%#%', //'%_%',
				'format'       => '', //?paged=%#%
				'total'        => $custom_query->max_num_pages,
				'current'      => max( 1, $custom_query->get( 'paged' ) ),
				'show_all'     => false,
				'prev_next'    => true,
				'prev_text'    => __( 'Prev', 'mp' ),
				'next_text'    => __( 'Next', 'mp' ),
				'add_args'     => true,
				'add_fragment' => '',
			) );

			$html .= '
				</nav>';
		}

		/**
		 * Filter the products nav html
		 *
		 * @since 3.0
		 *
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

if ( ! function_exists( 'mp_products_filter' ) ) :

	/**
	 * Display product filters
	 *
	 * @since 3.0
	 *
	 * @param bool $hidden Are the filters hidden or visible?
	 * @param int $per_page The number of posts per page
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	function mp_products_filter( $hidden = false, $per_page = null, $query = null ) {
		$default = '-1';
		if ( $query instanceof WP_Query && $query->get( 'taxonomy' ) == 'product_category' ) {
			$term    = get_term_by( 'slug', $query->get( 'term' ), $query->get( 'taxonomy' ) );
			$default = $term->term_id;
		} elseif ( 'product_category' == get_query_var( 'taxonomy' ) ) {
			$term    = get_queried_object(); //must do this for number tags
			$default = $term->term_id;
		}

		$terms = wp_dropdown_categories( array(
			'name'             => 'product_category',
			'class'            => 'mp_select2',
			'id'               => 'mp-product-category',
			'taxonomy'         => 'product_category',
			'show_option_none' => __( 'Show All', 'mp' ),
			'show_count'       => 1,
			'orderby'          => 'name',
			'selected'         => $default,
			'echo'             => 0,
			'hierarchical'     => true
		) );

		if( !isset( $_SESSION ) ){
			$_SESSION = array();
		}

		if ( $query instanceof WP_Query ) {
			$current_order = strtolower( $query->get( 'order_by' ) . '-' . $query->get( 'order' ) );
		} else {
			$current_order = '';
		}

		if ( isset( $_SESSION['mp_product_list_order_by'] ) && isset( $_SESSION['mp_product_list_order'] ) ) {
			$current_order = $_SESSION['mp_product_list_order_by'] . '-' . $_SESSION['mp_product_list_order'];
		}

		$options = array(
			array( '0', '', __( 'Default', 'mp' ) ),
			array( 'date', 'desc', __( 'Release Date (Latest to Oldest)', 'mp' ) ),
			array( 'date', 'asc', __( 'Release Date (Oldest to Latest)', 'mp' ) ),
			array( 'title', 'asc', __( 'Name (A-Z)', 'mp' ) ),
			array( 'title', 'desc', __( 'Name (Z-A)', 'mp' ) ),
			array( 'price', 'asc', __( 'Price (Low to High)', 'mp' ) ),
			array( 'price', 'desc', __( 'Price (High to Low)', 'mp' ) ),
			array( 'sales', 'desc', __( 'Popularity (Most Popular - Least Popular)', 'mp' ) ),
			array( 'sales', 'asc', __( 'Popularity (Least Popular - Most Popular)', 'mp' ) )
		);

		$options_html = '';
		foreach ( $options as $k => $t ) {
			$value = $t[0] . '-' . $t [1];
			$options_html .= '<option value="' . $value . '" ' . selected( $value, $current_order, false ) . '>' . $t[2] . '</option>';
		}


		$return = '
		<a id="mp-product-top"></a>
		<!-- Products Filter -->
		<section class="mp_products_filter">
			<form id="mp-products-filter-form" name="mp_products_filter_form" class="mp_form mp_form-products-filter" method="get">
			
				<div class="mp_form_fields">
					<div class="mp_form_field mp_products_filter_field mp_products_filter_category" data-placeholder="' . __( 'Product Category', 'mp' ) . '">
						<label for="mp_product_category" class="mp_form_label">' . __( 'Category', 'mp' ) . '</label>
						' . $terms . '
					</div><!-- mp_listing_products_category -->

					<div class="mp_form_field mp_products_filter_field mp_products_filter_orderby">
						<label for="mp_sort_orderby" class="mp_form_label">' . __( 'Order By', 'mp' ) . '</label>
						<select id="mp_sort_orderby" class="mp_select2" name="order">
							' . $options_html . '
						</select>
					</div><!-- mp_products_filter_orderby -->
				</div>
				
				' . ( ( is_null( $per_page ) ) ? '' : '<input type="hidden" name="per_page" value="' . $per_page . '">' ) . '
				<input type="hidden" name="page" value="' . max( get_query_var( 'paged' ), 1 ) . '">
			
			</form><!-- mp_products_filter_form -->
		</section><!-- end mp_products_filter -->
		';

		return apply_filters( 'mp_products_filter', $return );


	}

endif;

if ( ! function_exists( 'mp_province_field' ) ) :
	/*
	 * Display state/province dropdown field
	 *
	 * @param string $country two-digit country code
	 * @param string $selected state code form value to be shown/selected
	 */

	function mp_province_field( $country = 'US', $selected = null ) {
		_deprecated_function( 'mp_province_field', '3.0', 'MP_Checkout::province_field' );
	}

endif;

if ( ! function_exists( 'mp_related_products' ) ) :

	/**
	 * Get related products
	 *
	 * @since 3.0
	 *
	 * @param int $product_id .
	 * @param string $relate_by Optional, how to relate the products - either by category, tag, or both.
	 * @param bool $echo Echo or return.
	 * @param int $limit . Optional The number of products we want to retrieve.
	 * @param bool $simple_list Optional, whether to show the related products based on the "list_view" setting or as a simple unordered list.
	 */
	function mp_related_products() {
		_deprecated_function( 'mp_related_products', '3.0', 'MP_Product::related_products()' );

		$defaults = array(
			'product_id'  => null,
			'echo'        => false,
			'relate_by'   => mp_get_setting( 'related_products->relate_by' ),
			'limit'       => mp_get_setting( 'related_products->show_limit' ),
			'simple_list' => mp_get_setting( 'related_products->simple_list' ),
		);
		$args     = array_replace_recursive( $defaults, array_combine( array_keys( $defaults ), func_get_args() ) );
		$html     = '';

		if ( ! is_null( $args['product_id'] ) ) {
			$product = new MP_Product( $args['product_id'] );

			if ( $product->exists() ) {
				$args['echo'] = false;
				$html .= $product->related_products( $args );
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
	 *
	 * @param string $email The email address to send to.
	 * @param string $subject The subject of the email.
	 * @param string $msg The email message.
	 * @param array $attachments
	 *
	 * @return bool
	 */
	function mp_send_email( $email, $subject, $msg, $attachments = array() ) {
		return MP_Mailer::get_instance()->send( $email, $subject, $msg, $attachments );
	}

endif;

function mp_get_the_excerpt( $id = false, $length = 55, $variation = false ) {
	global $post;

	if ( empty( $post ) ) {
		$post               = new StdClass;
		$post->ID           = 0;
		$post->post_excerpt = '';
		$post->post_content = '';
	}

	$old_post = $post;

	if ( $id != $post->ID ) {
		$post = get_page( $id );
	}

	$excerpt = trim( $post->post_excerpt );

	if ( ! $excerpt ) {
		$excerpt = $post->post_content;
	}

	if ( $variation ) {
		$parent_post_id = wp_get_post_parent_id( $id );
		$parent_post    = get_post( $parent_post_id );
		if ( ! empty( $parent_post->post_excerpt ) ) {
			$excerpt = $parent_post->post_excerpt;
		} else {
			$excerpt = $parent_post->post_content;
		}
	}

	$excerpt = strip_shortcodes( $excerpt );
//$excerpt = apply_filters( 'the_content', $excerpt );
	$excerpt        = str_replace( ']]>', ']]&gt;', $excerpt );
	$excerpt        = strip_tags( $excerpt );
	$excerpt_length = apply_filters( 'excerpt_length', $length );
	//update from excerpt_more to mp_excerpt more, as the behavior of product except does'nt exactl
	$excerpt_more = apply_filters( 'mp_excerpt_more', '...' );

	$words = preg_split( "/[\n\r\t ]+/", $excerpt, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY );
	if ( count( $words ) > $excerpt_length ) {
		array_pop( $words );
		$excerpt = implode( ' ', $words );
		$excerpt = $excerpt . $excerpt_more;
	} else {
		$excerpt = implode( ' ', $words );
	}

	$post = $old_post;

	return apply_filters( 'the_excerpt', $excerpt );
}

function mp_all_countries_allowed() {
	if ( is_array( mp_get_setting( 'shipping->allowed_countries', '' ) ) ) {
		$allowed_countries = mp_get_setting( 'shipping->allowed_countries', '' );
	} else {
		$allowed_countries = explode( ',', mp_get_setting( 'shipping->allowed_countries', '' ) );
	}
	$key = array_search( 'all_countries', $allowed_countries );
	if ( is_numeric( $key ) ) {
		return true;
	} else {
		return false;
	}
}

if ( ! function_exists( 'mp_show_cart' ) ) {
	/**
	 * @param string $context
	 * @param null $checkoutstep
	 * @param bool|true $echo
	 *
	 * @return string
	 */
	function mp_show_cart( $context = '', $checkoutstep = null, $echo = true ) {
		$content = '';

		if ( get_the_ID() == mp_get_setting( 'pages->checkout' ) && $context != 'widget' ) {
			$content = MP_Checkout::get_instance()->display( array(
				'echo' => false
			) );
		} elseif ( get_the_ID() == mp_get_setting( 'pages->cart' ) ) {
			$content = MP_Cart::get_instance()->display( array(
				'echo' => false
			) );
		}

		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
	}
}

if ( ! function_exists( 'mp_items_in_cart' ) ) :
	/**
	 * Determine if there are any items in the cart
	 *
	 * @retuns bool whether items are in the cart for the current user.
	 */
	function mp_items_in_cart() {
		if ( mp_items_count_in_cart() ) {
			return true;
		} else {
			return false;
		}
	}
endif;

if ( ! function_exists( 'mp_items_count_in_cart' ) ) :
	/**
	 * Determine count of any items in the cart
	 *
	 * @retuns int number of items that are in the cart for the current user.
	 */
	function mp_items_count_in_cart() {
		return mp_cart()->item_count( false, false );
	}
endif;

if ( ! function_exists( 'mp_category_list' ) ) :
	/**
	 * Retrieve product's category list in either HTML list or custom format.
	 *
	 * @param int $product_id Optional. Post ID to retrieve categories.
	 * @param string $before Optional. Before list.
	 * @param string $sep Optional. Separate items using this.
	 * @param string $after Optional. After list.
	 */
	function mp_category_list( $product_id = false, $before = '', $sep = ', ', $after = '' ) {
		$terms = get_the_term_list( $product_id, 'product_category', $before, $sep, $after );
		if ( $terms ) {
			return $terms;
		} else {
			$return = __( 'Uncategorized', 'mp' );
		}

		return apply_filters( 'mp_category_list', $return, $product_id, $before, $sep, $after );
	}
endif;


if ( ! function_exists( 'mp_tag_list' ) ) :
	/**
	 * Retrieve product's tag list in either HTML list or custom format.
	 *
	 * @param int $product_id Optional. Post ID to retrieve categories.
	 * @param string $before Optional. Before list.
	 * @param string $sep Optional. Separate items using this.
	 * @param string $after Optional. After list.
	 */
	function mp_tag_list( $product_id = false, $before = '', $sep = ', ', $after = '' ) {
		$return = '';
		$terms  = get_the_term_list( $product_id, 'product_tag', $before, $sep, $after );
		if ( $terms ) {
			return $terms;
		}

		return apply_filters( 'mp_tag_list', $return, $product_id, $before, $sep, $after );
	}
endif;

if ( ! function_exists( 'mp_get_plugin_slug' ) ) {
	function mp_get_plugin_slug() {
		if ( file_exists( dirname( __FILE__ ) . '/includes/admin/dash-notice/wpmudev-dash-notification.php' ) ) {
			return 'marketpress/marketpress.php';
		} else {
			return 'wordpress-ecommerce/marketpress.php';
		}
	}
}

if ( ! function_exists( 'mp_product_title' ) ) :
	/*
	 * function mp_product_title
	 * Displays a title of a single product according to preference
	 *
	 * @param bool $echo Optional, whether to echo or return
	 * @param int $product_id the ID of the product to display
	 * @param bool $link Whether to display title with or without a link
	 * @param bool $formated Whether to display formated text (i.e h3 with a class) or not (just pure text)
	 * @param string $html_tag title surrounding HTML tag (i.e. <h3>title</h3>)
	 * @param string $css_class add custom css class to the title
	 * @param string $microdata add additional information to HTML content which is more descriptive and suitable for search engines (learn more here http://schema.org/docs/gs.html)
	 */

	function mp_product_title( $product_id, $echo = true, $link = false, $formated = true, $html_tag = 'h3', $css_class = 'mp_product_name', $microdata = 'itemprop="name"' ) {
		global $mp;

		$post = get_post( $product_id );

		if ( $link ) {
			$title = '<a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a>';
		} else {
			$title = $post->post_title;
		}

		if ( $formated ) {
			$before_title = '<' . $html_tag . ' ' . $microdata . ' class="entry-title ' . $css_class . '">';
			$after_title  = '</' . $html_tag . '>';
		} else {
			$before_title = '<span class="entry-title">';
			$after_title  = '</span>';
		}

		$return = apply_filters( 'mp_product_title', $before_title . $title . $after_title, $product_id, $link, $formated, $html_tag, $css_class, $microdata );

		if ( $echo ) {
			echo $return;
		} else {
			return $return;
		}
	}
endif;


if ( ! function_exists( 'mp_product_description' ) ) :
	/*
	 * function mp_product_description
	 * Displays a title of a single product according to preference
	 *
	 * @param bool $echo Optional, whether to echo or return
	 * @param int $product_id the ID of the product to display
	 * @param bool/string $content Whether and what type of content to display. Options are false, 'full', or 'excerpt'. Default 'full'
	 * @param string $html_tag title surrounding HTML tag (i.e. <div>title</div>)
	 * @param string $css_class add custom css class to the description
	 * @param string $microdata add additional information to HTML content which is more descriptive and suitable for search engines (learn more here http://schema.org/docs/gs.html)
	 */

	function mp_product_description( $product_id, $echo = true, $content = 'full', $html_tag = true, $css_class = 'mp_product_content', $microdata = 'itemprop="description"' ) {
		global $mp;

		$post        = get_post( $product_id );
		$description = '';

		if ( $content == 'excerpt' ) {
			$description .= $post->post_excerpt;
		} else {
			$description .= apply_filters( 'the_content', $post->post_content );
		}

		if ( $html_tag ) {
			$before_description = '<div ' . $microdata . ' class="' . $css_class . '">';
			$after_description  = '</div>';
		} else {
			$before_description = '';
			$after_description  = '';
		}

		$return = apply_filters( 'mp_product_description', $before_description . $description . $after_description, $product_id, $content, $html_tag, $css_class, $microdata );

		if ( $echo ) {
			echo $return;
		} else {
			return $return;
		}
	}
endif;


if ( ! function_exists( 'mp_product_meta' ) ) :
	/*
	 * function mp_product_meta
	 * Displays the product meta box
	 *
	 * @param bool $echo Optional, whether to echo or return
	 * @param string $context Options are list or single
	 * @param int $product_id The post_id for the product. Optional if in the loop
	 * @param sting $label A label to prepend to the price. Defaults to "Price: "
	 * @param string $html_tag title surrounding HTML tag (i.e. <div>title</div>)
	 * @param string $css_class add custom css class to the description
	 */

	function mp_product_meta( $echo = true, $context = 'context', $label = true, $product_id = null, $html_tag = true, $css_class = 'mp_product_meta' ) {

		if ( $html_tag ) {
			$content = '<div class="' . $css_class . '">';
		}
		$content .= mp_product_price( false, $product_id, $label );
		$content .= mp_buy_button( false, $context, $product_id );
		if ( $html_tag ) {
			$content .= '</div>';
		}

		$content = apply_filters( 'mp_product_meta', $content, $context, $label, $product_id, $html_tag, $css_class );

		if ( $echo ) {
			echo $content;
		} else {
			return $content;
		}
	}
endif;

if ( ! function_exists( 'mp_get_ajax_url' ) ) {
	function mp_get_ajax_url( $path = 'admin-ajax.php' ) {
		$ajax_url = admin_url( $path );
		if ( ! is_ssl() && force_ssl_admin() ) {
			//this case the frontend is non ssl, meanwhile backend is ssl, and that will make the cookies
			//wrong, need to fix it
			$ajax_url = admin_url( $path, 'http' );
		}

		return $ajax_url;
	}
}
