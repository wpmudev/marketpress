<?php

// include the inbound handler class
require_once 'stripe-files/Stripe_API_WP_Handler.php';

/**
 * MarketPress Stripe Connect Gateway Plugin
 * Author: Aaron Edwards, Marko Miljus
 * @author Saurabh Shukla <contact.saurabhshukla@gmail.com>
 */
class MP_Gateway_Stripe_Connect extends MP_Gateway_API {

    // private gateway slug. 
    var $plugin_name = 'stripe-connect';
    
    // name for the admin side.
    var $admin_name = '';

    // public name
    var $public_name = '';
    
    // image url for checkout. Displayed on checkout form
    var $method_img_url = '';
    
    // image url for an submit button. Displayed on checkout form
    var $method_button_img_url = '';
    
    // image url for connect method. Displayed on settings page
    var $connect_img_url = '';
    
    // image url for your disconnect method. Displayed on settings page
    var $disconnect_img_url = '';
    
    // Default url for IPN. Populated by the parent class
    var $ipn_url;
    
    // stripe url
    var $api_url;
    
    // if this is the only enabled gateway it can skip the payment_form step
    var $skip_form = false;
    
    //api vars
    var $api_settings= array();
    
    // same as api_settings in single site
    var $api_access = array();
    
    //set up WordPress install context, to avoid multiple function calls
    var $multisite = false;
    
