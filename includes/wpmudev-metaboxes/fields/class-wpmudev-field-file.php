<?php

class WPMUDEV_Field_File extends WPMUDEV_Field {
	/**
	 * Runs on creation of parent
	 *
	 * @since 1.0
	 * @access public
	 */
	public function on_creation( $args ) {
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
					"title" : "<?php _e('Select the file that you would like to use.', 'wpmudev_metaboxes'); ?>",
					"multiple" : false,
					"button" : { "text" : "<?php _e('Select File', 'wpmudev_metaboxes'); ?>" }
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
	}
	
	/**
	 * Displays the field.
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 * @param bool $echo
	 */
	public function display( $post_id, $echo = true ) {
		$value = $this->get_value($post_id);
		$html = '<input type="text" ' . $this->parse_atts() . ' value="' . $value . '" /> <a class="button wpmudev-field-file-select" href="#">' . __('Select File', 'wpmudev_metaboxes') . '</a>';

		/**
		 * Modify the display HTML before return/output.
		 *
		 * @since 1.0
		 * @param string $html The current display HTML.
		 * @param object $this The current field object.
		 */		
		$html = apply_filters('wpmudev_field_file_display', $html, $this);
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}