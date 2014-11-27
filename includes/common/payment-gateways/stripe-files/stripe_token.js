( function( $ ) {
	$( document ).on( 'mp_checkout_process_stripe', processCheckout );
	$( document ).ready( init );
	
	var errorCount = 0;
	
	function init() {
		Stripe.setPublishableKey( stripe.publisher_key );
	}
	
	function validateCardName( $input ) {
		var val = $.trim( $input.val() ); // remove leading/trailing whitespace
		var pattern = /^([a-z]+)([ ]{1})([a-z]+)$/ig;
		if ( val.length == 0 || ! pattern.test( val ) ) {
			showError( $input, mp_checkout_i18n.cc_fullname );
			$input.on( 'keyup.stripe', validateCardName );
		} else {
			hideError( $input );
			$input.off( 'keyup.stripe' );
		}
	}
	
	function validateCardNumber( $input ) {
		var val = $.trim( $input.val() ); // remove leading/trailing whitespace
		if ( val.length == 0 || ! $.payment.validateCardNumber( val ) ) {
			showError( $input, mp_checkout_i18n.cc_num );
			$input.on( 'keyup.stripe', function(){
				validateCardNumber( $input );
			} );
		} else {
			hideError( $input );
			$input.off( 'keyup.stripe' );
		}
	}
	
	function validateCardExpiration( $input ) {
		if ( $input === undefined ) {
			var $input = $( this );
		}
		
		var val = $.trim( $input.val() ); // remove leading/trailing whitespace
		var dateObj = $.payment.cardExpiryVal( val );
		if ( val.length == 0 || ! $.payment.validateCardExpiry( dateObj.month, dateObj.year ) ) {
			showError( $input, mp_checkout_i18n.cc_exp );
			$input.on( 'keyup.stripe', function() {
				validateCardExpiration( $input );
			} );
		} else {
			hideError( $input );
			$input.off( 'keyup.stripe' );
		}
	}
	
	function validateCardCVC( $input ) {
		if ( $input === undefined ) {
			var $input = $( this );
		}
		
		var val = $.trim( $input.val() ); // remove leading/trailing whitespace
		if ( val.length == 0 || ! $.payment.validateCardCVC( val ) ) {
			showError( $input, mp_checkout_i18n.cc_cvc );
			$input.on( 'keyup.stripe', function() {
				validateCardCVC( $input );
			} );
		} else {
			hideError( $input );
			$input.off( 'keyup.stripe' );
		}		
	}
		
	function processCheckout( $form ) {
		var $name = $( '#mp-stripe-name' );
		var $number = $( '#mp-stripe-cc-num' );
		var $exp = $( '#mp-stripe-cc-exp' );
		var $cvc = $( '#mp-stripe-cc-cvc' );
		
		validateCardName( $name );
		validateCardNumber( $number );
		validateCardExpiration( $exp );
		validateCardCVC( $cvc );
		
		if ( 0 == errorCount ) {
			var expObj = $.payment.cardExpiryVal( $exp.val() );
			
			Stripe.createToken( {
				name : $name.val(),
				number : $number.val(),
				exp_month : expObj.month,
				exp_year : expObj.year,
				cvc : $cvc.val()
			}, responseHandler );
		}
	}
	
	function hideError( $input ) {
		var $tip = $input.next( '.mp-tooltip' );
		
		if ( $tip.length == 0 ) {
			return;
		}
		
		$tip.tooltip( 'close' );
		errorCount -= 1;
	}
	
	function showError( $input, text ) {
		var $tip = $input.next( '.mp-tooltip' );
		
		if ( $tip.length == 0 ) {
			$input.after( '<div class="mp-tooltip" />');
			$tip = $input.next( '.mp-tooltip' );
			$tip.uniqueId().tooltip({
				content : "",
				items : "#" + $tip.attr( 'id' ),
				tooltipClass : "error",
				show : 300,
				hide : 300
			});
		}
		
		$tip.tooltip( 'option', 'content', text );
		$tip.tooltip( 'option', 'position', {
			of : $input,
			my : "center left-10",
			at : "center right"
		} );
		$tip.tooltip( 'open' );
		errorCount += 1;
	}
	
	function responseHandler(status, response) {
		/*if (response.error) {
			// re-enable the submit button
			jQuery('#mp_payment_confirm').removeAttr("disabled").show();
			jQuery('#stripe_processing').hide();
			// show the errors on the form
			jQuery("#stripe_checkout_errors").append('<div class="mp_checkout_error">' + response.error.message + '</div>');
		} else {
			// token contains id, last4, and card type
			var token = response['id'];
			// insert the token into the form so it gets submitted to the server
			jQuery("#mp_payment_form").append("<input type='hidden' name='stripeToken' value='" + token + "' />");
			// and submit
			jQuery("#mp_payment_form").get(0).submit();
		}*/
	}

			
	/*jQuery(document).ready(function($) {
		$("#mp_payment_form").submit(function(event) {
			
			// FPM: Seems this JS is trapping on all types of payment. So we need to only process if the payment 
			// type is stripe or stipe is the only option
	
			// If we have the radio buttons allowing the user to select the payment method? ...
			// IF the length is zero then stripe or some other payment gateway is the only one defined. 
			if ( $('input.mp_choose_gateway').length ) {
				
				// If the payment option selected is not stripe then return and bypass input validations
				if ( $('input.mp_choose_gateway:checked').val() != "stripe" ) {
					return true;
				}
			}
			
			//clear errors
			$("#stripe_checkout_errors").empty();
			var is_error = false;
			
			//check form fields
			if ( $('#cc_name').val().length < 4 ) {
				$("#stripe_checkout_errors").append('<div class="mp_checkout_error">' + stripe.name + '</div>');
				is_error = true;
			}
			if ( !Stripe.validateCardNumber( $('#cc_number').val() )) {
				$("#stripe_checkout_errors").append('<div class="mp_checkout_error">' + stripe.number + '</div>');
				is_error = true;
			}
			if ( !Stripe.validateExpiry( $('#cc_month').val(), $('#cc_year').val() ) ) {
				$("#stripe_checkout_errors").append('<div class="mp_checkout_error">' + stripe.expiration + '</div>');
				is_error = true;
			}
			if ( !Stripe.validateCVC($('#cc_cvv2').val())) {
				$("#stripe_checkout_errors").append('<div class="mp_checkout_error">' + stripe.cvv2 + '</div>');
				is_error = true;
			}
			if (is_error) return false;
			
			// disable the submit button to prevent repeated clicks
			$('#mp_payment_confirm').attr("disabled", "disabled").hide();
			$('#stripe_processing').show();
		
			// createToken returns immediately - the supplied callback submits the form if there are no errors
			Stripe.createToken({
				name: $('#cc_name').val(),
				number: $('#cc_number').val(),
				cvc: $('#cc_cvv2').val(),
				exp_month: $('#cc_month').val(),
				exp_year: $('#cc_year').val()
				}, stripeResponseHandler);
				return false; // submit from callback
		});
	}); */
}( jQuery ) );