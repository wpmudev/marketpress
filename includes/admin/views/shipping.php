<?php

class MarketPress_Admin_Page_Shipping {
	function __construct() {
		add_action('marketpress_admin_page_shipping', array(&$this, 'settings'));
		add_filter('marketpress_save_settings_shipping', array(&$this, 'save_settings'));
		add_filter('marketpress_settings_tabs', array(&$this, 'tab'));
		add_action('wp_ajax_mp_settings_toggle_shipping_method', array(&$this, 'toggle_shipping_method'));
		add_action('wp_ajax_mp_settings_toggle_calculated_shipping_option', array(&$this, 'toggle_calculated_shipping_option'));
		add_action('wp_ajax_mp_settings_toggle_shipping_system', array(&$this, 'toggle_shipping_system'));		
	}
	
	
	function tab( $tabs ) {
		$tabs['shipping'] = array(
			'name' => __('Shipping', 'mp'),
			'cart_only' => true,
			'order' => 4,
		);
		
		return $tabs;
	}
	
	function toggle_shipping_system() {
		global $mp, $mp_shipping_active_plugins;
		
		check_ajax_referer('mp-settings-ajax-toggle-shipping-system', 'mp_settings_ajax_nonce');
		
		$settings = get_option('mp_settings');
		$system = mp_get_post_value('system');
		
		if ( !$system ) exit;
		
		$settings['shipping']['system'] = $system;
		update_option('mp_settings', $settings);
		
		foreach ( $mp_shipping_active_plugins as $subtype => $plugins ) {
			foreach ( $plugins as $plugin ) {
				$plugin->shipping_settings_box($settings);
			}
		}
		
		exit;
	}
	
	function toggle_calculated_shipping_option() {
		global $mp, $mp_shipping_active_plugins;
		
		check_ajax_referer('mp-settings-ajax-toggle-calculated-shipping-option', 'mp_settings_ajax_nonce');
		
		$settings = get_option('mp_settings');
		$method = mp_get_post_value('method');
		$calculated = mp_get_post_value('calculated');
		$activate = mp_get_post_value('activate');
		
		$settings['shipping']['calc_methods'] = is_array($settings['shipping']['calc_methods']) ? $settings['shipping']['calc_methods'] : array();

		if ( $activate ) {
			$settings['shipping']['calc_methods'][$method] = $method;
			update_option('mp_settings', $settings);
			
			mp_toggle_plugin($method, 'shipping', 'calculated', $activate);
			$mp_shipping_active_plugins['calculated'][$method]->shipping_settings_box($settings);
		} else {
			if ( count($settings['shipping']['calc_methods']) == 1 ) {
				$settings['shipping']['calc_methods'] = array();
			} else {
				unset($settings['shipping']['calc_methods'][$method]);
			}
			
			update_option('mp_settings', $settings);
			mp_toggle_plugin($method, 'shipping', 'calculated', false);
		}
		
		exit;		
	}
	
	function toggle_shipping_method() {
		global $mp, $mp_shipping_active_plugins;
		
		check_ajax_referer('mp-settings-ajax-toggle-shipping-method', 'mp_settings_ajax_nonce');
		
		$settings = get_option('mp_settings');
		$method = mp_get_post_value('method');
		$calculated = mp_get_post_value('calculated');
		$activate = mp_get_post_value('activate');
		$mp_shipping_active_plugins['non_calculated'] = array();
		
		if ( $activate ) {
			$settings['shipping']['method'] = $method;
			update_option('mp_settings', $settings);
			
			if ( $method != 'none' && $method != 'calculated' ) {
				mp_toggle_plugin($method, 'shipping', 'non_calculated', true);
				$mp_shipping_active_plugins['non_calculated'][$method]->shipping_settings_box($settings);
			}
		}
			
		exit;
	}
	
	function save_settings( $settings ) {
		if (isset($_POST['mp_settings_shipping_nonce'])) {
			check_admin_referer('mp_settings_shipping', 'mp_settings_shipping_nonce');
			
			//allow plugins to verify settings before saving
			$settings = wp_parse_args($settings, apply_filters('mp_shipping_settings_filter', $_POST['mp']));
			update_option('mp_settings', $settings);
			echo '<div class="updated fade"><p>'.__('Settings saved.', 'mp').'</p></div>';
		}
		return $settings;
	}
	
	function output_js() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				var $shippingSettings = $('#mp-shipping-settings');
				
				$("#mp-select-all").click(function(e) {
					e.preventDefault();
					$("#mp-target-countries input[type='checkbox']").attr('checked', true);
				});
				
				$("#mp-select-eu").click(function(e) {
					e.preventDefault();
					$("#mp-target-countries input[type='checkbox'].eu").attr('checked', true);
				});
				
