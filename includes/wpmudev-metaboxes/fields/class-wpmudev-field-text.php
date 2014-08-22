<?php

class WPMUDEV_Field_Text extends WPMUDEV_Field {
	/**
	 * Runs on construct of parent
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 *		Array of arguments. Optional.
	 *
	 *		@type string $after_field Text show after the input field.
	 *		@type string $before_field Text show before the input field.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'before_field' => '',
			'after_field' => '',
		), $args);
	}
	
	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$this->before_field();
		?>
		<input type="text" <?php echo $this->parse_atts(); ?> value="<?php echo $this->get_value($post_id); ?>" />
		<?php
		$this->after_field();
	}
}