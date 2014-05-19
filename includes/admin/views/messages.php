<?php

class MarketPress_Admin_Page_Messages {
	function __construct() {
		add_action('marketpress_admin_page_messages', array(&$this, 'settings'));
		add_filter('marketpress_save_settings_messages', array(&$this, 'save_settings'));
		add_filter('marketpress_settings_tabs', array(&$this, 'tab'));	
	}
	
	function tab( $tabs ) {
		$tabs['messages'] = array(
			'name' => __('Messages', 'mp'),
			'cart_only' => true,
			'order' => 3,
		);
		
		return $tabs;
	}
	
	function save_settings( $settings ) {
		
		
		//save settings
		if (isset($_POST['mp_settings_messages_nonce'])) {
			check_admin_referer('mp_settings_messages', 'mp_settings_messages_nonce');
			
			//remove html from emails
			$_POST['mp']['email'] = array_map('wp_filter_nohtml_kses', (array)$_POST['mp']['email']);

			//filter msg inputs if necessary
			if (!current_user_can('unfiltered_html')) {
				$_POST['mp']['msg'] = array_map('wp_kses_post', (array)$_POST['mp']['msg']);
			}
			
			//strip slashes
			$_POST['mp']['msg'] = array_map('stripslashes', (array)$_POST['mp']['msg']);
			$_POST['mp']['email'] = array_map('stripslashes', (array)$_POST['mp']['email']);
			
			//wpautop
			$_POST['mp']['msg'] = array_map('wpautop', (array)$_POST['mp']['msg']);
			
			$settings = array_merge($settings, apply_filters('mp_messages_settings_filter', $_POST['mp']));
			update_option('mp_settings', $settings);

			echo '<div class="updated fade"><p>'.__('Settings saved.', 'mp').'</p></div>';
		}
		
		return $settings;
	}
	
