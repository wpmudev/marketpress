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
	if ( $include == 'tags' )
		$where	 = " WHERE t.type = 'product_tag'";
	else if ( $include == 'categories' )
		$where	 = " WHERE t.type = 'product_category'";

	$limit = intval( $limit );

	$tags = $wpdb->get_results( "SELECT name, slug, type, count(post_id) as count FROM {$wpdb->base_prefix}mp_terms t LEFT JOIN {$wpdb->base_prefix}mp_term_relationships r ON t.term_id = r.term_id$where GROUP BY t.term_id ORDER BY count DESC LIMIT $limit", ARRAY_A );

	if ( !$tags )
		return;

	$tags = apply_filters( 'mp_global_tag_cloud_tags', $tags );

	//sort by name
	foreach ( $tags as $tag ) {
		//skip empty tags
		if ( $tag[ 'count' ] == 0 )
			continue;

		if ( $tag[ 'type' ] == 'product_category' )
			$tag[ 'link' ] = get_home_url( mp_main_site_id(), $settings[ 'slugs' ][ 'marketplace' ] . '/' . $settings[ 'slugs' ][ 'categories' ] . '/' . $tag[ 'slug' ] . '/' );
		else if ( $tag[ 'type' ] == 'product_tag' )
			$tag[ 'link' ] = get_home_url( mp_main_site_id(), $settings[ 'slugs' ][ 'marketplace' ] . '/' . $settings[ 'slugs' ][ 'tags' ] . '/' . $tag[ 'slug' ] . '/' );

		$sorted_tags[ $tag[ 'name' ] ] = $tag;
	}

	ksort( $sorted_tags );

	//remove keys
	$tags	 = array();
	foreach ( $sorted_tags as $tag )
		$tags[]	 = $tag;

	$counts		 = array();
	$real_counts = array(); // For the alt tag
	foreach ( (array) $tags as $key => $tag ) {
		$real_counts[ $key ] = $tag[ 'count' ];
		$counts[ $key ]		 = $tag[ 'count' ];
	}

	$min_count	 = min( $counts );
	$spread		 = max( $counts ) - $min_count;
	if ( $spread <= 0 )
		$spread		 = 1;
	$font_spread = 22 - 8;
	if ( $font_spread < 0 )
		$font_spread = 1;
	$font_step	 = $font_spread / $spread;

	$a = array();

	foreach ( $tags as $key => $tag ) {
		$count		 = $counts[ $key ];
		$real_count	 = $real_counts[ $key ];
		$tag_link	 = '#' != $tag[ 'link' ] ? esc_url( $tag[ 'link' ] ) : '#';
		$tag_id		 = isset( $tags[ $key ][ 'id' ] ) ? $tags[ $key ][ 'id' ] : $key;
		$tag_name	 = $tags[ $key ][ 'name' ];
		$a[]		 = "<a href='$tag_link' class='tag-link-$tag_id' title='" . esc_attr( $real_count ) . ' ' . __( 'Products', 'mp' ) . "' style='font-size: " .
		( 8 + ( ( $count - $min_count ) * $font_step ) )
		. "pt;'>$tag_name</a>";
	}

	$return = join( $seperator, $a );

	if ( $echo )
		echo apply_filters( 'mp_global_tag_cloud', '<div id="mp_tag_cloud">' . $return . '</div>' );

	return apply_filters( 'mp_global_tag_cloud', '<div id="mp_tag_cloud">' . $return . '</div>' );
}

