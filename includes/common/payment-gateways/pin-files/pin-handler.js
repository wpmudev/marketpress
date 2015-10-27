( function( $ ) {
	var api = new Pin.Api( pin_vars.publishable_api_key, pin_vars.mode );

	$( document ).on( 'mp_checkout/step_changed', function( evt, $out, $in ) {
		if ( $in.next( '.mp-checkout-section' ).length == 0 ) {
			addInputNames();
		}
	} );	
	  
	$( document ).on( 'mp_checkout_process_pin', function( e, $form ) {
		marketpress.loadingOverlay( 'show' );
		removeInputNames();
		
    var expObj = $.payment.cardExpiryVal( $( '#mp-cc-exp' ).val() );
    var card = {
      number: $( '#mp-cc-num' ).val().replace( /[^0-9]/ig, '' ),
      name: $( '#mp-cc-name' ).val(),
      expiry_month: expObj.month,
      expiry_year: expObj.year,
      cvc: $( '#mp-cc-cvc' ).val(),
      address_line1: $( 'input[name="billing[address1]"]' ).val(),
      address_line2: $( 'input[name="billing[address2]"]' ).val(),
      address_city: $( 'input[name="billing[city]"]' ).val(),
      address_state: $( 'input[name="billing[state]"]' ).val(),
      address_postcode: $( 'input[name="billing[zip]"]' ).val(),
      address_country: $( 'input[name="billing[country]"]' ).val()
    };

    // Request a token for the card from Pin
    api.createCardToken( card ).then( handleSuccess, handleError ).done();
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
	 * Handle the success response from the createCardToken method
	 *
	 * @since 3.0
	 * @param object card
	 */
	function handleSuccess( card ) {
    var $div = $( '#mp-gateway-form-pin' );
    var $form = $( '#mp-checkout-form' );
    
    // Add the card token and ip address of the customer to the form
    // You will need to post these to Pin when creating the charge.
    $('<input>')
      .attr( { type: 'hidden', name: 'card_token' })
      .val( card.token )
      .appendTo( $div );
                
    $form.get( 0 ).submit();
  };

	/**
	 * Handle the error response from the createCardToken method
	 *
	 * @since 3.0
	 * @param object response
	 */
	function handleError( response ) {
		marketpress.loadingOverlay( 'hide' );
		
		var $errorList = $( '<ul>' );
		
    if ( response.messages ) {
      $.each( response.messages, function( index, errorMessage ) {
        $( '<li>' )
          .html( errorMessage.message )
          .appendTo( $errorList );
      });
    }

    errorMessage( 'show', '<p>' + response.error_description + '</p>' + $errorList.wrap('<div>').parent().html(), false );		
  };
  
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
}( jQuery ) );