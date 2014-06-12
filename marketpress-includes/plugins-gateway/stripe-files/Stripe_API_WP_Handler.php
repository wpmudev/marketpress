<?php
/**
 * Handles API Callbacks from Stripe to MarketPress
 *
 * @author Saurabh Shukla <contact.saurabhshukla@gmail.com>
 */
class Stripe_API_WP_Handler {

    public function __construct() {
        
    //Add action to handle call back requests
    add_action('wp_ajax_wpmu_mp_stripe_connect', array($this,'connect'));

    //Web hook action
    add_action('wp_ajax_wpmu_mp_stripe_hook', array($this,'webhook'));
    add_action('wp_ajax_nopriv_wpmu_mp_stripe_hook', array($this,'webhook'));
        
    }
    
    /**
     * Handles the connect registration callback
     */
    function connect(){
        
        // get the returned values
        $client_code = @$_REQUEST['code'];
        $state = @$_REQUEST['state'];
        
        // split state into useful variables
        list($blogid, $nonce, $mode) = explode('_', $state);
        
        // if the nonce is bad, get out early        
        if(!wp_verify_nonce($nonce, 'mp_stripe_marketplace_connect' )){
            // "they" get nothing
            
            die();
        }
        
        // nonce is fine! Was a code given in the response?
        if (!$client_code) {
            $error['code']=1;
        }else{
            // all fine, let's process
            $error = $this->process($mode, $client_code, $blogid);
        }
        
        // redirect to the gateway setup screen for further processing
        $admin_url = get_admin_url($blogid,
                        'edit.php?post_type=product&page=marketpress&tab=gateways'
                        . '&e='.$error['code'].'&mode='.$mode.'&msg='.$error['msg']
                        );
        wp_redirect( $admin_url);
        exit();
    }
    
    /**
     * Prcess the request and response
     * 
     * @param string $mode live/test mode
     * @param string $code client code from Stripe
     * @param int $blogid blog id of requesting blog
     * @return array
     */
    function process($mode,$code, $blogid){
        // get relevant stripe settings
        $network_settings = get_site_option('mp_network_settings');
        $settings = $network_settings['gateways']['stripe-connect'];

        // setup parameters
        $client_secret = $settings[$mode]['secret_key'];

        $args= array(
            'client_secret' => $client_secret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
            'scope'         => 'read_write'
        );

        // this is our URL to call
        $url = "https://connect.stripe.com/oauth/token";

        // make the API request
        $response = $this->access_token_request($url, $args);

        // send it for analysis, and get an report
        $error = $this->analyse_response($blogid,$mode,$response);
        return $error;
    }
    
    /**
     * Make the HTTP Post reqeust
     * 
     * @param string $url the url to request
     * @param string $args arguments to pass in querystring
     * @return object
     */
    function access_token_request($url='', $args=array()){
        // prepare the query
        $request = add_query_arg($args, $url);
        
        // post and return the response!
        $response = wp_remote_post($request);
        return $response;  
    }
    
    /**
     * Analyse the response and report success status
     * 
     * @param int $blogid blog id of requesting blog
     * @param string $mode live/test mode
     * @param array $response The response array
     * @return int
     */
    
    function analyse_response($blogid,$mode,$response){
        
        //return early, become fast
        
        // the request failed
        if(is_wp_error($response)){
            $error['code']= 503;
            return $error;
        }
        
        // the response was empty
        if (empty($response['body'])) {
            $error['code']= 3;
            return $error;
        }

        // we got a response! decode the json in response body
        $response = json_decode($response['body'], true);
        
        // have we been sent an error by Stripe?
        if ($response['error']) {
            // just pass what we get from Stripe
            $error['code'] = $response['error_code'];
            $error['msg'] = $response['error_description'];
            return $error;
        }
        
        // everything is fine, if we've reached here
        
        // take the response to update settings
        $error = $this->update_settings($blogid,$mode,$response);
        
        return $error;    
        
    }
    
