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
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = wp_parse_args($args, array(
			'value' => 1
		));
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
		$value = $this->get_value($post_id); ?>
		<label class="<?php echo $this->args['label']['class']; ?>" for="<?php echo $this->get_id(); ?>">
		<input type="radio" <?php echo $this->parse_atts(); ?> <?php checked($value, $this->args['value']); ?> /> <span><?php echo $this->args['label']['text']; ?></span></label>
		<?php
	}
}