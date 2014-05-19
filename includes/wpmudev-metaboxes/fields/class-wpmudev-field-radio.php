<?php

class WPMUDEV_Field_Radio extends WPMUDEV_Field {
	/**
	 * Sanitizes the field value before saving to database
	 *
	 * @since 1.0
	 * @access public
	 * @param $value
	 */	
	public function sanitize_for_db( $value ) {
		$value = ( empty($value) ) ? 0 : $value;
		return apply_filters('wpmudev_field_sanitize_for_db', $value, $post_id, $this);
	}

	/**
	 * Displays the field
	 *
	 * @since 3.0
	 * @access public
	 * @param int $post_id
	 * @param bool $echo (optional)
	 */
	public function display( $post_id, $echo = true ) {
		$value = $this->get_value($post_id, false);
		
		if ( $value === false ) {
			$checked = checked($this->args['value'], $this->args['default_value'], false);
		} else {
			$checked = checked($this->args['value'], $value, false);
		}
		
		$html  = '<label class="' . $this->args['label']['class'] . '" for="' . $this->get_id() . '">';
		$html .= '<input type="radio" ' . $this->parse_atts() . ' ' . $checked . ' /> <span>' . $this->args['label']['text'] . '</span></label>';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}