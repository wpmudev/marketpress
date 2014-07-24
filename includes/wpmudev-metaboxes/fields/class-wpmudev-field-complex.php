<?php

class WPMUDEV_Field_Complex extends WPMUDEV_Field {
	/**
	 * Stores reference to the subfields
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	var $subfields = array();
	
	/**
	 * Gets the field value from the database.
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $post_id
	 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
	 * @return mixed
	 */
	public function get_value( $post_id, $raw = false ) {
		if ( ! is_null($this->_value) ) {
			return $this->_value;
		}
		
		$value = array();		
		foreach ( $this->subfields as $subfield ) {
			$meta_key = $this->args['original_name'] . '_' . $subfield->args['original_name'];
			$value[$subfield->args['original_name']] = $subfield->get_value($post_id, $meta_key, $raw);
		}

		/**
		 * Modify the returned value.
		 *
		 * @since 1.0
		 * @param mixed $value The return value
		 * @param mixed $post_id The current post id or option name
		 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
		 * @param object $this Refers to the current field object		 
		 */
		$value = apply_filters('wpmudev_field_get_value', $value, $post_id, $raw, $this);
		$value = apply_filters('wpmudev_field_get_value_' . $this->args['name'], $value, $post_id, $raw, $this);
		
		return $value;
	}
	
	/**
	 * Saves the field to the database.
	 *
	 * @since 1.0
	 * @access public
	 * @action save_post
	 * @param int $post_id
	 * @param string $meta_key The meta key to use when storing the field value. Defaults to null.
	 * @param mixed $value The value of the field. Defaults to null.
	 * @param bool $force Whether to bypass the is_subfield check. Subfields normally don't run their own save routine. Defaults to false.
	 */
	public function save_value( $post_id, $meta_key = null, $value = null, $force = false ) {
		if ( $this->is_subfield && ! $force ) {
			return;
		}
		
		if ( is_null($value) ) {
			$post_key = $this->get_post_key();
			$value = $this->get_post_value($post_key, null);
		}
		
		if ( is_null($meta_key) ) {
			$meta_key = $this->get_meta_key();
		}
		
		/**
		 * Modify the value before it's saved to the database. Return null to bypass internal saving mechanisms.
		 *
		 * @since 1.0
		 * @param mixed $value The field's value
		 * @param mixed $post_id The current post id or option name
		 * @param object $this Refers to the current field object
		 */
		$value = apply_filters('wpmudev_field_save_value', $this->sanitize_for_db($value), $post_id, $this);
		$value = apply_filters('wpmudev_field_save_value_' . $this->args['name'], $value, $post_id, $this);
		
		if ( is_null($value) ) {
			return;
		}
		
		$sub_meta_keys = array();
		foreach ( $this->subfields as $index => $subfield ) {
			$sub_meta_key = $sub_meta_keys[] = $meta_key . '_' . $subfield->args['original_name'];
			update_post_meta($post_id, $sub_meta_key, array_shift($value));
			update_post_meta($post_id, '_' . $sub_meta_key, get_class($subfield));					
		}
		
		update_post_meta($post_id, $meta_key, $sub_meta_keys);
		update_post_meta($post_id, '_' . $meta_key, get_class($this));
	}
	
	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 * @param bool $echo
	 */
	public function display( $post_id  ) {
		$numfields = count($this->subfields);
		$label_width = floor(100 / $numfields) . '%';
		$value = $this->get_value($post_id);
		$class = 'wpmudev-field-complex-wrap clearfix';
		$atts = '';
		
		foreach ( $this->args['custom'] as $key => $att ) {
			if ( strpos($key, 'data-conditional') !== false ) {
				$atts .= ' ' . $key . '="' . esc_attr($att) . '"';
			}
		}
		
		if ( strlen($atts) > 0 ) {
			$class .= ' wpmudev-field-has-conditional';
		} ?>
		<div class="<?php echo $class; ?>"<?php echo $atts; ?>>
			<?php
			foreach ( $this->subfields as $field ) :
				if ( ! is_null($this->subfield_id) ) {
					$field->set_subfield_id($this->subfield_id);
				}
				
				if ( is_array($value) && ($value = array_shift($value)) ) {
					$field->set_value($value);
				} ?>
			<label class="wpmudev-field-complex-label" style="width:<?php echo $label_width ; ?>"><?php $field->display($post_id); ?><span><?php echo $field->args['label']['text']; ?></span></label>
			<?php
			endforeach; ?>
		</div>
		<?php
	}
	
	/**
	 * Adds a sub field to the complex field
	 *
	 * @since 1.0
	 * @access public
	 * @param string $type The type of field to add.
	 * @param array $args @see WPMUDEV_Field construct
	 */
	public function add_field( $type, $args ) {
		$class = apply_filters('wpmudev_field_complex_add_field', 'WPMUDEV_Field_' . ucfirst($type), $type, $args);
		
		if ( ! class_exists($class) ) {
			return false;	
		}
		
		//subfields don't support validation (yet) so make sure these arguments are reset accordingly
		$args['validation'] = array();
		$args['custom_validation_message'] = '';
		
		$args['echo'] = false;
		$args['original_name'] = $args['name'];
		
		if ( $this->is_subfield ) {
			$args['name'] = str_replace('[new]', '[' . $args['name'] . '][new]', $this->args['name']);
		} else {
			$args['name'] = $this->args['name'] . '[' . $args['name'] . ']';
		}
		
		$field = new $class($args);
		$field->is_subfield = true;
		
		$this->subfields[] = $field;
	}
}