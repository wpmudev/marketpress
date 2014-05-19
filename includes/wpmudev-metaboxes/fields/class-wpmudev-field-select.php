<?php

class WPMUDEV_Field_Select extends WPMUDEV_Field {
	/**
	 * Displays the field
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo
	 */
	public function display( $echo = true ) {
		$html = '<select ' . $this->parse_atts() . '>';
		
		foreach ( $this->args['options'] as $val => $label ) {
			$html .= '<option value="' . esc_attr($val) . '" ' . selected($this->args['selected'], $val, false) . '>' . esc_attr($label) . '</option>';	
		}
		
		$html .= '</select>';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}