    // we're going to store settings in a property for ease of use
    var $network_settings = array();
    var $local_settings = array();
    /**
     * Instantiation
     */
    function on_creation() {
        global $mp;
        
        // Set up properties
        
        // Set multisite context, so we don't have to call the function repeatedly
        if(is_multisite()){
            $this->multisite = true;
        }
        
        //set names here to be able to translate
        $this->admin_name = __('Stripe Connect', 'mp');
        $this->public_name = __('Stripe Connect', 'mp');
        
        // load graphics 
        $this->method_img_url = $mp->plugin_url . 'images/credit_card.png';
        $this->method_button_img_url = $mp->plugin_url . 'images/cc-button.png';
        $this->connect_img_url = $mp->plugin_url . 'images/stripe_connect.png';
        $this->disconnect_img_url = $mp->plugin_url . 'images/stripe_disconnect.png';
        
        
        // set up settings
        $this->refresh_settings();
        
        // api settings
        if ($this->multisite) {
            $api_settings = $this->network_settings;
        } else {
            $api_settings = $this->local_settings;
        }
        
        // is this working in test mode or live?
        $this->mode = $api_settings['mode'];
        
        /* get the working api settings depending on the mode.
         * right now this is from network settings in multisite
         * and local settings for single site
         */
        $this->api_settings = $api_settings[$this->mode];
        
        // set up api_access settings
        if($this->multisite){
            // in multisite, the access_token, etc is stored per site
            $this->api_access = $this->local_settings[$this->mode];
        }else{
            // on a single site, it is the same as api settings
            $this->api_access = $this->api_settings;
        }
        
        // get the currency from General Settings
        $this->currency = $mp->get_setting('currency','USD');
        
        // set the stripe api url
        $this->stripe_url = "https://connect.stripe.com/oauth/authorize?response_type=code&client_id=";
                
        // set up notices for super admin
        if ($this->multisite && is_super_admin()) {
            $this->_settings_notice();
        }

        // instantiate class to handle inbound calls
        new Stripe_API_WP_Handler();
        
        // load some js 
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Sets up or refreshes setting attributes
     */
    function refresh_settings(){
        global $mp;
        $this->local_settings = $mp->get_setting('gateways->stripe-connect');
        $network_settings = get_site_option('mp_network_settings');
        $this->network_settings = $network_settings['gateways']['stripe-connect'];
        
    }
    
    /**
     * Enqueues the necessary js
     * 
     * @global object $mp
     */
    function enqueue_scripts() {
        
        global $mp;
        
        // load some js on the store, but not the admin side
        if (!is_admin() 
                && get_query_var('pagename') == 'cart' 
                && get_query_var('checkoutstep') == 'checkout') {
            
            // enqueue the stripe js'es
            wp_enqueue_script(
                    'js-stripe',
                    'https://js.stripe.com/v1/',
                    array('jquery')
                    );
            wp_enqueue_script(
                    'stripe-connect-token',
                    $mp->plugin_url 
                    . 'plugins-gateway/stripe-files/stripe_connect_token.js',
                    array('js-stripe', 'jquery')
                    );
            
            // get the publishable_key, different for multisite and single site
            $key_index = ($this->multisite?'stripe_':'').'publishable_key';
            $publishable_key = $this->api_access[$key_index];
        
            // localize publisher keys and translatable strings
            wp_localize_script(
                'stripe-connect-token', 
                'stripe_connect', 
                array(
                    'publisher_key' => $publishable_key,
                    'name' => __('Please enter the full Cardholder Name.', 'mp'),
                    'number' => __('Please enter a valid Credit Card Number.', 'mp'),
                    'expiration' => __('Please choose a valid expiration date.', 'mp'),
                    'cvv2' => __('Please enter a valid card security code.'
                            . ' This is the 3 digits on the signature panel,'
                            . ' or 4 digits on the front of Amex cards.', 'mp')
                    )
                );
        }
    }

    /**
     * Settings notice
     *
     * We notify the super admin incase the settings have not been put
     */
    function _settings_notice() {
        
        // check if client id's are set, if not, throw a notice
        if (
                empty($this->network_settings['live']['client_id'])
                || empty($this->network_settings['test']['client_id'])) {
            add_action('admin_notices', array($this, 'setup_notice'));
        }
        
        // check if store currency will work
        if($this->currency_check()===false){
            add_action('admin_notices', array($this, 'currency_notice'));
        }
        
        // check if stripe is activated, alongside
        global $mp;
        $allowed = in_array('stripe',$mp->get_setting('gateways->allowed'));
        if($allowed){
            add_action('admin_notices', array($this, 'stripe_conflict_notice'));
        }
    }
    
    /**
     * Provides the admin notice text, was an anonymous function, earlier
     * Anon fns break in earlier PHP functions
     */
    function setup_notice(){
        ?>
        <div class="error fade">
            <p><?php printf(
                    __('Stripe Connect is not set up. <a href="%s">Please add API credentials</a>', 'mp'),
                    network_admin_url('settings.php?page=marketpress-ms')
                    ); ?></p>
        </div>
        <?php
    }
    
    /**
     * displays a notice for currency mismatch
     */
    function currency_notice(){
        ?>
        <div class="error fade">
            <p><?php printf(
                    __('The store\'s currency is not supported by Stripe.'
                            . ' <a target="_blank" href="%s">See list of supported currencies</a>'
                            . ' for your account', 'mp'),
                    'https://support.stripe.com/questions/which-currencies-does-stripe-support'
                    ); ?></p>
        </div>
        <?php
    }
    
    /**
     * displays a notice if standard stripe is active as well
     */
    function stripe_conflict_notice(){
        ?>
        <div class="error fade">
            <p><?php
                    _e('Stripe and Stripe Connect will not work together. '
                            . 'Please deactivate one of them.', 'mp'
                    ); ?></p>
        </div>
        <?php
    }
    
    /**
     * Checks if the currency is supported by stripe
     * 
     * @global object $mp
     * @return boolean
     */
    function currency_check(){
        global $mp;
        // setup the API private key
        if($this->multisite){

            // this is the access token we received on connect for the merchant
            $key = $this->api_access['access_token'];
        }else{

            // this is the private key, from the settings
            $key = $this->api_access['secret_key'];
        }
        
        // no key, don't do anything
        if($key){
        
            // include the Stripe lib
            if (!class_exists('Stripe')) {
                require_once($mp->plugin_dir 
                        . "plugins-gateway/stripe-files/lib/Stripe.php");
            }
            try{
                Stripe::setApiKey($key);

                $account= Stripe_Account::retrieve();
                
                // supported currencies
                $stripe_currencies = $account['currencies_supported'];

                // if the store currency is not supported on stripe
                if(!in_array(
                        strtolower($this->currency),
                        $stripe_currencies
                        )){
                    return false;
                }
                
                // otherwise, all set
                return true;
            }
            catch (Exception $e){
                // do nothing
                // this call failed, doesn't help us with the currency

            }
        }
    }

    /**
     * Return fields you need to add to the top of the payment screen, like your credit card info fields
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
    function payment_form($cart, $shipping_info) {
        
        //check to see if we're using the embedded form
        $embedded_form = ($this->local_settings['embedded_form_type']==='default') ?
                true : false;

        /* get the publishable key
         * on multisite, this would be the stripe_publishable_key for a merchant,
         * that we got with the access token
         * on single site, this is the default application's API key
         */
        $publishable_key=$this->api_access[($this->multisite?'stripe_':'').'publishable_key'];
        
        
        // we don't have a key, bail out
        if(!$publishable_key){
            $content =__('Stripe is not connected. Please contact admin', 'mp');
                
            return $content;
        }
        
        //if we need to use stripe's embedded form
        if ($embedded_form) {
            // calculate totals
            $total = $this->order_total($cart)*100;
            // get the embedded form
            $content = $this->embed_form_html($publishable_key, $total);
            
        }else{
            // we are going to use our own form
            $content = $this->custom_form_html();
        }
        
        return $content;
        
    }
    
    /**
     * Generates html for stripe's embedded credit card form
     * 
     * @param string $key Publishable Key
     * @param string $total Order total
     * @return string The html
     */
    function embed_form_html($key, $total){            
        // print some js and html to include the embedded form
        return 
            '<script>
                jQuery(document).ready(function($){
                    var gateway = $(".mp_choose_gateway").val();
                    if( gateway	 === "stripe-connect" ) {
                        $("#stripe-connect .mp_cart_direct_checkout").hide();
                        $("#mp_payment_confirm").click(function(e){
                            e.preventDefault();
                            $("#mp_payment_form").submit();
                        });
                    }
                    $(".mp_choose_gateway").change(function(){
                        if($(this).val() === "stripe-connect") {
                            $("#stripe-connect .mp_cart_direct_checkout").hide();
                            $("#mp_payment_confirm").click(function(e){
                                e.preventDefault();
                                $("#mp_payment_form").submit();
                            });
                        }
                    });
                });
            </script>
            <div align="right">
                <form action="" method="POST">
                    <script
                        src="//checkout.stripe.com/v2/checkout.js"
                        class="stripe-button"
                        data-key="' . $key . '"
                        data-amount="' . $total . '"
                        data-name="' . get_bloginfo('title') . '"
                        data-description="' . __('Your Order', 'mp') . '"
                        data-image="">
                    </script>
                </form>
                </div>';
    }
    