    /**
     * Update settings and return success code
     * 
     * @param int $blogid blog id of requesting blog
     * @param string $mode live/test mode
     * @param array $response the response array
     * @return int
     */
    function update_settings($blogid,$mode,$response){
        // an array of parameter keys, makes the rest of the code easy
        $params=array(
            'access_token',
            'stripe_publishable_key',
            'stripe_user_id',
            'refresh_token',
            'livemode'
        );

        // get the default settings
        $settings = get_blog_option($blogid, "mp_settings");
        // assign each value from response to the settings array
        foreach($params as $param){
            $settings['gateways']['stripe-connect'][$mode][$param] = 
                    sanitize_text_field($response[$param]);
        }
        
        // update the settings with newly added values
        update_blog_option($blogid, "mp_settings", $settings);
        
        // we were successful!
        $error['code']=0;
        return $error;

    }
    
    /**
     * Handles deauthorisation callbacks from stripe
     */
    function webhook(){
        global $mp;
        
        // get the contents of the json file sent
        $body = @file_get_contents('php://input');
        
        // get the json into an array
        $event = json_decode($body,true);
        
        // if there's something
        if ($event) {
            
            // if it's a deauthorization call, handle it directly
            if($event['type']==='account.application.deauthorized'){
                $this->account_event($event);
            }else{
            
                // otherwise, this is mostly transactions
                // only keep some data, ignore the rest
                $event = $this->pre_process_event($event);
                
                // fetch the data again from stripe
                $fetched_event = $this->fetch_event($event);
                
                // process the hook, appropriately
                $this->hook_event($fetched_event, $event['mode']);
        
            }        
        }
    }
    
    /**
     * Collects some values from the sent json, for fetching it from tripe, again
     * 
     * @param array $event the original event notification
     * @return array
     */
    function pre_process_event($event){
        
            $pre_event = array(
                
                // we need id for fetching again
                'id' => $event['id'],
                
                // these are for ease in processing
                'mode' => ($event['livemode'])?'live':'test',
                'type' => $event['type'],
                'order_id'  => $event['data']['object']['metadata']['order_id'],
                'blog_id'   => $event['data']['object']['metadata']['blog_id']
            );
            
            return $pre_event;

    }
    
    /**
     * Fetch the event from stripe using event_id
     * 
     * @global type $mp
     * @param type $event
     */
    function fetch_event($event){
        
        global $mp;
        
        // get the appropriate api settings
        if(is_multisite()){
            $api_settings = get_site_option('mp_network_settings');
                
        }else{
            $api_settings = get_option('mp_settings');
        }
        
        // get the secret key
        $key = $api_settings['gateways']['stripe-connect'][$event['mode']]['secret_key'];
        
        // for charges, we need the merchant's access token, not the app's secret key
        if(is_multisite() && (strpos($event['type'],'application_fee')!==0)){
                    $api_settings =  get_blog_option($event['blog_id'], 'mp_settings');
                    $key = $api_settings['gateways']['stripe-connect'][$event['mode']]['access_token'];
                }
        
        //include the Stripe API lib
        if (!class_exists('Stripe')) {
            require_once($mp->plugin_dir 
                    . "plugins-gateway/stripe-files/lib/Stripe.php");
        }
        
        // fetch the event
        $fetched_event = Stripe_Event::retrieve($event['id'],$key);
        
        return $fetched_event;
        
    }
    
    /**
     * Given the event and mode hook the event processing methods
     * 
     * @param object $event The fetched event
     * @param string $mode live/test mode
     */
    function hook_event($event, $mode){
        
        // get event type
        $type = $event->type;
        
        // split type into useful variables
        $type_array = explode('.', $type);
        
        // create context
        $context = $type_array[0];
        
        // get the hook status
        $type_array_reverse = array_reverse($type_array);
        $status = $type_array_reverse[0];
        
        // process differently based on context
        
        if($context==='charge'){
            $this->charge_event($event, $status, $mode);
        }else{
            do_action('mp_stripe_webhook_event', $event);
        }        
    }
    
