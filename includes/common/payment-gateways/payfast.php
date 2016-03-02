<?php
/**
 * payfast.php
 *
 * MarketPress PayFast Gateway Plugin
 *
 * Copyright (c) 2013 PayFast (Pty) Ltd
 *
 * LICENSE:
 *
 * This payment module is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation; either version 3 of the License, or (at
 * your option) any later version.
 *
 * This payment module is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public
 * License for more details.
 *
 *
 * @version v2.0
 *
 * @author Jonathan Page
 * @since v1.0
 *
 * @author  Ron Darby ron.darby@payfast.co.za
 * @since 2.0
 * @date    2013-07-04
 *
 * @copyright  2013 PayFast (Pty) Ltd
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://www.payfast.co.za/help/cube_cart
 */

class MP_Gateway_PayFast extends MP_Gateway_API {
    //private gateway slug. Lowercase alpha (a-z) and dashes (-) only please!
    var $plugin_name = 'payfast';

    //name of your gateway, for the admin side.
    var $admin_name = '';

    //public name of your gateway, for lists and such.
    var $public_name = '';

    //url for an image for your checkout method. Displayed on checkout form if set
    var $method_img_url = '';

    //url for an submit button image for your checkout method. Displayed on checkout form if set
    var $method_button_img_url = '';

    //whether or not ssl is needed for checkout page
    var $force_ssl = false;

    //always contains the url to send payment notifications to if needed by your gateway. Populated by the parent class
    var $ipn_url;

    //whether if this is the only enabled gateway it can skip the payment_form step
    var $skip_form = true;

    //only required for global capable gateways. The maximum stores that can checkout at once
    var $max_stores = 1;

    // Payment action
    var $payment_action = 'Sale';

    //payfast vars
    var $pfMerchantId, $pfMerchantKey, $SandboxFlag, $returnURL, $cancelURL, $payfastURL, $version, $currencyCode, $passphrase, $test_mode;

    /****** Below are the public methods you may overwrite via a plugin ******/
    /**
     * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
     */
    function on_creation() {
        global $mp;
        $settings = get_option('mp_settings');

        if ( $mp->global_cart )
            $settings = get_site_option( 'mp_network_settings' );

        //set names here to be able to translate
        $this->admin_name = __('PayFast', 'mp');
        $this->public_name = __('PayFast', 'mp');

        $this->method_img_url = 'https://www.payfast.co.za/images/logo/PayFast_Logo_75.png';
        $this->method_button_img_url = 'https://www.payfast.co.za/images/logo/PayFast_Logo_75.png';

        // Enable logging to payfast.log file
        if ($settings['gateways']['payfast']['debug'] == 'no')
            define('PF_DEBUG', false);
        else
            define('PF_DEBUG', true);

        $this->currencyCode = "ZAR";
        $this->returnURL = mp_checkout_step_url('confirmation');
        $this->cancelURL = mp_checkout_step_url('checkout') . "?cancel=1";
        $this->version = "2.0"; //api version

        //set api urls
        if ($mp->get_setting('gateways->payfast->mode') == 'test') {
            $this->pfMerchantId = '10000100';
            $this->pfMerchantKey = '46f0cd694581a';
            $this->payfastURL = 'https://sandbox.payfast.co.za/eng/process';
        } else {
            $this->pfMerchantId =$mp->get_setting('gateways->payfast->merchantid');
            $this->pfMerchantKey = $mp->get_setting('gateways->payfast->merchantkey');
            $this->payfastURL = 'https://www.payfast.co.za/eng/process';
        }
        $this->test_mode = $mp->get_setting('gateways->payfast->mode') == 'test' ? true : false;

        $this->passphrase = $mp->get_setting('gateways->payfast->passphrase');
    }

