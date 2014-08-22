<?php

class WPMUDEV_Field_Datepicker extends WPMUDEV_Field {
	/**
	 * Runs on construct of parent class
	 *
	 * @since 1.0
	 * @access public
	 */
	public function on_creation( $args ) {
		$this->args['class'] .= ' wpmudev-datepicker-field';
	}

	/**
	 * Prints scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function print_scripts() {
		?>
<script type="text/javascript">
(function($){	
	$(document).on('wpmudev_repeater_field_before_add_field_group', function(e){
		$('.wpmudev-datepicker-field').datepicker('destroy');
	});
	
	$(document).on('wpmudev_repeater_field_after_add_field_group', function(e, $group){
		$('.wpmudev-datepicker-field').each(function(){
			var $this = $(this);
			
			$this.datepicker({
				"dateFormat" : "<?php echo $this->format_date_for_jquery(get_option('date_format')); ?>",
				"altField" : $this.prev('input[type="hidden"]'),
				"altFormat" : "yy-mm-dd"
			});
		});
	});
	
	$(document).ready(function(){
		$('.wpmudev-datepicker-field').each(function(){
			var $this = $(this);
			
			$this.datepicker({
				"dateFormat" : "<?php echo $this->format_date_for_jquery(get_option('date_format')); ?>",
				"altField" : $this.prev('input[type="hidden"]'),
				"altFormat" : "yy-mm-dd"
			});
		});
	});
}(jQuery));
</script>
		<?php
		parent::print_scripts();
	}
	
	/**
	 * Takes a PHP date format and converts it to jquery-ui dateFormat
	 *
	 * @since 1.0
	 * @access public
	 * @param string $format
	 * @return string
	 */
	public function format_date_for_jquery( $format ) {
		$pattern = array('d', 'j', 'l', 'z', 'F', 'M', 'n', 'm', 'Y', 'y');
		$replace = array('dd', 'd', 'DD', 'o', 'MM', 'M', 'm', 'mm', 'yy', 'y');
		
		foreach ( $pattern as &$p ) {
			$p = '/' . $p . '/';
		}
		
		return preg_replace($pattern, $replace, $format);
	}
	
	/**
	 * Checks if provided string is a valid timestamp
	 *
	 * @since 1.0
	 * @access public
	 * @param string $value
	 * @return bool
	 */
	public function is_timestamp( $value ) {
		return ( is_numeric($value) && (int) $value == $value );
	}

	/**
	 * Formats the field value for display
	 *
	 * @since 1.0
	 * @access public
	 */
	public function format_value( $value ) {
		if ( ! empty($value) ) {
			$value = $this->is_timestamp($value) ? $value : strtotime($value);
			$value = date_i18n(get_option('date_format'), $value);
		}
		
		return apply_filters('wpmudev_field_format_value', $value, $post_id, $this);
	}

	/**
	 * Sanitizes the field value before saving to database
	 *
	 * @since 1.0
	 * @access public
	 * @param $value
	 */	
	public function sanitize_for_db( $value ) {
		$value = $this->is_timestamp($value) ? date('Y-m-d', $value) : $value;
		return apply_filters('wpmudev_field_sanitize_for_db', $value, $post_id, $this);
	}
		
	/**
	 * Enqueue scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('jquery-ui-datepicker');
	}

	/**
	 * Enqueue styles
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_styles() {
		wp_enqueue_style('jquery-ui-smoothness-theme', WPMUDEV_Metabox::class_url('ui/smoothness/jquery-ui.min.css'), false, WPMUDEV_METABOX_VERSION);
	}
	
	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$this->before_field();
		?>
		<input type="hidden" <?php echo $this->parse_atts(); ?> value="<?php echo $this->get_value($post_id, null, true); ?>" />
		<input type="text" class="wpmudev-datepicker-field" value="<?php echo $this->get_value($post_id); ?>" />
		<?php
		$this->after_field();
	}
}