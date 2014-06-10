<?php

global $mp_settings_metaboxes;
$mp_settings_metaboxes['general_tax'] = new MP_Settings_Metabox_General_Tax_Settings();

class MP_Settings_Metabox_General_Tax_Settings extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('Tax Settings', 'mp');
		$this->tab = 'general';
		$this->order = 2;
	}
	
	function on_creation() {
		
	}	

	function save_settings( $settings ) {
		$settings['tax']['tax_shipping'] = mp_get_post_value('mp->tax->tax_shipping', 0);
		$settings['tax']['tax_inclusive'] = mp_get_post_value('mp->tax->tax_inclusive', 0);
		$settings['tax']['tax_digital'] = mp_get_post_value('mp->tax->tax_digital', 0);
		$settings['tax']['downloadable_address'] = mp_get_post_value('mp->tax->downloadable_address', 0);

		if ( isset($_POST['mp']['tax']['canada_rate']) ) {
			foreach	( $_POST['mp']['tax']['canada_rate'] as $key => $rate ) {
				$tax_rate = $rate * .01;
				$settings['tax']['canada_rate'][$key] = ($tax_rate < 1 && $tax_rate >= 0) ? $tax_rate : 0;
			}
		} else {
			$tax_rate = $_POST['mp']['tax']['rate'] * .01;
			$settings['tax']['rate'] = ($tax_rate < 1 && $tax_rate >= 0) ? $tax_rate : 0;
		}
		
		return $settings;
	}
	
	function display_inside( $echo = true ) {
		$html = '
		<table class="form-table">';

		switch (mp_get_setting('base_country')) {
			case 'US':
				$html .= '
				<tr>
					<th scope="row">' . sprintf(__('%s Tax Rate', 'mp'), esc_attr(mp()->usa_states[mp_get_setting('base_province', 'CA')])) . '</th>
					<td>' .
						new MP_Field_Text(array(
							'echo' => false,
							'name' => 'mp[tax][rate]',
							'class' => 'number',
							'value' => mp_get_setting('tax->rate') * 100,
							'size' => 3,
							'style' => 'text-align:right',
						)) . ' %
					</td>
				</tr>';
			break;
		
			case 'CA':
				$html .= '
				<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><p>Need additional information about charging provincial sales taxes on online sales?</p><p><a class="button" href="http://sbinfocanada.about.com/od/pst/a/PSTecommerce.htm" target="_blank">Read More</a></div>' .
					__('Total Tax Rates (VAT,GST,PST,HST)', 'mp') . '
					<p><a class="button" href="http://en.wikipedia.org/wiki/Sales_taxes_in_Canada" target="_blank">' . __('Current Rates &raquo;', 'mp') . '</a></p>
				</th>
				<td>
					<table cellspacing="0">';
					
					foreach (mp()->canadian_provinces as $key => $label) :
						$html .= '
						<tr>
							<td style="padding: 0 5px;">
								<label for="mp_tax_' . $key . '">' . esc_attr($label) . '</label>
							</td>
							<td style="padding: 0 5px;">' .
								new MP_Field_Text(array(
									'echo' => false,
									'name' => 'mp[tax][canada_rate][' . $key . ']',
									'id' => 'mp_tax_' . $key,
									'size' => 3,
									'value' => mp_get_setting("tax->canada_rate->$key") * 100,
									'style' => 'text-align:right;',
								)) . '%
						</tr>';
					endforeach;
					
					$html .= '
					</table>
				</td>
				</tr>';
			break;
		
			case 'GB':
				$html .= '
				<tr>
					<th scope="row">' . __('VAT Tax Rate', 'mp') . '</th>
					<td>' .
						new MP_Field_Text(array(
							'echo' => false,
							'name' => 'mp[tax][rate]',
							'value' => mp_get_setting('tax->rate') * 100,
							'size' => 3,
							'style' => 'text-align:right',
						)) . '%
					</td>
				</tr>';
			break;
		
			case 'AU':
				$html .= '
				<tr>
					<th scope="row">' . __('GST Tax Rate', 'mp') . '</th>
					<td>' .
						new MP_Field_Text(array(
							'echo' => false,
							'name' => 'mp[tax][rate]',
							'value' => mp_get_setting('tax->rate') * 100,
							'size' => 3,
							'style' => 'text-align:right;',
						)) . '%
					</td>
				</tr>';
			break;
		
			default:
				//in european union
				if ( in_array(mp_get_setting('base_country'), mp()->eu_countries) ) {
					$html .= '
					<tr>
						<th scope="row">' . __('VAT Tax Rate', 'mp') . '</th>
						<td>' .
							new MP_Field_Text(array(
								'echo' => false,
								'name' => 'mp[tax][rate]',
								'value' => mp_get_setting('tax->rate') * 100,
								'style' => 'text-align:right;',
								'size' => 3,
							)) . '%
						</td>
					</tr>';
				} else { //all other countries
					$html .= '
					<tr>
						<th scope="row">' . __('Country Total Tax Rate (VAT, GST, Etc.)', 'mp') . '</th>
						<td>' .
							new MP_Field_Text(array(
								'echo' => false,
								'name' => 'mp[tax][rate]',
								'value' => '',//mp_get_setting('tax->rate') * 100,
								'style' => 'text-align:right;',
								'size' => 3,
							)) . '%
						</td>
					</tr>
					<tr>
						<th scope="row">' . __('Tax Orders Outside Your Base Country?', 'mp') . '</th>
						<td>' .
							new MP_Field_Checkbox(array(
								'echo' => false,
								'name' => 'mp[tax][tax_outside]',
								'checked' => mp_get_setting('tax->tax_outside'),
								'value' => '1',
								'label' => array('text' => __('Yes', 'mp')),
							)) . '
						</td>
					</tr>';
				}
			break;
		}
		
		$html .= '
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text">' . __('The label shown for the tax line item in the cart. Taxes, VAT, GST, etc.', 'mp') . '</div>' .
					__('Tax Label', 'mp') . '
				</th>
				<td>' .
					new MP_Field_Text(array(
						'echo' => false,
						'name' => 'mp[tax][label]',
						'value' => esc_attr(mp_get_setting('tax->label', __('Taxes', 'mp'))),
						'size' => 10,
					)) . '
				</td>
			</tr>
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text">' . __('Please see your local tax laws. Most areas charge tax on shipping fees.', 'mp') . '</div>' .
					__('Apply Tax To Shipping Fees?', 'mp') . '
				</th>
				<td>' .
					new MP_Field_Checkbox(array(
						'echo' => false,
						'name' => 'mp[tax][tax_shipping]',
						'checked' => mp_get_setting('tax->tax_shipping'),
						'value' => '1',
						'label' => array('text' => __('Yes', 'mp')),
					)) . '
				</td>
			</tr>
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text">' . __('Enabling this option allows you to enter and show all prices inclusive of tax, while still listing the tax total as a line item in shopping carts. Please see your local tax laws.', 'mp') . '</div>' .
					__('Enter Prices Inclusive of Tax?', 'mp') . '
				</th>
				<td>' .
					new MP_Field_Checkbox(array(
						'echo' => false,
						'name' => 'mp[tax][tax_inclusive]',
						'checked' => mp_get_setting('tax->tax_inclusive'),
						'value' => '1',
						'label' => array('text' => __('Yes', 'mp')),
					)) . '
				</td>
			</tr>
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text">' . __('Please see your local tax laws. Note if this is enabled and a downloadable only cart, rates will be the default for your base location.', 'mp') . '</div>' .
					__("Apply Tax to Downloadable Products?", 'mp') . '
				</th>
				<td>' .
					new MP_Field_Checkbox(array(
						'echo' => false,
						'name' => 'mp[tax][tax_digital]',
						'checked' => mp_get_setting('tax->tax_digital'),
						'value' => '1',
						'label' => array('text' => __('Yes', 'mp')),
					)) . '
				</td>
			</tr>
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text">' . __('If you need to tax downloadable products and don\'t want to default to the rates to your base location, enable this to always collect the shipping address.', 'mp') .'</div>' .
					__("Collect Address on Downloadable Only Cart?", 'mp') . '
				</th>
				<td>' .
					new MP_Field_Checkbox(array(
						'echo' => false,
						'name' => 'mp[tax][downloadable_address]',
						'checked' => mp_get_setting('tax->downloadable_address'),
						'value' => '1',
						'label' => array('text' => __('Yes', 'mp')),
					)) . '
				</td>
			</tr>
		</table>';

		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}

