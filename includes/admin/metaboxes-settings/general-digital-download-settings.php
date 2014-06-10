<?php

$mp_settings_metaboxes[] = new MP_Settings_Metabox_Digital_Downloads();

class MP_Settings_Metabox_Digital_Downloads extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('Digital Download Settings', 'mp');
		$this->tab = 'general';
	}
	
	function on_creation() {
		
	}
		
	function save_settings( $settings ) {
		
		$settings['download_order_limit'] = mp_get_post_value('mp->download_order_limit', 0);
		return $settings;
	}
	
	function display_inside() {
		
		?>
		<table class="form-table">
			<tr id="mp-downloads-setting">
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('How many times may a customer download a file they have purchased? (It\'s best to set this higher than one in case they have any problems downloading)', 'mp') ?></div>
					<?php _e('Maximum Downloads', 'mp') ?>
				</th>
				<td>
					<?php
					new MP_Field_Text(array(
						'name' => 'mp[max_downloads]',
						'class' => 'digits',
						'value' => mp_get_setting('max_downloads', 5),
					));
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('This will prevent multiples of the same downloadable product form being added to the cart. Per-product custom limits will override this.', 'mp') ?></div>
					<?php _e('Limit Digital Products Per-order', 'mp') ?>
				</th>
				<td>
					<?php
					new MP_Field_Checkbox(array(
						'name' => 'mp[download_order_limit]',
						'checked' => mp_get_setting('download_order_limit', 1),
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