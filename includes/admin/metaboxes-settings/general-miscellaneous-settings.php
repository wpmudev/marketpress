<?php

$mp_settings_metaboxes['general_misc'] = new MP_Settings_Metabox_General_Misc_Settings();

class MP_Settings_Metabox_General_Misc_Settings extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('Miscellaneous Settings', 'mp');
		$this->tab = 'general';
	}
	
	function on_creation() {
		
	}	
	
	function save_settings( $settings ) {
		
		$settings['force_login'] = mp_get_post_value('mp->force_login', 0);
		$settings['disable_cart'] = mp_get_post_value('mp->disable_cart', 0);
		$settings['special_instructions'] = mp_get_post_value('mp->special_instructions', 0);
		return $settings;
	}
	
	function display_inside() {
		
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('Whether or not customers must be registered and logged in to checkout. (Not recommended: Enabling this can lower conversions)', 'mp') ?></div>
					<?php _e('Force Login', 'mp') ?>
				</th>
				<td>
					<?php
					new MP_Field_Checkbox(array(
						'name' => 'mp[force_login]',
						'checked' => mp_get_setting('force_login'),
						'value' => '1',
						'label' => array('text' => __('Yes', 'mp')),
					));
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('This option turns MarketPress into more of a product listing plugin, disabling shopping carts, checkout, and order management. This is useful if you simply want to list items you can buy in a store somewhere else, optionally linking the "Buy Now" buttons to an external site. Some examples are a car dealership, or linking to songs/albums in itunes, or linking to products on another site with your own affiliate links.', 'mp') ?></div>
					<?php _e('Product Listings Only', 'mp') ?>
				</th>
				<td>
					<?php
					new MP_Field_Checkbox(array(
						'name' => 'mp[disable_cart]',
						'checked' => mp_get_setting('disable_cart'),
						'value' => '1',
						'label' => array('text' => __('Yes', 'mp')),
					));
					?>
				</td>
			</tr>
			<tr>
			<th scope="row">
				<a class="mp-help-icon" href="javascript:;"></a>
				<div class="mp-help-text"><?php _e('<p>If you already use Google Analytics for your website, you can track detailed ecommerce information by enabling this setting. Choose whether you are using the new asynchronous or old tracking code.</p><p>Before Google Analytics can report ecommerce activity for your website, you must enable ecommerce tracking on the profile settings page for your website. Also keep in mind that some gateways do not reliably show the receipt page, so tracking may not be accurate in those cases. It is recommended to use the PayPal gateway for the most accurate data.<p><p class="last"><a class="button" href="http://analytics.blogspot.com/2009/05/how-to-use-ecommerce-tracking-in-google.html" target="_blank">More information</a></p>', 'mp') ?></div>
				<?php _e('Google Analytics Ecommerce Tracking', 'mp') ?></th>
				<td>
					<?php if ( defined( 'MP_LITE' ) ) : ?>
					<a class="mp-pro-update" href="http://premium.wpmudev.org/project/e-commerce/" title="<?php _e('Upgrade Now', 'mp'); ?> &raquo;"><?php _e('Upgrade to enable Google Analytics Ecommerce Tracking &raquo;', 'mp'); ?></a><br />
					<?php else : ?>
					<div class="mp-column">
						<?php
						new MP_Field_Radio(array(
							'name' => 'mp[ga_ecommerce]',
							'checked' => mp_get_setting('ga_ecommerce'),
							'value' => 'none',
							'label' => array('class' => 'mp-label', 'text' => __('None', 'mp')),
						));
						
						new MP_Field_Radio(array(
							'name' => 'mp[ga_ecommerce]',
							'checked' => mp_get_setting('ga_ecommerce'),
							'value' => 'new',
							'label' => array('class' => 'mp-label', 'text' => __('Asynchronous', 'mp')),
						));
						?>
					</div>
					<div class="mp-column last">
						<?php
						new MP_Field_Radio(array(
							'name' => 'mp[ga_ecommerce]',
							'checked' => mp_get_setting('ga_ecommerce'),
							'value' => 'old',
							'label' => array('class' => 'mp-label', 'text' => __('Non Asynchronous', 'mp')),
						));
						
						new MP_Field_Radio(array(
							'name' => 'mp[ga_ecommerce]',
							'checked' => mp_get_setting('ga_ecommerce'),
							'value' => 'universal',
							'label' => array('class' => 'mp-label', 'text' => __('Universal Analytics', 'mp')),
						));
						?>			
					</div>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php printf(__('Enabling this field will display a textbox on the shipping checkout page for users to enter special instructions for their order. Useful for product personalization, etc. Note you may want to <a href="%s">adjust the message</a> on the shipping page.', 'mp'), admin_url('edit.php?post_type=product&page=marketpress&tab=messages#mp_msgs_shipping')); ?></div>
					<?php _e('Special Instructions Field', 'mp') ?>
				</th>
				<td>
					<?php
					new MP_Field_Checkbox(array(
						'name' => 'mp[special_instructions]',
						'checked' => mp_get_setting('special_instructions'),
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
