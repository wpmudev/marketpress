<?php

if ( ! function_exists( 'mp_global_list_products' ) ) {
	function mp_global_list_products( $args = array() ) {
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
				$query['posts_per_page'] = intval( mp_get_setting( 'per_page' ) );
			}

// Figure out page
			if ( ! is_null( $args['page'] ) ) {
				$query['paged'] = intval( $args['page'] );
			} elseif ( get_query_var( 'paged' ) != '' ) {
				$query['paged'] = $args['page'] = intval( get_query_var( 'paged' ) );
			}

// Get order by
			if ( ! is_null( $args['order_by'] ) ) {
				if ( 'price' == $args['order_by'] ) {
					$query['meta_key'] = 'regular_price';
					$query['orderby']  = 'meta_value_num';
				} else if ( 'sales' == $args['order_by'] ) {
					$query['meta_key'] = 'mp_sales_count';
					$query['orderby']  = 'meta_value_num';
				} else {
					$query['orderby'] = $args['order_by'];
				}
			} elseif ( 'price' == mp_get_setting( 'order_by' ) ) {
				$query['meta_key'] = 'regular_price';
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

		// The Query
		$custom_query = new Network_Query( $query );

		// Get layout type
		$layout_type = mp_get_setting( 'list_view' );
		if ( ! is_null( $args['list_view'] ) ) {
			$layout_type = $args['list_view'] ? 'list' : 'grid';
		}

		// Build content
		$content = '';

		if ( ! mp_doing_ajax() ) {
			$per_page = ( is_null( $args['per_page'] ) ) ? null : $args['per_page'];
			//$content .= ( ( is_null( $args['filters'] ) && 1 == mp_get_setting( 'show_filters' ) ) || $args['filters'] ) ? mp_products_filter( false, $per_page, $custom_query ) : mp_products_filter( true, $per_page, $custom_query );
		}

		$content .= '<div id="mp_product_list" class="clearfix hfeed mp_' . $layout_type . '">';

		if ( $last = $custom_query->post_count ) {
			$content .= $layout_type == 'grid' ? _mp_global_products_html_grid( $custom_query ) : _mp_global_products_html_grid( $custom_query );
		} else {
			$content .= '<div id="mp_no_products">' . apply_filters( 'mp_global_product_list_none', __( 'No Products', 'mp' ) ) . '</div>';
		}

		$content .= '</div>';

		$content .= ( ! $args['nopaging'] ) ? mp_products_nav( false, $custom_query ) : '';

		/**
		 * Filter product list html
		 *
		 * @since 3.0
		 *
		 * @param string $content The current html content.
		 * @param array $args The arguments passed to mp_list_products
		 */
		$content = apply_filters( 'mp_global_list_products', $content, $args );
		network_reset_postdata();
		if ( $args['echo'] ) {
			echo $content;
		} else {
			return $content;
		}
	}
}

if ( ! function_exists( '_mp_global_products_html_list' ) ) {
	function _mp_global_products_html_list( $custom_query ) {
		return _mp_global_products_html( 'list', $custom_query );
	}
}

if ( ! function_exists( '_mp_global_products_html_grid' ) ) {
	function _mp_global_products_html_grid( $custom_query ) {
		return _mp_global_products_html( 'grid', $custom_query );
	}
}

