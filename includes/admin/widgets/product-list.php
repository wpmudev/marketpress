<?php

//Product listing widget
class MarketPress_Product_List extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'mp_widget mp_widget_products_list', 'description' => __( 'Shows a customizable list of products from your MarketPress store.', 'mp' ) );
		parent::__construct( 'mp_product_list_widget', __( 'Product List', 'mp' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		global $mp, $post;

		if ( $instance[ 'only_store_pages' ] && !mp_is_shop_page() )
			return;

		extract( $args );

		echo $before_widget;
		$title = $instance[ 'title' ];
		if ( !empty( $title ) ) {
			echo $before_title . apply_filters( 'widget_title', $title ) . $after_title;
		};

		if ( !empty( $instance[ 'custom_text' ] ) )
			echo '<div class="mp_widget_custom-text">' . $instance[ 'custom_text' ] . '</div>';

		/* setup our custom query */

		//setup taxonomy if applicable
		if ( $instance[ 'taxonomy_type' ] == 'category' ) {
			$taxonomy_query = '&product_category=' . $instance[ 'taxonomy' ];
		} else if ( $instance[ 'taxonomy_type' ] == 'tag' ) {
			$taxonomy_query = '&product_tag=' . $instance[ 'taxonomy' ];
		} else {
			$taxonomy_query = '';
		}

		//figure out perpage
		if ( isset( $instance[ 'num_products' ] ) && intval( $instance[ 'num_products' ] ) > 0 ) {
			$paginate_query = '&posts_per_page=' . intval( $instance[ 'num_products' ] ) . '&paged=1';
		} else {
			$paginate_query = '&posts_per_page=10&paged=1';
		}

		//get order by
		if ( $instance[ 'order_by' ] ) {
			if ( $instance[ 'order_by' ] == 'price' )
				$order_by_query	 = '&meta_key=mp_price_sort&orderby=meta_value_num';
			else if ( $instance[ 'order_by' ] == 'sales' )
				$order_by_query	 = '&meta_key=mp_sales_count&orderby=meta_value_num';
			else if ( $instance[ 'order_by' ] == 'featured' )
				$order_by_query	 = '&meta_key=featured&orderby=meta_value_num';
			else
				$order_by_query	 = '&orderby=' . $instance[ 'order_by' ];
		} else {
			$order_by_query = '&orderby=title';
		}

		//get order direction
		if ( $instance[ 'order' ] ) {
			$order_query = '&order=' . $instance[ 'order' ];
		} else {
			$order_query = '&orderby=DESC';
		}

		// check for post type setting (patch for widget not working with "mp_product" type
                // Added by Adam, modified by Ash
		$product_type = MP_Product::get_post_type();
		$query_string = 'post_type=' . $product_type . $taxonomy_query . $paginate_query . $order_by_query . $order_query;

		$query_array = array();

		//Convert the query string to array so we can merge it later
		parse_str( $query_string, $query_array );

		//set to show only featured products, if configured to
		if ( isset( $instance[ 'show_only_featured' ] ) && (bool) $instance[ 'show_only_featured' ] == true ) {
			//the meta_query must be a nested array, that's why we needed an array instead of a query string
			$query_array = array_merge( $query_array, array(
				'meta_query' => array(
					array(
						'key'     => 'featured',
						'value'   => '1',
						'compare' => '=',
					),
				),
			));
		}

		//The Query
		$custom_query = new WP_Query( $query_array );

		//do we have products?
		if ( $custom_query->have_posts() ) {
			echo '<div id="mp-products-list-widget" class="hfeed mp_widget_products mp_widget_products-list mp-multiple-products">';
			while ( $custom_query->have_posts() ) : $custom_query->the_post();

				$product = new MP_Product( $post->ID );
				echo '<div class="mp_product_item">';
				echo '<div itemscope itemtype="http://schema.org/Product" ' . mp_product_class( false, array( 'mp_product' ), $post->ID ) . '>';
				echo '<h3 class="mp_product_name entry-title" itemprop="name"><a href="' . get_permalink( $post->ID ) . '">' . esc_attr( $post->post_title ) . '</a></h3>';

				if ( $instance[ 'show_thumbnail' ] ) {
					echo '<a class="mp_product_img_link" href="' . get_the_permalink( $post->ID ) . '">' . $product->image_custom( false, $instance[ 'size' ], array(
						'show_thumbnail_placeholder' => isset( $instance[ 'show_thumbnail_placeholder' ] ) ? $instance[ 'show_thumbnail_placeholder' ] : false,
						'class'						 => 'mp_product_image_list'
					)
					) . '</a>';
				}

				if ( $instance[ 'show_excerpt' ] ) {
					echo '<div class="mp_product_excerpt">' . $product->post_excerpt . '</div><!-- end mp_product_excerpt -->';
				}

				if ( $instance[ 'show_price' ] || $instance[ 'show_button' ] ) {
					echo '<div class="mp_product_meta">';

					if ( $instance[ 'show_price' ] ) {
						echo $product->display_price();
					}

					if ( $instance[ 'show_button' ] ) {
						echo $product->buy_button( false, 'list', array(), true );
					}

					echo '</div><!-- mp_product_meta -->';
				}

				echo '<div style="display:none">
							<time class="updated">' . get_the_time( 'Y-m-d\TG:i' ) . '</time> by
							<span class="author vcard"><span class="fn">' . get_the_author_meta( 'display_name' ) . '</span></span>
						</div>';
				echo '</div><!-- end mp_product -->';
				echo '</div><!-- end mp_product_item -->';
			endwhile;

			wp_reset_postdata();

			echo '</div><!-- end mp-widget-products-list -->';
		} else {
			?>
			<div class="mp_widget_empty">
				<?php _e( 'No Products', 'mp' ) ?>
			</div><!-- end mp_widget_empty -->
			<?php
		}

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance					 = $old_instance;
		$instance[ 'title' ]		 = stripslashes( wp_filter_nohtml_kses( $new_instance[ 'title' ] ) );
		$instance[ 'custom_text' ]	 = stripslashes( wp_filter_kses( $new_instance[ 'custom_text' ] ) );

		$instance[ 'num_products' ]	 = intval( $new_instance[ 'num_products' ] );
		$instance[ 'order_by' ]		 = $new_instance[ 'order_by' ];
		$instance[ 'order' ]		 = $new_instance[ 'order' ];
		$instance[ 'taxonomy_type' ] = $new_instance[ 'taxonomy_type' ];
		$instance[ 'taxonomy' ]		 = ($new_instance[ 'taxonomy_type' ]) ? sanitize_title( $new_instance[ 'taxonomy' ] ) : '';

		$instance[ 'show_only_featured' ]			 = !empty( $new_instance[ 'show_only_featured' ] ) ? 1 : 0;
		$instance[ 'show_thumbnail' ]				 = !empty( $new_instance[ 'show_thumbnail' ] ) ? 1 : 0;
		$instance[ 'size' ]							 = !empty( $new_instance[ 'size' ] ) ? intval( $new_instance[ 'size' ] ) : 50;
		$instance[ 'show_excerpt' ]					 = !empty( $new_instance[ 'show_excerpt' ] ) ? 1 : 0;
		$instance[ 'show_price' ]					 = !empty( $new_instance[ 'show_price' ] ) ? 1 : 0;
		$instance[ 'show_thumbnail_placeholder' ]	 = !empty( $new_instance[ 'show_thumbnail_placeholder' ] ) ? true : false;
		$instance[ 'show_button' ]					 = !empty( $new_instance[ 'show_button' ] ) ? 1 : 0;

		$instance[ 'only_store_pages' ] = !empty( $new_instance[ 'only_store_pages' ] ) ? 1 : 0;

		return $instance;
	}

	function form( $instance ) {
		$instance	 = wp_parse_args( (array) $instance, array( 'title' => __( 'Our Products', 'mp' ), 'custom_text' => '', 'num_products' => 10, 'order_by' => 'title', 'order' => 'DESC', 'show_thumbnail' => 1, 'size' => 50, 'only_store_pages' => 0 ) );
		$title		 = $instance[ 'title' ];
		$custom_text = $instance[ 'custom_text' ];

		$num_products	 = intval( $instance[ 'num_products' ] );
		$order_by		 = $instance[ 'order_by' ];
		$order			 = $instance[ 'order' ];
		$taxonomy_type	 = isset( $instance[ 'taxonomy_type' ] ) ? $instance[ 'taxonomy_type' ] : '';
		$taxonomy		 = isset( $instance[ 'taxonomy' ] ) ? $instance[ 'taxonomy' ] : '';

		$show_only_featured = isset ( $instance[ 'show_only_featured' ] ) ? (bool) $instance[ 'show_only_featured' ] : false;
		$show_thumbnail	 = isset( $instance[ 'show_thumbnail' ] ) ? (bool) $instance[ 'show_thumbnail' ] : false;
		$size			 = !empty( $instance[ 'size' ] ) ? intval( $instance[ 'size' ] ) : 50;
		$show_excerpt	 = isset( $instance[ 'show_excerpt' ] ) ? (bool) $instance[ 'show_excerpt' ] : false;
		$show_price		 = isset( $instance[ 'show_price' ] ) ? (bool) $instance[ 'show_price' ] : false;

		$show_thumbnail_placeholder = isset( $instance[ 'show_thumbnail_placeholder' ] ) ? (bool) $instance[ 'show_thumbnail_placeholder' ] : false;

		$show_button = isset( $instance[ 'show_button' ] ) ? (bool) $instance[ 'show_button' ] : false;

		$only_store_pages = isset( $instance[ 'only_store_pages' ] ) ? (bool) $instance[ 'only_store_pages' ] : false;
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'mp' ) ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>
		<p><label for="<?php echo $this->get_field_id( 'custom_text' ); ?>"><?php _e( 'Custom Text:', 'mp' ) ?><br />
				<textarea class="widefat" id="<?php echo $this->get_field_id( 'custom_text' ); ?>" name="<?php echo $this->get_field_name( 'custom_text' ); ?>"><?php echo esc_attr( $custom_text ); ?></textarea></label>
		</p>

		<h3><?php _e( 'List Settings', 'mp' ); ?></h3>
		<p>
			<label for="<?php echo $this->get_field_id( 'num_products' ); ?>"><?php _e( 'Number of Products:', 'mp' ) ?> <input id="<?php echo $this->get_field_id( 'num_products' ); ?>" name="<?php echo $this->get_field_name( 'num_products' ); ?>" type="text" size="3" value="<?php echo $num_products; ?>" /></label><br />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'order_by' ); ?>"><?php _e( 'Order Products By:', 'mp' ) ?></label><br />
			<select id="<?php echo $this->get_field_id( 'order_by' ); ?>" name="<?php echo $this->get_field_name( 'order_by' ); ?>">
				<option value="title"<?php selected( $order_by, 'title' ) ?>><?php _e( 'Product Name', 'mp' ) ?></option>
				<option value="date"<?php selected( $order_by, 'date' ) ?>><?php _e( 'Publish Date', 'mp' ) ?></option>
				<option value="ID"<?php selected( $order_by, 'ID' ) ?>><?php _e( 'Product ID', 'mp' ) ?></option>
				<option value="author"<?php selected( $order_by, 'author' ) ?>><?php _e( 'Product Author', 'mp' ) ?></option>
				<option value="sales"<?php selected( $order_by, 'sales' ) ?>><?php _e( 'Number of Sales', 'mp' ) ?></option>
				<option value="price"<?php selected( $order_by, 'price' ) ?>><?php _e( 'Product Price', 'mp' ) ?></option>
				<option value="featured"<?php selected( $order_by, 'featured' ) ?>><?php _e( 'Featured', 'mp' ) ?></option>
				<option value="rand"<?php selected( $order_by, 'rand' ) ?>><?php _e( 'Random', 'mp' ) ?></option>
			</select><br />
			<label><input value="DESC" name="<?php echo $this->get_field_name( 'order' ); ?>" type="radio"<?php checked( $order, 'DESC' ) ?> /> <?php _e( 'Descending', 'mp' ) ?></label>
			<label><input value="ASC" name="<?php echo $this->get_field_name( 'order' ); ?>" type="radio"<?php checked( $order, 'ASC' ) ?> /> <?php _e( 'Ascending', 'mp' ) ?></label>
		</p>
		<p>
			<label><?php _e( 'Taxonomy Filter:', 'mp' ) ?></label><br />
			<select id="<?php echo $this->get_field_id( 'taxonomy_type' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy_type' ); ?>">
				<option value=""<?php selected( $taxonomy_type, '' ) ?>><?php _e( 'No Filter', 'mp' ) ?></option>
				<option value="category"<?php selected( $taxonomy_type, 'category' ) ?>><?php _e( 'Category', 'mp' ) ?></option>
				<option value="tag"<?php selected( $taxonomy_type, 'tag' ) ?>><?php _e( 'Tag', 'mp' ) ?></option>
			</select>
			<input id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>" type="text" size="17" value="<?php echo $taxonomy; ?>" title="<?php _e( 'Enter the Slug', 'mp' ); ?>" />
		</p>

		<h3><?php _e( 'Display Settings', 'mp' ); ?></h3>
		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_only_featured' ); ?>" name="<?php echo $this->get_field_name( 'show_only_featured' ); ?>"<?php checked( $show_only_featured ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_only_featured' ); ?>"><?php _e( 'Show Only Featured Products', 'mp' ); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'show_thumbnail' ); ?>"<?php checked( $show_thumbnail ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>"><?php _e( 'Show Thumbnail', 'mp' ); ?></label><br />
			<label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e( 'Thumbnail Size:', 'mp' ) ?> <input id="<?php echo $this->get_field_id( 'size' ); ?>" name="<?php echo $this->get_field_name( 'size' ); ?>" type="text" size="3" value="<?php echo $size; ?>" /></label></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>"<?php checked( $show_excerpt ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>"><?php _e( 'Show Excerpt', 'mp' ); ?></label><br />

			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_price' ); ?>" name="<?php echo $this->get_field_name( 'show_price' ); ?>"<?php checked( $show_price ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_price' ); ?>"><?php _e( 'Show Price', 'mp' ); ?></label><br />

			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_thumbnail_placeholder' ); ?>" name="<?php echo $this->get_field_name( 'show_thumbnail_placeholder' ); ?>"<?php checked( $show_thumbnail_placeholder ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_thumbnail_placeholder' ); ?>"><?php _e( 'Show Thumbnail Placeholder image (if image is not set)', 'mp' ); ?></label><br />

			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_button' ); ?>" name="<?php echo $this->get_field_name( 'show_button' ); ?>"<?php checked( $show_button ); ?> />
			<label for="<?php echo $this->get_field_id( 'show_button' ); ?>"><?php _e( 'Show Buy Button', 'mp' ); ?></label></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'only_store_pages' ); ?>" name="<?php echo $this->get_field_name( 'only_store_pages' ); ?>"<?php checked( $only_store_pages ); ?> />
			<label for="<?php echo $this->get_field_id( 'only_store_pages' ); ?>"><?php _e( 'Only show on store pages', 'mp' ); ?></label></p>
		<?php
	}

}

add_action( 'widgets_init', create_function( '', 'return register_widget("MarketPress_Product_List");' ) );
?>