    /**
     * Generates a custom credit card form
     * 
     * @global object $mp
     * @return string
     */
    function custom_form_html(){

        global $mp;

        $name = isset($_SESSION['mp_shipping_info']['name']) ?
                    $_SESSION['mp_shipping_info']['name'] : '';

        return
            '<div id="stripe_checkout_errors"></div>'
               . '<table class="mp_cart_billing">'
                   . '<thead>'
                       . '<tr>'
                           . '<th colspan="2">'
                               . __('Enter Your Credit Card Information:', 'mp')
                           . '</th>'
                       . '</tr>'
                   . '</thead>'
                   . '<tbody>'
                   . '<tr>'
                       . '<td align="right">' 
                                       . __('Cardholder Name:', 'mp') 
                       . '</td>'
                       . '<td>'
                           . '<input size="35" id="cc_name" type="text"'
                           . 'value="' . esc_attr($name) . '" />'
                       . '</td>'
                   . '</tr>'
                   . '<tr>'
                       . '<td>'
                           . __('Card Number', 'mp') . ' '
                       . '</td>'
                       . '<td>'
                           . '<input type="text" size="30" autocomplete="off"'
                           . ' id="cc_number"/>'
                       . '</td>'
                   . '</tr>'
                   . '<tr>'
                   . '<td>'
                       .__('Expiration:', 'mp')

                   . '</td>'
                   . '<td>'
                       . '<select id="cc_month">'
                           . $this->_print_month_dropdown()
                       . '</select>'
                       . '<span> / </span>'
                       . '<select id="cc_year">'
                           . $this->_print_year_dropdown('', true)
                       . '</select>'
                   . '</td>'
               . '</tr>'
               . '<tr>'
                   . '<td>'
                   . __('CVC:', 'mp')
                   . '</td>'
                   . '<td>'
                       . '<input type="text" size="4" autocomplete="off"'
                       . ' id="cc_cvv2" />'
                   . '</td>'
               . '</tr>'
           . '</table>'
           . '<span id="stripe_processing"'
           . ' style="display: none;float: right;">'
               . '<img src="' . $mp->plugin_url . 'images/loading.gif" /> '
           .  __('Processing...', 'mp') 
           . '</span>';

    }
    

    /**
     * Return the chosen payment details here for final confirmation. You probably don't need
     * 	to post anything in the form as it should be in your $_SESSION var already.
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
    function confirm_payment_form($cart, $shipping_info) {
        global $mp;

        //make sure token is set at this point
        if (!isset($_SESSION['stripeconnectToken'])) {
            $mp->cart_checkout_error(sprintf(
                __('The Stripe Token was not generated correctly.'
                        . ' Please <a href="%s">go back and try again</a>.', 'mp'),
                mp_checkout_step_url('checkout')
                ));

            return false;
        }

        // include the Stripe lib
        if (!class_exists('Stripe')) {
            require_once($mp->plugin_dir 
                    . "plugins-gateway/stripe-files/lib/Stripe.php");
        }

        // setup the API private key
        if($this->multisite){

            // this is the access token we received on connect for the merchant
            $key = $this->api_access['access_token'];
        }else{

            // this is the private key, from the settings
            $key = $this->api_access['secret_key'];
        }

        // instantiate the API handler with the private key
        Stripe::setApiKey($key);

        // retreive the token, for processing
        try {
            $token = Stripe_Token::retrieve($_SESSION['stripeconnectToken']);
        } catch (Exception $e) {
            $mp->cart_checkout_error(
                sprintf(
                    __('%s. Please <a href="%s">go back and try again</a>.', 'mp'),
                    $e->getMessage(),
                    mp_checkout_step_url('checkout')
                    )
                );

            return false;
        }
        // display some order confirmation html
        return $this->confirm_html($token);
    }
    
    /**
     * Generates html for confirmation step
     * 
     * @param string $token Stripe token
     * @return string
     */
    function confirm_html($token){
        // print out order details and payment information
        return
                '<table class="mp_cart_billing">'
                    . '<thead>'
                        . '<tr>'
                            . '<th>' 
                                . __('Billing Information:', 'mp') 
                            . '</th>'
                            . '<th align="right">'
                                . '<a href="' 
                                . mp_checkout_step_url('checkout') 
                                . '">' 
                                . __('&laquo; Edit', 'mp') 
                                . '</a>'
                            . '</th>'
                        . '</tr>'
                    . '</thead>'
                    . '<tbody>'
                        . '<tr>'
                            . '<td align="right">' 
                                . __('Payment method:', 'mp') 
                            . '</td>'
                            . '<td>' 
                                . sprintf(__('Your <strong>%1$s Card</strong>'
                                . ' ending in <strong>%2$s</strong>.'
                                . ' Expires <strong>%3$s</strong>', 'mp'),
                                    $token->card->type,
                                    $token->card->last4,
                                    $token->card->exp_month . '/' . $token->card->exp_year
                                ) 
                            . '</td>'
                        . '</tr>'
                    . '</tbody>'
                . '</table>';
    }

    /**
     * Runs before page load incase you need to run any scripts before loading the success message page
     */
    function order_confirmation($order) {
        return;
    }

