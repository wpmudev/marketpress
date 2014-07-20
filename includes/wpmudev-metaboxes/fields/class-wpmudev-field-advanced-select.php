<?php

class WPMUDEV_Field_Advanced_Select extends WPMUDEV_Field {
	/**
	 * Runs on parent construct
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 *		An array of arguments. Optional.
	 *
	 *		@type bool $multiple Whether to allow multi-select or only one option.
	 *		@type string $placeholder The text that shows up when the field is empty.
	 *		@type array $options An array of $key => $value pairs of the available options.
	 * }	 
	 */
	public function on_creation( $args ) {
		$this->args = wp_parse_args($args, array(
			'multiple' => true,
			'placeholder' => __('Select One', 'mp'),
			'options' => array(),
		));
		
		$this->args['class'] .= ' wpmudev-advanced-select';			
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
(function($){
	var initSelect2 = function(){
		$('input.wpmudev-advanced-select').each(function(){
			var $this = $(this),
			options = [];
			
			$($this.attr('data-options').split('||')).each(function(){
				var val = this.split('=');
				options.push({ "id" : val[0], "text" : val[1] });
			});
			
			$this.select2({
				"allowSelectAllNone" : true,
				"multiple" : <?php echo $this->args['multiple']; ?>,
				"placeholder" : "<?php echo $this->args['placeholder']; ?>",
				"initSelection" : function(element, callback){
					var data = [];
					
					$(element.attr('data-value').split('||')).each(function(){
						var val = this.split('=');
						data.push({ "id" : val[0], "text" : val[1] });
					});
					
					callback(data);
				},			
				"data" : options,
				"width" : "100%"
			}) 
		});		
	}
	
	$(document).on('wpmudev_repeater_field_before_add_field_group', function(){
		$('input.wpmudev-advanced-select').select2('destroy');
	});
	
	$(document).on('wpmudev_repeater_field_after_add_field_group', function(e, $group){
		initSelect2();
	});
	
	$(document).ready(function(){
		initSelect2();
		
		$('.wpmudev-field').on('click', '.wpmudev-advanced-select-all-link', function(){
			var $this = $(this),
					options = [];
			
			$($this.siblings('.wpmudev-advanced-select').attr('data-options').split('||')).each(function(){
				var val = this.split('=');
				options.push(val[0]);
			});
			
			console.log(options);
		});
	});
}(jQuery));
</script>
	<?php		
	}

	/**
	 * Sanitizes the field value before saving to database.
	 *
	 * @since 1.0
	 * @access public
	 * @param $value
	 */	
	public function sanitize_for_db( $value ) {
		$value = trim($value, ',');
		return parent::sanitize_for_db($value);
	}

	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$value = $this->get_value($post_id);
		$vals = explode(',', $value);
		$values = array();
		$options = array();
		
		foreach ( $vals as $val ) {
			$values[] = $val . '=' . $this->args['options'][$val];
		}
		
		foreach ( $this->args['options'] as $val => $label ) {
			$options[] = $val . '=' . $label;
		}
		
		$this->args['custom']['data-options'] = implode('||', $options);
		$this->args['custom']['data-value'] = implode('||', $values); ?>
		<input type="hidden" <?php echo $this->parse_atts(); ?> value="<?php echo $value; ?>" />
		<?php
	}
	
	/**
	 * Enqueues the field's scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('wpmudev-field-select2', WPMUDEV_Metabox::class_url('ui/select2/select2.min.js'), array('jquery'), '3.4.8');
	}
	
	/**
	 * Enqueues the field's styles
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_styles() {
		wp_enqueue_style('wpmudev-field-select2',  WPMUDEV_Metabox::class_url('ui/select2/select2.css'), array(), '3.4.8');
	}
}