if ( !function_exists( 'mp_global_list_products' ) ) {

	function mp_global_list_products( $args = array() ) {
		// Init args
		$func_args			 = func_get_args();
		$args				 = mp_parse_args( $func_args, mp()->defaults[ 'list_products' ] );
		//$args[ 'nopaging' ]	 = false;

// Init query params
		$query = array(
			'post_type'		 => 'mp_ms_indexer',
			'post_status'	 => 'publish',
		);

// Setup pagination
		if ( (!is_null( $args[ 'paginate' ] ) && !$args[ 'paginate' ] ) || ( is_null( $args[ 'paginate' ] ) && !mp_get_setting( 'paginate' ) ) ) {
			$query[ 'nopaging' ] = $args[ 'nopaging' ]	 = true;
		} else {
// Figure out per page
			if ( !is_null( $args[ 'per_page' ] ) ) {
				$query[ 'posts_per_page' ] = intval( $args[ 'per_page' ] );
			} else {
				$query[ 'posts_per_page' ] = intval( mp_get_setting( 'per_page' ) );
			}

// Figure out page
			if ( !is_null( $args[ 'page' ] ) ) {
				$query[ 'paged' ] = intval( $args[ 'page' ] );
			} elseif ( get_query_var( 'paged' ) != '' ) {
				$query[ 'paged' ]	 = $args[ 'page' ]		 = intval( get_query_var( 'paged' ) );
			}

// Get order by
			if ( !is_null( $args[ 'order_by' ] ) ) {
				if ( 'price' == $args[ 'order_by' ] ) {
					$query[ 'meta_key' ] = 'regular_price';
					$query[ 'orderby' ]	 = 'meta_value_num';
				} else if ( 'sales' == $args[ 'order_by' ] ) {
					$query[ 'meta_key' ] = 'mp_sales_count';
					$query[ 'orderby' ]	 = 'meta_value_num';
				} else {
					$query[ 'orderby' ] = $args[ 'order_by' ];
				}
			} elseif ( 'price' == mp_get_setting( 'order_by' ) ) {
				$query[ 'meta_key' ] = 'regular_price';
				$query[ 'orderby' ]	 = 'meta_value_num';
			} elseif ( 'sales' == mp_get_setting( 'order_by' ) ) {
				$query[ 'meta_key' ] = 'mp_sales_count';
				$query[ 'orderby' ]	 = 'meta_value_num';
			} else {
				$query[ 'orderby' ] = mp_get_setting( 'order_by' );
			}
		}

// Get order direction
		$query[ 'order' ] = mp_get_setting( 'order' );
		if ( !is_null( $args[ 'order' ] ) ) {
			$query[ 'order' ] = $args[ 'order' ];
		}

		// The Query
		$custom_query	 = new WP_Query( $query );
		// Get layout type
		$layout_type	 = mp_get_setting( 'list_view' );
		if ( !is_null( $args[ 'list_view' ] ) ) {
			$layout_type = $args[ 'list_view' ] ? 'list' : 'grid';
		}

		// Build content
		$content = '';

		if ( !mp_doing_ajax() ) {
			$per_page = ( is_null( $args[ 'per_page' ] ) ) ? null : $args[ 'per_page' ];
			$content .= ( ( is_null( $args[ 'filters' ] ) && 1 == mp_get_setting( 'show_filters' ) ) || $args[ 'filters' ] ) ? mp_global_products_filter( false, $per_page, $custom_query ) : mp_global_products_filter( true, $per_page, $custom_query );
		}

		$content .= '<div id="mp_product_list" class="clearfix hfeed mp_' . $layout_type . '">';

		if ( $last = $custom_query->post_count ) {
			$content .= $layout_type == 'grid' ? _mp_global_products_html_grid( $custom_query ) : _mp_global_products_html_grid( $custom_query );
		} else {
			$content .= '<div id="mp_no_products">' . apply_filters( 'mp_global_product_list_none', __( 'No Products', 'mp' ) ) . '</div>';
		}

		$content .= '</div>';

		$content .= (!$args[ 'nopaging' ] ) ? mp_products_nav( false, $custom_query ) : '';

		/**
		 * Filter product list html
		 *
		 * @since 3.0
		 *
		 * @param string $content The current html content.
		 * @param array $args The arguments passed to mp_list_products
		 */
		$content = apply_filters( 'mp_global_list_products', $content, $args );
		wp_reset_postdata();
		if ( $args[ 'echo' ] ) {
			echo $content;
		} else {
			return $content;
		}
	}

}