    /**
     * Print the years' dropdown for credit card form
     */
    function _print_year_dropdown() {

        $this_year = date('Y');
        $output = "<option value=''>--</option>";
        for($i=$this_year; $i<$this_year+15; $i++) {
            $output.= '<option value="'.$i.'">'.$i.'</option>'."\n";
        }
        return($output);
    }

    /**
     * Print the months
     */
    function _print_month_dropdown() {
        $output = "<option value=''>--</option>";

        for($m = 1;$m <= 12; $m++){ 
            $month =  date("M", mktime(0, 0, 0, $m)); 
            $ml = sprintf('%02d', $m);
            $output .= "<option value='$m'>$ml - $month</option>"; 
        } 

        return($output);
    }

    /**
     * Use this to process any fields you added. Use the $_POST global,
     * and be sure to save it to both the $_SESSION and usermeta if logged in.
     * DO NOT save credit card details to usermeta as it's not PCI compliant.
     * Call $mp->cart_checkout_error($msg, $context); to handle errors. If no errors
     * it will redirect to the next step.
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
    function process_payment_form($cart, $shipping_info) {
        global $mp;

        // check the token
        if (!isset($_POST['stripeconnectToken']))
            $mp->cart_checkout_error(sprintf(
                __('The Stripe Token was not generated correctly.'
                        . ' Please <a href="%s">go back and try again</a>.', 'mp'),
                mp_checkout_step_url('checkout')
                ));

        //save to session
        if (!$mp->checkout_error) {
            $_SESSION['stripeconnectToken'] = $_POST['stripeconnectToken'];
        }
    }

    /**
     * Filters the order confirmation email message body. You may want to append something to
     * 	the message. Optional
     *
     * Don't forget to return!
     */
    function order_confirmation_email($msg, $order = null) {
        return $msg;
    }

    /**
     * Return any html you want to show on the confirmation screen after checkout. This
     * 	should be a payment details box and message.
     *
     * Don't forget to return!
     */
    function order_confirmation_msg($content, $order) {
        global $mp;

        // check, if the payment is done
        if ($order->post_status === 'order_paid'){
            $total = $mp->format_currency(
                                $order->mp_payment_info['currency'],
                                $order->mp_payment_info['total']
                            );
            $content = '<p>' 
                    . sprintf(
                        __('Your payment for this order'
                        . ' totaling %s is complete.', 'mp'),
                        $total
                        ) 
                    . '</p>';
        }
        return $content;
    }

    /**
     * Echo a settings meta box with whatever settings you need for you gateway.
     * 	Form field names should be prefixed with mp[gateways][plugin_name], like "mp[gateways][plugin_name][mysetting]".
     * 	You can access saved settings via $settings array.
     */
    function gateway_settings_box($settings) {
        // some js
        $this->mode_script();
        // the form html
        $this->form_div();
    }
    
    /**
     * Prints some jQuery to handle APi mode change
     */
    function mode_script(){
        // some jQuery to post the form automatically, if mode is changed
        ?>
        <script>
            jQuery('document').ready(function(){
                jQuery('#mp_stripe-connect_settings').on('change', 'select',function(e){
                    jQuery(this).closest('form').first().submit();
                });
            });

        </script>
        <?php
    }
    
