( function( $ ) {

    $( document ).on( 'mp_checkout_process_stripe', processCheckout );

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
        $( '#mp-stripe-name' ).attr( 'name', 'mp_stripe_name' );
        $( '#mp-stripe-cc-num' ).attr( 'name', 'mp_stripe_cc_num' );
        $( '#mp-stripe-cc-exp' ).attr( 'name', 'mp_stripe_cc_exp' );
        $( '#mp-stripe-cc-cvc' ).attr( 'name', 'mp_stripe_cc_cvc' );
    }

    /**
     * Remove CC input names before submitting form for PCI compliance
     *
     * @since 3.0
     */
    function removeInputNames() {
        $( '#mp-stripe-name, #mp-stripe-cc-num, #mp-stripe-cc-exp, #mp-stripe-cc-cvc' ).removeAttr( 'name' );
    }

    /**
     * Initialization function
     *
     * @since 3.0
     */
    function init() {
        Stripe.setPublishableKey( stripe.publisher_key );
    }

    /**
     * Process checkout
     *
     * @since 3.0
     * @event mp_checkout_process_stripe
     */
    function processCheckout( e, $form ) {
        marketpress.loadingOverlay( 'show' );
        removeInputNames();

        var $name = $( '#mp-stripe-name' );
        var $number = $( '#mp-stripe-cc-num' );
        var $exp = $( '#mp-stripe-cc-exp' );
        var $cvc = $( '#mp-stripe-cc-cvc' );
        var expObj = $.payment.cardExpiryVal( $exp.val() );

        Stripe.card.createToken( {
            name: $name.val(),
            number: $number.val().replace( /[^0-9]/ig, '' ),
            exp_month: expObj.month,
            exp_year: expObj.year,
            cvc: $cvc.val()
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
    function responseHandler( status, response ) {
        if ( response.error ) {
            marketpress.loadingOverlay( 'hide' );
            errorMessage( 'show', response.error.message );
        } else {
            errorMessage( 'hide' );

            // Submit order for processing
            var data = $( '#mp-checkout-form' ).serialize() + '&stripe_token=' + response.id;
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