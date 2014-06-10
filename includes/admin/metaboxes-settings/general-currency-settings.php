<?php

global $mp_settings_metaboxes;
$mp_settings_metaboxes['general_currency'] = new MP_Settings_Metabox_General_Currency();

class MP_Settings_Metabox_General_Currency extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('Currency Settings', 'mp');
		$this->tab = 'general';
		$this->description = __('Settings to determine how currency is displayed');
	}
	
	function on_creation() {
		add_action('wp_ajax_mp_change_currency', array(&$this, 'ajax_change_currency'));		
	}

	function ajax_change_currency() {
		global $mp_settings_metaboxes;
		
		//update settings
		check_ajax_referer('mp-change-currency', 'mp_change_currency_nonce');
		$settings = get_option('mp_settings');
		$settings['currency'] = $_POST['currency'];
		
		//we don't want to save here - just update the settings cache so we can output the appropriate html below
		wp_cache_set('mp_settings', $settings, 'mp');
		
		$this->display_inside();
				
		exit;
	}

	function print_scripts() {
		?>
		<script type="text/javascript">
			 jQuery(document).ready(function($) {
			 	// #mp-base-province
				$('#mp-general-currency-settings').on('change', '#mp-currency-select', function() {
					var $this = $(this),
							$metabox = $this.closest('.inside'),
							data = [
								{
									"name" : "currency",
									"value" : $this.val()
								},{
									"name" : "mp_change_currency_nonce",
									"value" : "<?php echo wp_create_nonce('mp-change-currency'); ?>"
								},{
									"name" : "action",
									"value" : "mp_change_currency"
								}
							];
					
					$metabox.html(MarketPress.ajaxLoader);
					
					$.post(ajaxurl, $.param(data)).done(function(resp){
						$metabox.html(resp);
						
						mp_chosen($metabox.find('.mp-chosen-select'));
						mp_tooltips($metabox.find('.mp-help-icon'));
					});
				});
			});
		</script>
		<?php
	}
	
	function save_settings( $settings ) {
		
		$settings['curr_decimal'] = mp_get_post_value('mp->curr_decimal', 0);
		return $settings;
	}
	
	function display_inside() {
		
		?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e('Store Currency', 'mp') ?></th>
				<td>
					<?php
					$currencies = apply_filters('mp_currencies', mp()->currencies);
					$options = array();
					
					foreach ( $currencies as $key => $value ) {
						$options[$key] = esc_attr($value[0]) . ' - ' . mp_format_currency($key);
					}
					
					new MP_Field_Select(array(
						'name' => 'mp[currency]',
						'class' => 'mp-chosen-select',
						'id' => 'mp-currency-select',
						'options' => $options,
						'selected' => mp_get_setting('currency'),
					));
					?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Currency Symbol Position', 'mp') ?></th>
				<td>
					<div class="mp-column">
						<?php
						new MP_Field_Radio(array(
							'name' => 'mp[curr_symbol_position]',
							'checked' => mp_get_setting('curr_symbol_position'),
							'value' => '1',
							'label' => array('class' => 'mp-label', 'text' => mp_format_currency(mp_get_setting('currency')) . '100'),
						));
						
						new MP_Field_Radio(array(
							'name' => 'mp[curr_symbol_position]',
							'checked' => mp_get_setting('curr_symbol_position'),
							'value' => '2',
							'label' => array('class' => 'mp-label', 'text' => mp_format_currency(mp_get_setting('currency')) . ' 100'),
						));
						?>
					</div>
					<div class="mp-column last">
						<?php
						new MP_Field_Radio(array(
							'name' => 'mp[curr_symbol_position]',
							'checked' => mp_get_setting('curr_symbol_position'),
							'value' => '3',
							'label' => array('class' => 'mp-label', 'text' => '100' . mp_format_currency(mp_get_setting('currency'))),
						));
						
						new MP_Field_Radio(array(
							'name' => 'mp[curr_symbol_position]',
							'checked' => mp_get_setting('curr_symbol_position'),
							'value' => '4',
							'label' => array('class' => 'mp-label', 'text' => '100 ' .mp_format_currency(mp_get_setting('currency'))),
						));
						?>			
					</div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e('Show Decimal in Prices', 'mp') ?></th>
				<td>
					<?php
					new MP_Field_Checkbox(array(
						'name' => 'mp[curr_decimal]',
						'checked' => mp_get_setting('curr_decimal'),
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
