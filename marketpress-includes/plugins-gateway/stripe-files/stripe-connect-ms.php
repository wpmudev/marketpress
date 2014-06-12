<?php
//tie into network settings form
add_action('mp_network_gateway_settings', 'stripe_mp_network_gateway_settings_box');


function stripe_mp_network_gateway_settings_box($settings) {
    global $mp;
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#gbl_gw_stripe-connect, #gw_full_stripe-connect, \n\
    #gw_supporter_stripe-connect, #gw_none_stripe-connect")
                    .change(function() {
                        $("#mp-main-form").submit();
                    });
        });
    </script>
    <?php
    // get the settings for this gateway
    $stripe_settings = $settings['gateways']['stripe-connect'];

    // get allowed status
    $allowed = $settings['allowed_gateways']['stripe-connect'];

    // get the global gateway
    $global_gateway = $settings['global_gateway'];

    // the global cart

    $global_cart = $settings['global_cart'];

    //skip if not enabled
    $hide = false;

    if (
            ($allowed != 'full' && $allowed != 'supporter' && $global_gateway != 'stripe-connect') || $global_cart
    ) {
        $hide = true;
    }

    if (!isset($stripe_settings['msg'])) {
        $stripe_settings['msg'] = __('Please be aware that we will deduct a ?% fee from'
                . ' the total of each transaction in addition to any'
                . ' fees Stripe may charge you. If for any reason'
                . ' you need to refund a customer for an order,'
                . ' please contact us with a screenshot of the '
                . 'refund receipt in your Stripe history as well '
                . 'as the Transaction ID of our fee deduction so '
                . 'we can issue you a refund. Thank you!', 'mp');
    }
    ?>
    <div id="mp_stripe-connect" class="postbox"
    <?php echo ($hide) ? ' style="display:none;"' : ''; ?>
         >
        <h3 class='hndle'>
            <span>
                <?php _e('Stripe Connect Settings', 'mp'); ?>
            </span>
        </h3>
        <div class="inside">
            <span class="description">
                <?php
                _e('Using Stripe Connect allows you as '
                        . 'the multisite network owner to collect'
                        . ' a predefined fee or percentage of all sales'
                        . ' on network MarketPress stores! '
                        . 'This is invisible to the customers '
                        . 'who purchase items in a store, and all '
                        . 'Stripe fees will be charged to the store owner.'
                        . ' To use this option you must create '
                        . 'API credentials, and you should make all '
                        . 'other gateways unavailable or limited above.', 'mp');
                ?>
            </span>
            <table class="form-table">
                <?php MP_Gateway_Stripe_Connect::api_ui($stripe_settings); ?>
                <tr>
                    <th scope="row">
                        <em>
                            <?php _e('Step 5', 'mp'); ?>:
                        </em>
                        <?php _e('Set your fees/commission', 'mp'); ?>
                    </th>
                    <td>
                        <span class="description">
                            <?php
                            
                            $percent = empty($stripe_settings['percentage'])?
                                    '0':$stripe_settings['percentage'];
                            $fixed = empty($stripe_settings['fixed'])?
                                    '0':$stripe_settings['fixed'];
                            _e('Enter a fee to be collected from each sale.'
                                    . ' It can be a percentage or fixed fee or both.'
                                    . ' Set to 0 or leave empty, if you don\'t'
                                    . ' want to charge any of these.'
                                    . ' Decimals allowed.', 'mp')
                            ?>
                        </span>
                        <br />
                        <?php
                        echo $mp->format_currency($mp->get_setting('currency'), false);
                        ?><input value="<?php echo esc_attr($fixed); ?>" 
                        size="4" name="mp[gateways][stripe-connect][fixed]" type="text" /> <?php
                        _e('fixed fee','mp');
                        ?> + <input value="<?php echo esc_attr($percent); ?>" 
                        size="4" name="mp[gateways][stripe-connect][percentage]" type="text" />% <?php
                        _e('of each sale','mp');
                        ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <em>
                            <?php _e('Step 6', 'mp'); ?>:
                        </em>
                            <?php _e('Gateway Settings Page Message', 'mp'); ?>
                    </th>
                    <td>
                        <span class="description">
                            <?php
                            _e('This message is displayed at the top'
                                    . ' of the gateway settings page to store admins.'
                                    . ' It\'s a good place to inform them of your fees'
                                    . ' or put any sales messages.'
                                    . ' Optional, HTML allowed.', 'mp')
                            ?>
                        </span>
                        <br />
                        <textarea class="mp_msgs_txt"
                                  name="mp[gateways][stripe-connect][msg]"><?php echo esc_html($stripe_settings['msg']); ?></textarea>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <?php
}                     