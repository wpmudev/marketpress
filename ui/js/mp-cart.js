
var mp_cart = {};

(function($){
	mp_cart.init = function(){
		this.initCartAnimation();
		this.initCartListeners();
	}
	
	mp_cart.initCartListeners = function(){
		$('#mp_product_list').on('submit', '.mp_buy_form', function(e){
			e.preventDefault();
			
			var $this = $(this);
			mp_cart.addItem($this, $this.find('[name="product_id"]').val());
		});
		
		$('#mp_product_list').on('click', '.mp_variations_flyout a', function(e){
			e.preventDefault();
			
			var $this = $(this), $form = $this.closest('.mp_buy_form');
			mp_cart.addItem($form, $this.attr('data-product-id'));
		});
	};
	
	mp_cart.addItem = function( $form, item, qty ){
		if ( item === undefined || typeof($form) !== 'object' ) {
			return false;
		}
		
		if ( qty === undefined ) {
			qty = 1;
		}
		
		$form.addClass('invisible');
		$('body').children('.mp-ajax-loader').clone().insertAfter($form).show();
		
		// we use the AjaxQ plugin here because we need to queue multiple add-to-cart requests http://wp.mu/96f
		$.ajaxq('addtocart', {
			"data" : {
				"product_id" : item,
				"qty" : qty,
				"cart_action" : "add_item"
			},
			"type" : "POST",
			"url" : $form.attr('data-ajax-url'),
		})
		.success(function(resp){
			$form.removeClass('invisible').next('.mp-ajax-loader').remove();
			
			if ( resp.success ) {
				mp_cart.update(resp.data);
				
				setTimeout(function(){
					$('#mp-floating-cart').trigger('click');
					setTimeout(function(){
						$('#mp-floating-cart').removeClass('visible in-transition');
					}, 3000);
				}, 100);
			}
		});
	};
	
	mp_cart.update = function( html ){
		$('#mp-floating-cart').replaceWith(html);
		this.initCartAnimation();
	}
	
	mp_cart.initCartAnimation = function(){
		var $cart = $('#mp-floating-cart');
		
		$cart.hover(function(){
			$cart.addClass('in-transition');
			setTimeout(function(){
				$cart.addClass('visible');
			}, 300);
		},function(){
			$cart.removeClass('visible in-transition');
		}).click(function(){
			$cart.addClass('in-transition');
			setTimeout(function(){
				$cart.addClass('visible');
			}, 300);
		});
	};
}(jQuery));

jQuery(document).ready(function($){
	mp_cart.init();
});