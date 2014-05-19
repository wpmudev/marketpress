<?php

class MarketPress_Admin_Page_Gateways {
	function __construct() {
		add_filter('marketpress_save_settings_gateways', array(&$this, 'save_settings'));
		add_action('marketpress_admin_page_gateways', array(&$this, 'settings'));
		add_filter('marketpress_settings_tabs', array(&$this, 'tab'));	
		add_action('wp_ajax_mp_settings_toggle_gateway', array(&$this, 'toggle_gateway'));
	}
	
	function toggle_gateway() {
		global $mp_gateway_active_plugins;
		check_ajax_referer('mp-settings-ajax-request', 'mp_settings_ajax_nonce');
		
		$settings = get_option('mp_settings');
		$gateway = $_POST['gateway'];
		
		if ( $_POST['activate'] ) {
			$settings['gateways']['allowed'][$gateway] = $gateway;
			update_option('mp_settings', $settings);
			mp_toggle_plugin($gateway, 'gateway', '', true);
			$mp_gateway_active_plugins[$gateway]->gateway_settings_box($settings);
		} else {
			unset($settings['gateways']['allowed'][$gateway]);
			update_option('mp_settings', $settings);
			mp_toggle_plugin($gateway, 'gateway', '', false);
		}
		
		exit;
	}
	
	function update_settings( $settings ) {
		$settings['gateways']['allowed'] = array(); //reset allowed gateways
		
		if ( isset($_POST['mp']) ) {
			$filtered_settings = apply_filters('mp_gateway_settings_filter', $_POST['mp']);
			$settings = array_merge($settings, $filtered_settings);
		}
		
		update_option('mp_settings', $settings);
	}

	function tab( $tabs ) {
		$tabs['gateways'] = array(
			'name' => __('Payments', 'mp'),
			'cart_only' => true,
			'order' => 5,
		);
		
		return $tabs;
	}
	
	function save_settings( $settings ) {
		
		
		//save settings
		if (isset($_POST['mp_settings_gateways_nonce'])) {
			check_admin_referer('mp_settings_gateways', 'mp_settings_gateways_nonce');
			mp_update_settings($settings);
			echo '<div class="updated fade"><p>'.__('Settings saved.', 'mp').'</p></div>';
		}
		
		return $settings;
	}
	
	function output_js() {
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($){
		//Store the indexes of each possible settings postbox
		var boxPositions = [];
		$("#mp_gateways").find(':checkbox').each(function(index){
			var $this = $(this);
			boxPositions[index] = {
				"slug" : $this.val(),
				"activated" : $this.is(':checked')
			};
		});
					
		//toggle settings metaboxes
		$('input.mp_allowed_gateways').change(function() {
			var $this = $(this),
					$loadingImage = $('#mp-ajax-loading-image'),
					activate = $this.is(':checked') ? 1 : 0,
					$siblings = $this.closest('td').find(':checkbox'),
					index = $siblings.index(this),
					data = [
						{
							"name" : "action",
							"value" : "mp_settings_toggle_gateway"
						},{
							"name" : "mp_settings_ajax_nonce",
							"value" : "<?php echo wp_create_nonce('mp-settings-ajax-request'); ?>"
						},{
							"name" : "gateway",
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
					var $gatewaySettings = $('#mp-gateways-settings');
					if ( index == 0 ) {
						$gatewaySettings.prepend(resp);
					} else {
						// all this just makes sure that the postbox is inserted in the correct position
						for ( i = (index - 1); i >= 0; i-- ) {
							var slug = boxPositions[i].slug,
									$target = $('#mp-gateway-settings-' + slug);
							
							if ( $target.length ) {
								$target.after(resp);
								break;
							}
							
							if ( i == 0 ) {
								$gatewaySettings.prepend(resp);
							}
						}
					}
				} else {
					$('#mp-gateway-settings-' + $this.val()).remove();
				}
			}).fail(function(resp){
				//an error occurred
			});
		});	
	});
	</script>
	<?php
	}
	
	function settings( $settings ) {
		global $mp_gateway_plugins, $mp;
		$this->output_js();
		?>
		<!--<h2><?php _e('Payment Settings', 'mp'); ?></h2>-->
	
		<form id="mp-gateways-form" method="post" action="<?php echo add_query_arg(array()); ?>">
			
	
			<?php if (!mp()->global_cart) { ?>
			<div id="mp_gateways" class="mp-postbox">
				<h3 class='hndle'><img id="mp-ajax-loading-image" style="display:none;float:right" src="<?php echo includes_url('images/wpspin.gif'); ?>" /><span><?php _e('General Settings', 'mp') ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr>
						<th scope="row"><?php _e('Select Payment Gateway(s)', 'mp') ?></th>
						<td>
						<?php
						//check network permissions
						if (is_multisite() && !is_main_site() && !is_super_admin()) {
							$network_settings = get_site_option( 'mp_network_settings' );
							foreach ((array)$mp_gateway_plugins as $code => $plugin) {
								if ($network_settings['allowed_gateways'][$code] == 'full') {
									$allowed_plugins[$code] = $plugin;
								} else if ($network_settings['allowed_gateways'][$code] == 'supporter' && function_exists('is_pro_site') && is_pro_site(false, $network_settings['gateways_pro_level'][$code]) ) {
	
									$allowed_plugins[$code] = $plugin;
								}
							}
							$mp_gateway_plugins = $allowed_plugins;
						}
	
						foreach ((array)$mp_gateway_plugins as $code => $plugin) {
							if ($plugin[3]) { //if demo
								?><label><input type="checkbox" class="mp_allowed_gateways" name="mp[gateways][allowed][]" value="<?php echo $code; ?>" disabled="disabled" /> <?php echo esc_attr($plugin[1]); ?></label> <a class="mp-pro-update" href="http://premium.wpmudev.org/project/e-commerce" title="<?php _e('Upgrade', 'mp'); ?> &raquo;"><?php _e('Pro Only &raquo;', 'mp'); ?></a><br /><?php
							} else {
								?><label><input type="checkbox" class="mp_allowed_gateways" name="mp[gateways][allowed][]" value="<?php echo $code; ?>"<?php echo (in_array($code, mp_get_setting('gateways->allowed', array()))) ? ' checked="checked"' : ''; ?> /> <?php echo esc_attr($plugin[1]); ?></label><br /><?php
							}
						}
						?>
						</td>
						</tr>
					</table>
				</div>
			</div>
			<?php } ?>
			
			<div id="mp-gateways-settings">
				<?php do_action('mp_gateway_settings', $settings); ?>
			</div>
	
			<p class="submit">
				<input class="button-primary" type="submit" name="submit_settings" value="<?php _e('Save Changes', 'mp') ?>" />
			</p>
			
			<?php wp_nonce_field('mp_settings_gateways', 'mp_settings_gateways_nonce'); ?>
		</form>
		<?php
	}
}

mp_register_admin_page('MarketPress_Admin_Page_Gateways');
