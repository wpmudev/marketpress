<?php

/**
 * Display Global Products tag cloud.
 *
 * @param bool $echo Optional. Whether or not to echo.
 * @param int $limit Optional. How many tags to display.
 * @param string $seperator Optional. String to seperate tags by.
 * @param string $include Optional. What to show, 'tags', 'categories', or 'both'.
 */
function mp_global_tag_cloud( $echo = true, $limit = 45, $seperator = ' ', $include = 'both' ) {
	global $wpdb;
	$settings = get_site_option( 'mp_network_settings' );

	//include categories as well
	if ( $include == 'tags' ) {
		$where = " WHERE t.type = 'product_tag'";
	} else if ( $include == 'categories' ) {
		$where = " WHERE t.type = 'product_category'";
	}

	$limit = intval( $limit );

	$tags = $wpdb->get_results( "SELECT name, slug, type, count(post_id) as count FROM {$wpdb->base_prefix}mp_terms t LEFT JOIN {$wpdb->base_prefix}mp_term_relationships r ON t.term_id = r.term_id$where GROUP BY t.term_id ORDER BY count DESC LIMIT $limit", ARRAY_A );

	if ( ! $tags ) {
		return;
	}

	$tags = apply_filters( 'mp_global_tag_cloud_tags', $tags );

	//sort by name
	foreach ( $tags as $tag ) {
		//skip empty tags
		if ( $tag['count'] == 0 ) {
			continue;
		}

		if ( $tag['type'] == 'product_category' ) {
			$tag['link'] = get_home_url( mp_main_site_id(), $settings['slugs']['marketplace'] . '/' . $settings['slugs']['categories'] . '/' . $tag['slug'] . '/' );
		} else if ( $tag['type'] == 'product_tag' ) {
			$tag['link'] = get_home_url( mp_main_site_id(), $settings['slugs']['marketplace'] . '/' . $settings['slugs']['tags'] . '/' . $tag['slug'] . '/' );
		}

		$sorted_tags[ $tag['name'] ] = $tag;
	}

	ksort( $sorted_tags );

	//remove keys
	$tags = array();
	foreach ( $sorted_tags as $tag ) {
		$tags[] = $tag;
	}

	$counts      = array();
	$real_counts = array(); // For the alt tag
	foreach ( (array) $tags as $key => $tag ) {
		$real_counts[ $key ] = $tag['count'];
		$counts[ $key ]      = $tag['count'];
	}

	$min_count = min( $counts );
	$spread    = max( $counts ) - $min_count;
	if ( $spread <= 0 ) {
		$spread = 1;
	}
	$font_spread = 22 - 8;
	if ( $font_spread < 0 ) {
		$font_spread = 1;
	}
	$font_step = $font_spread / $spread;

	$a = array();

	foreach ( $tags as $key => $tag ) {
		$count      = $counts[ $key ];
		$real_count = $real_counts[ $key ];
		$tag_link   = '#' != $tag['link'] ? esc_url( $tag['link'] ) : '#';
		$tag_id     = isset( $tags[ $key ]['id'] ) ? $tags[ $key ]['id'] : $key;
		$tag_name   = $tags[ $key ]['name'];
		$a[]        = "<a href='$tag_link' class='tag-link-$tag_id' title='" . esc_attr( $real_count ) . ' ' . __( 'Products', 'mp' ) . "' style='font-size: " .
		              ( 8 + ( ( $count - $min_count ) * $font_step ) )
		              . "pt;'>$tag_name</a>";
	}

	$return = join( $seperator, $a );

	if ( $echo ) {
		echo apply_filters( 'mp_global_tag_cloud', '<div id="mp_tag_cloud">' . $return . '</div>' );
	}

	return apply_filters( 'mp_global_tag_cloud', '<div id="mp_tag_cloud">' . $return . '</div>' );
}