    /**
     * Return fields you need to add to the top of the payment screen, like your credit card info fields
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
    function payment_form($cart, $shipping_info) {

        global $mp;
        if (isset($_GET['cancel']))
            echo '<div class="mp_checkout_error">' . __('Your PayFast transaction has been canceled.',
                'mp') . '</div>';
    }

    /**
     * Use this to process any fields you added. Use the $_REQUEST global,
     *  and be sure to save it to both the $_SESSION and usermeta if logged in.
     *  DO NOT save credit card details to usermeta as it's not PCI compliant.
     *  Call $mp->cart_checkout_error($msg, $context); to handle errors. If no errors
     *  it will redirect to the next step.
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
    function process_payment_form($cart, $shipping_info) {
        global $mp;
        $mp->generate_order_id();
    }

    /**
     * Return the chosen payment details here for final confirmation. You probably don't need
     *  to post anything in the form as it should be in your $_SESSION var already.
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
    function confirm_payment_form($cart, $shipping_info) {
        global $mp;

    }

    /**
     * Use this to do the final payment. Create the order then process the payment. If
     *  you know the payment is successful right away go ahead and change the order status
     *  as well.
     *  Call $mp->cart_checkout_error($msg, $context); to handle errors. If no errors
     *  it will redirect to the next step.
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
    function process_payment($cart, $billing_info, $shipping_info) {
        global $mp;
        require_once ('payfast_common.inc');
        $order_id = $_SESSION['mp_order'];
        $shipping_info = $_SESSION['mp_shipping_info'];
        $pfAmount = 0;
        $pfDescription = '';
        $pfOutput = '';
        $timestamp = time();

        $selected_cart = $global_cart;
        $settings = get_site_option('mp_network_settings');

        //loop through cart items
        if (!is_array($cart) || count($cart) == 0) {
            return;
        }
        $totals = array();

        foreach ($cart as $product_id => $variations) {
            foreach ($variations as $variation => $data) {
                $totals[] = $mp->before_tax_price($data['price']) * $data['quantity'];
            }
        }
        $total = array_sum($totals);

        //coupon line
        if ($coupon = $mp->coupon_value($mp->get_coupon_code(), $total)) {
            $total = $coupon['new_total'];
        }

        //shipping line
        if (($shipping_price = $mp->shipping_price(false)) !== false) {
            $total = $total + $shipping_price;
        }

        //tax line
        if (($tax_price = $mp->tax_price(false)) !== false) {
            $total = $total + $tax_price;
        }

        $pfAmount = $total;

        // Construct variables for post
        $data = array(
            'merchant_id' => $this->pfMerchantId,
            'merchant_key' => $this->pfMerchantKey,
            'return_url' => $this->returnURL,
            'cancel_url' => $this->cancelURL,
            'notify_url' => $this->ipn_url . "/?itn_request=true", // Item details
            'm_payment_id' => $order_id,
            'amount' => number_format(sprintf("%01.2f", $pfAmount), 2, '.', ''),
            'item_name' => 'Order #' . $_SESSION['mp_order']
            );
        foreach( $data as $key => $val )
        {
            if(!empty($val))
            {
                $pfOutputSig .= $key .'='. urlencode( trim( $val ) ) .'&';
            }
        }


        if( !empty( $this->passphrase ) && !$this->test_mode )
        {
            $getString .= $pfOutputSig.'passphrase='.urlencode( $this->passphrase );
        }
        else
        {
            // Remove last ampersand
            $getString .= substr( $pfOutputSig, 0, -1 );
        }


        $pfOutput = $pfOutputSig .'signature='.md5($getString);

        // Create the order
        $payment_info['gateway_public_name'] = $this->public_name;
        $payment_info['gateway_private_name'] = $this->admin_name;
        $payment_info['status'][$timestamp] = __("Received", 'mp');
        $payment_info['total'] = $pfAmount;
        $payment_info['currency'] = $this->currencyCode;
        $payment_info['method'] = "PayFast";

        $paid = false;

        $order = $mp->create_order($order_id, $mp->get_cart_contents(), $_SESSION['mp_shipping_info'], $payment_info, $paid);

        // Send to PayFast (GET)
        header("Location: " . $this->payfastURL . "?" . $pfOutput);
        exit(0);
    }

    /**
     * Filters the order confirmation email message body. You may want to append something to
     *  the message. Optional
     *
     * Don't forget to return!
     */
    function order_confirmation_email($msg, $order) {
        return $msg;
    }

    /**
     * Return any html you want to show on the confirmation screen after checkout. This
     *  should be a payment details box and message.
     *
     * Don't forget to return!
     */
    function order_confirmation_msg($content, $order) {
        global $mp;

        if ($order->post_status == 'order_received') {
          $content .= '<p>' . sprintf(__('Your payment via PayFast for this order totaling %s is not yet complete. Here is the latest status:', 'mp'), mp_format_currency('', $order->mp_payment_info['total'])) . '</p>';
            $statuses = $order->mp_payment_info['status'];
            krsort($statuses); //sort with latest status at the top
            $status = reset($statuses);
            $timestamp = key($statuses);
          $content .= '<p><strong>' . date(get_option('date_format') . ' - ' . get_option('time_format'), $timestamp) . ':</strong> ' . htmlentities($status) . '</p>';
        } else {
          $content .= '<p>' . sprintf(__('Your payment via PayFast for this order totaling %s is complete. The transaction number is <strong>%s</strong>.', 'mp'), mp_format_currency('', $order->mp_payment_info['total']), $order->mp_payment_info['transaction_id']) . '</p>';
        }
        return $content;
    }

