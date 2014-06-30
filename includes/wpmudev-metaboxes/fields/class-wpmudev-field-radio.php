<?php

class WPMUDEV_Field_Radio extends WPMUDEV_Field {
	/**
	 * Runs on construct of parent
	 *
	 * @param array $args {
	 *		An array of arguments.
	 *
	 *		@see WPMUDEV_Field::__construct() for core list of available arguments.
	 *		@type array $fields A value => label list of fields.
	 * }
	 */
	public function on_creation( $args ) {
	}
	
	/**
	 * Sanitizes the field value before saving to database
	 *
	 * @since 1.0
	 * @access public
	 * @param $value
	 */	
	public function sanitize_for_db( $value ) {
		$value = ( empty($value) ) ? 0 : $value;
		parent::sanitize_for_db($value);
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
		
		$html  = '<label class="' . $this->args['label']['class'] . '" for="' . $this->get_id() . '">';
		$html .= '<input type="radio" ' . $this->parse_atts() . ' ' . checked($value, $this->args['value'], false) . ' /> <span>' . $this->args['label']['text'] . '</span></label>';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}