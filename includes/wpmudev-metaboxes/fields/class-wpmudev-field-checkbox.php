<?php

class WPMUDEV_Field_Checkbox extends WPMUDEV_Field {
	/**
	 * Runs on construct of parent class
	 *
	 * @since 3.0
	 * @access public
	 * @param array $args {
	 *		An array of arguments.
	 *
	 *		@type mixed $value The value of the checkbox when checked. Optional. Defaults to 1.
	 *		@type string $message The message that should be displayed next to the checkbox (e.g. Yes, No, etc). Optional. Defaults to "Yes".
	 *		@type bool $is_toggl_switch If the checkbox is a toggle switch instead of a normal checkbox. Optional. Defaults to true.
	 *		@type string $orientation
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'value' => 1,
			'message' => __('Yes', 'wpmudev_metaboxes'),
			'orientation' => '',
		), $args);
	}
	
	/**
	 * Sanitizes the field value before saving to database
	 *
	 * @since 1.0
	 * @access public
	 * @param $value
	 * @param $post_id
	 */	
	public function sanitize_for_db( $value, $post_id ) {
		$value = ( empty($value) ) ? 0 : $value;
		return parent::sanitize_for_db($value, $post_id);
	}

	/**
	 * Displays the field
	 *
	 * @since 3.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$value = $this->get_value($post_id);
		$this->before_field(); ?>
		<label style="<?php echo ( $this->args['orientation'] == 'horizontal' ) ? 'width:' . $this->args['width'] : ''; ?>" class="<?php echo $this->args['label']['class']; ?>" for="<?php echo $this->get_id(); ?>">
		<input type="checkbox" <?php echo $this->parse_atts(); ?> <?php checked($value, $this->args['value']); ?> /> <span><?php echo $this->args['message']; ?></span></label>
		<?php
		$this->after_field();
	}
}