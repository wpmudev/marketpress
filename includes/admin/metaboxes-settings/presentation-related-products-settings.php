<?php

$mp_settings_metaboxes['presentation_related_products'] = new MP_Settings_Metabox_Presentation_Related_Products_Settings();

class MP_Settings_Metabox_Presentation_Related_Products_Settings extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('Related Products Settings', 'mp');
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
				<th scope="row"><?php _e('Related Product Limit', 'mp') ?></th>
				<td>
					<?php
					new MP_Field_Text(array(
						'name' => 'mp[related_products][show_limit]',
						'value' => intval(mp_get_setting('related_products->show_limit')),
						'size' => 2,
						'class' => 'digits',
					)); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Relate Products By', 'mp') ?></th>
				<td>
					<?php
					new MP_Field_Select(array(
						'name' => 'mp[related_products][relate_by]',
						'options' => array(
							'both' =>  __('Category &amp; Tags', 'mp'),
							'category' => __('Category Only', 'mp'),
							'tags' => __('Tags Only', 'mp')
						),
						'selected' => mp_get_setting('related_products->relate_by'),
					)); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('Unchecking will use the List/Grid View setting.', 'mp') ?></div>
					<?php _e('Show Related Products As Simple List', 'mp') ?>
				</th>
				<td>
					<?php
					new MP_Field_Checkbox(array(
						'name' => 'mp[related_products][simple_list]',
						'checked' => mp_get_setting('related_products->simple_list'),
						'value' => '1',
						'label' => array('text' => __('Yes', 'mp')),
					)); ?>
				</td>
			</tr>
		</table>
		<?php
	}
}
