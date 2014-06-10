<?php

class WPMUDEV_Field_Datepicker extends WPMUDEV_Field {
	/**
	 * Runs on construct of parent class
	 *
	 * @since 3.0
	 * @access public
	 */
	public function on_creation( $args ) {
		$this->args['class'] .= ' wpmudev-datepicker-field';
	}

	/**
	 * Prints scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function print_scripts() {
	?>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('.wpmudev-datepicker-field').datepicker({
		"dateFormat" : "yy-mm-dd"
	});
});
</script>
	<?php
	}

	/**
	 * Formats the field value for display
	 *
	 * @since 1.0
	 * @access public
	 */
	public function format_value( $value ) {
		return apply_filters('wpmudev_field_format_value', date('Y-m-d', $value), $post_id, $this);
	}

	/**
	 * Sanitizes the field value before saving to database
	 *
	 * @since 1.0
	 * @access public
	 * @param $value
	 */	
	public function sanitize_for_db( $value ) {
		return apply_filters('wpmudev_field_sanitize_for_db', strtotime($value), $post_id, $this);
	}
		
	/**
	 * Enqueue scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('jquery-ui-datepicker');
	}

	/**
	 * Enqueue styles
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_styles() {
		wp_enqueue_style('jquery-ui-smoothness-theme', WPMUDEV_Metabox::class_url('ui/smoothness/jquery-ui-1.10.4.custom.min.css'), false, '1.10.4');
	}
	
	/**
	 * Displays the field
	 *
	 * @since 3.0
	 * @access public
	 * @param int $post_id
	 * @param bool $echo
	 */
	public function display( $post_id, $echo = true ) {
		$value = $this->get_value($post_id);
		
		if ( $value === false ) {
			$value = $this->args['default_value'];
		}
		
		$html = '<input type="text" ' . $this->parse_atts() . ' value="' . $value . '" />';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}