<?php

class WPMUDEV_Field_Complex extends WPMUDEV_Field {
	/**
	 * Stores reference to the subfields
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	var $fields = array();

	/**
	 * Displays the field
	 *
	 * @since 3.0
	 * @access public
	 * @param int $post_id
	 * @param bool $echo
	 */
	public function display( $post_id, $echo = true ) {
		$numfields = count($this->fields);
		$label_width = floor(100 / $numfields) . '%';
		$atts = ' ';
		$html = '';
		
		foreach ( $this->args['custom'] as $key => $att ) {
			if ( strpos($key, 'data-conditional') !== false ) {
				$atts .= $key . '="' . esc_attr($att) . '" ';
			}
		}
		
		$html .= '<div class="wpmudev-field-complex-wrap"' . trim($atts) . '>';
		
		foreach ( $this->fields as $field ) {
			$html .= '<label class="wpmudev-field-complex-label" for="' . $field->get_id() . '" style="width:' . $label_width . '">' . $field->display($post_id, false) . '<span>' . $field->args['label']['text'] . '</span></label>';
		}
		
		$html .= '</div>';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
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
		$args['name'] = str_replace('[]', '', $this->args['name']) . '[' . $args['name'] . '][]'; //sub fields should be an array
		$this->fields[] = new $class($args);
	}
}