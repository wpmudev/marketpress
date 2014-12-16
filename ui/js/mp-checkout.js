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
			this.initUpdateStateFieldListeners();
			this.initCardValidation();
			this.initCheckoutSteps();
		},
		
		/**
		 * Update state list/zipcode field when country changes
		 *
		 * @since 3.0
		 */
		initUpdateStateFieldListeners : function() {
			$( '[name="billing[country]"], [name="shipping[country]"]' ).on( 'change', function() {
				var $this = $( this );
				var url = mp_i18n.ajaxurl + '?action=mp_update_states_dropdown';
				
				if ( $this.attr( 'name' ).indexOf( 'billing' ) == 0 ) {
					var $state = $( '[name="billing[state]"]' );
					var $zip = $( '[name="billing[zip]"]' );
					var type = 'billing';
					var $row = $state.closest( '.mp-checkout-form-row' );
				} else {
					var $state = $( '[name="shipping[state]"]' );
					var $zip = $( '[name="shipping[zip]"]' )
					var type = 'shipping';
					var $row = $state.closest( '.mp-checkout-form-row' );
				}
				
				var data = {
					country : $this.val(),
					type : type
				}
				
				$state.select2( 'destroy' );
				$row.ajaxLoading( 'show' );
						
				$.post( url, data ).done( function( resp ) {
					if ( resp.success ) {
						$row.ajaxLoading( 'false' );
						if ( resp.data.states ) {
							$state.html( resp.data.states );
							$state.closest( '.mp-checkout-column' ).show();
							marketpress.initSelect2();
						} else {
							$state.closest( '.mp-checkout-column' ).hide();
						}
						
						if ( resp.data.show_zipcode ) {
							$zip.closest( '.mp-checkout-column' ).show();
						} else {
							$zip.closest( '.mp-checkout-column' ).hide();
						}
					}
				} );
			} );
		},
		
		/**
		 * Show the checkout form
		 *
		 * @since 3.0
		 */
		showForm : function() {
			$( '#mp-checkout' ).show();
		},
		
		/**
		 * Adjust height of #mp-checkout to the height of the active step
		 *
		 * @since 3.0
		 */
		autoHeight : function() {
			var $checkout = $( '#mp-checkout' );
			
			$checkout.animate({
				height : $checkout.find( '.cycle-slide.current' ).outerHeight()
			}, 300);
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
		 * Show/hide checkout section error summary
		 *
		 * @since 3.0
		 * @param string action Either "show" or "hide".
		 * @param int count The number of errors.
		 */
		errorSummary : function( action, count ) {
			var $section = $( '.mp-checkout-section' ).filter( '.current' ).find( '.mp-checkout-section-errors' );
			var $checkout = $( '#mp-checkout' );
			
			if ( 'show' == action ) {
				var errorVerb = ( count > 1 ) ? mp_checkout_i18n.error_plural : mp_checkout_i18n.error_singular;
				var errorString = mp_checkout_i18n.errors.replace( '%d', count ).replace( '%s', errorVerb );
				$section.html( errorString ).addClass( 'show' );
			} else {
				$section.removeClass( 'show' );
			}
			
			mp_checkout.autoHeight();
		},
		
		/**
		 * Execute when on the last step of checkout
		 *
		 * @since 3.0
		 * @event cycle-initialized, cycle-after
		 */
		lastStep : function( evt, opts ) {
			var $checkout = $( '#mp-checkout' );
			if ( opts.slideNum == opts.slideCount || opts.currSlide == (opts.slideCount - 1) ) {
				$checkout.addClass( 'last-step' );
			} else {
				$checkout.removeClass( 'last-step' );
			}
		},
				
		/**
		 * Initialize checkout steps
		 *
		 * @since 3.0
		 */
		initCheckoutSteps : function(){
			var $checkout = $(' #mp-checkout' );
			
			// Setup cycle-initialized and cycle-after events
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
				
				if ( slideNum == opts.slideCount ) {
					$checkout.addClass( 'last-step' );
				} else {
					$checkout.removeClass( 'last-step' );
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
			
			// Trim values before validating
			$.each( $.validator.methods, function( key, val ) {
				$.validator.methods[ key ] = function() {
					if ( arguments.length > 0 ) {
						var $el = $( arguments[1] );
						var newVal = $.trim( $el.val() );
						
						$el.val( newVal );
						arguments[0] = newVal;
					}
					
					return val.apply( this, arguments );
				}
			} );

			// Validate form
			$checkout.validate({
				onkeyup : false,
				onclick : false,
				ignore : function( index, element ){
					return ( ! $( element ).closest( '.cycle-slide.current' ).length || $( element ).is( ':hidden' ) || $( element ).prop( 'disabled' ) );
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
					
					if ( this.numberOfInvalids() == 0 ) {
						mp_checkout.errorSummary( 'hide' );
					}
				},
				submitHandler : function( form ){
					var $form = $( form );
					var $email = $form.find( '[name="mp_login_email"]' );
					var $pass = $form.find( '[name="mp_login_password"]' );
					
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
						} else if ( $.trim( $email.val() ).length > 0 && $.trim( $pass.val() ).length > 0 ) {
							var $btn = $( '#mp-button-checkout-login' );
							$btn.ajaxLoading();
							
							// Destroy any tooltips
							if ( $( '#mp-login-tooltip' ).length > 0 ) {
								$( '#mp-login-tooltip' ).remove();
							}
							
							var data = {
								action : "mp_ajax_login",
								email : $email.val(),
								pass : $pass.val(),
								mp_login_nonce : $form.find( '[name="mp_login_nonce"]' ).val()
							};
							
							$.post( mp_i18n.ajaxurl, data ).done( function( resp ){
								if ( resp.success ) {
									window.location.href = window.location.href;
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
					if ( this.numberOfInvalids() > 0 ) {
						mp_checkout.errorSummary( 'show', this.numberOfInvalids() );
					}
					
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
							if ( $input.hasClass( 'mp-input-error' ) ) {
								$tip.tooltip( 'open' );
							}
						} );
						
						$input.on( 'blur', function() {
							$tip.tooltip( 'close' );
						} );
					});
					
					this.defaultShowErrors();
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
			$( '.mp-input-cc-exp' ).payment( 'formatCardExpiry' );
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
				
				if ( $target.find( '.mp-input-error' ).filter( ':visible' ).length > 0 ) {
					$checkout.valid();	
				} else {
					mp_checkout.errorSummary( 'hide' );
				}
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
	mp_checkout.showForm();
	mp_checkout.initListeners();
	mp_checkout.toggleShippingAddressFields();
});