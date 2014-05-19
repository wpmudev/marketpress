<?php

$mp_settings_metaboxes['presentation_store_slugs'] = new MP_Settings_Metabox_Presentation_Store_Pages_Settings();

class MP_Settings_Metabox_Presentation_Store_Pages_Settings extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('Store Pages', 'mp');
		$this->tab = 'presentation';
	}
	
	function on_creation() {
		
	}
	
	function save_settings() {
		
	}
	
	function display_inside() {
		
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('This page will be created so you can change it\'s content and the order in which it appears in navigation menus if your theme supports it.', 'mp') ?></div>
					<?php _e('Store Base', 'mp') ?>
				</th>
				<td>/<input type="text" name="mp[slugs][store]" value="<?php echo esc_attr(mp_get_setting('slugs->store')); ?>" size="20" maxlength="50" />/</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Products List', 'mp') ?></th>
				<td>/<?php echo esc_attr(mp_get_setting('slugs->store')); ?>/<input type="text" name="mp[slugs][products]" value="<?php echo esc_attr(mp_get_setting('slugs->products')); ?>" size="20" maxlength="50" />/</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Shopping Cart Page', 'mp') ?></th>
				<td>/<?php echo esc_attr(mp_get_setting('slugs->store')); ?>/<input type="text" name="mp[slugs][cart]" value="<?php echo esc_attr(mp_get_setting('slugs->cart')); ?>" size="20" maxlength="50" />/</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Order Status Page', 'mp') ?></th>
				<td>/<?php echo esc_attr(mp_get_setting('slugs->store')); ?>/<input type="text" name="mp[slugs][orderstatus]" value="<?php echo esc_attr(mp_get_setting('slugs->orderstatus')); ?>" size="20" maxlength="50" />/</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Product Category', 'mp') ?></th>
				<td>/<?php echo esc_attr(mp_get_setting('slugs->store')); ?>/<?php echo esc_attr(mp_get_setting('slugs->products')); ?>/<input type="text" name="mp[slugs][category]" value="<?php echo esc_attr(mp_get_setting('slugs->category')); ?>" size="20" maxlength="50" />/</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Product Tag', 'mp') ?></th>
				<td>/<?php echo esc_attr(mp_get_setting('slugs->store')); ?>/<?php echo esc_attr(mp_get_setting('slugs->products')); ?>/<input type="text" name="mp[slugs][tag]" value="<?php echo esc_attr(mp_get_setting('slugs->tag')); ?>" size="20" maxlength="50" />/</td>
			</tr>
		</table>
		<?php
	}
}
