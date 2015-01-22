var PAYMILL_PUBLIC_KEY = paymill_token.public_key;

( function( $ ) {

    $( document ).on( 'mp_checkout_process_paymill', processCheckout );
    $( document ).ready( init );

    $( document ).on( 'mp_checkout/step_changed', function( evt, $out, $in ) {
        if ( $in.next( '.mp-checkout-section' ).length == 0 ) {
            addInputNames();
        }
    } );

    /**
     * Add input names for jQuery validation plugin
     *
     * Not ideal, but the jQuery validate plugin requires fields to have names
     * so this function will add them at the last possible minute
     *
     * @since 3.0
     */
    function addInputNames() {
        $( '#mp-paymill-name' ).attr( 'name', 'mp_paymill_name' );
        $( '#mp-paymill-cc-num' ).attr( 'name', 'mp_paymill_cc_num' );
        $( '#mp-paymill-cc-exp' ).attr( 'name', 'mp_paymill_cc_exp' );
        $( '#mp-paymill-cc-cvc' ).attr( 'name', 'mp_paymill_cc_cvc' );
    }

    /**
     * Remove CC input names before submitting form for PCI compliance
     *
     * @since 3.0
     */
    function removeInputNames() {
        $( '#mp-paymill-name, #mp-paymill-cc-num, #mp-paymill-cc-exp, #mp-paymill-cc-cvc' ).removeAttr( 'name' );
    }

    /**
     * Initialization function
     *
     * @since 3.0
     */
    function init() {

    }

    /**
     * Process checkout
     *
     * @since 3.0
     * @event mp_checkout_process_stripe
     */
    function processCheckout( $form ) {
        marketpress.loadingOverlay( 'show' );
        removeInputNames();

        var $name = $( '#mp-paymill-name' );
        var $number = $( '#mp-paymill-cc-num' );
        var $exp = $( '#mp-paymill-cc-exp' );
        var $cvc = $( '#mp-paymill-cc-cvc' );
        var $currency = $( '#mp-paymill-currency' );
        var $amount = $( '#mp-paymill-amount' );
        var expObj = $.payment.cardExpiryVal( $exp.val() );

        paymill.createToken( {
            number: $number.val().replace( /[^0-9]/ig, '' ),
            exp_month: expObj.month,
            exp_year: expObj.year,
            cvc: $cvc.val(),
            cardholdername: $name.val(),
            amount: $amount.val(),
            currency: $currency.val()
        }, responseHandler );
    }

    /**
     * Show/hide the payment error message
     *
     * @since 3.0
     * @param string action Either "show" or "hide".
     * @param string message The message to show. Required if action is "show".
     */
    function errorMessage( action, message ) {
        var $errors = $( '#mp-checkout-payment-form-errors' );

        if ( 'show' == action ) {
            $errors.html( '<p>' + message + '</p>' ).addClass( 'show' );
        } else {
            $errors.removeClass( 'show' );
        }
    }

    /**
     * Callback function for Stripe.createToken
     *
     * @since 3.0
     */
    function responseHandler( error, response ) {
        if ( response.error ) {
            var error_message = '';
            if ( error.apierror == 'field_invalid_card_cvc' ) {
                error_message = paymill_token.invalid_cvc;
            } else if ( error.apierror == 'field_invalid_card_exp' ) {
                error_message = paymill_token.expired_card;
            } else if ( error.apierror == 'field_invalid_card_holder' ) {
                error_message = paymill_token.invalid_cardholder;
            } else {
                error_message = error.apierror;
            }
            marketpress.loadingOverlay( 'hide' );
            errorMessage( 'show', error_message );
        } else {
            var token = result.token;
            errorMessage( 'hide' );

            // Submit order for processing
            var data = $( '#mp-checkout' ).serialize() + '&paymill_token=' + token;
            var url = mp_i18n.ajaxurl + '?action=mp_process_checkout';

            $.post( url, data ).done( function( resp ) {
                if ( resp.success ) {
                    window.location.href = resp.data.redirect_url;
                } else {
                    marketpress.loadingOverlay( 'hide' );

                    var message = '';
                    $.each( resp.data.errors, function( index, value ) {
                        message += value + '<br />';
                    } );
                    errorMessage( 'show', message );
                }
            } );
        }
    }
}( jQuery ) );