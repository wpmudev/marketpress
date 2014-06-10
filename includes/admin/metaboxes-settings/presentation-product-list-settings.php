<?php

$mp_settings_metaboxes['presentation_product_list'] = new MP_Settings_Metabox_Presentation_List_Settings();

class MP_Settings_Metabox_Presentation_List_Settings extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('List Settings', 'mp');
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
			<th scope="row"><?php _e('How should products be displayed?', 'mp') ?></th>
			<td>
				<?php
				new MP_Field_Radio(array(
					'name' => 'mp[list_view]',
					'checked' => mp_get_setting('list_view'),
					'value' => 'list',
					'label' => array('text' => __('List', 'mp'), 'class' => 'mp-column'),
				));
				
				new MP_Field_Radio(array(
					'name' => 'mp[list_view]',
					'checked' => mp_get_setting('list_view'),
					'value' => 'grid',
					'label' => array('text' => __('Grid', 'mp'), 'class' => 'mp-column last'),
				)); ?>
			</td>
			</tr>
			<tr>
			<th scope="row"><?php _e('Checkout Button Type', 'mp') ?></th>
			<td>
				<?php
				new MP_Field_Radio(array(
					'name' => 'mp[list_button_type]',
					'checked' => mp_get_setting('list_button_type'),
					'value' => 'addcart',
					'label' => array('text' => __('Add To Cart', 'mp'), 'class' => 'mp-column'),
				));
				
				new MP_Field_Radio(array(
					'name' => 'mp[list_button_type]',
					'checked' => mp_get_setting('list_button_type'),
					'value' => 'buynow',
					'label' => array('text' => __('Buy Now', 'mp'), 'class' => 'mp-column last'),
				)); ?>
			</td>
			</tr>
			<tr>
			<th scope="row"><?php _e('Show Product Thumbnail', 'mp') ?></th>
			<td>
				<?php
				new MP_Field_Checkbox(array(
					'name' => 'mp[show_thumbnail]',
					'checked' => mp_get_setting('show_thumbnail'),
					'value' => '1',
					'label' => array('text' => __('Yes', 'mp')),
				)); ?>
			</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Product Thumbnail Size', 'mp') ?></th>
				<td>
					<?php
					new MP_Field_Select(array(
						'name' => 'mp[list_img_size]',
						'class' => 'mp-product-img-size',
						'options' => array(
							'thumbnail' => __('WP Thumbnail Size', 'mp'),
							'medium' => __('WP Medium Size', 'mp'),
							'large' => __('WP Large Size', 'mp'),
							'custom' => __('Custom Size', 'mp'),
						),
						'selected' => mp_get_setting('list_img_size'),
					)); ?>
				</td>
			</tr>
			<tr class="mp-product-img-size-custom-values"<?php echo ( 'custom' == mp_get_setting('list_img_size') ) ? '' : ' style="display:none"'; ?>>
				<th scope="row"><?php _e('Product Thumbnail Dimensions', 'mp'); ?></th>
				<td>
					<?php
					new MP_Field_Text(array(
						'name' => 'mp[list_img_width]',
						'value' => esc_attr(mp_get_setting('list_img_width')),
						'size' => 3,
					));
					
					new MP_Field_Text(array(
						'name' => 'mp[list_img_height]',
						'value' => esc_attr(mp_get_setting('list_img_height')),
						'size' => 3,
					)); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Show Excerpts', 'mp') ?></th>
				<td>
					<?php
						new MP_Field_Checkbox(array(
							'name' => 'mp[show_excerpt]',
							'checked' => mp_get_setting('show_excerpt'),
							'value' => '1',
							'label' => array('text' => __('Yes', 'mp')),
						)); ?>
				</td>
			</tr>
			<tr class="mp-paginate-products-row">
				<th scope="row"><?php _e('Paginate Products', 'mp') ?></th>
				<td>
					<?php
					new MP_Field_Checkbox(array(
						'name' => 'mp[paginate]',
						'checked' => mp_get_setting('paginate'),
						'value' => '1',
						'label' => array('text' => __('Yes', 'mp')),
					)); ?>
				</td>
			</tr>
			<tr class="mp-products-per-page-row"<?php echo mp_get_setting('paginate') ? '' : ' style="display:none"'; ?>>
				<th scope="row"><?php _e('Products Per Page', 'mp') ?></th>
				<td>
					<?php
					new MP_Field_Text(array(
						'name' => 'mp[per_page]',
						'value' => esc_attr(mp_get_setting('per_page', 20)),
						'size' => 2,
						'class' => 'digits',
					)); ?>
				</td>								
			<tr>
				<th scope="row"><?php _e('Order Products By', 'mp') ?></th>
				<td>
					<?php
						new MP_Field_Select(array(
							'name' => 'mp[order_by]',
							'options' => array(
								'title' => __('Product Name', 'mp'),
								'date' => __('Publish Date', 'mp'),
								'ID' =>  __('Product ID', 'mp'),
								'author' => __('Product Author', 'mp'),
								'sales' => __('Number of Sales', 'mp'),
								'price' => __('Product Price', 'mp'),
								'rand' => __('Random', 'mp'),
							),
							'selected' => mp_get_setting('order_by'),
						)); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e('Sort Order', 'mp') ?>
				</th>
				<td>
					<?php
					new MP_Field_Radio(array(
						'name' => 'mp[order]',
						'checked' => mp_get_setting('order'),
						'value' => 'ASC',
						'label' => array('text' => __('Ascending', 'mp'), 'class' => 'mp-column'),
					));
					
					new MP_Field_Radio(array(
						'name' => 'mp[order]',
						'checked' => mp_get_setting('order'),
						'value' => 'DESC',
						'label' => array('text' => __('Descending', 'mp'), 'class' => 'mp-column last'),
					)); ?>
				</td>
			</tr>
			<tr>
			<th scope="row">
				<a class="mp-help-icon" href="javascript:;"></a>
				<div class="mp-help-text"><?php _e('Show "Product Category" and "Order By" filters at the top of listings pages. Uses AJAX for instant updates based on user selection.', 'mp') ?></div>
				<?php _e('Show Product Filters', 'mp') ?>
			</th>
			<td>
				<?php
				new MP_Field_Checkbox(array(
					'name' => 'mp[show_filters]',
					'checked' => mp_get_setting('show_filters'),
					'value' => '1',
					'label' => array('text' => __('Yes', 'mp')),
				)); ?>
			</td>
			</tr>
		</table>
		<?php
	}
}
