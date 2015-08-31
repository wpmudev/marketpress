<?php

class WPMUDEV_Field_Colorpicker extends WPMUDEV_Field {
	/**
	 * Use this to setup your child form field instead of __construct()
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args
	 */
	public function on_creation( $args ) {
		$this->args['class'] .= ' wpmudev-field-colorpicker-input';
		$this->args['style'] .= 'width:100px;';
	}

	/**
	 * Enqueue scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('wpmudev-field-colorpicker', WPMUDEV_Metabox::class_url('ui/colorpicker/js/colorpicker.js'), array('jquery'), WPMUDEV_METABOX_VERSION);
	}
	
	/**
	 * Enqueue styles
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_styles() {
		wp_enqueue_style('wpmudev-field-colorpicker', WPMUDEV_Metabox::class_url('ui/colorpicker/css/colorpicker.css'), array(), WPMUDEV_METABOX_VERSION);
	}

	/**
	 * Prints inline javascript
	 *
	 * @since 1.0
	 * @access public
	 */	
	public function print_scripts() {
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.wpmudev-field-colorpicker-input').each(function(){
				var that = $(this);
				$(this).ColorPicker({
					"onSubmit": function(hsb, hex, rgb, el) {
						$(el).val(hex);
						$(el).ColorPickerHide();
					},
					"onBeforeShow": function() {
						$(this).ColorPickerSetColor(this.value);
					},
					"onChange" : function(hsb, hex, rgb) {
						that.val(hex);

					}
				}).bind('keyup', function(){
					$(this).ColorPickerSetColor(this.value);
				});
			})
		});
		</script>		
		<?php
		parent::print_scripts();
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
		<input type="text" <?php echo $this->parse_atts(); ?> value="<?php echo $this->get_value($post_id); ?>" />
		<?php
		$this->after_field();
	}
}