( function( $ ) {
	$( document ).on( 'mp_checkout_process_simplify', generateToken );

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
	 * Generate card token
	 *
	 * @since 3.0
	 */		
	function generateToken() {
		marketpress.loadingOverlay( 'show' );
		removeInputNames();
		
		var expObj = $.payment.cardExpiryVal( $( '#mp-cc-exp' ).val() );
		SimplifyCommerce.generateToken( {
			key : simplify.publicKey,
			card : {
				number : $( "#mp-cc-num" ).val().replace( /[^0-9]/ig, '' ),
				cvc : $( "#mp-cc-cvc" ).val(),
				expMonth : expObj.month,
				expYear : expObj.year.toString().substr( 2, 2 ),
				name : $( '#mp-cc-name' ).val().substr( 0, 50 ),
				addressLine1 : $( '[name="billing[address1]"]' ).val().substr( 0, 255 ),
				addressLine2 : ( $( '[name="billing[address2]"]' ).length > 0 ) ? $( '[name="billing[address2]"]' ).val().substr( 0, 255 ) : '',
				addressCity : $( '[name="billing[city]"]' ).val().substr( 0, 50 ),
				addressState : $( '[name="billing[state]"]' ).val().substr( 0, 2 ),
				addressZip : $( '[name="billing[zip]"]' ).val().substr( 0, 9 ),
				addressCountry : $( '[name="billing[country]"]' ).val().substr( 0, 2 )
			}
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
			$errors.html( message ).addClass( 'show' );
		} else {
			$errors.removeClass( 'show' );
		}
	}

	function responseHandler( data, status ) {
		if ( status != 200 ) {
			marketpress.loadingOverlay( 'hide' );
			errorMessage( 'show', data.error.message );
		} else {
			var $form = $( '#mp-checkout-form' );
			var $paymentForm = $( '#mp-gateway-form-simplify' );
			
			$( '<input/>' )
				.attr( { type : "hidden", name : "simplify_token" } )
				.val( data.id )
				.appendTo( $paymentForm );
			
			$form.get( 0 ).submit();
		}
	}
	
}(jQuery));