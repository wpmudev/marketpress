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
		$has_variations = get_post_meta( $post_id, 'has_variations', false );


		if ( $has_variations ) {

			if ( false === ( $special_query_results = get_transient( 'special_query_results' ) ) ) {
				// It wasn't there, so regenerate the data and save the transient
				$special_query_results = new WP_Query( 'cat=5&order=random&tag=tech&post_meta_key=thumbnail' );
				set_transient( 'special_query_results', $special_query_results, 12 * HOUR_IN_SECONDS );
			}


			$product_attributes			 = MP_Product_Attributes_Admin::get_product_attributes();
			$product_attributes_array	 = array();

			$args = array(
				'post_parent'	 => $post_id,
				'post_type'		 => 'mp_product_variation',
				'posts_per_page' => -1,
				'post_status'	 => 'publish'
			);

			$children = get_children( $args, OBJECT );

			$variation_attributes = array();

			foreach ( $children as $child ) {
				foreach ( $product_attributes as $product_attribute ) {
					$product_attributes_array[ $product_attribute->attribute_id ] = $product_attribute->attribute_name;

					$child_terms = get_the_terms( $child->ID, 'product_attr_' . $product_attribute->attribute_id );
					if ( isset( $child_terms[ 0 ]->term_id ) && $child_terms[ 0 ]->name ) {
						$variation_attributes[ $product_attribute->attribute_id ][ $child_terms[ 0 ]->term_id ] = array( $product_attribute->attribute_id, $child_terms[ 0 ]->name );
					}
				}
			}
			?>
			<div class="tablenav top">

				<div class="alignleft actions bulkactions">
					<label for="bulk-action-selector-top" class="screen-reader-text"><?php _e( 'Select bulk action', 'mp' ); ?></label>
					<select name="action" id="bulk-action-selector-top">
						<option value="-1" selected="selected"><?php _e( 'Bulk Actions', 'mp' ); ?></option>
						<option value="variant_update_images"><?php _e( 'Update Images', 'mp' ); ?></option>
						<option value="variant_update_inventory"><?php _e( 'Update Inventory', 'mp' ); ?></option>
						<option value="variant_update_prices"><?php _e( 'Update Prices', 'mp' ); ?></option>
						<option value="variant_delete"><?php _e( 'Delete Variants', 'mp' ); ?></option>
					</select>
					<input type="submit" name="" id="doaction" class="button action" value="Apply">
				</div>




				<br class="clear">
			</div>
			<span <?php echo $this->parse_atts(); ?>>
				<div class="select_attributes_filter">
					<span class="select_title"><?php _e( 'Select:', 'mp' ); ?> | </span>
					<span class="select_all"><a href="#" class="select_all_link"><?php _e( 'All', 'mp' ); ?></a> | </span>
					<span class="select_none"><a href="#" class="select_none_link"><?php _e( 'None', 'mp' ); ?></a> | </span>
					<?php
					$order = 1;
					foreach ( $variation_attributes as $variation_attribute ) {
						foreach ( $variation_attribute as $term => $term_info ) {
							?>
							<span class="variation_color <?php echo MP_Product_Attributes_Admin::get_product_attribute_color( $term_info[ 0 ], $order ); ?>" data-term-id="<?php echo $term; ?>" data-attribute-id="<?php echo $term_info[ 0 ]; ?>"><a href="#"><?php echo $term_info[ 1 ]; ?></a> <span class="separating-pipe">|</span> </span>
							<?php
						}
						$order++;
					}
					?>
				</div>
				<table class="wp-list-table widefat fixed posts">
					<thead>
						<tr>
							<th scope="col" id="cb" class="manage-column column-cb check-column">
								<input id="cb-select-all" type="checkbox">
							</th>

							<th scope="col" id="title" class="manage-column">
								<?php _e( 'Img.', 'mp' ); ?>
							</th>
							<?php foreach ( array_keys( $variation_attributes ) as $variation_attribute ) { ?>
								<th scope="col" class="manage-column">
									<?php
									echo $product_attributes_array[ $variation_attribute ];
									?>
								</th>
							<?php } ?>

							<th scope="col" id="tags" class="manage-column">
								<?php _e( 'Inventory', 'mp' ); ?>
							</th>

							<th scope="col" id="comments" class="manage-column">
								<?php _e( 'Price', 'mp' ); ?>
							</th>

							<th scope="col" id="date" class="manage-column">
								<?php _e( 'SKU', 'mp' ); ?>
							</th>

							<th scope="col" id="date" class="manage-column">
								<?php _e( 'Weight', 'mp' ); ?>
							</th>
						</tr>
					</thead>

					<tbody id="the-list">
						<?php
						$style = '';
						foreach ( $children as $child ) {
							$style	 = ( isset( $style ) && 'alternate' == $style ) ? '' : 'alternate';
							?>
						<tr id="post-<?php echo $child->ID; ?>" data-id="<?php echo esc_attr($child->ID); ?>" class="hentry <?php echo $style; ?>">
								<th scope="row" class="check-column">
									<input type="checkbox" class="check-column-box" name="selected_variation[]" value="<?php echo esc_attr($child->ID); ?>">
								</th>
								<td class="">
									IMG
								</td>
								<?php
								$order	 = 1;
								foreach ( array_keys( $variation_attributes ) as $variation_attribute ) {
									?>
									<th scope="col" class="manage-column">
										<?php
										$child_term	 = get_the_terms( $child->ID, 'product_attr_' . $variation_attribute );
										$child_term	 = isset( $child_term[ 0 ] ) ? $child_term[ 0 ] : '';
										?>
										<span class="variation_value variation_term_<?php echo isset( $child_term->term_id ) ? esc_attr($child_term->term_id) : ''; ?> <?php echo MP_Product_Attributes_Admin::get_product_attribute_color( $term_info[ 0 ], $order ); ?>" data-term-id="<?php echo isset( $child_term->term_id ) ? esc_attr($child_term->term_id) : ''; ?>" data-attribute-id="<?php echo esc_attr($variation_attribute); ?>"><?php echo is_object( $child_term ) ? esc_attr($child_term->name) : '-'; ?></span>
									</th>
									<?php
									$order++;
								}
								?>
								<td class="">
									&infin;
								</td>
								<td class="field_editable" data-field-type="number">
									<?php
									$price	 = get_post_meta( $child->ID, 'regular_price', true );
									echo mp_format_currency( '', $price, 'original_value field_subtype field_subtype_price', 'currency', array('data-meta' => 'regular_price', 'data-default' => 0));
									?>
									<input type="hidden" class="editable_value" value="" />
								</td>
								<td class="field_editable" data-field-type="text">
									<span class="original_value field_subtype field_subtype_sku" data-meta="sku" data-default="-">
										<?php
										$sku	 = get_post_meta( $child->ID, 'sku', true );
										echo esc_attr( isset( $sku ) && !empty( $sku ) ? $sku : '-'  );
										?>
									</span>
									<input type="hidden" class="editable_value" value="" />
								</td>
								<td class="">
									- Weight
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</span>
			<?php
		} else {
			?>

			<span <?php echo $this->parse_atts(); ?>>
				<div class="repeat">
					<table class="wrapper" width="100%">
						<tbody class="container">
							<tr class="template row">

								<td width="100%">
									<div class="variation-row">
										<div class="variation-first-col">
											<div class="wpmudev-field-label"><?php _e( 'Variation Name', 'mp' ); ?> <span class="mp_meta_small_desc"><?php _e( '(e.g. Color)', 'mp' ); ?></span></div>
											<?php
											$product_taxonomies = MP_Product_Attributes_Admin::get_product_attributes_select( 'product_attributes_categories[]', 'id' );
											?>
											<!--<span class="variation_create_new_title"><?php _e( 'Or create new variation', 'mp' ); ?></span>-->

											<input type="text" class="mp-variation-attribute-name" placeholder="<?php esc_attr_e( __( 'Type variation name', 'mp' ) ); ?>" name="variation_names[]" />
										</div>

										<div class="variation-second-col">
											<div class="wpmudev-field-label"><?php _e( 'Variation Values', 'mp' ); ?> <span class="mp_meta_small_desc"><?php _e( '(e.g. White, Grey, Red etc.)', 'mp' ); ?></span></div>
											<textarea name="variation_values[]" class="variation_values" value="" placeholder="<?php esc_attr_e( __( 'Enter as many values as requred', 'mp' ) ); ?>"></textarea>
										</div>

										<div class="variation-third-col">
											<span class="remove"><i class="fa fa-trash-o fa-lg"></i></span>
										</div>

									</div>
								</td>


							</tr>
						</tbody>
						<tfoot>
							<tr>
								<td width="10%" colspan="4">
									<a class="add mp-add-new-variation button"><?php _e( 'Add Another Variant', 'mp' ); ?></span>
										<a href="" id="mp_make_combinations" class='button button-primary create-variations-button'><?php _e( 'Create Variations', 'mp' ); ?></a>
								</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</span>
			<?php
		}
		/* <input type="text" <?php echo $this->parse_atts(); ?> value="<?php echo $this->get_value($post_id); ?>" /> */
		$this->after_field();
	}

}
