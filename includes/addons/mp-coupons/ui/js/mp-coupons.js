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
	/**
	 * Remove a coupon
	 *
	 * @since 3.0
	 */
	mp_coupons.remove = function( id ){
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
				alert(resp.data.message);
			}
		});
	};
	
	/**
	 * Init coupon form listeners
	 *
	 * @since 3.0
	 */
	mp_coupons.initCouponFormListeners = function(){
		var $form = $('#mp-cart-form'),
				$couponCode = $form.find('[name="mp_cart_coupon"]');
		
		$couponCode.on('keyup', function(){
			$couponCode.mpCartError({
				"action" : "remove"
			});
		});
	
		$form.submit(function(e){
			var couponCode = $couponCode.val().toUpperCase().replace(/[^A-Z0-9]/g, '');
			
			if ( couponCode.length > 0 ) {
				e.preventDefault();
				mp_cart.applyCoupon(couponCode, $form);
			}
		})
		.on('mp_cart/apply_coupon/error', function(e, message){
			$couponCode.mpCartError({
				"action" : "add",
				"message" : message
			});
		})
		.on('mp_cart/apply_coupon/success', function(e, data){
			$.each(data.products, function(index, value){
				$('#mp-cart-item-' + index).replaceWith(value);
			});
			$('#mp-cart-meta').replaceWith(data.cart_meta);
		});		
	};
}(jQuery));

jQuery(document).ready(function($){
	mp_coupons.initCouponFormListeners();
});