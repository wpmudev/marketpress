(function($){
	/**
	 * Apply a coupon code to the cart
	 *
	 * @since 3.0
	 * @param string couponCode The coupon code to apply.
	 * @param object $scope Optional, the scope of the triggered events. Defaults to document.
	 */
	mp_cart.applyCoupon = function( couponCode, $scope ){
		if ( couponCode === undefined ) {
			return false;
		}
		
		marketpress.loadingOverlay('show');
		
		var url = mp_cart_i18n.ajaxurl + '?action=mp_coupons_apply',
				data = { "coupon_code" : couponCode };
		
		$.post(url, data).done(function(resp){
			marketpress.loadingOverlay('hide');
			marketpress.ajaxEvent('mp_cart/apply_coupon', resp, $scope);
		});
	};	
}(jQuery));

var mp_coupons = {};
(function($){
	mp_coupons = {
		/**
		 * Remove a coupon
		 *
		 * @since 3.0
		 */
		remove : function( id ){
			var url = mp_coupons_i18n.ajaxurl + '?action=mp_coupons_remove';
			var data = {
				"coupon_id" : id	
			};
			
			marketpress.loadingOverlay('show');
			
			$.post(url, data).done(function(resp){
				marketpress.loadingOverlay('hide');
				marketpress.ajaxEvent('mp_coupons/remove', resp);
				
				if ( resp.success ) {
					$.each(resp.data.products, function(key, val){
						$('#mp-cart-item-' + key).replaceWith(val);
					});
					$('#mp-cart-meta').replaceWith(resp.data.cartmeta);
					marketpress.initSelect2();
				} else {
					$( '#mp-coupon-tooltip' )
						.tooltip( 'option', 'content', resp.data.message )
						.tooltip( 'option', 'tooltipClass', 'error' )
						.tooltip( 'open' );
				}
			});
		},
		
		/**
		 * Init coupon form listeners
		 *
		 * @since 3.0
		 */
		initCouponFormListeners : function(){
			var $form = $('#mp-cart-form'),
					$couponCode = $form.find('[name="mp_cart_coupon"]');
			
			// Create tooltip for future use
			$couponCode.before( '<div id="mp-coupon-tooltip" />' );
			$( '#mp-coupon-tooltip' ).tooltip({
				items : "#mp-coupon-tooltip",
				tooltipClass : "error",
				content : "",
				position : {
					of : $couponCode,
					my : "center bottom-10",
					at : "center top"
				},
				show : 400,
				hide : 400
			});
			var $tooltip = $( '#mp-coupon-tooltip' );
			
			$form.submit(function(e){
				e.preventDefault();
				var couponCode = $couponCode.val().toUpperCase().replace(/[^A-Z0-9]/g, '');
				
				$tooltip.tooltip( 'close' );
				
				if ( couponCode.length > 0 ) {
					mp_cart.applyCoupon( couponCode, $form );
				} else {
					$tooltip
						.tooltip( 'option', 'content', mp_coupons_i18n.messages.required )
						.tooltip( 'option', 'tooltipClass', 'error' )
						.tooltip( 'open' );
				}
			})
			.on('mp_cart/apply_coupon/error', function( e, message ){
				$tooltip
					.tooltip( 'option', 'content', message )
					.tooltip( 'option', 'tooltipClass', 'error' )
					.tooltip( 'open' );
			})
			.on('mp_cart/apply_coupon/success', function( e, data ){
				$couponCode.val( '' );
								
				$.each( data.products, function( index, value ){
					$( '#mp-cart-item-' + index ).replaceWith( value );
				});
				$( '#mp-cart-meta').replaceWith( data.cart_meta );
				marketpress.initSelect2();
				$tooltip
					.on( 'tooltipopen.mp_coupons', function( e, ui ) {
						setTimeout( function(){
							$tooltip.tooltip( 'close' );
							$tooltip.off( 'tooltipopen.mp_coupons' );
						}, 4000);
					} )
					.tooltip( 'option', 'content', mp_coupons_i18n.messages.added )
					.tooltip( 'option', 'tooltipClass', 'success' )
					.tooltip( 'open' );
			});		
		}
	};
}(jQuery));

jQuery(document).ready(function($){
	mp_coupons.initCouponFormListeners();
});