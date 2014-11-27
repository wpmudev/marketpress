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
			this.initCardValidation();
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
			
			/* Maybe hide previous button - we use this instead of the "prev" option
			because slides can dynamically be updated and the prev button would stop
			working */
			$( document ).on( 'cycle-initialized cycle-after', '#mp-checkout', function( e, opts ){
				var $btn = $checkout.find( '.mp-button-checkout-prev-step' );
				
				var slideNum = (opts.currSlide + 1)
				if ( opts.slideNum !== undefined ) {
					slideNum = opts.slideNum;
				}
				
				if ( slideNum == 1 ) {
					$btn.addClass( 'disabled' );
				} else {
					$btn.removeClass( 'disabled' );
				}
			} );
			
			// Init previous button events
			$checkout.on( 'click', '.mp-button-checkout-prev-step', function( e ){
				e.preventDefault();
				$checkout.cycle( 'prev' );
			} );
			
			// Initialize step transitions
			$checkout.cycle({
				allowWrap : false,
				autoHeight : "container",
				log : false,
				nowrap : true,
				slideActiveClass : "current",
				slides : "> .mp-checkout-section",
				sync : false,
				timeout : 0
			});
			
			// Hide field errors when going to previous step
			$checkout.on( 'cycle-prev', function( e, opts ){
				$( '.mp-tooltip' ).tooltip( 'close' );
			} );
			
			// Validate form
			$checkout.validate({
				ignore : function( index, element ){
					return ( ! $( element).closest( '.cycle-slide.current' ).length );
				},
				highlight : function( element, errorClass ){
					$( element ).addClass( 'mp-input-error' ).prev( 'label' ).addClass( 'mp-label-error' );
				},
				unhighlight : function( element, errorClass, validClass ){
					var $tip = $( element ).siblings( '.mp-tooltip' );
					if ( $tip.length > 0 ) {
						$tip.tooltip( 'close' );
					}
					
					$( element ).removeClass( 'mp-input-error' ).prev( 'label' ).removeClass( 'mp-label-error' );
					
					if ( $( '.mp-input-error' ).length == 0 ) {
						$( '.mp-checkout-section' ).filter( '.current' ).find( '.mp-checkout-section-errors' ).removeClass( 'show' );
					}
				},
				submitHandler : function( form ){
					var $form = $( form );
					
					if ( $form.valid() ) {
						if ( $checkout.hasClass( 'last-step' ) ) {
							var gateway = $( '[name="payment_method"]' ).filter( ':checked' ).val();
							
							/**
							 * Trigger checkout event
							 *
							 * For gateways to tie into and process checkout.
							 *
							 * @since 3.0
							 * @param jQuery $form The checkout form object.
							 */
							$( document ).trigger( 'mp_checkout_process_' + gateway, $form );
						} else {
							var url = mp_i18n.ajaxurl + '?action=mp_update_checkout_data';
							marketpress.loadingOverlay( 'show' );
							
							$.post( url, $form.serialize() ).done( function( resp ){
								$.each( resp.data, function( index, value ){
									$( '#' + index ).find( '.mp-checkout-section-content' ).html( value );
								} );
								
								mp_checkout.initCardValidation();
								marketpress.loadingOverlay( 'hide' );
								$checkout.cycle( 'next' );
							} );
						}
					}
				},
				showErrors : function( errorMap, errorList ){
					var errorVerb = ( $( errorMap ).length > 1 ) ? mp_checkout_i18n.error_plural : mp_checkout_i18n.error_singular;
					var errorString = mp_checkout_i18n.errors.replace( '%d', $( errorMap ).length ).replace( '%s', errorVerb );
					$( '.mp-checkout-section' ).filter( '.current' ).find( '.mp-checkout-section-errors' ).html( errorString ).addClass( 'show' );
					
					$checkout.animate({
						height : $checkout.find( '.cycle-slide.current' ).outerHeight()
					}, 300);
					
					$.each( errorMap, function( inputName, message ){
						var $input = $( '[name="' + inputName + '"]' );
						var $tip = $input.siblings( '.mp-tooltip' );
						
						if ( $tip.length == 0 ) {
							$input.after( '<div class="mp-tooltip" />');
							$tip = $input.siblings( '.mp-tooltip' );
							$tip.uniqueId().tooltip({
								content : "",
								items : "#" + $tip.attr( 'id' ),
								tooltipClass : "error",
								show : 300,
								hide : 300
							});
						}
						
						$tip.tooltip( 'option', 'content', message );
						$tip.tooltip( 'option', 'position', {
							of : $input,
							my : "center bottom-10",
							at : "center top"
						} );
						
						$input.on( 'focus', function() {
							$tip.tooltip( 'open' );
						} );
						
						$input.on( 'blur', function() {
							$tip.tooltip( 'close' );
						} );
					});
					
					this.defaultShowErrors();
				}
			});
			
			// Add/remove "last-step" class when on last step
			$checkout.on( 'cycle-after', function( evt, opts ){
				if ( opts.slideNum == opts.slideCount ) {
					$checkout.addClass( 'last-step' );
				} else {
					$checkout.removeClass( 'last-step' );
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
		 * Initialize credit card validation events/rules
		 *
		 * @since 3.0
		 */
		initCardValidation : function(){
			$( '.mp-input-cc-num' ).payment( 'formatCardNumber' );
			$( '.mp-input-cc-expiry' ).payment( 'formatCardExpiry' );
			$( '.mp-input-cc-cvc' ).payment( 'formatCardCVC' );
			
			// Validate card fullname
			$.validator.addMethod( 'cc-fullname', function( val, element){
				var pattern = /^([a-z]+)([ ]{1})([a-z]+)$/ig;
				return this.optional( element ) || pattern.test( val );
			}, mp_checkout_i18n.cc_fullname );
			
			// Validate card numbers
			$.validator.addMethod( 'cc-num', function( val, element){
				return this.optional( element ) || $.payment.validateCardNumber( val );
			}, mp_checkout_i18n.cc_num );
			
			// Validate card expiration
			$.validator.addMethod( 'cc-exp', function( val, element){
				var dateObj = $.payment.cardExpiryVal( val );
				return this.optional( element ) || $.payment.validateCardExpiry( dateObj.month, dateObj.year );
			}, mp_checkout_i18n.cc_exp );
			
			// Validate card cvc
			$.validator.addMethod( 'cc-cvc', function( val, element){
				return this.optional( element ) || $.payment.validateCardCVC( val );
			}, mp_checkout_i18n.cc_cvc );
		},
		
		/**
		 * Init events related to toggling payment options
		 *
		 * @since 3.0
		 * @access public
		 */
		initPaymentOptionListeners : function(){
			$( '.mp-checkout-section' ).on( 'change', 'input[name="payment_method"]', function(){
				var $this = $( this ),
						$target = $( '#mp-gateway-form-' + $this.val() ),
						$checkout = $( '#mp-checkout' );
				
				$target.show().siblings( '.mp_gateway_form' ).hide();
				$checkout.animate({
					height : $checkout.find( '.cycle-slide.current' ).outerHeight()
				}, 300);
			});
			
			// On load, open the payment form for the selected payment option
			var $input = $( 'input[name="payment_method"]' );
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