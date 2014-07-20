<?php

class WPMUDEV_Field_Textarea extends WPMUDEV_Field {
	/**
	 * Displays the field
	 *
	 * @since 3.0
	 * @access public
	 * @param int $post_id
	 * @param bool $echo
	 */
	public function display( $post_id, $echo = true ) {
		?>
		<textarea <?php echo $this->parse_atts(); ?>><?php echo esc_textarea($this->get_value($post_id)); ?></textarea>
		<?php
	}
}