if ( ! function_exists( 'mp_global_list_products' ) ) {

	function mp_global_list_products( $args = array() ) {
		global $wpdb;

		$func_args = func_get_args();
		$args      = mp_parse_args( $func_args, mp()->defaults['list_products'] );

		if ( ! isset( $args['nopaging'] ) ) {
			$args['nopaging'] = false;
		}
		if ( ! isset( $args['version'] ) ) {
			$args['version'] = '';
		}

		if ( isset( $args['widget_id'] ) && ! empty( $args['widget_id'] ) ) {
			$args['widget_id'] = str_replace( 'mp_global_product_list_widget-', '', $args['widget_id'] );
			
			$widget_settings = get_option( 'widget_mp_global_product_list_widget' );

			if ( isset( $widget_settings[ $args['widget_id'] ] ) ) {
				$args['as_list'] = true;
				$args['context'] = 'widget';
				//$args['nopaging']                                  = true;
				$args['version']                                   = '3';
				$widget_settings[ $args['widget_id'] ]['order']    = $args['order'];
				$widget_settings[ $args['widget_id'] ]['order_by'] = $args['order_by'];
				$args                                              = array_merge( $args, $widget_settings[ $args['widget_id'] ] );
			}
		}


// Init query params
		$query = array();

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
				$query['paged']  = intval( $args['page'] );
				$query['offset'] = ( $query['paged'] - 1 ) * $query['posts_per_page'];
			} elseif ( get_query_var( 'paged' ) != '' ) {
				$query['paged']  = $args['page'] = intval( get_query_var( 'paged' ) );
				$query['offset'] = ( $query['paged'] - 1 ) * $query['posts_per_page'];
			} elseif ( get_query_var( 'page' ) != '' ) {
				$query['paged'] = $args['page'] = intval( get_query_var( 'page' ) );
				$query['offset'] = ( $query['paged'] - 1 ) * $query['posts_per_page'];
			} else {
				$query['paged']  = 1;
				$query['offset'] = 0;
			}

// Get order by
			if ( ! is_null( $args['order_by'] ) ) {
				if ( 'price' == $args['order_by'] ) {
					//$query['meta_key'] = 'regular_price';
					$query['orderby'] = 'price';
				} else if ( 'sales' == $args['order_by'] ) {
					//$query['meta_key'] = 'mp_sales_count';
					$query['orderby'] = 'sales_count';
				} elseif ( 0 != $args['order_by'] ) {
					$query['orderby'] = $args['order_by'];
				} elseif ( 'title' == $args['order_by'] ) {
					$query['orderby'] = 'post_title';
				} elseif ( 'date' == $args['order_by'] ) {
					$query['orderby'] = 'post_date';
				} elseif ( 'rand' == $args['order_by'] ) {
					$query['orderby'] = 'RAND()';
				} elseif ( 'author' == $args['order_byd'] ) {
					$query['orderby'] = 'post_author';
				}
			} elseif ( 'price' == mp_get_setting( 'order_by' ) ) {
				///$query['meta_key'] = 'regular_price';
				$query['orderby'] = 'price';
			} elseif ( 'sales' == mp_get_setting( 'order_by' ) ) {
				//$query['meta_key'] = 'mp_sales_count';
				$query['orderby'] = 'sales_count';
			} elseif ( 'title' == mp_get_setting( 'order_by' ) ) {
				//$query['meta_key'] = 'mp_sales_count';
				$query['orderby'] = 'post_title';
			} elseif ( 'date' == mp_get_setting( 'order_by' ) ) {
				//$query['meta_key'] = 'mp_sales_count';
				$query['orderby'] = 'post_date';
			} elseif ( 'rand' == mp_get_setting( 'order_by') ) {
				$query['orderby'] = 'RAND()';
			} elseif ( 'author' == mp_get_setting( 'order_by') ) {
				$query['orderby'] = 'post_author';
			} else {
				$query['orderby'] = mp_get_setting( 'order_by' );
			}
		}
		
		if ( ! is_null( $args['limit'] ) ) {
			$query['posts_per_page'] = intval( $args['limit'] );
			$args['nopaging'] 		 = true;
		}

		if ( ! is_null( $args['category'] ) ) {
			$query['taxonomy'] = 'product_category';
			$query['term']     = $args['category'];
		}

		if ( ! is_null( $args['tag'] ) ) {
			$query['taxonomy'] = 'product_tag';
			$query['term']     = $args['tag'];
		}

