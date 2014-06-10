<?php

$mp_settings_metaboxes['general_inventory'] = new MP_Settings_Metabox_General_Inventory_Settings();

class MP_Settings_Metabox_General_Inventory_Settings extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('Inventory Settings', 'mp');
		$this->tab = 'general';
	}
	
	function on_creation() {
		
	}
			
	function save_settings( $settings ) {
		
		$settings['inventory_remove'] = mp_get_post_value('mp->inventory_remove', 0);
		return $settings;	
	}
	
	function display_inside() {
		
		?>
		<table class="form-table">
			<tr id="mp-inventory-setting">
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('At what low stock count do you want to be warned for products you have enabled inventory tracking for?', 'mp') ?></div>
					<?php _e('Inventory Warning Threshold', 'mp') ?>
				</th>
				<td>
					<?php
					new MP_Field_Text(array(
						'name' => 'mp[inventory_threshold]',
						'value' => mp_get_setting('inventory_threshhold', 3),
						'class' => 'digits',
					));
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('This will set the product to draft if inventory of all variations is gone.', 'mp') ?></div>
					<?php _e('Hide Out of Stock Products', 'mp') ?>
				</th>
				<td>
					<?php
					new MP_Field_Checkbox(array(
						'name' => 'mp[inventory_remove]',
						'checked' => mp_get_setting('inventory_remove'),
						'value' => '1',
						'label' => array('text' => __('Yes', 'mp')),
					));
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}