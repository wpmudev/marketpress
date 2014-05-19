<?php
$order = $this->get_order((int)$_GET['order_id']);

if ( !$order )
wp_die(__('Invalid Order ID', 'mp'));

$max_downloads = mp_get_setting('max_downloads', 5);

//save tracking number
if (isset($_POST['mp_tracking_number'])) {
$order->mp_shipping_info['tracking_num'] = stripslashes(trim($_POST['mp_tracking_number']));
$order->mp_shipping_info['method'] = stripslashes(trim($_POST['mp_shipping_method']));
update_post_meta($order->ID, 'mp_shipping_info', $order->mp_shipping_info);

if (isset($_POST['add-tracking-shipped'])) {
	$this->update_order_status($order->ID, 'shipped');
	$order->post_status = 'order_shipped';
	?><div class="updated fade"><p><?php _e('This order has been marked as Shipped.', 'mp'); ?></p></div><?php
}

if (!current_user_can('unfiltered_html'))
	$_POST['mp_order_notes'] = wp_filter_post_kses(trim(stripslashes($_POST['mp_order_notes'])));

$order->mp_order_notes = stripslashes($_POST['mp_order_notes']);
update_post_meta($order->ID, 'mp_order_notes', $_POST['mp_order_notes']);
?><div class="updated fade"><p><?php _e('Order details have been saved!', 'mp'); ?></p></div><?php
}
?>
<div class="wrap">
<div class="icon32"><img src="<?php echo $this->plugin_url . 'images/shopping-cart.png'; ?>" /></div>
<h2><?php echo sprintf(__('Order Details (%s)', 'mp'), esc_attr($order->post_title)); ?></h2>

<div id="poststuff" class="metabox-holder mp-settings has-right-sidebar">

<div id="side-info-column" class="inner-sidebar">
<div id='side-sortables' class='meta-box-sortables'>