if ( !function_exists( 'mp_global_products_filter' ) ) :

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
	function mp_global_products_filter( $hidden = false, $per_page = null, $query = null ) {
		$current_order	 = strtolower( $query->get( 'order_by' ) . '-' . $query->get( 'order' ) );
		$options		 = array(
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
		$options_html	 = '';
		foreach ( $options as $k => $t ) {
			$value = $t[ 0 ] . '- ' . $t [ 1 ];
			$options_html .= '<option value="' . $value . '" ' . selected( $value, $current_order, false ) . '>' . $t[ 2 ] . '</option>';
		}

		$return = '
<a name="mp-product-list-top"></a>
<div class="mp_list_filter"' . ( ( $hidden ) ? ' style="display:none"' : '' ) . '>
	<form id="mp_global_product_list_refine" name="mp_global_product_list_refine" class="mp-form mp_global_product_list_refine clearfix" method="get">
		<div class="one_filter">
			<label for="mp-sort-order">' . __( 'Order By', 'mp' ) . '</label>
			<select id="mp-sort-order" class="mp_select2" name="order" data-placeholder="' . __( 'Product Category', 'mp' ) . '">
				' . $options_html . '
			</select>
		</div>' .
		( ( is_null( $per_page ) ) ? '' : '<input type="hidden" name="per_page" value="' . $per_page . '">' ) . '
		<input type="hidden" name="page" value="' . max( get_query_var( 'paged' ), 1 ) . '">
	</form>
</div>';

		return apply_filters( 'mp_global_products_filter', $return );
	}

endif;

if ( !function_exists( '_mp_global_products_html_list' ) ) {

	function _mp_global_products_html_list( $custom_query ) {
		return _mp_global_products_html( 'list', $custom_query );
	}

}

if ( !function_exists( '_mp_global_products_html_grid' ) ) {

	function _mp_global_products_html_grid( $custom_query ) {
		return _mp_global_products_html( 'grid', $custom_query );
	}

}

if ( !function_exists( '_mp_global_products_html' ) ) {

	function _mp_global_products_html( $view, WP_Query $custom_query ) {
		$html	 = '';
		$per_row = (int) mp_get_setting( 'per_row' );
		$width	 = round( 100 / $per_row, 1 ) . '%';
		$column	 = 1;

		//get image width
		if ( mp_get_setting( 'list_img_size' ) == 'custom' ) {
			$img_width = mp_get_setting( 'list_img_width' ) . 'px';
		} else {
			$size		 = mp_get_setting( 'list_img_size' );
			$img_width	 = get_option( $size . '_size_w' ) . 'px';
		}
		$current_blog_id = get_current_blog_id();
		foreach ( $custom_query->get_posts() as $post ) {
			$blog_id	 = get_post_meta( $post->ID, 'blog_id', true );
			$product_id	 = get_post_meta( $post->ID, 'post_id', true );
			switch_to_blog( $blog_id );
			$product	 = new MP_Product( $product_id );
			if ( !is_object( $product ) || $product->exists() == false ) {
				continue;
			}
			$align = null;
			if ( 'list' == mp_get_setting( 'list_view' ) ) {
				$align = mp_get_setting( 'image_alignment_list' );
			}
			$img = $product->image( false, 'list', null, $align );

			$excerpt				 = mp_get_setting( 'show_excerpts' ) ? '<div class="mp_excerpt">' . $product->excerpt() . '</div>' : '';
			$mp_product_list_content = apply_filters( 'mp_product_list_content', $excerpt, $product->ID );

			$pinit	 = $product->pinit_button( 'all_view' );
			$fb		 = $product->facebook_like_button( 'all_view' );
			$twitter = $product->twitter_button( 'all_view' );

			$class	 = array();
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
					$column	 = 1;
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
			//restore back to root
			switch_to_blog( 1 );
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

if ( !function_exists( 'mp_global_taxonomy_list' ) ) :

	function mp_global_taxonomy_list( $taxonomy, $atts, $echo = true ) {
		$defaults = array(
			'limit'		 => 50,
			'order_by'	 => 'count',
			'order'		 => 'DESC',
			'show_count' => 0
		);

		$atts = wp_parse_args( $atts, $defaults );
		extract( $atts );

		$key	 = 'mp_' . $taxonomy;
		$results = get_site_option( $key );

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

if ( !function_exists( '_mp_global_categories_list' ) ) {

	function _mp_global_categories_list( $results, $taxonomy ) {
		$html			 = '<ul id="mp_category_list">';
		$current_blog_id = get_current_blog_id();
		foreach ( $results as $row ) {
			switch_to_blog( $row[ 'blog_id' ] );
			$url = get_term_link( $row[ 'slug' ], $taxonomy );
			if ( !is_wp_error( $url ) ) {
				$html .= '<li class="cat-item cat-item-' . $row[ 'term_id' ] . '">
			<a href="' . $url . '">' . $row[ 'name' ] . '</a>
			</li>';
			}
		}
		$html .= '</ul>';
		switch_to_blog( $current_blog_id );

		return $html;
	}

}

if ( !function_exists( '_mp_global_tags_cloud' ) ) {

	function _mp_global_tags_cloud( $results, $taxonomy ) {
		$html			 = '<div id="mp_tag_cloud">';
		$current_blog_id = get_current_blog_id();
		foreach ( $results as $row ) {
			switch_to_blog( $row[ 'blog_id' ] );
			$url = get_term_link( $row[ 'slug' ], $taxonomy );
			if ( !is_wp_error( $url ) ) {
				$html .= '<a href="' . $url . '" class="tag-link tag-link-' . $row[ 'term_id' ] . '" title="">' . $row[ 'name' ] . '</a> ';
			}
		}
		$html .= '</div>';
		switch_to_blog( $current_blog_id );

		return $html;
	}

}