// Get order direction
		$query['order'] = mp_get_setting( 'order' );
		if ( ! is_null( $args['order'] ) ) {
			$query['order'] = $args['order'];
		}

		//build SQL
		$sql   = "SELECT SQL_CALC_FOUND_ROWS products.* FROM {$wpdb->base_prefix}mp_products products";
		$join  = "";
		$where = " WHERE post_status = 'publish' AND blog_public = 1";
		$group = "";
		
		if ( ! empty( $args['category'] ) || ! empty( $args['tag'] ) ) {
			$join .= " INNER JOIN {$wpdb->base_prefix}mp_term_relationships rel ON rel.post_id = products.id";
			$join .= " INNER JOIN {$wpdb->base_prefix}mp_terms terms ON terms.term_id = rel.term_id";
			
			if( ! empty( $args['category'] ) ) {
				if ( is_numeric( $args['category'] ) ) {
					$where .= $wpdb->prepare( " AND terms.term_id=%d", $args['category'] );
				} else {
					$where .= $wpdb->prepare( " AND terms.slug=%s", $args['category'] );
				}
			}
			
			if( ! empty( $args['tag'] ) ) {
				if ( is_numeric( $args['tag'] ) ) {
					$where .= $wpdb->prepare( " AND terms.term_id=%d", $args['tag'] );
				} else {
					$where .= $wpdb->prepare( " AND terms.slug=%s", $args['tag'] );
				}
			}
			
			$group .= " GROUP BY products.post_id";
		}

		$order = "";
		if ( mp_arr_get_value( 'orderby', $query, '' ) != '' ) {
			$orderby = mp_arr_get_value( 'orderby', $query, '' );
			switch ( $orderby ) {
				case 'title':
					$orderby = 'post_title';
					break;
				case 'date':
					$orderby = 'post_modified';
					break;

			}
			$order = " ORDER BY " . $orderby;
		}
		if ( mp_arr_get_value( 'order', $query, '' ) != '' && strlen( $order ) > 0 ) {
			$order .= " " . mp_arr_get_value( 'order', $query );
		}
		$paging = "";
		if ( mp_arr_get_value( 'posts_per_page', $query, 0 ) > 0 ) {
			$limit  = mp_arr_get_value( 'posts_per_page', $query, 0 );
			$offset = mp_arr_get_value( 'offset', $query );
			$paging = " LIMIT $offset,$limit";
		}

		$sql .= $join . $where . $group . $order . $paging;
		$custom_query = $wpdb->get_results( $sql );
		$count        = $wpdb->get_var( "SELECT FOUND_ROWS();" );
		// Get layout type
		$layout_type = mp_get_setting( 'list_view' );

		if ( ! is_null( $args['list_view'] ) ) {
			$layout_type = $args['list_view'] ? 'list' : 'grid';
		}
		
		// Build content
		$content = '';

		if ( ! mp_doing_ajax() ) {
			$per_page = ( is_null( $args['per_page'] ) ) ? null : $args['per_page'];
			//$content .= ( ( ( is_null( $args['filters'] ) && 1 == mp_get_setting( 'show_filters' ) ) || $args['filters'] ) && mp_arr_get_value( 'context', $args, null ) != 'widget' ) ? mp_global_products_filter( false, $per_page, $custom_query, $args ) : mp_global_products_filter( true, $per_page, $custom_query, $args );
			if( isset( $args['context'] ) && $args['context'] == 'widget' ) {
				$content .= ( ( ( is_null( $args['filters'] ) && 1 != mp_get_setting( 'hide_products_filter' ) ) || $args['filters'] ) ) ? mp_global_products_filter( false, $per_page, $custom_query, $args ) : '';
			}
		}

		$extra_id = mp_arr_get_value( 'context', $args, null ) == 'widget' ? '-widget-' . rand() : '';
		$content .= '<!-- MP Product List --><section id="mp-products' . $extra_id . '" class="hfeed mp_products mp_products-' . $layout_type . '">';

		if ( $last = count( $custom_query ) ) {
			if( isset( $args['context'] ) && $args['context'] == 'widget' ) {
				$content .= _mp3_global_products_html_widget( $custom_query, $args );
			} else { 
				$content .= _mp3_global_products_html_grid( $custom_query, $args );
			}
		} else {
			$content .= '<div id="mp_no_products">' . apply_filters( 'mp_global_product_list_none', __( 'No Products', 'mp' ) ) . '</div>';
		}

		$content .= '</section><!-- end mp-products -->';

		$content .= ( ! $args['nopaging'] ) ? mp_global_products_nav( false, mp_arr_get_value( 'posts_per_page', $query ), $count ) : '';

		/**
		 * Filter product list html
		 *
		 * @since 3.0
		 *
		 * @param string $content The current html content.
		 * @param array $args The arguments passed to mp_list_products
		 */
		$content = apply_filters( 'mp_global_list_products', $content, $args );

		if ( $args['echo'] ) {
			echo $content;
		} else {
			return $content;
		}
	}

}

