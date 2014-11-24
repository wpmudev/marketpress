var mp_checkout;

(function($){
	mp_checkout = {
		/**
		 * Initialize event listeners
		 *
		 * @since 3.0
		 */
		initListeners : function(){
			this.initShippingAddressListeners();
			this.initPaymentOptionListeners();
			this.initUpdateShippingMethodsListeners();
			this.initCardValidationListeners();
			this.initAjaxLogin();
			this.initCheckoutSteps();
		},
		
		/**
		 * Get a value from a hashed query string
		 *
		 * @since 3.0
		 * @param string what The name of the variable to retrieve.
		 * @param mixed defaultVal Optional, what to return if the variable doesn't exist. Defaults to false.
		 * @return mixed
		 */
		getHash : function( what, defaultVal ) {
			var hash = window.location.hash;
			
			if ( undefined === defaultVal ) {
				defaultVal = false;
			}
			
			if ( 0 > hash.indexOf( '#!' ) || undefined === defaultVal ) {
				return defaultVal;
			}
			
			var hashParts = hash.substr( 2 ).split( '&' ), hashPairs = {};
			
			$.each( hashParts, function( index, value ){
				var tmp = value.split( '=' );
				hashPairs[ tmp[0] ] = tmp[1];
			});
			
			if ( undefined === hashPairs[ what ] ) {
				return defaultVal;
			}
			
			return hashPairs[ what ];
		},
		
		/**
		 * Initialize checkout steps
		 *
		 * @since 3.0
		 */
		initCheckoutSteps : function(){
			var $checkout = $(' #mp-checkout' );
			
			// Initialize steps
			$checkout.cycle({
				allowWrap : false,
				autoHeight : "calc",
				fx : "scrollHorz",
				log : false,
				prev : "#mp-checkout .mp-button-checkout-prev-step",
				slideActiveClass : "current",
				slides : "> .mp-checkout-section",
				timeout : 0
			});
			
			// Validate form
			$checkout.validate({
				highlight : function(){
					// Intentionally left blank
				},
				unhighlight : function( element, errorClass, validClass ){
					var $tip = $( element ).prev( '.mp-tooltip' );
					if ( $tip.length > 0 ) {
						$tip.tooltip( 'close' );
					}
				},
				showErrors : function( errorMap, errorList ){
					$.each( errorMap, function( inputName, message ){
						var $input = $( '[name="' + inputName + '"]' );
						var $tip = $input.prev( '.mp-tooltip' );
						
						if ( $tip.length == 0 ) {
							$input.before( '<div class="mp-tooltip" />');
							$tip = $input.prev( '.mp-tooltip' );
							$tip.uniqueId().tooltip({
								content : "",
								items : "#" + $tip.attr( 'id' ),
								tooltipClass : "error",
								show : 300,
								hide : 300,
								position : {
									of : $input,
									my : "center bottom-10",
									at : "center top"
								}
							});
						}
						
						$tip.tooltip( 'option', 'content', message );
						$tip.tooltip( 'open' );
					});
					
					this.defaultShowErrors();
				}
			});
			
			// Handle next step click
			$checkout.find( '.mp-button-checkout-next-step' ).click( function( e ){
				e.preventDefault();
				if ( $checkout.valid() ) {
					$checkout.cycle( 'next' );
				}
			});
		},
		
		/**
		 * Initialize AJAX login
		 *
		 * @since 3.0
		 */
		initAjaxLogin : function(){
			$( '#mp-checkout' ).submit( function( e ){
				var $this = $( this ),
						$email = $this.find( '[name="mp_login_email"]' ),
						$pass = $this.find( '[name="mp_login_password"]' ),
						$btn = $( '#mp-button-checkout-login' );
				
				if ( $.trim( $email.val() ).length > 0 && $.trim( $pass.val() ).length > 0 ) {
					e.preventDefault();
					
					$btn.ajaxLoading();
					
					// Destroy any tooltips
					if ( $( '#mp-login-tooltip' ).length > 0 ) {
						$( '#mp-login-tooltip' ).remove();
					}
					
					var data = {
						action : "mp_ajax_login",
						email : $email.val(),
						pass : $pass.val(),
						mp_login_nonce : $this.find( '[name="mp_login_nonce"]' ).val()
					};
					
					$.post( mp_i18n.ajaxurl, data ).done( function( resp ){
						if ( resp.success ) {
							window.location.reload();
						} else {
							$btn.ajaxLoading( 'hide' );
							$email.before( '<a id="mp-login-tooltip"></a>' );
							$( '#mp-login-tooltip' ).tooltip({
								items : '#mp-login-tooltip',
								content : resp.data.message,
								tooltipClass : "error",
								open : function( event, ui ){
									setTimeout( function(){
										$( '#mp-login-tooltip' ).tooltip( 'destroy' );
									}, 4000);
								},
								position : {
									of : $email,
									my : "center bottom-10",
									at : "center top"
								},
								show : 300,
								hide : 300
							}).tooltip( 'open' );
						}
					});
				}
			});
		},
		
		initUpdateShippingMethodsListeners : function(){
		},
		
		/**
		 * Initialize events related to credit card validation
		 *
		 * @since 3.0
		 */
		initCardValidationListeners : function(){
			$( '.mp-input-cc-num' ).payment( 'formatCardNumber' );
			$( '.mp-input-cc-expiry' ).payment( 'formatCardExpiry' );
			$( '.mp-input-cc-cvc' ).payment( 'formatCardCVC' )
		},
		
		/**
		 * Init events related to toggling payment options
		 *
		 * @since 3.0
		 * @access public
		 */
		initPaymentOptionListeners : function(){
			var $input = $( 'input[name="payment_method"]' );
			
			$input.change( function(){
				var $this = $( this ),
						$target = $( '#mp-gateway-form-' + $this.val() );
				
				$target.show().siblings( '.mp_gateway_form' ).hide();
			});
			
			if ( $input.filter( ':checked' ).length ) {
				$input.filter( ':checked' ).trigger( 'click' );
			} else {
				$input.eq(0).trigger( 'click' );
			}
		},
		
		/**
		 * Enable/disable shipping address fields
		 *
		 * @since 3.0
		 */
		toggleShippingAddressFields : function(){
			var $cb = $( 'input[name="enable_shipping_address"]' ),
					$shippingFields = $( '[name^="shipping["]' );
			
			if ( $cb.prop( 'checked' ) ) {
				$shippingFields.each( function(){
					$( this ).prop( 'disabled', false );
				});
			} else {
				$shippingFields.each( function(){
					$( this ).prop( 'disabled', true );
				});
			}
		},
	
		/**
		 * Initialize events related to enabling/disable shipping address fields
		 *
		 * @since 3.0
		 */
		initShippingAddressListeners : function(){
			$( 'input[name="enable_shipping_address"]' ).change( mp_checkout.toggleShippingAddressFields );
		}
	};
}( jQuery ));

jQuery( document ).ready( function( $ ){
	mp_checkout.initListeners();
	mp_checkout.toggleShippingAddressFields();
});