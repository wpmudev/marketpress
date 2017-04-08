<?php

class WPMUDEV_Field_File extends WPMUDEV_Field {
	/**
	 * Runs on creation of parent
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 *		Any array of arguments. Optional.
	 *
	 *		@type string $title The text that shows up in the header of the media lightbox. Optional.
	 *		@type string $button_label The label of the send-to-editor button. Optional.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'title' => __('Select the file that you would like to use.', 'wpmudev_metaboxes'),
			'button_label' => __('Select File', 'wpmudev_metaboxes'),
		), $args);
		
		$this->args['custom']['data-media-title'] = $this->args['title'];
		$this->args['custom']['data-media-button-label'] = $this->args['button_label'];
		$this->args['style'] .= ' width:60%;';
	}

	/**
	 * Enqueues necessary field javascript.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('media-upload');
		
		// 3.5 media gallery
		if ( function_exists('wp_enqueue_media') && ! did_action('wp_enqueue_media') ) {
			wp_enqueue_media();
		}
	}
	
	/**
	 * Print necessary field javascript.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function print_scripts() {
		?>
<script type="text/javascript">
jQuery(document).ready(function($){
	/*
	 * Show the media library popup
	 */
	$('.wpmudev-fields').on('click', '.wpmudev-field-file-select', function(e){
		e.preventDefault();
		
		var $this = $(this),
				$input = $this.siblings('input'),
				frame = wp.media({
					"title" : $input.attr('data-media-title'),
					"multiple" : false,
					"button" : { "text" : $input.attr('data-media-button-label') }
				});
		
		/*
		 * Send image data back to the calling field
		 */
		frame.on('select', function(){
			var selection = frame.state().get('selection');
			
			selection.each(function(attachment){
				$input.val(attachment.attributes.url);
			});
		});
		
		/*
		 * Set the selected image
		 */
		frame.on('open', function(){
			var selection = frame.state().get('selection'),
      		id = $input.val();
      
      if ( id.length ) {
      	var attachment = wp.media.attachment(id);
				attachment.fetch();
				selection.add(attachment ? [attachment] : []);
      }
		});
		
		frame.open();
	});
});
</script>
		<?php
		parent::print_scripts();
	}
	
	/**
	 * Displays the field.
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$value = $this->get_value($post_id);

		//Check if we have an array in our value and only return the first
		if ( is_array( $value ) && count( $value ) > 0 ){
			$value = $value[0];
		}
		
		$this->before_field(); ?>
		<input type="text" <?php echo $this->parse_atts(); ?> value="<?php echo $value; ?>" /> <a class="button wpmudev-field-file-select" href="#"><?php _e('Select File', 'wpmudev_metaboxes'); ?></a>
		<?php
		$this->after_field();
	}
}