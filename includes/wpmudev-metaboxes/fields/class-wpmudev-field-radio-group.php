<?php

class WPMUDEV_Field_Radio_Group extends WPMUDEV_Field {
	/**
	 * Runs on construct of parent class
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param array $args {
	 *        An array of arguments
	 *
	 * @type string $orientation The orientation of each radio field (horizontal or vertical). Defaults to horizontal.
	 * @type array $options The radio group options in $value => $label format.
	 * @type string $width The width of each checkbox with it's label. Optional.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive( array(
			'orientation' => 'horizontal',
			'options'     => array(),
			'width'       => null,
		), $args );
	}

	/**
	 * Displays the field
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param int $post_id
	 */
	public function display( $post_id = null ) {
		$args = $this->args;

		if ( is_null( $args['width'] ) ) {
			$width = '100%';
			if ( count( $args['options'] ) > 0 ) {
				$width = max( floor( 100 / count( $args['options'] ) ), 20 ) . '%';
			}
		} else {
			$width = $this->args['width'];
		}


		$this->before_field(); ?>
		<div class="wpmudev-radio-group <?php echo $args['orientation']; ?>">
			<?php
			$index = 0;
			foreach ( $args['options'] as $value => $label ) {
				/* these attributes are already set so we don't want to re-run the
				initialization logic for each radio field */
				$args['conditional'] = false;

			$args['value'] = $value;
			$args['label'] = array( 'text' => $label );
			$args['width'] = $width;
			//we will need to update the id field
			if ( ! empty( $args['id'] ) ) {
				$args['id'] = $args['id'] . '_' . $index;
			}
			$field = new WPMUDEV_Field_Radio( $args );
			$field->display( $post_id );
			$index ++;
		} ?>
		</div>
		<?php
		$this->after_field();
	}
}