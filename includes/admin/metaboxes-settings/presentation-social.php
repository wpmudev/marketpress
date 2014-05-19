<?php

$mp_settings_metaboxes['presentation_social'] = new MP_Settings_Metabox_Presentation_Social_Settings();

class MP_Settings_Metabox_Presentation_Social_Settings extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('Social Settings', 'mp');
		$this->tab = 'presentation';
	}
	
	function on_creation() {
		
	}
	
	function save_settings() {
		
	}
	
	function display_inside() {
		
		?>
		<img src="<?php echo mp_plugin_url('images/134x35_pinterest_logo.png'); ?>" width="134" height="35" alt="Pinterest">
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e('Show "Pin It" button','mp');?></th>
				<td>
					<?php
					new MP_Field_Select( array(
						'name' => 'mp[social][pinterest][show_pinit_button]',
						'selected' => mp_get_setting('social->pinterest->show_pinit_button'),
						'options' => array(
							'off' => __('Off', 'mp'),
							'single_view' => __('Single View', 'mp'),
							'all_view' => __('All View', 'mp'),
						),
					));
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e('Pin Count','mp');?></th>
				<td>
					<?php
					new MP_Field_Select( array(
						'name' => 'mp[social][pinterest][show_pin_count]',
						'selected' => mp_get_setting('social->pinterest->show_pin_count'),
						'options' => array(
							'none' => __('None', 'mp'),
							'above' => __('Above', 'mp'),
							'beside' => __('Beside', 'mp'),
						),
					));
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}
