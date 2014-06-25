<?php

class WPMUDEV_Field_Select extends WPMUDEV_Field {
	/**
	 * Runs on parent construct
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 * 		An array of arguments.
	 *
	 *		@type array $options The options of the select field in key => value format.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = wp_parse_args($args, array(
			'options' => array(),
		));
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
		$value = $this->get_value($post_id, false);
	
		$html = '<select ' . $this->parse_atts() . '>';
		
		foreach ( $this->args['options'] as $val => $label ) {
			if ( $value === false ) {
				$selected = selected($val, $this->args['default_value'], false);
			} else {
				$selected = checked($val, $value, false);
			}

			$html .= '<option value="' . esc_attr($val) . '" ' . $selected . '>' . esc_attr($label) . '</option>';	
		}
		
		$html .= '</select>';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}