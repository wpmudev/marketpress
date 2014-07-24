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
		$this->args = array_replace_recursive(array(
			'orientation' => 'horizontal',
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
	public function display( $post_id = null ) {
		?>
		<div class="wpmudev-radio-group <?php echo $this->args['orientation']; ?>">
		<?php
		foreach ( $this->args['options'] as $value => $label ) {
			$field = new WPMUDEV_Field_Radio(array_merge($this->args, array(
				'name' => $this->args['name'],
				'value' => $value,
				'default_value' => $this->args['default_value'],
				'label' => array('text' => $label)
			)));
			$field->display($post_id);
		} ?>
		</div>
		<?php
	}
}