    /**
     * Runs before page load incase you need to run any scripts before loading the success message page
     */
    function order_confirmation($order) {
        global $mp;
    }

    /**
     * Echo a settings meta box with whatever settings you need for you gateway.
     *  Form field names should be prefixed with mp[gateways][plugin_name], like "mp[gateways][plugin_name][mysetting]".
     *  You can access saved settings via $settings array.
     */
    function gateway_settings_box($settings) {
        global $mp;
    ?>
    <div id="mp_payfast" class="postbox">
      <h3 class='hndle'><span><?php _e('PayFast Checkout Settings', 'mp'); ?></span></h3>
      <div class="inside">
        <span class="description"><?php _e('PayFast is a payments processing service for South Africa. We make it safe for buyers to send money and easy for sellers to receive money. <a target="_blank" href="http://www.payfast.co.za">More Info &raquo;</a>', 'mp') ?></span>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('PayFast Mode', 'mp') ?></th>
                <td>
                    <select name="mp[gateways][payfast][mode]">
                        <option value="test"<?php selected($mp->get_setting('gateways->payfast->mode'), 'test') ?>><?php _e('Test', 'mp') ?></option>
                        <option value="live"<?php selected($mp->get_setting('gateways->payfast->mode'), 'live') ?>><?php _e('Live', 'mp') ?></option>

                    </select>
                </td>
            </tr>
            <tr<?php echo ($mp->global_cart) ? ' style="display:none;"' : '';?>>
                <th scope="row"><?php _e('PayFast Merchant Credentials', 'mp') ?></th>
                <td>
                    <span class="description"><?php _e('You can find your credentials on your integration page.', 'mp')?></span>
                    <p><label><?php _e('Merchant ID', 'mp') ?><br />
                            <input value="<?php echo esc_attr($mp->get_setting('gateways->payfast->merchantid')); ?>" size="30" name="mp[gateways][payfast][merchantid]" type="text" />
                    </label></p>
                    <p><label><?php _e('Merchant Key', 'mp') ?><br />
                        <input value="<?php echo esc_attr($mp->get_setting('gateways->payfast->merchantkey')); ?>" size="20" name="mp[gateways][payfast][merchantkey]" type="text" />
                    </label></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('PayFast Passphrase', 'mp') ?></th>
                <td>
                    <span class="description"><?php _e('DO NOT INPUT A VALUE UNLESS YOU HAVE SET ONE IN THE SETTINGS SECTION OF YOUR PAYFAST SETTINGS.', 'mp')?></span>
                    <p>
                        <input value="<?php echo esc_attr($mp->get_setting('gateways->payfast->passphrase')); ?>" size="20" name="mp[gateways][payfast][passphrase]" type="text" />
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Log Debugging Info?', 'mp') ?></th>
                <td>
                    <span class="description"><?php _e('This setting will log all PayFast communication to the "payfast.log" file.', 'mp')?></span>
                    <p><select name="mp[gateways][payfast][debug]">
                        <option value="yes"<?php selected($mp->get_setting('gateways->payfast->debug', 'yes')); ?>><?php _e('Yes', 'mp') ?></option>
                        <option value="no"<?php selected($mp->get_setting('gateways->payfast->debug', 'no')); ?>><?php _e('No', 'mp') ?></option>
                    </select></p>
                </td>
            </tr>
        </table>
      </div>
    </div>
    <?php
    }

    /**
     * Filters posted data from your settings form. Do anything you need to the $settings['gateways']['plugin_name']
     *  array. Don't forget to return!
     */
    function process_gateway_settings($settings) {
        return $settings;
    }

