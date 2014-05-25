<?php

class WPMUDEV_Field_Repeater extends WPMUDEV_Field {
	/**
	 * Stores reference to the repeater's subfields
	 *
	 * @since 3.0
	 * @access public
	 * @var array
	 */
	var $subfields = array();
	
	/**
	 * Displays the field
	 *
	 * @since 3.0
	 * @access public
	 * @param int $post_id
	 * @param bool $echo
	 */
	public function display( $post_id, $echo = true ) {
		$html = '';
		
		foreach ( $this->subfields as $subfield ) {
			$html .= $subfield->display($post_id, false);
		}
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
	
	/**
	 * Adds a sub field to the repeater
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_sub_field( $type, $args ) {
		$class = apply_filters('wpmudev_field_repeater_add_sub_field', 'WPMUDEV_Field_' . ucfirst($type), $type, $args);
		
		if ( ! class_exists($class) ) {
			return false;	
		}
		
		$args['echo'] = false;
		$this->subfields[] = new $class($args);
	}
}