				$("#mp-select-none").click(function(e) {
					e.preventDefault();
					$("#mp-target-countries input[type='checkbox']").attr('checked', false);
				});
				
				//Toggle shipping methods
				$(".mp-shipping-method").change(function() {
					var $this = $(this),
							$loadingImage = $('#mp-ajax-loading-image'),
							$calcMethodsRow = $('#mp-calculated-methods-row'),
							calculated =  $this.val() == 'calculated' ? 1 : 0,
							data = [
								{
									"name" : "action",
									"value" : "mp_settings_toggle_shipping_method"
								},{
									"name" : "mp_settings_ajax_nonce",
									"value" : "<?php echo wp_create_nonce('mp-settings-ajax-toggle-shipping-method'); ?>"
								},{
									"name" : "method",
									"value" : $this.val()
								},{
									"name" : "activate",
									"value" : $this.is(':checked') || $this.prop('tagName') == 'SELECT' ? 1 : 0
								},{
									"name" : "calculated",
									"value" : calculated
								}
							];
					
					if ( calculated ) {
						$calcMethodsRow.show();
					} else {
						$calcMethodsRow.hide();
					}
						
					$loadingImage.show();
					
					$.ajax(ajaxurl, {
						"dataType" : "html",
						"type" : "POST",
						"data" : $.param(data)
					}).done(function(resp){
						$loadingImage.hide();
						$('#mp-shipping-settings').html(resp);
					}).fail(function(resp){
						//an error occurred
					});
				});
				
				//Store the indexes of each possible settings postbox
				var boxPositions = [];
				$("#mp-calculated-methods-row").find(':checkbox').each(function(index){
					var $this = $(this);
					boxPositions[index] = {
						"slug" : $this.val(),
						"activated" : $this.is(':checked')
					};
				});
				
				//Toggle calculated shipping options
				$("#mp-calculated-methods-row").find(':checkbox').change(function() {
					var $this = $(this),
							$loadingImage = $('#mp-ajax-loading-image'),
							activate = $this.is(':checked') ? 1 : 0,
							$siblings = $this.closest('td').find(':checkbox'),
							index = $siblings.index(this),
							data = [
								{
									"name" : "action",
									"value" : "mp_settings_toggle_calculated_shipping_option"
								},{
									"name" : "mp_settings_ajax_nonce",
									"value" : "<?php echo wp_create_nonce('mp-settings-ajax-toggle-calculated-shipping-option'); ?>"
								},{
									"name" : "method",
									"value" : $this.val()
								},{
									"name" : "activate",
									"value" : activate
								}
							];
					
					$loadingImage.show();
					
					$.ajax(ajaxurl, {
						"dataType" : "html",
						"type" : "POST",
						"data" : $.param(data)
					}).done(function(resp){
						$loadingImage.hide();
						
						if ( activate ) {
							if ( index == 0 ) {
								$shippingSettings.prepend(resp);
							} else {
								// all this just makes sure that the postbox is inserted in the correct position
								for ( i = (index - 1); i >= 0; i-- ) {
									var slug = boxPositions[i].slug,
											$target = $('#mp-calculated-shipping-option-' + slug);
									
									if ( $target.length ) {
										$target.after(resp);
										break;
									}
									
									if ( i == 0 ) {
										$shippingSettings.prepend(resp);
									}
								}
							}
						} else {
							$('#mp-calculated-shipping-option-' + $this.val()).remove();
						}
					}).fail(function(resp){
						//an error occurred
					});
				});
				
