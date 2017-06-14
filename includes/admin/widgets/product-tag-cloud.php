<?php

//Product tags cloud
class MarketPress_Tag_Cloud_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'widget_tag_cloud mp_widget mp_widget_tag_cloud', 'description' => __( "Your most used product tags in cloud format from your MarketPress store.", 'mp' ) );
		parent::__construct( 'mp_tag_cloud_widget', __( 'Product Tag Cloud', 'mp' ), $widget_ops );
	}

	function widget( $args, $instance ) {

		if ( $instance[ 'only_store_pages' ] && !mp_is_shop_page() )
			return;

		extract( $args );

		$current_taxonomy = 'product_tag';
		if ( !empty( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}

		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $after_title;

		echo '<div class="tagcloud mp_widget_tags">';

		wp_tag_cloud( apply_filters( 'widget_tag_cloud_args', array( 'taxonomy' => $current_taxonomy ) ) );

		echo "</div>\n";

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance[ 'title' ]			 = strip_tags( stripslashes( $new_instance[ 'title' ] ) );
		$instance[ 'only_store_pages' ]	 = !empty( $new_instance[ 'only_store_pages' ] ) ? 1 : 0;
		return $instance;
	}

	function form( $instance ) {
		$instance			 = wp_parse_args( (array) $instance, array( 'title' => __( 'Product Tags', 'mp' ), 'only_store_pages' => 0 ) );
		$only_store_pages	 = isset( $instance[ 'only_store_pages' ] ) ? (bool) $instance[ 'only_store_pages' ] : false;
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'mp' ) ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php
			if ( isset( $instance[ 'title' ] ) ) {
				echo esc_attr( $instance[ 'title' ] );
			}
			?>" /></p>

		<p><input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'only_store_pages' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'only_store_pages' ) ); ?>"<?php checked( $only_store_pages ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'only_store_pages' ) ); ?>"><?php _e( 'Only show on store pages', 'mp' ); ?></label></p>
		<?php
	}

}

add_action( 'widgets_init', create_function( '', 'return register_widget("MarketPress_Tag_Cloud_Widget");' ) );
?>
