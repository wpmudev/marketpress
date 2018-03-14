<?php
	
class MP_Exporter_Orders {
	/**
	 * Export orders
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function export() {
		global $wpdb;

		//check permissions
		if ( ! current_user_can( 'edit_others_orders' ) )
			wp_die( __( 'Cheatin&#8217; uh?', 'mp' ) );

		$query = "SELECT ID, post_title, post_date, post_status FROM {$wpdb->posts} WHERE post_type = 'mp_order'";
		$order_status = mp_get_post_value( 'order_status' );
		$order_date = mp_get_post_value( 'order_date' );
		
		if ( $order_status != 'all' ) {
			$query .= $wpdb->prepare( ' AND post_status = %s', $order_status );
		}
		
		//! TODO: finish order export

		if ( isset($_POST['m']) && $_POST['m'] > 0 ) {
			$_POST['m'] = '' . preg_replace('|[^0-9]|', '', $_POST['m']);
			$query .= " AND YEAR($wpdb->posts.post_date)=" . substr($_POST['m'], 0, 4);
			if ( strlen($_POST['m']) > 5 )
				$query .= " AND MONTH($wpdb->posts.post_date)=" . substr($_POST['m'], 4, 2);
			if ( strlen($_POST['m']) > 7 )
				$query .= " AND DAYOFMONTH($wpdb->posts.post_date)=" . substr($_POST['m'], 6, 2);
			if ( strlen($_POST['m']) > 9 )
				$query .= " AND HOUR($wpdb->posts.post_date)=" . substr($_POST['m'], 8, 2);
			if ( strlen($_POST['m']) > 11 )
				$query .= " AND MINUTE($wpdb->posts.post_date)=" . substr($_POST['m'], 10, 2);
			if ( strlen($_POST['m']) > 13 )
				$query .= " AND SECOND($wpdb->posts.post_date)=" . substr($_POST['m'], 12, 2);
		}

		$query .= " ORDER BY post_date DESC";

		$orders = $wpdb->get_results($query);

		// Keep up to 12MB in memory, if becomes bigger write to temp file
		$file = fopen('php://temp/maxmemory:'. (12*1024*1024), 'r+');
		fputcsv( $file, array('order_id', 'status', 'received_date', 'paid_date', 'shipped_date', 'tax', 'shipping', 'total', 'coupon_discount', 'coupon_code', 'item_count', 'items', 'email', 'name', 'address1', 'address2', 'city', 'state', 'zipcode', 'country', 'phone', 'shipping_method', 'shipping_method_option', 'special_instructions', 'gateway', 'gateway_method', 'payment_currency', 'transaction_id' ) );

		//loop through orders and add rows
		foreach ($orders as $order) {
			$meta = get_post_custom($order->ID);

			//unserialize a and add to object
			foreach ($meta as $key => $val)
				$order->$key = maybe_unserialize($meta[$key][0]);

			$fields = array();
			$fields['order_id'] = $order->post_title;
			$fields['status'] = $order->post_status;
			$fields['received_date'] = $order->post_date;
			$fields['paid_date'] = isset($order->mp_paid_time) ? date('Y-m-d H:i:s', $order->mp_paid_time) : null;
			$fields['shipped_date'] = isset($order->mp_shipped_time) ? date('Y-m-d H:i:s', $order->mp_paid_time) : null;
			$fields['tax'] = $order->mp_tax_total;
			$fields['shipping'] = $order->mp_shipping_total;
			$fields['total'] = $order->mp_order_total;
			$fields['coupon_discount'] = @$order->mp_discount_info['discount'];
			$fields['coupon_code'] = @$order->mp_discount_info['code'];
			$fields['item_count'] = $order->mp_order_items;
			//items
			if (is_array($order->mp_cart_info) && count($order->mp_cart_info)) {
				foreach ($order->mp_cart_info as $product_id => $variations) {
					foreach ($variations as $variation => $data) {
						if (!empty($fields['items']))
							$fields['items'] .= "\r\n";
							
						if (!empty($data['SKU']))
							$fields['items'] .= '[' . $data['SKU'] . '] ';

						$price = mp()->coupon_value_product($fields['coupon_code'], $data['price'] * $data['quantity'], $product_id);
						
						$fields['items'] .= $data['name'] . ': ' . number_format_i18n($data['quantity']) . ' * ' . mp_number_format($price / $data['quantity'], 2) . ' ' . $order->mp_payment_info['currency'];
					}
				}
			} else {
				$fields['items'] = 'N/A';
			}

			$fields['email'] = @$order->mp_shipping_info['email'];
			$fields['name'] = @$order->mp_shipping_info['name'];
			$fields['address1'] = @$order->mp_shipping_info['address1'];
			$fields['address2'] = @$order->mp_shipping_info['address2'];
			$fields['city'] = @$order->mp_shipping_info['city'];
			$fields['state'] = @$order->mp_shipping_info['state'];
			$fields['zipcode'] = @$order->mp_shipping_info['zip'];
			$fields['country'] = @$order->mp_shipping_info['country'];
			$fields['phone'] = @$order->mp_shipping_info['phone'];
			$fields['shipping_method'] = @$order->mp_shipping_info['shipping_option'];
			$fields['shipping_method_option'] = @$order->mp_shipping_info['shipping_sub_option'];
			$fields['special_instructions'] = @$order->mp_shipping_info['special_instructions'];
			$fields['gateway'] = @$order->mp_payment_info['gateway_private_name'];
			$fields['gateway_method'] = @$order->mp_payment_info['method'];
			$fields['payment_currency'] = @$order->mp_payment_info['currency'];
			$fields['transaction_id'] = @$order->mp_payment_info['transaction_id'];

			fputcsv( $file, $fields );
		}

		//create our filename
		$filename = 'orders_export';
		$filename .= isset($_POST['m']) ? '_' . $_POST['m'] : '';
		$filename .= '_' . time() . '.csv';

		//serve the file
		rewind($file);
		ob_end_clean(); //kills any buffers set by other plugins
		header('Content-Description: File Transfer');
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		$output = stream_get_contents($file);
		$output = "\xEF\xBB\xBF" . $output; // UTF-8 BOM
		header('Content-Length: ' . strlen($output));
		fclose($file);
		die($output);		
	}
	
	/**
	 * Display the export order form
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb, $wp_locale
	 */
	public static function export_form() {
		global $wpdb, $wp_locale;
		
		$months = $wpdb->get_results("
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM $wpdb->posts
			WHERE post_type = 'mp_order'
			ORDER BY post_date DESC
		");
		
		$statuses = get_post_stati( array( 'post_type' => 'mp_order' ), 'objects' );
		?>
<h2><?php _e( 'Export Orders', 'mp' ); ?></h2>
<form method="post" action="<?php echo add_query_arg( 'action', 'mp_export_orders' ); ?>">
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e( 'Order Date', 'mp' ); ?></th>
			<td>
				<select name="order_date">
					<option value=""><?php _e( 'All Dates', 'mp' ); ?>
					<?php foreach ( $months as $row ) :
						$month = zeroise( $row->month, 2 );
						$year = $row->year; ?>
					<option value="<?php echo $year . $month; ?>"><?php echo $wp_locale->get_month( $month ) . ' ' . $year; ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e( 'Order Status', 'mp' ); ?></th>
			<td>
				<select name="order_status">
					<option value=""><?php _e( 'All Statuses', 'mp' ); ?>
					<?php foreach ( $statuses as $key => $status ) : ?>
					<option value="<?php echo $key; ?>"><?php echo $status->label; ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
	</table>
	<p class="submit"><input class="button-primary" type="submit" value="<?php _e( 'Export Orders', 'mp' ); ?>" /></p>
</form>
		<?php
	}

}