				//Refresh active shipping module settings when toggling measurements
				$('#mp-measurement-system-row').find(':radio').change(function(){
					var $this = $(this),
							$loadingImage = $('#mp-ajax-loading-image'),
							data = [
								{
									"name" : "action",
									"value" : "mp_settings_toggle_shipping_system"
								},{
									"name" : "mp_settings_ajax_nonce",
									"value" : "<?php echo wp_create_nonce('mp-settings-ajax-toggle-shipping-system'); ?>"
								},{
									"name" : "system",
									"value" : $this.val()
								}
							];
					
					$loadingImage.show();
					
					$.ajax(ajaxurl, {
						"dataType" : "html",
						"type" : "POST",
						"data" : $.param(data)
					}).done(function(resp){
						$shippingSettings.html(resp);
						$loadingImage.hide();
					}).fail(function(resp){
						//an error occurred
					});
				});
			});
		</script>
		<?php		
	}
	
	function settings( $settings ) {
		global $mp, $mp_shipping_plugins;
		$this->output_js();
		?>
		<!--<h2><?php _e('Shipping Settings', 'mp'); ?></h2>-->

		<form id="mp-shipping-form" method="post" action="<?php echo add_query_arg(array()); ?>">
			<div id="mp_flat_rate" class="mp-postbox">
				<h3 class='hndle'><img id="mp-ajax-loading-image" style="display:none;float:right" src="<?php echo includes_url('images/wpspin.gif'); ?>" /><span><?php _e('General Settings', 'mp') ?></span></h3>
				<div class="inside">
					<table class="form-table">

						<tr>
						<th scope="row"><?php _e('Choose Target Countries', 'mp') ?></th>
						<td>
							<div><?php _e('Select:', 'mp') ?> <a id="mp-select-all" href="#"><?php _e('All', 'mp') ?></a>&nbsp; <a id="mp-select-eu" href="#"><?php _e('EU', 'mp') ?></a>&nbsp; <a id="mp-select-none" href="#"><?php _e('None', 'mp') ?></a></div>
							<div id="mp-target-countries">
							<?php
								foreach (mp()->countries as $code => $name) {
									?><label><input type="checkbox"<?php echo (in_array($code, mp()->eu_countries)) ? ' class="eu"' : ''; ?> name="mp[shipping][allowed_countries][]" value="<?php echo $code; ?>"<?php echo (in_array($code, mp_get_setting('shipping->allowed_countries', array()))) ? ' checked="checked"' : ''; ?> /> <?php echo esc_attr($name); ?></label><br /><?php
								}
							?>
							</div><br />
							<span class="description"><?php _e('These are the countries you will sell and ship to.', 'mp') ?></span>
						</td>
						</tr>

						<tr>
						<th scope="row"><?php _e('Select Shipping Method', 'mp') ?></th>
						<td>
							<select name="mp[shipping][method]" class="mp-shipping-method">
								<option value="none"<?php selected(mp_get_setting('shipping->method'), 'none'); ?>><?php _e('No Shipping', 'mp'); ?></option>
								<?php
								$calculated_methods = 0;
								foreach ((array)$mp_shipping_plugins as $code => $plugin) {
									if ($plugin[2]) {
										$calculated_methods++;
										continue;
									}
									?><option value="<?php echo $code; ?>"<?php selected(mp_get_setting('shipping->method'), $code); ?>><?php echo esc_attr($plugin[1]); ?></option><?php
								}
								if ($calculated_methods) {
									?><option value="calculated"<?php selected(mp_get_setting('shipping->method'), 'calculated'); ?>><?php _e('Calculated Options', 'mp'); ?></option><?php
								}
								?>
							</select>
							</td>
						</tr>
						<tr id="mp-calculated-methods-row"<?php echo ( $calculated_methods && mp_get_setting('shipping->method') == 'calculated' ) ? '' : ' style="display:none;"'; ?>>
						<th scope="row"><?php _e('Select Shipping Options', 'mp') ?></th>
						<td>
							<?php if ( defined( 'MP_LITE' ) ) { ?>
							<a class="mp-pro-update" href="http://premium.wpmudev.org/project/e-commerce/" title="<?php _e('Upgrade Now', 'mp'); ?> &raquo;"><?php _e('Upgrade to enable Calculated Shipping options &raquo;', 'mp'); ?></a><br />
							<?php } ?>
							<span class="description"><?php _e('Select which calculated shipping methods the customer will be able to choose from:', 'mp') ?></span><br />
							<?php
								foreach ((array)$mp_shipping_plugins as $code => $plugin) {
									if (!$plugin[2]) continue; //skip non calculated
									?><label style="display:block;padding-bottom:3px;"><input type="checkbox" name="mp[shipping][calc_methods][<?php echo $code; ?>]" value="<?php echo $code; ?>"<?php echo mp_get_setting("shipping->calc_methods->$code") ? ' checked="checked"' : ''; echo defined( 'MP_LITE' ) ? ' disabled="disabled"' : ''; ?> /> <?php echo esc_attr($plugin[1]); ?></label><?php
								}
							?>
						</td>
						</tr>
						<tr id="mp-measurement-system-row">
						<th scope="row"><?php _e('Measurement System', 'mp') ?></th>
						<td>
							<label><input value="english" name="mp[shipping][system]" type="radio"<?php checked(mp_get_setting('shipping->system'), 'english') ?> /> <?php _e('English (Pounds)', 'mp') ?></label>
							<label><input value="metric" name="mp[shipping][system]" type="radio"<?php checked(mp_get_setting('shipping->system'), 'metric') ?> /> <?php _e('Metric (Kilograms)', 'mp') ?></label>
						</td>
						</tr>
					</table>
				</div>
			</div>

			<div id="mp-shipping-settings">
			<?php do_action('mp_shipping_settings', $settings); ?>
			</div>

			<p class="submit">
				<input class="button-primary" type="submit" name="submit_settings" value="<?php _e('Save Changes', 'mp') ?>" />
			</p>
			
			<?php wp_nonce_field('mp_settings_shipping', 'mp_settings_shipping_nonce'); ?>
		</form>
		<?php
	}
}

mp_register_admin_page('MarketPress_Admin_Page_Shipping');
	