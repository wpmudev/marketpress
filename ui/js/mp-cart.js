/**
 * Escape special characters in a dynamic jQuery selector (e.g. period, colons, etc)
 *
 * @since 3.0
 */
String.prototype.escapeSelector = function() {
    return this.replace( /(:|\.|\[|\])/g, "\\$1" );
};

var mp_cart = { };

( function( $ ) {

    /**
     * Refers to the validation args for the add-to-cart/buy-now form
     *
     * @since 3.0
     * @type object
     */
    mp_cart.productFormValidationArgs = {
        "errorClass": "mp_form_input_error",
        "errorElement": "span",
        "errorPlacement": function( error, element ) {
            error.appendTo( element.closest( '.mp_product_options_att' ).find( '.mp_product_options_att_label' ) );
        },
        "ignore": "",
        "submitHandler": function( form ) {
            var $form = $( form );
            var qty = $form.find( '[name="product_quantity"]' ).val();
            var product_id = $form.find( '[name="product_id"]' ).val();
            var item;

            if ( $form.find( '[name^="product_attr_"]' ).length > 0 ) {
                // Product has attributes, build item object accordingly
                item = { };
                item['product_id'] = product_id;
                $form.find( '[name^="product_attr_"]' ).each( function() {
                    var $this = $( this );
                    item[ $this.attr( 'name' ) ] = $this.val();
                } );
            } else {
                // Product doesn't have attributes, just submit product_id as item
                item = product_id;
            }

            marketpress.loadingOverlay( 'show' );
            mp_cart.addItem( $form, item, qty );
        }
    };

    /**
     * Initialization method
     *
     * @since 3.0
     */
    mp_cart.init = function() {
        this.initCartAnimation();
        this.initCartListeners();
    };

    /**
     * Initialize product list listeners
     *
     * @since 3.0
     */
    mp_cart.initProductListListeners = function() {
        $( '#mp-products, .mp-multiple-products' ).on( 'submit', '.mp_form-buy-product', function( e ) {
            e.preventDefault();
            $( '.mp_ajax_loader' ).remove();

            var $this = $( this );

            $this.on( 'mp_cart/before_add_item', function( e, item, qty ) {
                $this.addClass( 'invisible' );
                //$( 'body' ).children( '.mp_ajax_loader' ).clone().insertAfter( $this ).show();
                if ( $( ".mp_ajax_loader" ).length ) {

                } else {
                    $( mp_cart_i18n.ajax_loader ).insertAfter( $this ).show();
                }
                //marketpress.loadingOverlay( 'show' );
            } );

            $this.on( 'mp_cart/after_add_item', function( e, resp, item, qty ) {
                $this.removeClass( 'invisible' );//.next( '.mp_ajax_loader' ).remove();
                $( '.mp_ajax_loader' ).remove();
                //marketpress.loadingOverlay( 'hide' );
            } );

            mp_cart.addItem( $this, $this.find( '[name="product_id"]' ).val() );
        } );
    };
	
	/**
     * Initialize cart buttons listeners
     *
     * @since 3.0
     */
    mp_cart.initCartButtonListeners = function() {
        $( '.mp_button-widget-cart-empty' ).on( 'click', function( e ) {
            e.preventDefault();
            mp_cart.emptyCart();
        } );
    };
	

    mp_cart.initShortcodeProductListeners = function() {
		var me = this;
		
        $( '.mp-shortcode-wrap' ).on( 'change', '[name^="product_attr_"]', this.updateProductAttributes );

		//We should loop through each form else jQuery validation is passing wrong form ID
		$( '.mp-shortcode-wrap' ).find( '.mp_buy_button' ).each(function(){
			$(this).on( 'mp_cart/before_add_item', function( e, item, qty ) {
				marketpress.loadingOverlay( 'show' );
			} )
			.on( 'mp_cart/after_add_item', function( e, resp, item, qty ) {
				marketpress.loadingOverlay( 'hide' );
			} )
			.validate(me.productFormValidationArgs);
		});
		
    };
    /**
     * Initalize single product listeners
     *
     * @since 3.0
     */
    mp_cart.initSingleProductListeners = function() {
        $( '#mp-single-product' ).on( 'change', '[name^="product_attr_"]', this.updateProductAttributes );
        $( '#mp-single-product' ).find( '.mp_form-buy-product' ).not('.mp_no_single, .mp_buy_button')
            .on( 'mp_cart/before_add_item', function( e, item, qty ) {
                marketpress.loadingOverlay( 'show' );
            } )
            .on( 'mp_cart/after_add_item', function( e, resp, item, qty ) {
                marketpress.loadingOverlay( 'hide' );
            } )
            .validate( this.productFormValidationArgs );
			
		 $( '#mp-single-product' ).find( '.mp_no_single' ).not( '.mp_buy_button' ).each(function(){
            $(this).on( 'mp_cart/before_add_item', function( e, item, qty ) {
                marketpress.loadingOverlay( 'show' );
            } )
            .on( 'mp_cart/after_add_item', function( e, resp, item, qty ) {
                marketpress.loadingOverlay( 'hide' );
            } )
            .validate( this.productFormValidationArgs );	
		});
    };

    /**
     * Initialize cart form listeners
     *
     * @since 3.0
     */
    mp_cart.initCartFormListeners = function() {
        $( '#mp-cart-form' )
            .on( 'change', 'select[name^="mp_cart_item-qty"]', function( e ) {
                var $this = $( this ),
                    itemId = $this.attr( 'name' ).match( /[0-9]+/ig ),
                    qty = $(this).val();

                mp_cart.updateItemQty( itemId[0], qty, $( '#mp-cart-form' ) );
            } );
    };

    /**
     * Initialize cart listeners
     *
     * @since 3.0
     */
    mp_cart.initCartListeners = function() {
        mp_cart.initProductListListeners();
        mp_cart.initSingleProductListeners();
        mp_cart.initShortcodeProductListeners();
        mp_cart.initCartFormListeners();
        mp_cart.initProductOptionsLightbox();
		mp_cart.initCartButtonListeners();
    };

    /**
     * Init colorbox listeners
     *
     * @since 3.0
     * @event cbox_complete
     */
    mp_cart.initCboxListeners = function() {
        $( '#mp-product-options-callout-form' )
            .on( 'mp_cart/after_add_item', function( e, resp ) {
                if ( resp.success ) {
                    $.colorbox.close();
                }
            } )
            .validate( this.productFormValidationArgs );

        $( '#cboxLoadedContent' ).on( 'change', '[name^="product_attr_"]', this.updateProductAttributes );
    };

    /**
     * Update product attributes via ajax
     *
     * @since 3.0
     */
    mp_cart.updateProductAttributes = function() {

        var $this = $( this );

        $( '[name^="product_attr_"]' ).each( function( i, obj ) {
            if ( $( this ).val() == '' ) {
                //$( this ).attr( 'selected', 'selected' );
                var attr_name = $( this ).attr( 'name' );
                $( '[name^="' + attr_name + '"]' + ' option:nth-child(2)' ).attr( "selected", true );
            }
        } );

        $form = $this.closest( 'form' );

        $container = ( $( '#colorbox' ).is( ':visible' ) ) ? $form : $( '#mp-single-product' );
        $qtyChanged = $form.find( 'input[name="product_qty_changed"]' );
        url = mp_cart_i18n.ajaxurl + '?action=mp_product_update_attributes';

        marketpress.loadingOverlay( 'show' );
        $this.closest( '.mp_product_options_att' ).nextAll( '.mp_product_options_att' ).find( '[name^="product_attr_"]' ).val( '' );
        //the this contex is product attributes select, there's no radio situation, so comment those value for now
        /*if ( !$this.is( ':radio' ) ) {
         $qtyChanged.val( '1' );
         } else {
         $qtyChanged.val( '0' );
         }*/

        $.post( url, $form.serialize() ).done( function( resp ) {

            marketpress.loadingOverlay( 'hide' );
            marketpress.ajaxEvent( 'mp_cart/after_update_product_attributes', resp );

            if ( resp.success ) {

                //console.log( resp.data );
                if (resp.data.image) {
                    if ($container.find('.mp_product_image_link').size() == 0) {
                        $('.mp_single_product_images').html(
                            $('<a/>').attr({
                                'class': 'mp_product_image_link mp_lightbox cboxElement',
                                'rel': 'lightbox enclosure',
                                'href': resp.data.image_full
                            }).html($('<img/>').attr({
                                'class': 'mp_product_image_single photo',
                                'src': resp.data.image
                            }))
                        );
                        //reinit the lightbox
                        $( '.mp_product_image_link' ).filter( '.mp_lightbox' ).colorbox( {
                            maxWidth: "90%",
                            maxHeight: "90%",
                            close: "&times;"
                        } );
                    } else {
                        $container.find('.mp_product_image_single').attr('src', resp.data.image);
                        $container.find('.mp_product_image_link').attr('href', resp.data.image_full);
                    }
                }else{
                    $('.mp_product_image_link').remove();
                }

                //if ( resp.data.description ) {
                $container.find( '.mp_product_tab_content_text' ).html( resp.data.description );
                //}
                //update content for lightbox
                if ( $( '.mp_product_options_excerpt' ).size() > 0 ) {
                    $( '.mp_product_options_excerpt' ).html( resp.data.excerpt );
                }

                //if ( resp.data.excerpt ) {
                $container.find( '.mp_product_excerpt' ).html( resp.data.excerpt );
                //}

                if ( resp.data.price ) {
                    $container.find( '.mp_product_price' ).replaceWith( resp.data.price );
                }

                $.each( resp.data, function( index, value ) {
                    var $elm = $( '#mp_' + index );

                    if ( index == 'qty_in_stock' || index == 'out_of_stock' || $elm.length == 0 ) {
                        return;
                    }

                    $elm.html( value );
                } );

                if ( resp.data.out_of_stock ) {
                    alert( resp.data.out_of_stock );
                    $form.find( 'input[name="product_quantity"]' ).val( resp.data.qty_in_stock );
                }

                $.colorbox.resize();
            }
        } );
    };

    /**
     * Initialize product options lightbox for variable products
     *
     * @since 3.0
     */
    mp_cart.initProductOptionsLightbox = function() {
        $( '.mp_link-buynow' ).filter( '.mp_button-has_variations' ).colorbox( {
            "close": "x",
            "href": function() {
                return $( this ).attr( 'data-href' );
            },
            "overlayClose": false,
            "trapFocus": false,
            "width": 300,
            "overlayClose": true,
            "escKey": true,
            onLoad: function() {
                $( "#colorbox" ).removeAttr( "tabindex" ); //remove tabindex before select2 init
            },
            onComplete: function() {
                $( "select.mp_select2" ).mp_select2( {
                    "dropdownCssClass": "mp_select2",
                    "dropdownAutoWidth": 1,
                    "minimumResultsForSearch": -1
                } );
            }
        } );
    };

    /**
     * Add an item to the shopping cart
     *
     * @since 3.0
     * @param object $form The current form object.
     * @param int/object item Either an item ID or, if a variable product, an item object.
     * @param int qty The quantity to add to the cart. Optional.
     */
    mp_cart.addItem = function( $form, item, qty ) {
        if ( item === undefined || typeof ( $form ) !== 'object' ) {
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
        $form.trigger( 'mp_cart/before_add_item', [ item, qty ] );

        // We use the AjaxQ plugin here because we need to queue multiple add-to-cart requests http://wp.mu/96f
        $.ajaxq( 'addtocart', {
            "data": {
                "product": item,
                "qty": qty,
                "cart_action": "add_item",
                "is_cart_page": mp_cart_i18n.is_cart_page
            },
            "type": "POST",
            "url": $form.attr( 'data-ajax-url' ),
        } )
            .done( function( resp ) {
                marketpress.ajaxEvent( 'mp_cart/after_add_item', resp, $form );

                var buttonType = $form.find( '[type="submit"]' ).attr( 'name' );

                if ( resp.success ) {

                    if ( resp.data.cart_updated === false ) {
                        alert( mp_cart_i18n.cart_updated_error_limit );
                    }

                    if ( 'buynow' == buttonType ) {
                        // buy now button - redirect to cart
                        window.location.href = $form.attr( 'action' );
                        return;
                    }

                    mp_cart.update( resp.data.minicart );
					mp_cart.update_widget( resp.data.widgetcart );
					
					//Init button listeners when ajax loaded
					mp_cart.initCartButtonListeners();
					
                    $form.get( 0 ).reset();

                    setTimeout( function() {
                        $( '#mp-floating-cart' ).trigger( 'click' );
                        setTimeout( function() {
                            $( '#mp-floating-cart' ).removeClass( 'visible in-transition' );
                        }, 3000 );
                    }, 100 );
                }
                $( window ).trigger( 'resize' );
            } );
    };

    /**
     * Remove an item from the shopping cart
     *
     * @since 3.0
     * @param int itemId The item ID to remove.
     */
    mp_cart.removeItem = function( itemId ) {
        if ( itemId === undefined ) {
            return false;
        }

        itemId = itemId.toString();

        var url = mp_cart_i18n.ajaxurl + '?action=mp_update_cart';
        var data = {
            "product": itemId,
            "cart_action": "remove_item",
            "is_cart_page": mp_cart_i18n.is_cart_page
        };

        marketpress.loadingOverlay( 'show' );

        $.post( url, data ).done( function( resp ) {
            if ( resp.success ) {
                if ( resp.data.item_count == 0 ) {
                    window.location.href = window.location.href;
                } else {
                    var $lineItem = $( '#mp-cart-item-' + itemId.escapeSelector() );

                    if ( $lineItem.siblings( '.mp_cart_item' ).length == 0 && $lineItem.closest( '.mp_cart_store' ).length > 0 ) {
                        $lineItem.closest( '.mp_cart_store' ).remove();
                    } else {
                        $lineItem.remove();
                    }

                    $( '#mp-cart-resume' ).replaceWith( resp.data.cartmeta );

                    marketpress.loadingOverlay( 'hide' );
                }
            }
            $( window ).trigger( 'resize' );
        } );
    }
	
	/**
     * Remove all items from the shopping cart
     *
     * @since 3.0
     */
    mp_cart.emptyCart = function() {
        var url = mp_cart_i18n.ajaxurl + '?action=mp_update_cart';
        var data = {
            "cart_action": "empty_cart"
        };

        $.post( url, data ).done( function( resp ) {
            if ( resp.success ) {
                if ( resp.data.item_count == 0 ) {
                    window.location.href = window.location.href;
                }
            }
        } );
    }

    /**
     * Update the cart html
     *
     * @since 3.0
     * @param string html The cart html.
     */
    mp_cart.update = function( html ) {
        $( '#mp-floating-cart' ).replaceWith( html );
        this.initCartAnimation();
    };
	
	/**
     * Update the cart widget html
     *
     * @since 3.0
     * @param string html The cart html.
     */
    mp_cart.update_widget = function( html ) {
        $( '.mp_cart_widget_content' ).html( html );
    };

    /**
     * Update an item's qty
     *
     * @since 3.0
     * @param int itemID The item ID to update.
     * @param int qty The new qty.
     * @param object $scope Optional, the scope of triggered events. Defaults to document.
     */
    mp_cart.updateItemQty = function( itemId, qty, $scope ) {
        var url = mp_cart_i18n.ajaxurl + '?action=mp_update_cart';
        var data = {
            "product": itemId,
            "qty": qty,
            "cart_action": "update_item",
            "is_cart_page": mp_cart_i18n.is_cart_page
        };

        if ( $scope === undefined ) {
            var $scope = $( document );
        }

        marketpress.loadingOverlay( 'show' );

        $.post( url, data ).done( function( resp ) {
            marketpress.loadingOverlay( 'hide' );
            marketpress.ajaxEvent( 'mp_cart/update_item_qty', resp, $scope );

            if ( resp.success ) {
                $.each( resp.data.product, function( key, val ) {
                    var $item = $( '#mp-cart-item-' + key );
                    $item.replaceWith( val );
                } );
                $( '#mp-cart-resume' ).replaceWith( resp.data.cartmeta );
                marketpress.initSelect2();
            }
        } );
    };

    /**
     * Initialize the cart show/hide animation
     *
     * @since 3.0
     */
    mp_cart.initCartAnimation = function() {
        var $cart = $( '#mp-floating-cart' );

        $cart.hover( function() {
            $cart.addClass( 'in-transition' );
            setTimeout( function() {
                $cart.addClass( 'visible' );
            }, 300 );
        }, function() {
            $cart.removeClass( 'visible in-transition' );
        } ).click( function() {
            $cart.addClass( 'in-transition' );
            setTimeout( function() {
                $cart.addClass( 'visible' );
            }, 300 );
        } );
    };
}( jQuery ) );

jQuery( document ).on( 'cbox_complete', function() {
    jQuery.colorbox.resize();
    mp_cart.initCboxListeners();
} );

jQuery( document ).ready( function( $ ) {
    mp_cart.init();
} );