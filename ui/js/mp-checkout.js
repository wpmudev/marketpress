var mp_checkout = {};
(function($){
	mp_checkout.initListeners = function(){
		this.initShippingAddressListeners();
	};
	
	mp_checkout.toggleShippingAddressFields = function(){
		var $cb = $('input[name="enable_shipping_address"]'),
				$shippingFields = $('[name^="shipping["]');
		
		if ( $cb.prop('checked') ) {
			$shippingFields.each(function(){
				$(this).prop('disabled', false);
			});
		} else {
			$shippingFields.each(function(){
				$(this).prop('disabled', true);
			});
		}
	};
	
	mp_checkout.initShippingAddressListeners = function(){
		$('input[name="enable_shipping_address"]').change(mp_checkout.toggleShippingAddressFields);
	};
}(jQuery));

jQuery(document).ready(function($){
	mp_checkout.initListeners();
	mp_checkout.toggleShippingAddressFields();
});