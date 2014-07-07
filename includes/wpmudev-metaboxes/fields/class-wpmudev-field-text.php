<?php

class WPMUDEV_Field_Text extends WPMUDEV_Field {
	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 * @param bool $echo
	 */
	public function display( $post_id, $echo = true ) {
		$html = '<input type="text" ' . $this->parse_atts() . ' value="' . $this->get_value($post_id, false) . '" />';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}