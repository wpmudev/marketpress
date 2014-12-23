<?php

class WPMUDEV_Field_Radio extends WPMUDEV_Field {
	/**
	 * Runs on construct of parent
	 *
	 * @param array $args {
	 *		An array of arguments.
	 *
	 *		@see WPMUDEV_Field::__construct() for core list of available arguments.
	 *		@type mixed $value The value of the field.
	 *		@type string $width The width of the radio label (for horizontal radio groups)
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'value' => 1,
			'width' => '100%',
		), $args);
	}
	
	/**
	 * Sanitizes the field value before saving to database
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $value
	 * @param mixed $post_id
	 */	
	public function sanitize_for_db( $value, $post_id ) {
		$value = ( empty($value) ) ? 0 : $value;
		return parent::sanitize_for_db($value);
	}

	/**
	 * Displays the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$value = $this->get_value($post_id);
		$this->before_field(); ?>
		<label style="<?php echo ( $this->args['orientation'] == 'horizontal' ) ? 'width:' . $this->args['width'] : ''; ?>" class="<?php echo $this->args['label']['class']; ?>" for="<?php echo $this->get_id(); ?>">
		<input type="radio" <?php echo $this->parse_atts(); ?> <?php checked($value, $this->args['value']); ?> /> <span><?php echo $this->args['label']['text']; ?></span></label>
		<?php
		$this->after_field();
	}
}