	function settings( $settings ) {
		
		?>
		<!--<h2><?php _e('Messages Settings', 'mp'); ?></h2>-->

		<form id="mp-messages-form" method="post" action="<?php echo add_query_arg(array()); ?>">
			<div class="mp-postbox email-notifications">
				<h3 class='hndle'><span><?php _e('Email Notifications', 'mp') ?></span></h3>
				<div class="inside">
					<table class="form-table">
					<tr>
						<th scope="row"><?php _e('Store Admin Email', 'mp'); ?></th>
						<td>
						<?php $store_email = mp_get_setting('store_email') ? mp_get_setting('store_email') : get_option("admin_email"); ?>
						<span class="description"><?php _e('The email address that new order notifications are sent to and received from.', 'mp') ?></span><br />
						<input type="text" name="mp[store_email]" value="<?php echo esc_attr($store_email); ?>" maxlength="150" size="50" />
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('New Order', 'mp'); ?></th>
						<td>
						<span class="description"><?php _e('The email text sent to your customer to confirm a new order. These codes will be replaced with order details: CUSTOMERNAME, ORDERID, ORDERINFO, SHIPPINGINFO, PAYMENTINFO, TOTAL, TRACKINGURL, ORDERNOTES. No HTML allowed.', 'mp') ?></span><br />
						<label><?php _e('Subject:', 'mp'); ?><br />
						<input type="text" class="mp_emails_sub" name="mp[email][new_order_subject]" value="<?php echo esc_attr(mp_get_setting('email->new_order_subject')); ?>" maxlength="150" /></label><br />
						<label><?php _e('Text:', 'mp'); ?><br />
						<textarea class="mp_emails_txt" name="mp[email][new_order_txt]"><?php echo esc_textarea(mp_get_setting('email->new_order_txt')); ?></textarea>
						</label>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Order Shipped', 'mp'); ?></th>
						<td>
						<span class="description"><?php _e('The email text sent to your customer when you mark an order as "Shipped". These codes will be replaced with order details: CUSTOMERNAME, ORDERID, ORDERINFO, SHIPPINGINFO, PAYMENTINFO, TOTAL, TRACKINGURL, ORDERNOTES. No HTML allowed.', 'mp') ?></span><br />
						<label><?php _e('Subject:', 'mp'); ?><br />
						<input type="text" class="mp_emails_sub" name="mp[email][shipped_order_subject]" value="<?php echo esc_attr(mp_get_setting('email->shipped_order_subject')); ?>" maxlength="150" /></label><br />
						<label><?php _e('Text:', 'mp'); ?><br />
						<textarea class="mp_emails_txt" name="mp[email][shipped_order_txt]"><?php echo esc_textarea(mp_get_setting('email->shipped_order_txt')); ?></textarea>
						</label>
						</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="mp-postbox mp-pages-msgs store-pages">
				<h3 class='hndle'><span><?php _e('Store Pages', 'mp') ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr>
						<th scope="row"><?php _e('Store Page', 'mp'); ?></th>
						<td>
						<span class="description"><?php _e('The main store page is an actual page on your site. You can edit it here:', 'mp') ?></span>
						<?php
						$post_id = get_option('mp_store_page');
						edit_post_link(__('Edit Page &raquo;', 'mp'), '', '', $post_id);
						?>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Product Listing Pages', 'mp'); ?></th>
						<td>
						<span class="description"><?php _e('Displayed at the top of the product listing pages. Optional, HTML allowed.', 'mp') ?></span><br />
						<?php wp_editor( mp_get_setting('msg->product_list'), 'product_list', array('textarea_name'=>'mp[msg][product_list]') ); ?>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Order Status Page', 'mp'); ?></th>
						<td>
						<span class="description"><?php _e('Displayed at the top of the Order Status page. Optional, HTML allowed.', 'mp') ?></span><br />
						<?php wp_editor( mp_get_setting('msg->order_status'), 'order_status', array('textarea_name'=>'mp[msg][order_status]') ); ?>
						</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="mp-postbox mp-pages-msgs stopping-cart-pages">
				<h3 class='hndle'><span><?php _e('Shopping Cart Pages', 'mp') ?></span></h3>
				<div class="inside">
					<table class="form-table">
						<tr>
						<th scope="row"><?php _e('Shopping Cart Page', 'mp'); ?></th>
						<td>
						<span class="description"><?php _e('Displayed at the top of the Shopping Cart page. Optional, HTML allowed.', 'mp') ?></span><br />
						<?php wp_editor( mp_get_setting('msg->cart'), 'cart', array('textarea_name'=>'mp[msg][cart]') ); ?>
						</td>
						</tr>
						<tr id="mp_msgs_shipping">
						<th scope="row"><?php _e('Shipping Form Page', 'mp'); ?></th>
						<td>
						<span class="description"><?php _e('Displayed at the top of the Shipping Form page. Optional, HTML allowed.', 'mp') ?></span><br />
						<?php wp_editor( mp_get_setting('msg->shipping'), 'shipping', array('textarea_name'=>'mp[msg][shipping]') ); ?>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Payment Form Page', 'mp'); ?></th>
						<td>
						<span class="description"><?php _e('Displayed at the top of the Payment Form page. Optional, HTML allowed.', 'mp') ?></span><br />
						<?php wp_editor( mp_get_setting('msg->checkout'), 'checkout', array('textarea_name'=>'mp[msg][checkout]') ); ?>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Order Confirmation Page', 'mp'); ?></th>
						<td>
						<span class="description"><?php _e('Displayed at the top of the final Order Confirmation page. HTML allowed.', 'mp') ?></span><br />
						<?php wp_editor( mp_get_setting('msg->confirm_checkout'), 'confirm_checkout', array('textarea_name'=>'mp[msg][confirm_checkout]') ); ?>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Order Complete Page', 'mp'); ?></th>
						<td>
						<span class="description"><?php _e('Displayed at the top of the page notifying customers of a successful order. HTML allowed.', 'mp') ?></span><br />
						<?php wp_editor( mp_get_setting('msg->success'), 'success', array('textarea_name'=>'mp[msg][success]') ); ?>
						</td>
						</tr>
					</table>
				</div>
			</div>

			<?php
			//for adding additional messages
			do_action('mp_messages_settings', $settings);
			?>

			<p class="submit">
				<input class="button-primary" type="submit" name="submit_settings" value="<?php _e('Save Changes', 'mp') ?>" />
			</p>
			
			<?php wp_nonce_field('mp_settings_messages', 'mp_settings_messages_nonce'); ?>
		</form>
		<?php
	}
}

mp_register_admin_page('MarketPress_Admin_Page_Messages');
	