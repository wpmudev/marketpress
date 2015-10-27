<?php

class WPMUDEV_Field_Checkbox_Group extends WPMUDEV_Field {

	/**
	 * Runs on construct of parent class
	 *
	 * @since 3.0
	 * @access public
	 * @param array $args {
	 * 		An array of arguments
	 *
	 * 		@type string $orientation The orientation of each radio field (horizontal or vertical). Defaults to horizontal.
	 * 		@type array $options The radio group options in $value => $label format.
	 * 		@type bool $use_options_values Whether or not to use 0/1 for values or use the value in the $options array.
	 * 		@type string $width The width of each checkbox with it's label. Optional.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive( array(
			'orientation'		 => 'horizontal',
			'options'			 => array(),
			'use_options_values' => false,
			'width'				 => null,
		), $args );
	}

	/**
	 * Displays the field
	 *
	 * @since 3.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id = null ) {
		if ( is_null( $this->args[ 'width' ] ) ) {
			$width = '100%';
			if ( count( $this->args[ 'options' ] ) > 0 ) {
				$width = max( floor( 100 / count( $this->args[ 'options' ] ) ), 20 ) . '%';
			}
		} else {
			$width = $this->args[ 'width' ];
		}

		$this->before_field();
		?>
		<div class="wpmudev-checkbox-group <?php echo $this->args[ 'orientation' ]; ?>">
			<?php
			$index = 0;
			foreach ( $this->args['options'] as $value => $label ) {
				//we will need to update the id field
				if ( ! empty( $this->args['id'] ) ) {
					$this->args['id'] = $this->args['id'] . '_' . $index;
				}
				$field = new WPMUDEV_Field_Checkbox( array_replace_recursive( $this->args, apply_filters( 'WPMUDEV_Field_Checkbox_Group_Arguments_' . $value, array(
					'conditional'   => false,
					'name'          => $this->args['name'] . '[' . $value . ']',
					'default_value' => $this->args['default_value'],
					'value'         => ( $this->args['use_options_values'] ) ? $value : 1,
					'message'       => $label,
					'width'         => $width,
				) ) ) );
				$field->display( $post_id );
				$index ++;
			}
			?>
		</div>
		<?php
		$this->after_field();
	}

}