if ( ! function_exists( 'mp_global_products_nav' ) ) {
	function mp_global_products_nav( $echo = true, $per_page, $count ) {
		$html      = '';
		$paged     = 1;
		$max_pages = ceil( $count / $per_page );

		if ( $max_pages > 1 ) {
			$big = 999999999;
			
			if ( get_query_var( 'paged' ) != '' ) {
				$paged  = intval( get_query_var( 'paged' ) );
			} elseif ( get_query_var( 'page' ) != '' ) {
				$paged  = intval( get_query_var( 'page' ) );
			}

			$html = '
				<nav class="mp_listings_nav">';

			$html .= paginate_links( array(
				'base'         => '?paged=%#%', //'%_%',
				'format'       => '', //?paged=%#%
				'total'        => $max_pages,
				'current'      => max( 1, $paged ),
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
		$html = apply_filters( 'mp_global_products_nav', $html, $per_page );

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}

if ( ! function_exists( 'mp_global_products_filter' ) ) :

	/**
	 * Display product filters
	 *
	 * @since 3.0
	 *
	 * @param bool $hidden Are the filters hidden or visible?
	 * @param int $per_page The number of posts per page
	 * @param WP_Query $query
	 * @param array $args
	 *
	 * @return string
	 */
	function mp_global_products_filter( $hidden = false, $per_page = null, $query = null, $args = array() ) {
		$current_order = strtolower( get_query_var( 'order_by' ) . '-' . get_query_var( 'order' ) );
		$options       = array(
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
		$options_html  = '';
		foreach ( $options as $k => $t ) {
			$value = $t[0] . '- ' . $t [1];
			$options_html .= '<option value="' . $value . '" ' . selected( $value, $current_order, false ) . '>' . $t[2] . '</option>';
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = '-1';
		}

		global $wpdb;
		
		/*
		
		Product category filter is not used anymore
		
		$sql     = "SELECT * FROM {$wpdb->base_prefix}mp_terms WHERE `type`='product_category'";
		$results = $wpdb->get_results( $sql );

		$terms = '<select name="product_category" id="mp-product-category" class="mp_select2">';
		$terms .= '<option value="-1">' . __( "Show All", "mp" ) . '</option>';
		foreach ( $results as $term ) {
			$terms .= '<option value="' . $term->term_id . '">' . $term->name . '</option>';
		}
		$terms .= '</select>';
		
		<div class="mp_form_field mp_products_filter_field mp_products_filter_category" data-placeholder="' . __( 'Product Category', 'mp' ) . '">
			<label for="mp_product_category" class="mp_form_label">' . __( 'Category', 'mp' ) . '</label>
			' . $terms . '
		</div><!-- mp_listing_products_category -->
		
		*/
		$return = '
<a name="mp-product-list-top"></a>
<div class="mp_list_filter"' . ( ( $hidden ) ? ' style="display:none"' : '' ) . '>
	<form id="mp_global_product_list_refine" name="mp_global_product_list_refine" class="mp-form mp_global_product_list_refine clearfix" method="get">
		<div class="mp_form_fields">
		
		<div class="mp_form_field mp_products_filter_field mp_products_filter_orderby">
			<label for="mp_sort_orderby" class="mp_form_label">' . __( 'Order By', 'mp' ) . '</label>
			<select id="mp-sort-order" class="mp_select2" name="order" data-placeholder="' . __( 'Product Category', 'mp' ) . '">
				' . $options_html . '
			</select>
		</div>' .
		          ( ( is_null( $per_page ) ) ? '' : '<input type="hidden" name="per_page" value="' . $per_page . '">' ) . '
		<input type="hidden" name="page" value="' . max( get_query_var( 'paged' ), 1 ) . '">
		<input type="hidden" name="widget_id" value="' . $args['widget_id'] . '">
		</div>
	</form>
</div>';

		return apply_filters( 'mp_global_products_filter', $return );
	}

endif;

if ( ! function_exists( '_mp_global_products_html_list' ) ) {

	function _mp_global_products_html_list( $custom_query ) {
		return _mp_global_products_html( 'list', $custom_query );
	}

}

if ( ! function_exists( '_mp_global_products_html_grid' ) ) {

	function _mp_global_products_html_grid( $custom_query, $args = array() ) {
		return _mp_global_products_html( 'grid', $custom_query, $args );
	}

}

if ( ! function_exists( '_mp3_global_products_html_grid' ) ) {

	function _mp3_global_products_html_grid( $custom_query, $args = array() ) {
		return _mp3_global_products_html( 'grid', $custom_query, $args );
	}
}

if ( ! function_exists( '_mp3_global_products_html_widget' ) ) {

	function _mp3_global_products_html_widget( $custom_query, $args = array() ) {
		return _mp3_global_products_html( 'widget_List', $custom_query, $args );
	}
}

if ( ! function_exists( '_mp3_global_products_html' ) ) {

	function _mp3_global_products_html( $view, $custom_query, $args = array() ) {
		//$view    = 'widget_list';
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

		if ( count( $custom_query ) ) {
			foreach ( $custom_query as $post ) {
				$blog_id    = $post->blog_id;
				$product_id = $post->post_id;

				switch_to_blog( $blog_id );

				$product = new MP_Product( $product_id );

				$align = null;
				if ( 'list' == mp_get_setting( 'list_view' ) ) {
					$align = mp_get_setting( 'image_alignment_list' );
				}
				if ( mp_arr_get_value( 'show_thumbnail', $args, - 1 ) == 0 ) {
					$img = '';
				} else {
					$img = $product->image( false, 'list', null, $align, mp_arr_get_value( 'show_thumbnail_placeholder', $args, true ) );
				}
				$excerpt = '';
				if ( mp_arr_get_value( 'context', $args ) == 'widget' ) {
					if ( mp_arr_get_value( 'text', $args ) == 'excerpt' ) {
						$excerpt = '<div class="mp_product_excerpt">' . $product->excerpt( $post->post_excerpt, $post->post_content ) . '</div><!-- end mp_product_excerpt -->';
					}

					if ( mp_arr_get_value( 'text', $args ) == 'content' ) {
						$excerpt = '<div class="mp_product_excerpt">' . $product->content() . '</div><!-- end mp_product_excerpt -->';
					}
				} else {
					$excerpt = mp_get_setting( 'show_excerpts' ) ? '<div class="mp_product_excerpt"><p>' . $product->excerpt() . '</div></p><!-- end mp_product_excerpt -->' : '';
				}
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

				if ( ! empty( $img ) ) {
					$img = '<div class="mp_product_images">' . $img . '</div>';
				}

				$button = '<a class="mp_button mp_link-buynow" href="' . $product->url( false ) . '">' . __( 'Buy Now', 'mp' ) . '</a>';

				$html .= '
				<div class="mp_product_item' . ( ( 'grid' == $view ) ? ' mp_product_item-col-' . $per_row : '' ) . '">
					<div itemscope itemtype="http://schema.org/Product" class="mp_product' . ( ( strlen( $img ) > 0 ) ? ' mp_product-has-image' . $align_class : '' ) . ' ' . implode( $class, ' ' ) . '">
						' . $img . '
						<div class="mp_product_details">

							<div class="mp_product_meta">
								<h3 class="mp_product_name entry-title" itemprop="name">
	 								<a href="' . $product->url( false ) . '">' . $product->title( false ) . '</a>
	 							</h3>
								' . ( mp_arr_get_value( 'show_price', $args, - 1 ) != 0 ? $product->display_price( false ) : null ) . '
 								' . $mp_product_list_content . '

 								<div class="mp_social_shares">
									' . $pinit . '
									' . $fb . '
									' . $twitter . '
								</div><!-- end mp_social_shares -->

							</div><!-- end mp_product_meta -->

							<div class="mp_product_callout">
								' . $button . '
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
			}
		} else {
			$html .= '<div class="mp_widget_empty">' . __( 'No Products', 'mp' ) . '</div><!-- end mp_widget_empty -->';
		}

		switch_to_blog( $current_blog_id );


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

if ( ! function_exists( '_mp_global_products_html' ) ) {

	function _mp_global_products_html( $view, $custom_query, $args = array() ) {

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

		foreach ( $custom_query as $post ) {
			$blog_id    = $post->blog_id;
			$product_id = $post->post_id;
			switch_to_blog( $blog_id );

			$product = new MP_Product( $product_id );

			if ( ! is_object( $product ) || $product->exists() == false ) {
				continue;
			}

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
								' . $text . '
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
		global $wpdb;

		$defaults = array(
			'limit'      => 50,
			'order_by'   => 'count',
			'order'      => 'DESC',
			'show_count' => 0
		);

		$atts = wp_parse_args( $atts, $defaults );
		extract( $atts );

		//build the sql
		$sql     = $wpdb->prepare( "SELECT t.* FROM {$wpdb->base_prefix}mp_terms AS t 
			LEFT JOIN {$wpdb->base_prefix}mp_term_relationships AS r ON t.term_id = r.term_id
			WHERE `type`=%s AND r.public = 1 
			GROUP BY t.term_id HAVING COUNT(r.term_id) > 0", $taxonomy );
		$results = $wpdb->get_results( $sql );
		
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
		$html = '';
		if( ! empty( $results ) ) {
			$html .= '<ul id="mp_category_list">';
			foreach ( $results as $row ) {
				$html .= '<li class="cat-item cat-item-' . $row->term_id . '">
				<a href="' . mp_global_taxonomy_url( $row->slug, $taxonomy ) . '">' . $row->name . '</a>
				</li>';
			}
			$html .= '</ul>';
		} else {
			$html .= '<div id="mp_category_list">' . __( 'No Categories', 'mp' ) . '</div>';
		}

		return $html;
	}

}

if ( ! function_exists( '_mp_global_tags_cloud' ) ) {

	function _mp_global_tags_cloud( $results, $taxonomy ) {
		$html = '<div id="mp_tag_cloud">';
		if( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$html .= '<a href="' . mp_global_taxonomy_url( $row->slug, $taxonomy ) . '" class="tag-link tag-link-' . $row->term_id . '" title="">' . $row->name . '</a> ';
			}
		} else {
			$html .= __( 'No Tags', 'mp' );
		}
		$html .= '</div>';

		return $html;
	}
}

if ( ! function_exists( 'mp_global_term_exist' ) ) {
	/**
	 * @param $slug
	 * @param $taxonomy
	 *
	 * @return array|null|object|void
	 */
	function mp_global_term_exist( $slug, $taxonomy ) {
		global $wpdb;
		if ( ! is_array( $slug ) ) {
			$slug = array( $slug );
		}

		if ( is_array( $slug ) ) {
			$in = '(' . "'" . implode( "','", $slug ) . "'" . ')';
		}
		$sql = "SELECT * FROM {$wpdb->base_prefix}mp_terms WHERE `slug` IN $in AND type='$taxonomy'";

		return $wpdb->get_row( $sql );
	}
}

if ( ! function_exists( 'mp_global_taxonomy_url' ) ) {
	function mp_global_taxonomy_url( $slug, $type ) {
		switch ( $type ) {
			case 'product_category':
				$type = 'network_categories';
				break;
			case 'product_tag':
				$type = 'network_tags';
				break;
		}
		$page_id = mp_get_network_setting( 'pages->' . $type );
		switch_to_blog( 1 );
		$url = site_url( trailingslashit( get_page_uri( $page_id ) . '/' . $slug ) );
		restore_current_blog();

		return $url;
	}
}

if ( ! function_exists( 'mp_global_get_terms' ) ) {
	//todo update $args function
	function mp_global_get_terms( $taxonomy, $args = array() ) {
		global $wpdb;
		$sql     = $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}mp_terms WHERE `type`=%s", $taxonomy );
		$results = $wpdb->get_results( $sql );

		return $results;
	}
}