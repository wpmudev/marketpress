<?php

class WPMUDEV_Field_WYSIWYG extends WPMUDEV_Field {
	/**
	 * Runs on creation of parent
	 *
	 * @since 1.0
	 * @access public
	 * 
	 * @param array $args {
	 *		An array of arguments. Optional.
	 *
	 *		@type bool $media_buttons Whether to display media insert/upload buttons.
	 *		@type bool $wpautop Whether to use wpautop for adding in paragraphs.
	 *		@type int $rows The number of textarea rows. Defaults to 15.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'media_buttons' => true,
			'wpautop' => true,
			'rows' => 15,
		), $args);
		
		$this->args['custom']['rows'] = $this->args['rows'];
		$this->args['class'] .= ' wp-editor-area wpmudev-field-wysiwyg-textarea';
		
		add_filter('mce_external_plugins', array($this, 'mce_external_plugins'), 20, 1);
		add_filter('mce_buttons_2', array($this, 'mce_buttons_2'));
	}
	
	/**
	 * Init mce row 2 buttons
	 *
	 * @since 1.0
	 * @access public
	 * @param array $buttons
	 * @return array
	 */
	public function mce_buttons_2( $buttons ) {
		return array_merge($buttons, array('code'));
	}
	
	/**
	 * Init mce plugins
	 *
	 * @since 1.0
	 * @access public
	 * @param array $plugins
	 * @return array
	 */
	public function mce_external_plugins( $plugins ) {
		// this will add the "code" button back in which is not available in WP >= 3.9
		$plugins['code'] = WPMUDEV_Metabox::class_url('ui/js/tinymce.code.min.js');
		return $plugins;
	}
	
	/**
	 * Enqueues the field's scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('jquery-ui-core');	//required to use uniqueId() function
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
jQuery(document).ready(function($){	
	$(document).on('wpmudev_repeater_field/start_sort', function(e, $group){
		$group.find('.wp-editor-area').each(function(){
			tinymce.execCommand('mceRemoveEditor', false, $(this).attr('id'));
		});
	});

	$(document).on('wpmudev_repeater_field/stop_sort', function(e, $group){
		$group.find('.wp-editor-area').each(function(){
			$group.find('.mce-tinymce').remove();	// remove the tinymce editor
			tinyMCE.execCommand('mceAddEditor', false, $(this).attr('id'));
		});
	});
	
	$(document).on('wpmudev_repeater_field/after_add_field_group', function(e, $group){
		var $field = $group.find('.wpmudev-field-wysiwyg'),
				$textarea = $field.find('textarea'),
				$wrap = $field.find('.wp-editor-wrap'),
				oldId = $textarea.attr('id'),
				newId = '';
				
		$textarea.show().attr('id', '').uniqueId(); // show the field and generate a unique id
		newId = $textarea.attr('id');
		
		/*
		find all instances of old id and replace with new id. this is key to certain editor
		functions (e.g. mode switching) working correctly.
		*/
		$wrap.find('[id*="' + oldId + '"]').andSelf().each(function(){
			var $this = $(this);
					id = $this.attr('id');
			$this.attr('id', id.replace(oldId, newId));
		});
		
		if ( $wrap.hasClass('tmce-active') ) {
			$group.find('.mce-tinymce').remove();	// remove the tinymce editor
			tinymce.execCommand('mceAddEditor', false, newId); // init tinymce on textfield
		}
	});
});
</script>
		<?php
		parent::print_scripts();
	}
	
	/**
	 * Formats the field value for display.
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $value
	 * @param mixed $post_id
	 */
	public function format_value( $value, $post_id ) {
		$value = str_replace(']]>', ']]&gt;', $value);
		
		/**
		 * Modify the formatted value.
		 *
		 * @since 1.0
		 * @param mixed $value The return value
		 * @param mixed $post_id The current post id or option name
		 * @param object $this Refers to the current field object
		 */	
		$value = apply_filters('wpmudev_field/format_value', $value, $post_id, $this);		
		return apply_filters('wpmudev_field/format_value/' . $this->args['name'], $value, $post_id, $this);
	}

	/**
	 * Sanitizes the field value before saving to database.
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $value
	 * @param mixed $post_id
	 */		
	public function sanitize_for_db( $value, $post_id ) {
		$value = wp_kses_post(stripslashes($value));
		return parent::sanitize_for_db($value, $post_id);
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
		$id = $this->get_id();
		$args = array(
			'wpautop' => $this->args['wpautop'],
			'media_buttons' => $this->args['media_buttons'],
			'textarea_name' => $this->get_name(),
		);
		
		if ( $this->is_subfield ) {
			$args['quicktags'] = false;	// repeatable fields don't support quicktags
		}
		
		$this->before_field();
		wp_editor($value, $id, $args);
		$this->after_field();
	}
}