<div id="submitdiv" class="postbox mp-order-actions">
	<h3 class='hndle'><span><?php _e('Order Actions', 'mp'); ?></span></h3>
	<div class="inside">
	<div id="submitpost" class="submitbox">
	<div class="misc-pub-section"><strong><?php _e('Change Order Status:', 'mp'); ?></strong></div>
	<?php
	$actions = array();
	if ($order->post_status == 'order_received') {
		$actions['received current'] = __('Received', 'mp');
		$actions['paid'] = "<a title='" . esc_attr(__('Mark as Paid', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=paid&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Paid', 'mp') . "</a>";
		$actions['shipped'] = "<a title='" . esc_attr(__('Mark as Shipped', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=shipped&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Shipped', 'mp') . "</a>";
		$actions['closed'] = "<a title='" . esc_attr(__('Mark as Closed', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=closed&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Closed', 'mp') . "</a>";
		$actions['trash'] = "<a title='" . esc_attr(__('Trash', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=trash&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Trash', 'mp') . "</a>";
	} else if ($order->post_status == 'order_paid') {
		$actions['received'] = __('Received', 'mp');
		$actions['paid current'] = __('Paid', 'mp');
		$actions['shipped'] = "<a title='" . esc_attr(__('Mark as Shipped', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=shipped&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Shipped', 'mp') . "</a>";
		$actions['closed'] = "<a title='" . esc_attr(__('Mark as Closed', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=closed&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Closed', 'mp') . "</a>";
		$actions['trash'] = "<a title='" . esc_attr(__('Trash', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=trash&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Trash', 'mp') . "</a>";
	} else if ($order->post_status == 'order_shipped') {
		$actions['received'] = __('Received', 'mp');
		$actions['paid'] = __('Paid', 'mp');
		$actions['shipped current'] = __('Shipped', 'mp');
		$actions['closed'] = "<a title='" . esc_attr(__('Mark as Closed', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=closed&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Closed', 'mp') . "</a>";
		$actions['trash'] = "<a title='" . esc_attr(__('Trash', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=trash&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Trash', 'mp') . "</a>";
	} else if ($order->post_status == 'order_closed') {
		$actions['received'] = "<a title='" . esc_attr(__('Mark as Received', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=received&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Received', 'mp') . "</a>";
		$actions['paid'] = "<a title='" . esc_attr(__('Mark as Paid', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=paid&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Paid', 'mp') . "</a>";
		$actions['shipped'] = "<a title='" . esc_attr(__('Mark as Shipped', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=shipped&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Shipped', 'mp') . "</a>";
		$actions['closed current'] = __('Closed', 'mp');
		$actions['trash'] = "<a title='" . esc_attr(__('Trash', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=trash&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Trash', 'mp') . "</a>";
	} else if ($order->post_status == "trash") {
		$actions['received'] = "<a title='" . esc_attr(__('Mark as Received', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=received&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Received', 'mp') . "</a>";
		$actions['paid'] = "<a title='" . esc_attr(__('Mark as Paid', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=paid&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Paid', 'mp') . "</a>";
		$actions['shipped'] = "<a title='" . esc_attr(__('Mark as Shipped', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=shipped&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Shipped', 'mp') . "</a>";
		$actions['closed'] = "<a title='" . esc_attr(__('Mark as Closed', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=closed&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Closed', 'mp') . "</a>";
		$actions['delete'] = "<a title='" . esc_attr(__('Delete', 'mp')) . "' href='" . wp_nonce_url( admin_url( 'edit.php?post_type=product&amp;page=marketpress-orders&amp;action=delete&amp;post=' . $order->ID), 'update-order-status' ) . "'>" . __('Delete', 'mp') . "</a>";

}

	$action_count = count($actions);
	$i = 0;
	echo '<div id="mp-single-statuses" class="misc-pub-section">';
	foreach ( $actions as $action => $link ) {
		++$i;
		( $i == $action_count ) ? $sep = '' : $sep = ' &raquo; ';
		echo "<span class='$action'>$link</span>$sep";
	}
	echo '</div>';
	?>

		<div id="major-publishing-actions">
			<form id="mp-single-order-form" action="<?php echo admin_url('edit.php'); ?>" method="get">
			<div id="mp-single-order-buttons">
				<input type="hidden" name="post_type" class="post_status_page" value="product" />
				<input type="hidden" name="page" class="post_status_page" value="marketpress-orders" />
				<input name="save" class="button-primary" id="publish" tabindex="1" value="<?php _e('&laquo; Back', 'mp'); ?>" type="submit" />
			</div>
			</form>
			<div class="clear"></div>
		</div>
	</div>
	</div>
</div>

<div id="mp-order-status" class="postbox">
	<h3 class='hndle'><span><?php _e('Current Status', 'mp'); ?></span></h3>
	<div class="inside">
		<?php
		//get times
		$received = mp_format_date($order->mp_received_time);
		if (isset($order->mp_paid_time) && $order->mp_paid_time)
			$paid = mp_format_date($order->mp_paid_time);
		if (isset($order->mp_shipped_time) && $order->mp_shipped_time)
			$shipped = mp_format_date($order->mp_shipped_time);
		if (isset($order->mp_closed_time) && $order->mp_closed_time)
			$closed = mp_format_date($order->mp_closed_time);

		if ($order->post_status == 'order_received') {
			echo '<div id="major-publishing-actions" class="misc-pub-section">' . __('Received:', 'mp') . ' <strong>' . $received . '</strong></div>';
		} else if ($order->post_status == 'order_paid') {
			echo '<div id="major-publishing-actions" class="misc-pub-section">' . __('Paid:', 'mp') . ' <strong>' . $paid . '</strong></div>';
			echo '<div class="misc-pub-section">' . __('Received:', 'mp') . ' <strong>' . $received . '</strong></div>';
		} else if ($order->post_status == 'order_shipped') {
			echo '<div id="major-publishing-actions" class="misc-pub-section">' . __('Shipped:', 'mp') . ' <strong>' . $shipped . '</strong></div>';
			echo '<div class="misc-pub-section">' . __('Paid:', 'mp') . ' <strong>' . $paid . '</strong></div>';
			echo '<div class="misc-pub-section">' . __('Received:', 'mp') . ' <strong>' . $received . '</strong></div>';
		} else if ($order->post_status == 'order_closed') {
			echo '<div id="major-publishing-actions" class="misc-pub-section">' . __('Closed:', 'mp') . ' <strong>' . $closed . '</strong></div>';
			echo '<div class="misc-pub-section">' . __('Shipped:', 'mp') . ' <strong>' . $shipped . '</strong></div>';
			echo '<div class="misc-pub-section">' . __('Paid:', 'mp') . ' <strong>' . $paid . '</strong></div>';
			echo '<div class="misc-pub-section">' . __('Received:', 'mp') . ' <strong>' . $received . '</strong></div>';
		 } else if ($order->post_status == 'trash') {
			echo '<div id="major-publishing-actions" class="misc-pub-section">' . __('Trashed', 'mp') . '</div>';
}

		?>
	</div>
</div>

<div id="mp-order-payment" class="postbox">
	<h3 class='hndle'><span><?php _e('Payment Information', 'mp'); ?></span></h3>
	<div class="inside">
		<div id="mp_payment_gateway" class="misc-pub-section">
			<?php _e('Payment Gateway:', 'mp'); ?>
			<strong><?php echo $order->mp_payment_info['gateway_private_name']; ?></strong>
		</div>
		<?php if ($order->mp_payment_info['method']) { ?>
		<div id="mp_payment_method" class="misc-pub-section">
			<?php _e('Payment Type:', 'mp'); ?>
			<strong><?php echo $order->mp_payment_info['method']; ?></strong>
		</div>
		<?php } ?>
		<?php if ($order->mp_payment_info['transaction_id']) { ?>
		<div id="mp_transaction" class="misc-pub-section">
			<?php _e('Transaction ID:', 'mp'); ?>
			<strong><?php echo $order->mp_payment_info['transaction_id']; ?></strong>
		</div>
		<?php } ?>
		<div id="major-publishing-actions" class="misc-pub-section">
			<?php _e('Payment Total:', 'mp'); ?>
			<strong><?php echo mp_format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total']) . ' ' . $order->mp_payment_info['currency']; ?></strong>
		</div>
	</div>
</div>

<?php if (is_array($order->mp_payment_info['status']) && count($order->mp_payment_info['status'])) { ?>
<div id="mp-order-payment-history" class="postbox">
	<h3 class='hndle'><span><?php _e('Payment Transaction History', 'mp'); ?></span></h3>
	<div class="inside">
	<?php
	$statuses = $order->mp_payment_info['status'];
	krsort($statuses); //sort with latest status at the top
	$first = true;
	foreach ($statuses as $timestamp => $status) {
		if ($first) {
			echo '<div id="major-publishing-actions" class="misc-pub-section">';
			$first = false;
		} else {
			echo '<div id="mp_payment_gateway" class="misc-pub-section">';
		}
		?>
			<strong><?php echo mp_format_date($timestamp); ?>:</strong>
			<?php echo esc_html($status); ?>
		</div>
	<?php } ?>

	</div>
</div>
<?php } ?>

</div></div>

<div id="post-body">
<div id="post-body-content">

<div id='normal-sortables' class='meta-box-sortables'>

<div id="mp-order-products" class="postbox">
	<h3 class='hndle'><span><?php _e('Order Information', 'mp'); ?></span></h3>
	<div class="inside">

	<table id="mp-order-product-table" class="widefat">
		<thead><tr>
			<th class="mp_cart_col_thumb">&nbsp;</th>
			<th class="mp_cart_col_sku"><?php _e('SKU', 'mp'); ?></th>
			<th class="mp_cart_col_product"><?php _e('Item', 'mp'); ?></th>
			<th class="mp_cart_col_quant"><?php _e('Quantity', 'mp'); ?></th>
			<th class="mp_cart_col_price"><?php _e('Price', 'mp'); ?></th>
			<th class="mp_cart_col_subtotal"><?php _e('Subtotal', 'mp'); ?></th>
			<th class="mp_cart_col_downloads"><?php _e('Downloads', 'mp'); ?></th>
		</tr></thead>
		<tbody>
		<?php
		global $blog_id;
		$bid = (is_multisite()) ? $blog_id : 1; // FPM

		if (is_array($order->mp_cart_info) && count($order->mp_cart_info)) {
			foreach ($order->mp_cart_info as $product_id => $variations) {
				//for compatibility for old orders from MP 1.0
				if (isset($variations['name'])) {
					$data = $variations;
					echo '<tr>';
					 echo '	 <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id ) . '</td>';
					 echo '	 <td class="mp_cart_col_sku">' . esc_attr($data['SKU']) . '</td>';
					 echo '	 <td class="mp_cart_col_product"><a href="' . get_permalink($product_id) . '">' . esc_attr($data['name']) . '</a></td>';
					 echo '	 <td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
					 echo '	 <td class="mp_cart_col_price">' . mp_format_currency('', $data['price']) . '</td>';
					 echo '	 <td class="mp_cart_col_subtotal">' . mp_format_currency('', $data['price'] * $data['quantity']) . '</td>';
					 echo '	 <td class="mp_cart_col_downloads">' . __('N/A', 'mp') . '</td>';
					 echo '</tr>';
				} else {
					foreach ($variations as $variation => $data) {
						echo '<tr>';
						echo '	<td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id ) . '</td>';
						echo '	<td class="mp_cart_col_sku">' . esc_attr($data['SKU']) . '</td>';
						echo '	<td class="mp_cart_col_product"><a href="' . get_permalink($product_id) . '">' . esc_attr($data['name']) . '</a>';

						//Output product custom field information
						$cf_key = $bid .':'. $product_id .':'. $variation;
						if (isset($order->mp_shipping_info['mp_custom_fields'][$cf_key])) {
							$cf_item = $order->mp_shipping_info['mp_custom_fields'][$cf_key];

							$mp_custom_field_label = get_post_meta($product_id, 'mp_custom_field_label', true);
							if (isset($mp_custom_field_label[$variation]))
								$label_text = esc_attr($mp_custom_field_label[$variation]);
							else
								$label_text = __('Product Personalization:', 'mp');

							echo '<div class="mp_cart_custom_fields">'. $label_text .'<ol>';
							foreach ($cf_item as $item) {
								echo '<li>'. $item .'</li>';
							}
							echo '</ol></div>';
						}

						echo '</td>';
						echo '	<td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
						echo '	<td class="mp_cart_col_price">' . mp_format_currency('', $data['price']) . '</td>';
						echo '	<td class="mp_cart_col_subtotal">' . mp_format_currency('', $data['price'] * $data['quantity']) . '</td>';
						if (is_array($data['download']))
							 echo '	 <td class="mp_cart_col_downloads">' . number_format_i18n($data['download']['downloaded']) . (($data['download']['downloaded'] >= $max_downloads) ? __(' (Limit Reached)', 'mp') : '')	. '</td>';
						else
							echo '	<td class="mp_cart_col_downloads">' . __('N/A', 'mp') . '</td>';
						echo '</tr>';
					}
				}
			}
		} else {
			echo '<tr><td colspan="7">' . __('No products could be found for this order', 'mp') . '</td></tr>';
		}
		?>
		</tbody>
	</table><br />

	<?php //coupon line
	if ( isset($order->mp_discount_info) ) { ?>
	<h3><?php _e('Coupon Discount:', 'mp'); ?></h3>
	<p><?php echo $order->mp_discount_info['discount']; ?> (<?php echo $order->mp_discount_info['code']; ?>)</p>
	<?php } ?>

	<?php //shipping line
	if ( $order->mp_shipping_total ) { ?>
	<h3><?php _e('Shipping:', 'mp'); ?></h3>
	<p><?php echo mp_format_currency('', $order->mp_shipping_total) . ' ( ' . strtoupper(isset($order->mp_shipping_info['shipping_option']) ? $order->mp_shipping_info['shipping_option'] : '') . ' ' .	 (isset($order->mp_shipping_info['shipping_sub_option']) ? $order->mp_shipping_info['shipping_sub_option'] : '') . ' )'; ?></p>
	<?php } ?>

	<?php //tax line
	if ( $order->mp_tax_total ) { ?>
	<h3><?php echo esc_html(mp_get_setting('tax->label', __('Taxes', 'mp'))); ?>:</h3>
	<p><?php echo mp_format_currency('', $order->mp_tax_total); ?></p>
	<?php } ?>

	<h3><?php _e('Cart Total:', 'mp'); ?></h3>
	<p><?php echo mp_format_currency('', $order->mp_order_total); ?></p>

	<?php //special instructions line
	if ( !empty($order->mp_shipping_info['special_instructions']) ) { ?>
	<h3><?php _e('Special Instructions:', 'mp'); ?></h3>
	<p><?php echo wpautop(esc_html($order->mp_shipping_info['special_instructions'])); ?></p>
	<?php } ?>

	</div>
</div>

<form id="mp-shipping-form" action="" method="post">
<div id="mp-order-shipping-info" class="postbox">
	<h3 class='hndle'><span><?php _e('Shipping Information', 'mp'); ?></span></h3>
	<div class="inside">
		<h3><?php _e('Address:', 'mp'); ?></h3>
		<table>
			<tr>
			<td align="right"><?php _e('Full Name:', 'mp'); ?></td><td>
			<?php esc_attr_e($order->mp_shipping_info['name']); ?></td>
			</tr>

			<tr>
			<td align="right"><?php _e('Email:', 'mp'); ?></td><td>
			<?php esc_attr_e($order->mp_shipping_info['email']); ?></td>
			</tr>

			<tr>
			<td align="right"><?php _e('Address:', 'mp'); ?></td>
			<td><?php esc_attr_e($order->mp_shipping_info['address1']); ?></td>
			</tr>

			<?php if ($order->mp_shipping_info['address2']) { ?>
			<tr>
			<td align="right"><?php _e('Address 2:', 'mp'); ?></td>
			<td><?php esc_attr_e($order->mp_shipping_info['address2']); ?></td>
			</tr>
			<?php } ?>

			<tr>
			<td align="right"><?php _e('City:', 'mp'); ?></td>
			<td><?php esc_attr_e($order->mp_shipping_info['city']); ?></td>
			</tr>

			<?php if ($order->mp_shipping_info['state']) { ?>
			<tr>
			<td align="right"><?php _e('State/Province/Region:', 'mp'); ?></td>
			<td><?php esc_attr_e($order->mp_shipping_info['state']); ?></td>
			</tr>
			<?php } ?>

			<tr>
			<td align="right"><?php _e('Postal/Zip Code:', 'mp'); ?></td>
			<td><?php esc_attr_e($order->mp_shipping_info['zip']); ?></td>
			</tr>

			<tr>
			<td align="right"><?php _e('Country:', 'mp'); ?></td>
			<td><?php echo $this->countries[$order->mp_shipping_info['country']]; ?></td>
			</tr>

			<?php if ($order->mp_shipping_info['phone']) { ?>
			<tr>
			<td align="right"><?php _e('Phone Number:', 'mp'); ?></td>
			<td><?php esc_attr_e($order->mp_shipping_info['phone']); ?></td>
			</tr>
			<?php } ?>
		</table>

		<h3><?php _e('Cost:', 'mp'); ?></h3>
		<p><?php echo mp_format_currency('', $order->mp_shipping_total) . ' ( ' . strtoupper(isset($order->mp_shipping_info['shipping_option']) ? $order->mp_shipping_info['shipping_option'] : '') . ' ' .	 (isset($order->mp_shipping_info['shipping_sub_option']) ? $order->mp_shipping_info['shipping_sub_option'] : '') . ' )'; ?></p>

		<h3><?php _e('Shipping Method & Tracking Number:', 'mp'); ?></h3>
		<p>
		<select name="mp_shipping_method">
			<option value="other"><?php _e('Choose Method:', 'mp'); ?></option>
			<option value="UPS"<?php selected(@$order->mp_shipping_info['method'], 'UPS'); ?>>UPS</option>
			<option value="FedEx"<?php selected(@$order->mp_shipping_info['method'], 'FedEx'); ?>>FedEx</option>
			<option value="USPS"<?php selected(@$order->mp_shipping_info['method'], 'USPS'); ?>>USPS</option>
			<option value="DHL"<?php selected(@$order->mp_shipping_info['method'], 'DHL'); ?>>DHL</option>
			<option value="other"<?php selected(@$order->mp_shipping_info['method'], 'other'); ?>><?php _e('Other', 'mp'); ?></option>
			<?php do_action('mp_shipping_tracking_select', @$order->mp_shipping_info['method']); ?>
		</select>
		<input type="text" name="mp_tracking_number" value="<?php esc_attr_e(isset($order->mp_shipping_info['tracking_num']) ? $order->mp_shipping_info['tracking_num'] : ''); ?>" size="25" />
		<input type="submit" class="button-secondary" name="add-tracking" value="<?php _e('Save &raquo;', 'mp'); ?>" /><?php if ($order->post_status == 'order_received' ||$order->post_status == 'order_paid') { ?> <input type="submit" class="button-secondary" name="add-tracking-shipped" value="<?php _e('Save & Mark as Shipped &raquo;', 'mp'); ?>" /><?php } ?>
		</p>

		<?php //note line if set by gateway
		if ( $order->mp_payment_info['note'] ) { ?>
		<h3><?php _e('Special Note:', 'mp'); ?></h3>
		<p><?php esc_html_e($order->mp_payment_info['note']); ?></p>
		<?php } ?>

		<?php do_action('mp_single_order_display_shipping', $order); ?>

	</div>
</div>

<div id="mp-order-notes" class="postbox">
	<h3 class='hndle'><span><?php _e('Order Notes', 'mp'); ?></span> - <span class="description"><?php _e('These notes will be displayed on the order status page', 'mp'); ?></span></h3>
	<div class="inside">
		<p>
		<textarea name="mp_order_notes" rows="5" style="width: 100%;"><?php echo esc_textarea(isset($order->mp_order_notes) ? $order->mp_order_notes : ''); ?></textarea><br />
		<input type="submit" class="button-secondary" name="save-note" value="<?php _e('Save &raquo;', 'mp'); ?>" />
		</p>
	</div>
</div>
</form>

<?php do_action('mp_single_order_display_box', $order); ?>

</div>

<div id='advanced-sortables' class='meta-box-sortables'>
</div>

</div>
</div>
<br class="clear" />
</div><!-- /poststuff -->

</div><!-- /wrap -->