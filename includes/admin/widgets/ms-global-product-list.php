<?php
if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( is_multisite() ) {

	//Product listing widget
	class MarketPress_Global_Product_List extends WP_Widget {

		function __construct() {
			$widget_ops = array(
				'classname'   => 'mp_widget mp_global_product_list_widget',
				'description' => __( 'Shows a customizable global list of products from network MarketPress stores.', 'mp' )
			);
			parent::__construct( 'mp_global_product_list_widget', __( 'Global Product List', 'mp' ), $widget_ops );
		}

		function widget( $args, $instance ) {
			global $mp;

			extract( $args );

			echo $before_widget;

			$title = $instance['title'];

			if ( ! empty( $title ) ) {
				echo $before_title . apply_filters( 'widget_title', $title ) . $after_title;
			}

			if ( ! empty( $instance['custom-_text'] ) ) {
				echo '<div class="mp_widget_custom_text">' . $instance['custom_text'] . '</div>';
			}

			$instance['as_list']   = true;
			$instance['context']   = 'widget';
			$instance['nopaging']  = true;
			$instance['version']   = '3';
			$instance['widget_id'] = $args['widget_id'];


			//list global products
			mp_global_list_products( $instance );
			//mp_list_global_products( $instance );

			echo $after_widget;
		}

		function update( $new_instance, $old_instance ) {
			$instance                = $old_instance;
			$instance['title']       = strip_tags( stripslashes( $new_instance['title'] ) );
			$instance['custom_text'] = stripslashes( wp_filter_kses( $new_instance['custom_text'] ) );

			$instance['per_page'] = intval( $new_instance['per_page'] );
			$instance['order_by'] = $new_instance['order_by'];
			$instance['order']    = $new_instance['order'];
			$instance['category'] = ( $new_instance['category'] ) ? sanitize_title( $new_instance['category'] ) : '';
			$instance['tag']      = ( $new_instance['tag'] ) ? sanitize_title( $new_instance['tag'] ) : '';

			$instance['show_thumbnail']             = ! empty( $new_instance['show_thumbnail'] ) ? 1 : 0;
			$instance['thumbnail_size']             = ! empty( $new_instance['thumbnail_size'] ) ? intval( $new_instance['thumbnail_size'] ) : 50;
			$instance['text']                       = $new_instance['text'];
			$instance['show_price']                 = ! empty( $new_instance['show_price'] ) ? 1 : 0;
			$instance['show_thumbnail_placeholder'] = ! empty( $new_instance['show_thumbnail_placeholder'] ) ? true : false;

			return $instance;
		}

		function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array(
				'title'          => __( 'Global Products', 'mp' ),
				'custom_text'    => '',
				'per_page'       => 10,
				'order_by'       => 'date',
				'order'          => 'DESC',
				'show_thumbnail' => 1,
				'size'           => 50,
				'text'           => 'none'
			) );
			extract( $instance );

			$show_price                 = isset( $instance['show_price'] ) ? (bool) $instance['show_price'] : false;
			$show_thumbnail_placeholder = isset( $instance['show_thumbnail_placeholder'] ) ? (bool) $instance['show_thumbnail_placeholder'] : false;
			$thumbnail_size             = isset( $instance['thumbnail_size'] ) ? $instance['thumbnail_size'] : false;
			?>
			<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'mp' ) ?>
					<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
					       value="<?php echo esc_attr( $title ); ?>"/></label></p>
			<p><label
					for="<?php echo esc_attr( $this->get_field_id( 'custom_text' ) ); ?>"><?php _e( 'Custom Text:', 'mp' ) ?>
					<br/>
					<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'custom_text' ) ); ?>"
					          name="<?php echo esc_attr( $this->get_field_name( 'custom_text' ) ); ?>"><?php echo esc_attr( $custom_text ); ?></textarea></label>
			</p>

			<h3><?php _e( 'List Settings', 'mp' ); ?></h3>
			<p>
				<label
					for="<?php echo esc_attr( $this->get_field_id( 'per_page' ) ); ?>"><?php _e( 'Number of Products:', 'mp' ) ?>
					<input id="<?php echo esc_attr( $this->get_field_id( 'per_page' ) ); ?>"
					       name="<?php echo esc_attr( $this->get_field_name( 'per_page' ) ); ?>" type="text" size="3"
					       value="<?php echo esc_attr( $per_page ); ?>"/></label><br/>
			</p>
			<p>
				<label
					for="<?php echo esc_attr( $this->get_field_id( 'order_by' ) ); ?>"><?php _e( 'Order Products By:', 'mp' ) ?>
					<br/>
					<select id="<?php echo esc_attr( $this->get_field_id( 'order_by' ) ); ?>"
					        name="<?php echo esc_attr( $this->get_field_name( 'order_by' ) ); ?>">
						<option
							value="date"<?php selected( $order_by, 'date' ) ?>><?php _e( 'Publish Date', 'mp' ) ?></option>
						<option
							value="title"<?php selected( $order_by, 'title' ) ?>><?php _e( 'Product Name', 'mp' ) ?></option>
						<option
							value="sales"<?php selected( $order_by, 'sales' ) ?>><?php _e( 'Number of Sales', 'mp' ) ?></option>
						<option
							value="price"<?php selected( $order_by, 'price' ) ?>><?php _e( 'Product Price', 'mp' ) ?></option>
						<option
							value="rand"<?php selected( $order_by, 'rand' ) ?>><?php _e( 'Random', 'mp' ) ?></option>
					</select><br/>
					<label><input value="DESC" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>"
					              type="radio"<?php checked( $order, 'DESC' ) ?> /> <?php _e( 'Descending', 'mp' ) ?>
					</label>
					<label><input value="ASC" name="<?php echo esc_attr( $this->get_field_name( 'order' ) ); ?>"
					              type="radio"<?php checked( $order, 'ASC' ) ?> /> <?php _e( 'Ascending', 'mp' ) ?>
					</label>
			</p>
			<p>
				<label
					for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php _e( 'Limit To Product Category:', 'mp' ) ?></label><br/>
				<input id="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"
				       name="<?php echo esc_attr( $this->get_field_name( 'category' ) ); ?>" type="text"
				       value="<?php echo esc_attr( isset( $category ) ? $category : '' ); ?>"
				       title="<?php _e( 'Enter the Slug', 'mp' ); ?>" class="widefat"/>
			</p>
			<p>
				<label
					for="<?php echo esc_attr( $this->get_field_id( 'tag' ) ); ?>"><?php _e( 'Limit To Product Tag:', 'mp' ) ?></label><br/>
				<input id="<?php echo esc_attr( $this->get_field_id( 'tag' ) ); ?>"
				       name="<?php echo esc_attr( $this->get_field_name( 'tag' ) ); ?>" type="text"
				       value="<?php echo esc_attr( isset( $tag ) ? $tag : '' ); ?>"
				       title="<?php _e( 'Enter the Slug', 'mp' ); ?>" class="widefat"/>
			</p>

			<h3><?php _e( 'Display Settings', 'mp' ); ?></h3>
			<p>
				<input type="checkbox" class="checkbox"
				       id="<?php echo esc_attr( $this->get_field_id( 'show_thumbnail' ) ); ?>"
				       name="<?php echo esc_attr( $this->get_field_name( 'show_thumbnail' ) ); ?>"<?php checked( $show_thumbnail ); ?> />
				<label
					for="<?php echo esc_attr( $this->get_field_id( 'show_thumbnail' ) ); ?>"><?php _e( 'Show Thumbnail', 'mp' ); ?></label><br/>
				<label
					for="<?php echo esc_attr( $this->get_field_id( 'thumbnail_size' ) ); ?>"><?php _e( 'Thumbnail Size:', 'mp' ) ?>
					<input id="<?php echo esc_attr( $this->get_field_id( 'thumbnail_size' ) ); ?>"
					       name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_size' ) ); ?>" type="text"
					       size="3" value="<?php echo esc_attr( $thumbnail_size ); ?>"/></label>
			</p>


			<!--<p>
				<input type="checkbox" class="checkbox" id="<?php /*echo $this->get_field_id( 'show_thumbnail_placeholder' ); */ ?>" name="<?php /*echo $this->get_field_name( 'show_thumbnail_placeholder' ); */ ?>"<?php /*checked( $show_thumbnail_placeholder ); */ ?> />
				<label for="<?php /*echo $this->get_field_id( 'show_thumbnail_placeholder' ); */ ?>"><?php /*_e( 'Show Thumbnail Placeholder image (if image is not set)', 'mp' ); */ ?></label>
			</p>-->

			<p>
				<label
					for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php _e( 'Content To Show:', 'mp' ) ?></label><br/>
				<select id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"
				        name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>">
					<option value="none"<?php selected( $text, 'none' ) ?>><?php _e( 'None', 'mp' ) ?></option>
					<option value="excerpt"<?php selected( $text, 'excerpt' ) ?>><?php _e( 'Excerpt', 'mp' ) ?></option>
					<!--<option value="content"<?php /*selected( $text, 'content' ) */ ?>><?php /*_e( 'Content', 'mp' ) */ ?></option>-->
				</select>
			</p>

			<p>
				<input type="checkbox" class="checkbox"
				       id="<?php echo esc_attr( $this->get_field_id( 'show_price' ) ); ?>"
				       name="<?php echo esc_attr( $this->get_field_name( 'show_price' ) ); ?>"<?php checked( $show_price ); ?> />
				<label
					for="<?php echo esc_attr( $this->get_field_id( 'show_price' ) ); ?>"><?php _e( 'Show Price', 'mp' ); ?></label>
			</p>

			<?php
		}

	}

	add_action( 'widgets_init', create_function( '', 'return register_widget("MarketPress_Global_Product_List");' ) );
}
