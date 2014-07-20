<?php

class WPMUDEV_Field_Text extends WPMUDEV_Field {
	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		?>
		<input type="text" <?php echo $this->parse_atts(); ?> value="<?php echo $this->get_value($post_id); ?>" />
		<?php
	}
}