    /**
     * Process account deauthorization event
     * 
     * We don't fetch this, because after deauthorisation, we won't be able to
     * query using the deauthorized access_token and the app (super admin) 
     * doesn't have access anyway. Will need to check this for security
     * 
     * @param array $event The event
     */
    function account_event($event){
        
        // set the mode
        $mode = ($event['livemode'])?'live':'test';
        
        // get the stripe user_id
        $user_id = $event['user_id'];
        
        // get a list of all sites
        $sites = wp_get_sites();
        
        // loop through and look for this stripe user id
        foreach($sites as $site){
            
            $site_settings = get_blog_option( $site['blog_id'] , "mp_settings");
            
            // found it!
            if(($site_settings['gateways']['stripe-connect'][$mode]['stripe_user_id'] === $user_id)){
                
                // remove all api credentials for this deauthorised user
                unset($site_settings['gateways']['stripe-connect'][$mode]);
                update_blog_option ($site['blog_id'], "mp_settings", $site_settings);
            }
        }
            
    }
    
    /**
     * Handling charge webhooks
     * 
     * @global type $mp
     * @param object $event event object from stripe
     * @param string $status the event status
     * @param string $mode the api mode live/test
     */
    function charge_event($event, $status, $mode){
        global $mp;
        
        // thank god, we sent these with the charge call!
        $order_id = $event->data->object->metadata->order_id;
        $blog_id =  $event->data->object->metadata->blog_id;
        
        // handle different payment statuses
        switch ($status){
            case 'succeeded':
                $o_status = __('The payment was successful', 'mp');
                $create_order = true;
                $paid = true;
                break;
            case 'failed':
                $o_status = __('There was an error processing your card: ', 'mp');
                $create_order = false;
                $paid = false;
                break;
            case 'refunded':
                $o_status = __('The payment was refunded', 'mp');
                $create_order = false;
                $paid = false;
                break;
            default:
                break;
        }
        
        $og_blog_id = get_current_blog_id();
        
        // get to the right blog, $mp->get_order won't work, otherwise
        if(is_multisite()){
            switch_to_blog($blog_id);
        }
             
        // this order already exists
        // just update the paid status
        if($mp->get_order($order_id)){
             $mp->update_order_payment_status($order_id, $o_status, $paid);
             
        }else{
            // we need to create a new order
            if($create_order){
                $this->create_order($event, $order_id, $o_status, $paid);
            }            
        }
        
        // switch back to the old blog!
        if(is_multisite()){
            switch_to_blog($og_blog_id);
        }
    }
    
    /**
     * Creates a new order
     * 
     * @param object $event The charge event notification
     * @param string $order_id The order id
     * @param string $status Payment status message
     * @param boolean $paid The paid status
     */
    function create_order($event, $order_id, $status, $paid){
            
                // set up order details
                $timestamp = time();
                $payment_info['status'][$timestamp] = $status.$event->failure_message;
                $payment_info['total'] = $event->data->object->amount;
                $payment_info['currency'] = $event->data->object->currency;

                // fetch details from transients
                $cart = get_transient('mp_order_'. $order_id . '_cart');
                $shipping_info = get_transient('mp_order_'. $order_id . '_shipping');
                $shipping_total = get_transient('mp_order_'. $order_id . '_shipping_total');
                $tax_total = get_transient('mp_order_'. $order_id . '_tax_total');
                $user_id = get_transient('mp_order_'. $order_id . '_userid');
                $coupon = get_transient('mp_order_'. $order_id . '_coupon');
                
                // create a new order
                $order = $mp->create_order(
                        $order_id, 
                        $cart, 
                        $shipping_info, 
                        $payment_info,
                        $paid,
                        $user_id,
                        $shipping_total,
                        $tax_total,
                        $coupon
                        );
                
                // delete the transients, we don't need them, anymore
                MP_Gateway_Stripe_Connect::delete_transients($order_id);
            
    }
}