    /**
     * Print the settings box
     */
    function form_div(){
        ?>
        <div class="postbox">
            <h3 class='hndle'>
                <span>
                    <?php echo $this->admin_name; ?>
                </span> - <span class="description">
                    <?php _e('Stripe makes it easy to start accepting'
                            . ' credit cards directly on your site'
                            . ' with full PCI compliance',
                            'mp'); ?>
                </span>
            </h3>
            <div class="inside">
                <p class="description">
                    <?php _e("Accept Visa, MasterCard, American Express,"
                            . " Discover, JCB, and Diners Club cards"
                            . " directly on your site. You don't need"
                            . " a merchant account or gateway."
                            . " Stripe handles everything, including"
                            . " storing cards, subscriptions, and"
                            . " direct payouts to your bank account."
                            . " Credit cards go directly to Stripe's secure"
                            . " environment, and never hit your servers"
                            . " so you can avoid most PCI requirements.",
                            'mp'); ?>
                    <a href="https://stripe.com/" target="_blank">
                        <?php _e('More Info &raquo;', 'mp') ?>
                    </a>
                </p>
                <?php $this->form_table(); // the table with the form inputs ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Prints the table with form inputs
     */
    function form_table(){
        ?>
        <table class="form-table" id="mp_stripe-connect_settings<?php
            echo $this->multisite?'':'_single';
            ?>">
            <?php
            // the main settings fields
            $this->main_settings();

            // embed form setting
            $this->embed_form_ui();
            ?>
        </table>
        <?php
    }
    
    /**
     * Displays the main settings
     */
    function main_settings(){
        
        // make sure we have updated settings!
        $this->refresh_settings();
        
        // if this is multisite *and* the API is set up
        if($this->multisite 
                && (
                    !empty($this->network_settings['live']['client_id'])
                        && !empty($this->network_settings['test']['client_id'])
                )
                ){
            
            // set up api mode
            $mode = (empty($this->local_settings['mode']))?
                    $this->network_settings['mode']:$this->local_settings['mode'];
            
            if(is_super_admin()){
                // only super admin gets to set the mode
                self::api_mode_ui($mode, false);
            }
            
            // show the stripe connect/disconnect button
            $this->api_connect_ui($mode);

        // this is single site or multisite but api is not set up    
        } else {
                        
            // just print out the api settings
             self::api_ui($this->local_settings);
        }
    }
    
    
    /**
     * Displays a ui for connecting to Stripe API application in network mode
     * @param type $mode
     */
    function api_connect_ui($mode){
        
        // check if stripe needs to be disconnected
        $stripe_settings = $this->maybe_disconnect($mode, $this->local_settings);
        // get the access token
        $access_token = $stripe_settings[$mode]['access_token'];
        ?>
        <tr>
            <th scope="row">
                <?php _e('Connect', 'mp'); ?>
            </th>
            <td>
                <p class="description">
                    <?php echo $this->network_settings['msg']; ?>
                </p>

                <div class="mp_stripe-connect mp_stripe-connect_0">
                    <p>
                        <?php
                        // output connection status
                        echo $this->connect_error();
                        ?>
                    </p>
                    <p>
                        <?php
                        // display a button for connecting/disconnecting
                        $this->api_button($access_token,$mode);
                        ?>
                    </p>
                </div>
            </td>
        </tr>
        <?php
            
    }
    
    /**
     * Displays a button for connecting/ disconnecting with the Stripe API app
     * 
     * @param string $access_token Access token from Stripe
     * @param string $mode api mode. test/live
     */
    function api_button($access_token,$mode){
        
        // if there's no access token, the merchant hasn't registered
        if (empty($access_token)) {
            
            $blog_id = get_current_blog_id();
            $nonce = wp_create_nonce("mp_stripe_marketplace_connect");
            $state = $blog_id . '_' . $nonce . '_'.$mode;
            
            // the registration url
            $url = $this->stripe_url 
                    . $this->network_settings[$mode]['client_id'] 
                    . "&state=$state".'&scope=read_write';
            // button image
            $img = $this->connect_img_url;
        } else {
            
            // disconnect url
            $url = esc_url($_SERVER['REQUEST_URI'] 
                    . '&mp_stripe_marketplace_disconnect=' 
                    . wp_create_nonce("mp_stripe_marketplace_disconnect"));
            
            // button image
            $img = $this->disconnect_img_url;
        }
        ?>
            <p>
                <a href=" <?php echo $url ?> ">
                    <img src="<?php echo $img ?>" />
                </a>
            </p>
        <?php

    }
    
    /**
     * Ui to decide if an embedded form should be used
     */
    function embed_form_ui(){
        ?>
    
        <tr>
            <th scope="row"><?php _e('Form Options', 'mp'); ?></th>
            <td>
                <label for="stripe_form_type">
                    <?php _e('Use Default Embedded Form', 'mp'); ?>
                    <?php 
                    $form_type = isset($this->local_settings['embedded_form_type']) 
                            ? $this->local_settings['embedded_form_type'] : 'default';
                    ?>
                    <input type="checkbox" id="stripe_form_type" 
                           name="mp[gateways][stripe-connect][embedded_form_type]"
                           value="default" <?php checked($form_type, 'default'); ?> />
                </label>
            </td>
        </tr>
        <?php
    }
    /**
     * Filters posted data from your settings form. Do anything you need to the $settings['gateways']['plugin_name']
     * 	array. Don't forget to return!
     */
    function process_gateway_settings($settings) {
        
        // handle the checkbox since the default processing will not get
        // any values in the POST array and unchecking won't work
        if(!isset($_POST['mp']['gateways']['stripe-connect']['embedded_form_type'])){
            echo 'nope';
            $settings['gateways']['stripe-connect']['embedded_form_type']= 'custom';
        }
        return $settings;
    }

    /**
     * Use this to do the final payment. Create the order then process the payment. If
     * 	you know the payment is successful right away go ahead and change the order status
     * 	as well.
     * 	Call $mp->cart_checkout_error($msg, $context); to handle errors. If no errors
     * 	it will redirect to the next step.
     *
     * @param array $cart. Contains the cart contents for the current blog, global cart if $mp->global_cart is true
     * @param array $shipping_info. Contains shipping info and email in case you need it
     */
    function process_payment($cart, $shipping_info) {
        
        //make sure token is set at this point, otherwise, return early
        if (!isset($_SESSION['stripeconnectToken'])) {
            global $mp;
            $mp->cart_checkout_error(sprintf(
                    __('The Stripe Token was not generated correctly.'
                            . ' Please <a href="%s">go back and try again</a>.', 'mp'),
                    mp_checkout_step_url('checkout')
                    ));
            return false;
        }
        // we have a token, let's process
        $this->stripe_process($cart, $shipping_info);
        
    }
    
    /**
     * Process the payment
     *  
     * @global object $mp
     * @global object $current_user
     * @param array $cart
     * @param object $shipping_info
     * @return boolean
     */
    function stripe_process($cart, $shipping_info){
        global $mp, $current_user;
        
        // get the order total
        $total = $this->order_total($cart);
        
        // generate the order id
        $order_id = $mp->generate_order_id();
        
        // all set, let's process the order
        try {
            
            // set up charge parameters
            $charge_params = $this->stripe_parameters($total, $order_id);
            
            // send a charge request
            $charge = $this->stripe_charge($charge_params,$total);
            
            $status_txt = __('The payment was successful', 'mp');

            //setup our payment details
            $payment_info = $this->stripe_payment_info($charge, $total, $status_txt);
            
            // save transients, in case charge fails
            $this->transients($order_id, $cart, $shipping_info);
            
            // the charge was successful!
            if ($charge->paid == 'true') {
                
                // check if this order has been paid for (maybe by webhook
                if(!$mp->get_order($order_id)){
                    // create an order in thge system with these details
                    $order = $mp->create_order(
                            $order_id, 
                            $cart, 
                            $_SESSION['mp_shipping_info'], 
                            $payment_info,
                            true,
                            $current_user->id,
                            $mp->shipping_price(),
                            $mp->tax_price(),
                            $mp->get_coupon_code()
                            );
                }else{
                    // just update the existing order to paid
                    $mp->update_order_payment_status($order_id, $status_txt, true);
                }
                // delete the transients, we don't need them, anymore
                self::delete_transients($order_id);
                
                // unset the session token, we don't need it anymore
                unset($_SESSION['stripeconnectToken']);
                // unset the cart, as well
                $mp->set_cart_cookie(array());
                
            // charge failed!    
            }else{
                $mp->cart_checkout_error(sprintf(
                    __('There was an error processing your card: "%s".'
                            . ' Please <a href="%s">go back and try again</a>.', 'mp'),
                    $charge->failure_message,
                    mp_checkout_step_url('checkout')
                    ));
            }
                
        // something went wrong :(
        } catch (Exception $e) {
            // unset the token, we don't risk security
            unset($_SESSION['stripeconnectToken']);
            
            // throw an error
            $mp->cart_checkout_error(sprintf(
                    __('There was an error processing your card: "%s".'
                            . ' Please <a href="%s">go back and try again</a>.', 'mp'),
                    $e->getMessage(),
                    mp_checkout_step_url('checkout')
                    ));
            return false;
        }
    }
    
    
    /**
     * Calculate order total for processing
     * 
     * @global object $mp
     * @param array $cart the cart
     * @return num
     */
    function order_total($cart){
        global $mp;
        
        $totals = array();
        
        // get the coupon code, if any
        $coupon_code = $mp->get_coupon_code();
        
        // calculate the final amount for each item
        foreach ($cart as $product_id => $variations) {
            foreach ($variations as $variation => $data) {
                $price = $mp->coupon_value_product(
                        $coupon_code,
                        $data['price'] * $data['quantity'],
                        $product_id
                        );
                $totals[] = $price;
            }
        }
        
        // calculate the total
        $total = array_sum($totals);
        
        //shipping line
        if ( ($shipping_price = $mp->shipping_price()) !== false ) {
          $total += $shipping_price;
        }

        //tax line
        if ( ! $mp->get_setting('tax->tax_inclusive') ) {
            $total += $mp->tax_price();
        }
        
        return $total;
    }
    
    /**
     * Set up stripe parameters for charge request
     * 
     * @param num $total Order total
     * @param string $order_id Order id
     * @return array
     */
    function stripe_parameters($total, $order_id){
        return array(
                    "amount" => round($total * 100), // amount in cents, again
                    "currency" => strtolower($this->currency),
                    "card" => $_SESSION['stripeconnectToken'],
                    "description" => sprintf(
                            __( '%s Store Purchase - Order ID: %s, Email: %s', 'mp'),
                            get_bloginfo('name'),
                            $order_id,
                            $_SESSION['mp_shipping_info']['email']
                            ),
                    "metadata" => array(
                        'order_id' => $order_id,
                        'blog_id'=> get_current_blog_id()
                    )
                        );
            // multisite needs, special attention for commissions
            
    }
    
    /**
     * Calculate the commission
     * 
     * @param int $total Order total
     * @return int
     */
    function calculate_commission($total){
        $percentage = $this->network_settings['percentage'];
        $fixed =  $this->network_settings['fixed'];
        $application_fee = (($total * $percentage) / 100)+ $fixed;
        return round($application_fee * 100);
    }
    
    /**
     * Charge a stripe account
     * 
     * @param array $params The parameters
     * @param int $total The order total
     */
    function stripe_charge($params, $total){
        global $mp;
       
        //include the Stripe API lib
        if (!class_exists('Stripe')) {
            require_once($mp->plugin_dir 
                    . "plugins-gateway/stripe-files/lib/Stripe.php");
        }
        
        if (is_multisite()) {
            
            // calculate the commission
            $commission = $this->calculate_commission($total);
            // only set application fee if commission is charged
            if(!empty($commission)){
                $params["application_fee"]= $commission;
            }
            // retrieve the access token for thsi merchant,
            // it will be used like the private key
            $api_key = $this->api_access['access_token'];

        } else {
            // single site, just set the private key
            $api_key = $this->api_access['secret_key'];
        }
        
        // create the charge on Stripe's servers - this will charge the user's card
        return $charge = Stripe_Charge::create($params, $api_key);
 
    }
    
    /**
     * Setup payment information to add to an order
     * 
     * @param object $charge Stripe charge object
     * @param int $total Order total
     * @param string $status_txt Payment status text
     * @return array
     */
    function stripe_payment_info($charge, $total, $status_txt){
        
        // current time
        $timestamp = time();
        
        return array(
            'gateway_public_name' => $this->public_name,
            'gateway_private_name' => $this->admin_name,
            'method' => sprintf(
                    __('%1$s Card ending in %2$s - Expires %3$s', 'mp'), 
                    $charge->card->type, 
                    $charge->card->last4, 
                    $charge->card->exp_month . '/' . $charge->card->exp_year
                    ),
            'transaction_id' => $charge->id,
            'status'=> array(
                $timestamp => $status_txt
                ),
            'total' => $total,
            'currency' => $this->currency,
            );

    }
    
    /**
     * Setup transients for later payment if payment fails
     * 
     * @global object $mp
     * @global object $current_user
     * @param string $order_id the order id
     * @param array $cart the cart
     * @param array $shipping_info the shipping information
     */
    function transients($order_id, $cart, $shipping_info){
        global $mp, $current_user;
        
        set_transient('mp_order_'. $order_id . '_cart', $cart, 60*60*12);
        set_transient('mp_order_'. $order_id . '_shipping', $shipping_info, 60*60*12);
        set_transient('mp_order_'. $order_id . '_shipping_total', $mp->shipping_price(), 60*60*12);
        set_transient('mp_order_'. $order_id . '_tax_total', $mp->tax_price(), 60*60*12);
        set_transient('mp_order_'. $order_id . '_userid', $current_user->ID, 60*60*12);
        set_transient('mp_order_'. $order_id . '_coupon', $mp->get_coupon_code(), 60*60*12);
            
    }
    /**
     * Delete a particular order related transients
     * Is static because webhooks will use this
     * 
     * @param string $order_id the order id
     */
    static function delete_transients($order_id){
        delete_transient('mp_order_'. $order_id . '_cart');
        delete_transient('mp_order_'. $order_id . '_shipping');
        delete_transient('mp_order_'. $order_id . '_shipping_total');
        delete_transient('mp_order_'. $order_id . '_tax_total');
        delete_transient('mp_order_'. $order_id . '_userid');
        delete_transient('mp_order_'. $order_id . '_coupon');
    }

    /**
     * INS and payment return
     */
    function process_ipn_return() {
        //Look Ma, no IPN!
    }
    
    /**
     * Prints out fields for setting up stripe api
     * Common for multisite and single site
     * 
     * @param array $stripe_settings The settings
     */
    static function api_ui( $stripe_settings ){
        ?>
        <tr>
            <th scope="row">
                <em>
                <?php _e('Step 1', 'mp'); ?>:
                </em>
                <?php _e('Register with Stripe API', 'mp'); ?>
            </th>
            <td>
                <span class="description">
                    <?php
                    _e('Register an app with Stripe API'
                    . ' to get credentials.'
                    . ' <a target="_blank" href="https://stripe.com/docs/connect/getting-started">'
                    . 'More Information &raquo;</a>',
                    'mp');
                    ?>
                </span>
                <br />
                <p>
                    <a class="button button-primary" target="_blank"
                       href="https://manage.stripe.com/account/applications/settings">
                    <?php _e('Click to visit Stripe and create your app',
                        'mp') ?>
                    </a>
                    <br />
                    <?php _e('Use the values below, for Redirect URL and Webhook URL fields',
                                'mp') ?>
                    <table style="width:100%;">
                        <tr>
                            <td>
                                <p>
                                    <label>
                                        <?php _e('Development Redirect URL', 'mp') ?>
                                        <br />
                                        <input type="text" class="widefat" disabled="disabled"
                                value="<?php echo admin_url("admin-ajax.php?action=wpmu_mp_stripe_connect",'http'); ?>" />
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        <?php _e('Development Webhook URL', 'mp') ?>
                                        <br />
                                        <input type="text" class="widefat" disabled="disabled"
                                value="<?php echo admin_url("admin-ajax.php?action=wpmu_mp_stripe_hook", 'http'); ?>" />
                                    </label>
                                </p>
                            </td>
                            <td>
                                <p>
                                    <label>
                                        <?php _e('Production Redirect URL', 'mp') ?>
                                        <br />
                                        <input type="text" class="widefat" disabled="disabled"
                                value="<?php echo admin_url("admin-ajax.php?action=wpmu_mp_stripe_connect",'https'); ?>" />
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        <?php _e('Production Webhook URL', 'mp') ?>
                                        <br />
                                        <input type="text" class="widefat" disabled="disabled"
                                value="<?php echo admin_url("admin-ajax.php?action=wpmu_mp_stripe_hook", 'https'); ?>" />
                                    </label>
                                </p>
                            </td>
                        </tr>
                    </table>
                </p>
            </td>
        </tr>

        <tr class="mp_stripe-connect mp_stripe-connect_1">
            <th scope="row">
                <em>
                <?php _e('Step 2', 'mp'); ?>:
                </em>
                <?php _e('Get Client IDs', 'mp'); ?>
            </th>
            <td>
                <span class="description">
                    <?php
                    _e('After the application is registered,'
                            . ' you will get client ids for it,'
                            . ' on the Apps tab, itself',
                            'mp') ?>
                </span>

                <table style="width:100%;">
                    <tr>
                        <td>
                            <p>
                                <label>
                                    <?php _e('Development Client ID', 'mp') ?>
                                    <br />
                                    <input class="widefat" type="text"
                                        value="<?php echo esc_attr($stripe_settings['test']['client_id']); ?>"
                                        name="mp[gateways][stripe-connect][test][client_id]" />
                                </label>
                            </p>
                        </td>
                        <td>
                            <p>
                                <label>
                                    <?php _e('Production Client ID', 'mp') ?>
                                    <br />
                                    <input class="widefat" type="text"
                                        value="<?php echo esc_attr($stripe_settings['live']['client_id']); ?>"
                                        name="mp[gateways][stripe-connect][live][client_id]" />
                                </label>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr class="mp_stripe-connect mp_stripe-connect_1">
            <th scope="row">
                <em>
                <?php _e('Step 3', 'mp'); ?>:
                </em>
                <?php _e('Get API Keys', 'mp'); ?>
            </th>
            <td>
                <span class="description">
                    <?php
                    _e('From the Stripe accounts settings screen,'
                            . ' copy the API Keys and paste them here',
                            'mp') ?>
                </span>
                <p>
                    <a class="button button-primary" target="_blank"
                        href="https://manage.stripe.com/account/apikeys">
                        <?php _e('Click to get API keys', 'mp') ?>
                    </a>
                </p>
                <table style="width:100%;">
                    <tr>
                        <td>
                            <p>
                                <label>
                                    <?php _e('Test Secret Key', 'mp') ?>
                                    <br />
                                    <input class="widefat" type="text"
                                        value="<?php echo esc_attr($stripe_settings['test']['secret_key']); ?>"
                                        name="mp[gateways][stripe-connect][test][secret_key]" />
                                </label>
                            </p>
                            <p>
                                <label>
                                    <?php _e('Test Publishable Key', 'mp') ?>
                                    <br />
                                    <input class="widefat" type="text"
                                        value="<?php echo esc_attr($stripe_settings['test']['publishable_key']); ?>"
                                        name="mp[gateways][stripe-connect][test][publishable_key]" />
                                </label>
                            </p>
                        </td>
                        <td>
                            <p>
                                <label>
                                    <?php _e('Live Secret Key', 'mp') ?>
                                    <br />
                                    <input class="widefat" type="text" 
                                        value="<?php echo esc_attr($stripe_settings['live']['secret_key']); ?>"
                                        name="mp[gateways][stripe-connect][live][secret_key]" />
                                </label>
                            </p>
                            <p>
                                <label>
                                    <?php _e('Live Publishable Key', 'mp') ?>
                                    <br />
                                    <input class="widefat" type="text"
                                        value="<?php echo esc_attr($stripe_settings['live']['publishable_key']); ?>"
                                        name="mp[gateways][stripe-connect][live][publishable_key]" />
                                </label>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php
        // api mode selector
        self::api_mode_ui($stripe_settings['mode'], true);
    }
    
    /**
     * Prints out mode selector
     * 
     * @param string $mode The current mode
     * @param boolean $stepped whether it is in a step-by-step wizard
     */
    static function api_mode_ui($mode, $stepped='false'){
        ?>
        <tr>
            <th scope="row">
                <?php if ($stepped){ ?>
                <em>
                    <?php _e('Step 4', 'mp'); ?>:
                </em>
                <?php } ?>
                <?php _e('Set working mode', 'mp'); ?>
            </th>
            <td>
                <span class="description">
                    <?php
                    _e('Use Test mode to test the setup. '
                            . 'Use Live for production use. '
                            . 'Please note that you would need ssl'
                            . ' for live production use.'
                            ,'mp');
                    $values = array(
                        'live' => 'Live',
                        'test' => 'Test'
                        );
                    ?>
                </span>
                <br />
                <select name="mp[gateways][stripe-connect][mode]">
                    <?php
                    foreach ($values as $key => $value) {
                    ?>
                        <option value="<?php echo $key ?>"
                            <?php selected($mode, $key) ?>
                                >
                            <?php echo $value ?>
                        </option>
                    <?php
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php        
    }
    
    /**
     * Print out status messages for stripe connect registration
     * @return type
     */
    function connect_error(){
        if(!isset($_REQUEST['e'])){
            return;
        }
        
        if(isset($_REQUEST['mp_stripe_marketplace_disconnect'])){
           return __("Disconnected from Stripe", "mp");
        }
        $e = intval($_REQUEST['e']);
        
        switch ($e) {
            case 0:
                $msg = __("Successfully connected to Stripe", "mp");
                break;
            case 1:
                $msg = __("No code returned from request. Please try again", "mp");
                break;
            case 2:
                $msg = __("Authorization code expired. Please try again", "mp");
                break;
            case 3:
                $msg = __("No response from the gateway. Please try again", "mp");
                break;
            default:
                $msg = __("An error occured. Please try again.", "mp");
                break;
        }
        
        return $msg;
    }
    
    /**
     * Checks if the Stripe connect registration needs to be disconnected
     * 
     * @param string $mode The mode test or live
     * @param array $stripe_settings The settings
     * @return type
     */
    function maybe_disconnect($mode, $stripe_settings){
        global $mp;
        // check if there was a disconnect request
        if(isset($_REQUEST['mp_stripe_marketplace_disconnect'])){
            $settings = get_option('mp_settings');
            
            // remove the access tokens from saved settings
            unset($settings['gateways']['stripe-connect'][$mode]['access_token']);
            
            // remove the access token from the current working instance
            // of the settings
            unset($stripe_settings[$mode]['access_token']);
            
            // update the settings
            update_option('mp_settings', $settings);
        }
        return $stripe_settings;
    }
    

}

//set names here to be able to translate
$admin_name = __('Stripe Connect', 'mp');

//register gateway plugin
mp_register_gateway_plugin('MP_Gateway_Stripe_Connect', 'stripe-connect', $admin_name);


if (is_multisite()) {
    require_once 'stripe-files/stripe-connect-ms.php';
}