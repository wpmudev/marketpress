<?php

class WPMUDEV_Field_Select extends WPMUDEV_Field {
	/**
	 * Runs on parent construct
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 * 		An array of arguments.
	 *
	 *		@type array $options The options of the select field in key => value format.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive(array(
			'options' => array(),
		), $args);
	}
	
	/**
	 * Displays the field
	 *
	 * @since 3.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		$value = $this->get_value($post_id); ?>
		<select <?php echo $this->parse_atts(); ?>>
		<?php
		foreach ( $this->args['options'] as $val => $label ) : ?>
			<option value="<?php echo esc_attr($val); ?>" <?php selected($val, $value); ?>><?php echo esc_attr($label); ?></option>
		<?php
		endforeach; ?>
		</select>
		<?php
	}
}