( function( $ ) {
    if ( wepay_script.mode == "staging" ) {
        WePay.set_endpoint( "stage" );
    } else {
        WePay.set_endpoint( "production" );
    }

    $( document ).on( 'mp_checkout_process_wepay', generateToken );

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
        $( '#mp-cc-num' ).attr( 'name', 'mp_cc_num' );
        $( '#mp-cc-exp' ).attr( 'name', 'mp_cc_exp' );
        $( '#mp-cc-cvc' ).attr( 'name', 'mp_cc_cvc' );
    }

    /**
     * Remove CC input names before submitting form for PCI compliance
     *
     * @since 3.0
     */
    function removeInputNames() {
        $( '#mp-cc-num, #mp-cc-exp, #mp-cc-cvc' ).removeAttr( 'name' );
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
            $errors.html( message ).addClass( 'show' );
        } else {
            $errors.removeClass( 'show' );
        }
    }

    /**
     * Generate token from cc info
     *
     * @since 3.0
     * @access public
     */
    function generateToken( $form ) {
        marketpress.loadingOverlay( 'show' );
        removeInputNames();

        var expObj = $.payment.cardExpiryVal( $( '#mp-cc-exp' ).val() );
        var response = WePay.credit_card.create( {
            client_id: wepay_script.client_id,
            user_name: $( '#mp-cc-name' ).val(),
            email: $( '[name="billing[email]"]' ).val(),
            cc_number: $( '#mp-cc-num' ).val(),
            cvv: $( '#mp-cc-cvc' ).val(),
            expiration_month: expObj.month,
            expiration_year: expObj.year,
            address: {
				address1: $( '[name="billing[address1]"]' ).val().substr( 0, 60 ),
				address2: ( $( '[name="billing[address2]"]' ).length ) ? $( '[name="billing[address1]"]' ).val().substr( 0, 60 ) : '',
				city: $( '[name="billing[city]"]' ).val().substr( 0, 30 ),
				region: $( 'billing[state]' ).val().substr( 0, 2 ),
				postal_code: $( '[name="billing[zip]"]' ).val().substr( 0, 10 ),
				country: $( '[name="billing[country]"]' ).val().substr( 0, 2 )
            }
        }, function( data ) {
            if ( data.error ) {
                marketpress.loadingOverlay( 'hide' );
                errorMessage( 'show', data.error_description );
            } else {
                var $paymentForm = $( '#mp-gateway-form-wepay' );
                var $form = $( '#mp-checkout-form' );

                $( '<input />' )
                    .attr( { type: "hidden", name: "wepay_token" } )
                    .val( data.credit_card_id )
                    .appendTo( $paymentForm );

                $form.get( 0 ).submit();
            }
        } );
    }
}( jQuery ) );