    /**
     * INS and payment return
     */
    function process_ipn_return() {
        global $mp;
        require_once( 'payfast_common.inc' );
        $timestamp = time();

        if ($mp->get_setting('gateways->payfast->debug') == 'no')
            define('PF_DEBUG', false);
        else
            define('PF_DEBUG', true);

        $pfError = false;
        $pfErrMsg = '';
        $pfDone = false;
        $pfData = array();
        $pfHost = ( $this->test_mode ? 'www' : 'sandbox') . '.payfast.co.za';
        $pfOrderId = '';
        $pfParamString = '';


        pflog('PayFast ITN call received');

        //// Notify PayFast that information has been received
        if (!$pfError && !$pfDone) {
            header('HTTP/1.0 200 OK');
            flush();
        }

        //// Get data sent by PayFast
        if (!$pfError && !$pfDone) {
            pflog('Get posted data');

            // Posted variables from ITN
            $pfData = pfGetData();

            pflog('PayFast Data: ' . print_r($pfData, true));

            if ($pfData === false) {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Verify security signature
        if (!$pfError && !$pfDone)
        {
            pflog('Verify security signature');

            $pfPassPhrase = !empty( $this->passphrase ) && !$this->test_mode ? $this->passphrase : null;

            // If signature different, log for debugging
            if (!pfValidSignature($pfData, $pfParamString, $pfPassPhrase ) )
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_INVALID_SIGNATURE;
            }
        }

        //// Verify source IP (If not in debug mode)
        if (!$pfError && !$pfDone && !PF_DEBUG)
        {
            pflog('Verify source IP');

            if (!pfValidIP($_SERVER['REMOTE_ADDR']))
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_SOURCE_IP;
            }
        }

        //// Get internal order and verify it hasn't already been processed
        if( !$pfError && !$pfDone ) {
            // Get order data
            $pfOrderId = $pfData['m_payment_id'];
            $order = $mp->get_order($pfOrderId);

            pflog( "Purchase:\n". print_r( $order, true )  );

            // Check if order has already been processed
            // It has been "processed" if it has a status above "Order Received"
            if( $purchase['processed'] > get_option( 'payfast_pending_status' ) ) {
                pflog( "Order has already been processed" );
                $pfDone = true;
            }
        }

        //// Verify data received
        if (!$pfError)
        {
            pflog('Verify data received');

            $pfValid = pfValidData($pfHost, $pfParamString);

            if (!$pfValid)
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_BAD_ACCESS;
            }
        }

        //// Check data against internal order
        if (!$pfError && !$pfDone)
        {
            pflog('Check data against internal order');

            // Check order amount
            $detailstr = "";
            $i = 0;
            foreach ($order->mp_cart_info as $product_id => $variations)
            {
                foreach ($variations as $variation => $data)
                {
                    $totals[] = $data['price'] * $data['quantity'];
                }
            }
            $total = array_sum($totals);

            //coupon line
            if ($coupon = $mp->coupon_value($mp->get_coupon_code(), $total))
            {
                $total = $coupon['new_total'];
            }

            //shipping line
            if (($shipping_price = $mp->shipping_price(false)) !== false)
            {
                $total = $total + $shipping_price;
            }

            //tax line
            if (($tax_price = $mp->tax_price(false)) !== false)
            {
                $total = $total + $tax_price;
            }


            if (!pfAmountsEqual($pfData['amount_gross'], $order->mp_order_total))
            {
                $pfError = true;
                $pfErrMsg = PF_ERR_AMOUNT_MISMATCH . "PF:{$pfData['amount_gross']} vs Calc:{$total} vs {$order->mp_order_total}";
            }
        }

        //// Check status and update order
        if (!$pfError && !$pfDone)
        {
            pflog('Check status and update order');

            switch ($pfData['payment_status'])
            {
                case 'COMPLETE':
                    pflog('- Complete');
                    $status = __('The payment has been completed, and the funds have been added successfully to your account balance.', 'mp');
                    $payment_info = $order->mp_payment_info;
                    $payment_info['transaction_id'] = $pfData['pf_payment_id'];
                    $payment_info['method'] = "PayFast";
                    $payment_info['status'][$timestamp] = $status; //new
                    $payment_info['total'] = $pfData['amount_gross']; //new
                    update_post_meta($order->ID, 'mp_payment_info', $payment_info);
                    $mp->update_order_payment_status($pfOrderId, $status, true);  //new
                    break;

                case 'FAILED':
                    pflog('- Failed');
                    $status = __("The payment has failed. This happens only if the payment was made from your customer's bank account.", 'mp');
                    $paid = false;

                    // Need to wait for "Completed" before processing
                    break;

                case 'PENDING':
                    pflog('- Pending');
                    $status = __('The payment is pending.', 'mp');
                    $paid = false;

                    // Need to wait for "Completed" before processing
                    break;

                default:
                    // If unknown status, do nothing (safest course of action)
                    break;
            }

            $status = $_POST['payment_status'] . ': ' . $status;
        }


        // If an error occurred
        if ($pfError)
        {
            pflog('Error occurred: ' . $pfErrMsg);
        }

        // Close log
        pflog('', true);
        exit();
    }
}

//register payment gateway plugin
//mp_register_gateway_plugin('MP_Gateway_PayFast', 'payfast', __('PayFast', 'mp'));
?>
