<?php

$mp_settings_metaboxes['single_product'] = new MP_Settings_Metabox_Presentation_Single_Product_Settings();

class MP_Settings_Metabox_Presentation_Single_Product_Settings extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('Single Product Settings', 'mp');
		$this->tab = 'presentation';
	}
	
	function on_creation() {
		
	}
	
	function save_settings() {
		
	}
	
	function display_inside() {
		
		?>
		<table class="form-table">
			<tr>
			<th scope="row"><?php _e('Checkout Button Type', 'mp') ?></th>
			<td>
				<label class="mp-column"><input value="addcart" name="mp[product_button_type]" type="radio"<?php checked(mp_get_setting('product_button_type'), 'addcart') ?> /> <?php _e('Add To Cart', 'mp') ?></label>
				<label class="mp-column last"><input value="buynow" name="mp[product_button_type]" type="radio"<?php checked(mp_get_setting('product_button_type'), 'buynow') ?> /> <?php _e('Buy Now', 'mp') ?></label>
			</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Show Quantity Option', 'mp') ?></th>
				<td>
					<label><input value="1" name="mp[show_quantity]" type="checkbox"<?php checked(mp_get_setting('show_quantity'), 1) ?> /> <?php _e('Yes', 'mp') ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Show Product Image', 'mp') ?></th>
				<td>
					<label><input value="1" name="mp[show_img]" type="checkbox"<?php checked(mp_get_setting('show_img'), 1) ?> /> <?php _e('Yes', 'mp') ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Product Image Size', 'mp') ?></th>
				<td>
					<select class="mp-product-img-size" name="mp[product_img_size]">
						<option value="thumbnail"<?php selected(mp_get_setting('product_img_size', 'thumbnail')); ?>><?php _e('WP Thumbnail Size', 'mp'); ?></option>
						<option value="medium"<?php selected(mp_get_setting('product_img_size', 'medium')); ?>><?php _e('WP Medium Size', 'mp'); ?></option>
						<option value="large"<?php selected(mp_get_setting('product_img_size', 'large')); ?>><?php _e('WP Large Size', 'mp'); ?></option>
						<option value="custom"<?php selected(mp_get_setting('product_img_size', 'custom')); ?>><?php _e('Custom Size', 'mp'); ?></option>
					</select>
				</td>
			</tr>
			<tr class="mp-product-img-size-custom-values"<?php echo ( 'custom' == mp_get_setting('product_img_size') ) ? '' : ' style="display:none"'; ?>>
				<th scope="row"><?php _e('Product Image Dimensions', 'mp'); ?></th>
				<td>
					<label><?php _e('Height', 'mp') ?><input size="3" name="mp[product_img_height]" value="<?php echo esc_attr(mp_get_setting('product_img_height')) ?>" type="text" /></label>&nbsp;
					<label><?php _e('Width', 'mp') ?><input size="3" name="mp[product_img_width]" value="<?php echo esc_attr(mp_get_setting('product_img_width')) ?>" type="text" /></label>
				</td>
			</tr>
			</tr>
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('Makes clicking the single product image open an instant zoomed preview.', 'mp') ?></div>
					<?php _e('Show Image Lightbox', 'mp') ?>
				</th>
				<td>
					<label><input value="1" name="mp[show_lightbox]" type="checkbox"<?php checked(mp_get_setting('show_lightbox'), 1) ?> /> <?php _e('Yes', 'mp') ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('Disables "Display Larger Image" function. Clicking a product image will not display a larger image.', 'mp') ?></div>
					<?php _e('Disable Large Image display', 'mp') ?></th>
				<td>
					<label><input value="1" name="mp[disable_large_image]" type="checkbox"<?php checked(mp_get_setting('disable_large_image'), 1) ?> /> <?php _e('Yes', 'mp') ?></label>
				</td>
			</tr>
			<tr class="mp-related-products-row">
				<th scope="row"><?php _e('Show Related Products', 'mp') ?></th>
				<td>
					<label><input value="1" name="mp[related_products][show]" type="checkbox"<?php checked(mp_get_setting('related_products->show'), 1) ?> /> <?php _e('Yes', 'mp') ?></label>
				</td>
			</tr>
		</table>
		<?php
	}
}