if ( ! function_exists( '_mp_global_products_html' ) ) {
	function _mp_global_products_html( $view, $custom_query ) {
		$html    = '';
		$per_row = (int) mp_get_setting( 'per_row' );
		$width   = round( 100 / $per_row, 1 ) . '%';
		$column  = 1;

		//get image width
		if ( mp_get_setting( 'list_img_size' ) == 'custom' ) {
			$img_width = mp_get_setting( 'list_img_width' ) . 'px';
		} else {
			$size      = mp_get_setting( 'list_img_size' );
			$img_width = get_option( $size . '_size_w' ) . 'px';
		}
		$current_blog_id = get_current_blog_id();
		foreach ( $custom_query->get_posts() as $post ) {
			switch_to_blog( $post->BLOG_ID );
			$product = new MP_Product( $post->ID );

			$align = null;
			if ( 'list' == mp_get_setting( 'list_view' ) ) {
				$align = mp_get_setting( 'image_alignment_list' );
			}
			$img = $product->image( false, 'list', null, $align );

			$excerpt                 = mp_get_setting( 'show_excerpts' ) ? '<div class="mp_excerpt">' . $product->excerpt() . '</div>' : '';
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
					$html .= '<div class="mp_grid_row">';
					$column ++;
				} elseif ( $column == $per_row ) {
					$class[] = 'last';
					$column  = 1;
				} else {
					$column ++;
				}
			}

			$class = array_filter( $class, create_function( '$s', 'return ( ! empty( $s ) );' ) );

			$html .= '
				<div itemscope itemtype="http://schema.org/Product" class="hentry mp_one_tile ' . implode( $class, ' ' ) . ' ' . ( ( 'grid' == $view ) ? 'mp-grid-col-' . $per_row : '' ) . '"><!--style="width: ' . $width . '"-->
					<div class="mp_one_product clearfix"><!--' . ( ( 'grid' == $view ) ? ' style="width:' . $img_width . '"' : '' ) . '-->
						<div class="mp_product_detail">
							' . $img . '
							<div class="mp_product_content">
								<h3 class="mp_product_name entry-title" itemprop="name">
									<a href="' . $product->url( false ) . '">' . $product->title( false ) . '</a>
								</h3>
								<div class="mp-social-shares">
									' . $pinit . '
									' . $fb . '
									' . $twitter . '
								</div>
									' . $mp_product_list_content . '
							</div>
						</div>

						<div class="mp_price_buy">
							' . $product->display_price( false ) . '
							<a class="mp-button mp_link_buynow" href="' . esc_url( $product->url( false ) ) . '">' . __( 'Buy Now &raquo;', 'mp' ) . '</a>
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
		}
		switch_to_blog( $current_blog_id );
		if ( $column != 1 && $view == 'grid' ) {
			$html .= '</div><!-- END .mp_grid_row -->';
		}


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
}

if ( ! function_exists( 'mp_global_taxonomy_list' ) ) :

	function mp_global_taxonomy_list( $taxonomy, $atts, $echo = true ) {
		$defaults = array(
			'limit'      => 50,
			'order_by'   => 'count',
			'order'      => 'DESC',
			'show_count' => 0
		);

		$atts = wp_parse_args( $atts, $defaults );
		extract( $atts );

		global $wpdb;
		$sql = "
		SELECT * FROM {$wpdb->base_prefix}network_terms terms, {$wpdb->base_prefix}network_term_taxonomy tt, {$wpdb->base_prefix}network_term_relationships tr
WHERE terms.`term_id` = tt.term_id AND tr.`term_taxonomy_id`= tt.`term_taxonomy_id`
AND tt.taxonomy=%s
GROUP BY terms.term_id ORDER BY {$order_by} {$order} LIMIT {$limit}
		";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $taxonomy ) );
		if ( $taxonomy == 'product_tag' ) {
			$html = _mp_global_tags_cloud( $results, $taxonomy );
		} else {
			$html = _mp_global_categories_list( $results, $taxonomy );
		}
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
endif;

if ( ! function_exists( '_mp_global_categories_list' ) ) {
	function _mp_global_categories_list( $results, $taxonomy ) {
		$html            = '<ul id="mp_category_list">';
		$current_blog_id = get_current_blog_id();
		foreach ( $results as $row ) {
			switch_to_blog( $row->blog_id );
			$url = get_term_link( $row->slug, $taxonomy );
			if ( ! is_wp_error( $url ) ) {
				$html .= '<li class="cat-item cat-item-' . $row->term_id . '">
			<a href="' . $url . '">' . $row->name . '</a>
			</li>';
			}
		}
		$html .= '</ul>';
		switch_to_blog( $current_blog_id );

		return $html;
	}
}

if ( ! function_exists( '_mp_global_tags_cloud' ) ) {
	function _mp_global_tags_cloud( $results, $taxonomy ) {
		$html            = '<div id="mp_tag_cloud">';
		$current_blog_id = get_current_blog_id();
		foreach ( $results as $row ) {
			switch_to_blog( $row->blog_id );
			$url  = get_term_link( $row->slug, $taxonomy );
			if ( ! is_wp_error( $url ) ) {
				$html .= '<a href="' . $url . '" class="tag-link tag-link-' . $row->term_id . '" title="">' . $row->name . '</a> ';
			}
		}
		$html .= '</div>';
		switch_to_blog( $current_blog_id );

		return $html;
	}
}