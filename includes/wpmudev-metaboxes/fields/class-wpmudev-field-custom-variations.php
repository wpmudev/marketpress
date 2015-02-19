<?php

class WPMUDEV_Field_Variations extends WPMUDEV_Field {

	/**
	 * Runs on construct of parent
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 * 		Array of arguments. Optional.
	 *
	 * 		@type string $after_field Text show after the input field.
	 * 		@type string $before_field Text show before the input field.
	 * }
	 */
	public function on_creation( $args ) {
		$this->args = array_replace_recursive( array(
			'before_field'	 => '',
			'after_field'	 => '',
		), $args );
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
		<span <?php echo $this->parse_atts(); ?>>
			<div class="repeat">
				<table class="wrapper" width="100%">
					<thead>
						<tr>
							<td width="10%" colspan="4"><span class="add">Add</span></td>
						</tr>
					</thead>
					<tbody class="container">
						<tr class="template row">

							<td width="100%">
								<div class="variation-row">
									<div class="variation-first-col">
										<div class="wpmudev-field-label"><?php _e( 'Variation Name', 'mp' ); ?> <span class="mp_meta_small_desc"><?php _e( '(e.g. Color)', 'mp' ); ?></span></div>
										<?php
										$product_taxonomies	 = MP_Product_Attributes_Admin::get_product_attributes_select( 'product_attributes_categories[]', 'name' );
										?>
										<span class="variation_create_new_title"><?php _e( 'Or create new variation', 'mp' ); ?></span>

										<input type="text" placeholder="<?php esc_attr_e( __( 'Type variation name', 'mp' ) ); ?>" name="variation_names[]" />
									</div>

									<div class="variation-second-col">
										<div class="wpmudev-field-label"><?php _e( 'Variation Values', 'mp' ); ?> <span class="mp_meta_small_desc"><?php _e( '(e.g. White, Grey, Red etc.)', 'mp' ); ?></span></div>
										<textarea name="variation_values[]" value="" placeholder="<?php esc_attr_e( __( 'Enter as many values as requred', 'mp' ) ); ?>"></textarea>
									</div>

									<div class="variation-third-col">
										<span class="remove"><i class="fa fa-trash-o fa-lg"></i></span>
									</div>

								</div>
							</td>


						</tr>
					</tbody>
				</table>
			</div>
		</span>
		<?php
		/* <input type="text" <?php echo $this->parse_atts(); ?> value="<?php echo $this->get_value($post_id); ?>" /> */
		$this->after_field();
	}

}
