<?php
if ( !function_exists( 'is_plugin_active_for_network' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( is_multisite() && is_plugin_active_for_network( 'marketpress/marketpress.php' ) ) {

//Product tags cloud
	class MarketPress_Global_Tag_Cloud_Widget extends WP_Widget {

		function MarketPress_Global_Tag_Cloud_Widget() {
			$widget_ops = array( 'classname' => 'mp_widget mp_global_tag_cloud_widget', 'description' => __( "Displays global most used product tags in cloud format from network MarketPress stores." ) );
			parent::__construct( 'mp_global_tag_cloud_widget', __( 'Global Product Tag Cloud', 'mp' ), $widget_ops );
		}

		function widget( $args, $instance ) {
			extract( $args );

			if ( !empty( $instance[ 'title' ] ) ) {
				$title = $instance[ 'title' ];
			} else {
				$title = __( 'Global Product Tags', 'mp' );
			}
			$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

			echo $before_widget;
			if ( $title )
				echo $before_title . $title . $after_title;

			mp_global_tag_cloud( true, 45, ' ', $instance[ 'taxonomy' ] );

			echo $after_widget;
		}

		function update( $new_instance, $old_instance ) {
			$instance[ 'title' ]	 = strip_tags( stripslashes( $new_instance[ 'title' ] ) );
			$instance[ 'taxonomy' ]	 = stripslashes( $new_instance[ 'taxonomy' ] );
			return $instance;
		}

		function form( $instance ) {
			$instance = wp_parse_args( (array) $instance, array( 'title' => __( 'Global Product Tags', 'mp' ), 'taxonomy' => 'tags' ) );
			?>
			<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ) ?></label>
				<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php
				if ( isset( $instance[ 'title' ] ) ) {
					echo esc_attr( $instance[ 'title' ] );
				}
				?>" /></p>
			<p><label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Show:', 'mp' ) ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
					<option value="tags" <?php selected( $instance[ 'taxonomy' ], 'tags' ) ?>><?php _e( 'Product Tags', 'mp' ); ?></option>
					<option value="categories" <?php selected( $instance[ 'taxonomy' ], 'categories' ) ?>><?php _e( 'Product Categories', 'mp' ); ?></option>
					<option value="both" <?php selected( $instance[ 'taxonomy' ], 'both' ) ?>><?php _e( 'Both', 'mp' ); ?></option>
				</select></p>
			<?php
		}

	}

	//add_action( 'widgets_init', create_function( '', 'return register_widget("MarketPress_Global_Tag_Cloud_Widget");' ) );
}
?>