<?php

class WPMUDEV_Field_Radio_Group extends WPMUDEV_Field {
	/**
	 * Runs on construct of parent class
	 *
	 * @since 3.0
	 * @access public
	 * @param array $args {
	 *		An array of arguments
	 *
	 *		@type string $orientation The orientation of each radio field (horizontal or vertical). Defaults to horizontal.
	 *		@type array $options The radio group options in $value => $label format.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = wp_parse_args($this->args, array(
			'orientation' => 'horizontal',
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
	public function display( $post_id = null, $echo = true ) {
		$html = '<div class="wpmudev-radio-group ' . $this->args['orientation'] . '">';
		
		foreach ( $this->args['options'] as $value => $label ) {
			$field = new WPMUDEV_Field_Radio(array_merge($this->args, array(
				'name' => $this->args['name'],
				'value' => $value,
				'default_value' => $this->args['default_value'],
				'label' => array('text' => $label)
			)));
			$html .= $field->display($post_id, false);
		}
		
		$html .= '</div>';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}