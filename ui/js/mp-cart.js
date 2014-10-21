
var mp_cart = {};

(function($){
	/**
	 * Refers to the validation args for the add-to-cart/buy-now form
	 *
	 * @since 3.0
	 * @type object
	 */
	mp_cart.productFormArgs = {
		"errorClass" : "mp_input_error",
		"errorElement" : "span",
		"errorPlacement" : function(error, element){
			error.appendTo(element.closest('.mp_product_options_att').find('.mp_product_options_att_label'));
		},
		"ignore" : "",
		"submitHandler" : function(form){
			var $form = $(form),
					$loadingGraphic = $('#cboxLoadingOverlay'),
					item = {},
					qty = $form.find('[name="product_quantity"]').val();
					
			// Build item object
			item['product_id'] = $form.find('[name="product_id"]').val();
			$form.find('[name^="product_attr_"]').filter(':checked').each(function(){
				var $this = $(this);
				item[$this.attr('name')] = $this.val();
			});
			
			$loadingGraphic.show();
			
			$form.on('mp_cart/after_add_item', function(e, resp){
				if ( resp.success ) {
					$.colorbox.close();
				}
			});
			
			mp_cart.addItem($form, item, qty);
		}
	};
		
	/**
	 * Initialization method
	 *
	 * @since 3.0
	 */
	mp_cart.init = function(){
		this.initCartAnimation();
		this.initCartListeners();
		this.initProductOptionsLightbox();
	};
	
	/**
	 * Initialize cart listeners
	 *
	 * @since 3.0
	 */
	mp_cart.initCartListeners = function(){
		$('#mp_product_list').on('submit', '.mp_buy_form', function(e){
			e.preventDefault();
			
			var $this = $(this);
			
			$this.on('mp_cart/before_add_item', function(e, item, qty){
				$this.addClass('invisible');
				$('body').children('.mp-ajax-loader').clone().insertAfter($this).show();
			});
			
			$this.on('mp_cart/after_add_item', function(e, resp, item, qty){
				$this.removeClass('invisible').next('.mp-ajax-loader').remove();
			});
			
			mp_cart.addItem($this, $this.find('[name="product_id"]').val());
		});
		
		$('#mp_single_product').on('change', '.mp_product_options_att_input_label input', this.updateProductAttributes);
		$('#mp_single_product').find('.mp_buy_form').validate(this.productAttributeValidationArgs);
	};
	
	/**
	 * Init colorbox listeners
	 *
	 * @since 3.0
	 * @event cbox_complete
	 */
	mp_cart.initCboxListeners = function(){
		$('.mp_product_options_cb').validate(this.productAttributeValidationArgs);
		$('#cboxLoadedContent').on('change', '.mp_product_options_att_input_label input', this.updateProductAttributes);
	};
	
	/**
	 * Update product attributes via ajax
	 *
	 * @since 3.0
	 */
	mp_cart.updateProductAttributes = function(){
		var $this = $(this),
				$form = $this.closest('form'),
				$loadingGraphic = $('#cboxLoadingOverlay'),
				$qtyChanged = $form.find('input[name="product_qty_changed"]');
				url = mp_cart_i18n.ajaxurl + '?action=mp_product_update_attributes';
		
		$loadingGraphic.show();
		$this.closest('.mp_product_options_att').nextAll('.mp_product_options_att').find(':radio').prop('checked', false);
		
		if ( ! $this.is(':radio') ) {
			$qtyChanged.val('1');
		} else {
			$qtyChanged.val('0');
		}
		
		$.post(url, $form.serialize(), function(resp){
			$loadingGraphic.hide();
			
			if ( resp.success ) {
				$.each(resp.data, function(index, value){
					if ( index == 'qty_in_stock' || index == 'out_of_stock' ) {
						return;
					}
					
					$('#mp_' + index).html(value);
				});
				
				if ( resp.data.out_of_stock ) {
					alert(resp.data.out_of_stock);
					$form.find('input[name="product_quantity"]').val(resp.data.qty_in_stock);
				}
			}
		});
	};
	
	/**
	 * Initialize product options lightbox for variable products
	 *
	 * @since 3.0
	 */
	mp_cart.initProductOptionsLightbox = function(){
		$('.mp_link_buynow').filter('.has_variations').colorbox({
			"close" : "x",
			"href" : function(){
				return $(this).attr('data-href');
			},
			"overlayClose" : false
		});
	};
	
	/**
	 * Add an item to the shopping cart
	 *
	 * @since 3.0
	 * @param object $form The current form object.
	 * @param int/object item Either an item ID or, if a variable product, an item object.
	 * @param int qty The quantity to add to the cart. Optional.
	 */
	mp_cart.addItem = function( $form, item, qty ){
		if ( item === undefined || typeof($form) !== 'object' ) {
			return false;
		}
		
		if ( qty === undefined ) {
			qty = 1;
		}

		/**
		 * Fires before adding an item to the cart
		 *
		 * @since 3.0
		 * @param object/int item The item id or item object (if a variation).
		 * @param int qty The quantity added.
		 */
		$form.trigger('mp_cart/before_add_item', [ item, qty ]);
		
		// We use the AjaxQ plugin here because we need to queue multiple add-to-cart requests http://wp.mu/96f
		$.ajaxq('addtocart', {
			"data" : {
				"product" : item,
				"qty" : qty,
				"cart_action" : "add_item"
			},
			"type" : "POST",
			"url" : $form.attr('data-ajax-url'),
		})
		.success(function(resp){
			/**
			 * Fires after successfully adding an item to the cart
			 *
			 * @since 3.0
			 * @param object resp The response object,
			 * @param object/int item The item id or item object (if a variation).
			 * @param int qty The quantity added.
			 */
			$form.trigger('mp_cart/after_add_item', [ resp, item, qty ]);
			
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
	
	/**
	 * Update the cart html
	 *
	 * @since 3.0
	 * @param string html The cart html.
	 */
	mp_cart.update = function( html ){
		$('#mp-floating-cart').replaceWith(html);
		this.initCartAnimation();
	};
	
	/**
	 * Initialize the cart show/hide animation
	 *
	 * @since 3.0
	 */
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

jQuery(document).on('cbox_complete', function(){
	jQuery.colorbox.resize();
	mp_cart.initCboxListeners();
});

jQuery(document).ready(function($){
	mp_cart.init();
});