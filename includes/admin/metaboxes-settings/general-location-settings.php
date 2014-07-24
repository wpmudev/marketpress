<?php

global $mp_settings_metaboxes;
$mp_settings_metaboxes['general_location'] = new MP_Settings_Metabox_General_Location_Settings();

class MP_Settings_Metabox_General_Location_Settings extends MP_Settings_Metabox {
	/**
	 * Initialize variables
	 * 
	 * @since 3.0
	 * @access public
	 */
	function init_vars() {
		$this->title = __('Location Settings', 'mp');
		$this->tab = 'general';
		$this->description = __('Set the base location that shipping and tax rates will be calculated from.', 'mp');
		$this->order = 1;
	}
	
	/**
	 * Run any initialization code - called by parent::__construct()
	 *
	 * @since 3.0
	 * @access public
	 */
	function on_creation() {
		add_action('wp_ajax_mp_change_location', array(&$this, 'ajax_change_location'));
	}
	
	/**
	 * Update the location settings - called by admin-ajax.php
	 *
	 * @since 3.0
	 * @access public
	 */
	function ajax_change_location() {
		global $mp, $mp_settings_metaboxes;
		
		//update settings
		check_ajax_referer('mp-change-location', 'mp_change_location_nonce');
		$settings = get_option('mp_settings');
		
		if ( isset($_POST['mp']['base_country']) ) {
			$settings['base_country'] = $_POST['mp']['base_country'];
		}
		
		if ( isset($_POST['mp']['base_province']) ) {
			$settings['base_province'] = $_POST['mp']['base_province'];
		}
		
		//we don't want to save here - just update the settings cache so we can output the appropriate html below
		wp_cache_set('mp_settings', $settings, 'mp');
		
		$json = array();
		
		//get tax settings html
		$json['general_tax'] = $mp_settings_metaboxes['general_tax']->display_inside(false);
		$json['general_location'] = $this->display_inside(false);
		
		//send json
		wp_send_json($json);
	}
	
	/**
	 * Prints any necessary javascript
	 *
	 * @since 3.0
	 * @access public
	 */
	function print_scripts() {
		?>
		<script type="text/javascript">
			 jQuery(document).ready(function($) {
				$('#mp-general-location-settings').on('change', '#mp-country-select, #mp-base-province', function() {
					var $this = $(this),
							$taxSettings = $('#mp-general-tax-settings').find('.inside'),
							$locationSettings = $('#mp-general-location-settings').find('.inside'),
							data = [
								{
									"name" : $this.attr('name'),
									"value" : $this.val()
								},{
									"name" : "mp_change_location_nonce",
									"value" : "<?php echo wp_create_nonce('mp-change-location'); ?>"
								},{
									"name" : "action",
									"value" : "mp_change_location"
								}
							];
					
					$taxSettings.html(MarketPress.ajaxLoader);
					$locationSettings.html(MarketPress.ajaxLoader);
					
					$.post(ajaxurl, $.param(data)).done(function(resp){
						$taxSettings.html(resp.general_tax);
						$locationSettings.html(resp.general_location);
					});
				});
			});
		</script>
		<?php
	}
	
	/**
	 * Displays the metabox content
	 *
	 * @since 3.0
	 * @access public
	 */
	function display_inside( $echo = true ) {
		$html = '
		<table class="form-table">
			<tr>
				<th scope="row"><label for="mp-country-select">' . __('Base Country', 'mp') . '</label></th>
				<td>' .
					new MP_Field_Select(array(
						'echo' => false,
						'name' => 'mp[base_country]',
						'id' => 'mp-country-select',
						'class' => 'mp-chosen-select',
						'options' => mp()->countries,
						'selected' => mp_get_setting('base_country'),
					)) . '
				</td>
			</tr>';
		
		$country = mp_get_setting('base_country', false);
		$list = false;
		
		//only show if correct country
		if ( in_array($country, array('US','CA','GB','AU')) ) :
			switch ( $country ) {
				case 'US' :
					$list = mp()->usa_states;
				break;
				
				case 'CA' :
					$list = mp()->canadian_provinces;
				break;
				
				case 'GB' :
					$list = mp()->uk_counties;
				break;
				
				case 'AU' :
					$list = mp()->australian_states;
				break;
			}
			
			$html .= '
			<tr>
				<th scope="row"><label for="mp-base-province">' . __('Base State/Province/Region', 'mp') . '</label></th>
				<td>' .
					new MP_Field_Select(array(
						'echo' => false,
						'name' => 'mp[base_province]',
						'id' => 'mp-base-province',
						'class' => 'mp-chosen-select',
						'options' => $list,
						'groups' => false,
						'selected' => mp_get_setting('base_province'),
					)) . '
				</td>
			</tr>';
		endif;
		
		//only show if correct country or US province
		if ( is_array($list) || in_array( mp_get_setting('base_country'), array('UM','AS','FM','GU','MH','MP','PW','PR','PI') )	) {
			$html .= '
			<tr>
				<th scope="row">' . __('Base Zip/Postal Code', 'mp') . '</th>
				<td>' .
					new MP_Field_Text(array(
						'echo' => false,
						'name' => 'mp[base_zip]',
						'value' => esc_attr(mp_get_setting('base_zip')),
						'size' => 10,
						'custom' => array(
							'minlength' => 3,
						),
					)) . '
				</td>
			</tr>';
		}
		
		$html .= '
		</table>';
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
}
