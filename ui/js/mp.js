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
        var me = this;

        $( '.mp-single-product' ).each( function() {
            $(this).on( 'change', '[name^="product_attr_"]', me.updateProductAttributes );
            $(this).on( 'input', '[name^="product_quantity"]', me.checkProductQuantity );

            $(this).find( '.mp_form-buy-product' ).not('.mp_no_single, .mp_buy_button')
                .on( 'mp_cart/before_add_item', function( e, item, qty ) {
                    marketpress.loadingOverlay( 'show' );
                } )
                .on( 'mp_cart/after_add_item', function( e, resp, item, qty ) {
                    marketpress.loadingOverlay( 'hide' );
                } )
                .validate( me.productFormValidationArgs );

            $(this).find( '.mp_no_single' ).not( '.mp_buy_button' ).each( function() {
                $(this).on( 'mp_cart/before_add_item', function( e, item, qty ) {
                    marketpress.loadingOverlay( 'show' );
                } )
                .on( 'mp_cart/after_add_item', function( e, resp, item, qty ) {
                    marketpress.loadingOverlay( 'hide' );
                } )
                .validate( me.productFormValidationArgs );
            } );
		} );
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

        $container = ( $( '#colorbox' ).is( ':visible' ) ) ? $form : $this.closest( '.mp-single-product' );
		$meta_container = $this.closest( '.mp_product' );
        $qtyChanged = $form.find( 'input[name="product_qty_changed"]' );
        url = mp_cart_i18n.ajaxurl + '?action=mp_product_update_attributes';

        marketpress.loadingOverlay( 'show' );
        
        var form_data = $form.serializeArray();

        // Change siblings attr name to "other_'attr_name'" instead of just not sending them
        // Used to get the current variation selected and check it's stock
        
        //$this.parents( '.mp_product_options_att' ).siblings( '.mp_product_options_att' ).find( '[name^="product_attr_"]' ).val( '' );

        $this.parents( '.mp_product_options_att' ).siblings( '.mp_product_options_att' ).find( '[name^="product_attr_"]' ).each(function(i, el) {
        	$(form_data).each(function(j, el2) {
        		el2.name === $(el).attr( 'name' ) && ( form_data[j]['name'] = 'other_' + form_data[j]['name'] ) ;
        	});
        });

        //the this contex is product attributes select, there's no radio situation, so comment those value for now
        /*if ( !$this.is( ':radio' ) ) {
         $qtyChanged.val( '1' );
         } else {
         $qtyChanged.val( '0' );
         }*/

        $.post( url, jQuery.param( form_data ) ).done( function( resp ) {

            marketpress.loadingOverlay( 'hide' );
            marketpress.ajaxEvent( 'mp_cart/after_update_product_attributes', resp );

            if ( resp.success ) {

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

						$( '.mp_product_options_thumb' ).attr('src', resp.data.image);
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
                $meta_container.find( '.mp_product_excerpt' ).html( resp.data.excerpt );
                //}

                if ( resp.data.price ) {
                    $container.find( '.mp_product_price' ).replaceWith( resp.data.price );
                }

                if ( resp.data.product_input ) {
    				$container.find( '#mp_product_options_att_quantity-error' ).remove();
					$container.find( '#mp_product_options_att_quantity' ).replaceWith( resp.data.product_input );
						if ( typeof( resp.data.in_stock ) !== 'undefined' ) {
                			$container.find( '#mp_product_options_att_quantity' ).trigger('blur');
                		}    
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

                $( '[name^="product_attr_"].mp_select2' ).mp_select2( {
                    "dropdownCssClass": "mp_select2",
                    "dropdownAutoWidth": 1,
                    "minimumResultsForSearch": -1   // hide the search box
                } );

                $( '[name^="product_attr_"].mp_select2_search' ).mp_select2( {
                    "dropdownCssClass": "mp_select2",
                    "dropdownAutoWidth": 1
                } );

                $.colorbox.resize();
            }
        } );
    };

	/**
     * Update product quantity
     *
     * @since 3.0
     */
    mp_cart.checkProductQuantity = function() {
    	var $this = $( this );

    	if( $this.attr( 'max' ) > 0 && parseInt( $this.val() ) > parseInt( $this.attr( 'max' ) ) ) { 
    		$this.trigger('blur');
    		// Delay before fixing input value to give time to validator to process.
    		setTimeout(function(){$this.val($this.attr( 'max' ))}, 50);
    	}
    }


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
					mp_cart.update_product_input( resp.data.product_input, $form );
					mp_cart.update_product_buttons( resp.data.out_of_stock, $form );
					
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
                        $lineItem.after( resp.data.cart_item_line ).remove();
                    }

                    $( '#mp-cart-resume' ).replaceWith( resp.data.cartmeta );

                    marketpress.loadingOverlay( 'hide' );
                }
            }
            $( window ).trigger( 'resize' );
        } );
    }

    /**
     * Undo Remove an item from the shopping cart
     *
     * @since 3.0
     * @param int itemId The item ID to remove.
     */
    mp_cart.undoRemoveItem = function( itemId ) {
        if ( itemId === undefined ) {
            return false;
        }

        itemId = itemId.toString();

        var url = mp_cart_i18n.ajaxurl + '?action=mp_update_cart';
        var data = {
            "product": itemId,
            "cart_action": "undo_remove_item",
            "is_cart_page": mp_cart_i18n.is_cart_page
        };

        marketpress.loadingOverlay( 'show' );

        $.post( url, data ).done( function( resp ) {
            if ( resp.success ) {
                var $lineItem = $( '#mp-cart-item-' + itemId.escapeSelector() );
                $lineItem.after( resp.data.cart_item_line ).remove();
                $( '#mp-cart-resume' ).replaceWith( resp.data.cartmeta );
                marketpress.loadingOverlay( 'hide' );
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
     * Update the product qty input
     *
     * @since 3.0
     * @param string html The product qty input.
     */
    mp_cart.update_product_input = function( html, $form ) {
    	$form.find( '#mp_product_options_att_quantity-error' ).remove();
        if ( $( html ).is('label') ){
            $form.find( '[name="product_quantity"]' ).prev( 'label' ).remove();
        }
        $form.find( '[name="product_quantity"]' ).after( html ).remove();
    };

     /**
     * Update the product add to cart button
     *
     * @since 3.0
     * @param bool is the product out of stock.
     */
    mp_cart.update_product_buttons = function( out_of_stock, $form ) {
		if( out_of_stock === true ) {
			$form.find( '.mp_button' ).attr( 'disabled' , true );
		}
		else {
			$form.find( '.mp_button' ).attr( 'disabled' , false );
		}
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

jQuery( document ).on( 'cbox_cleanup', function() {
	if ( typeof jQuery('.mp_select2').mp_select2 !== 'undefined' ) {
		jQuery('.mp_select2').mp_select2('close');
	}
} );

jQuery( document ).on( 'cbox_complete', function() {
    jQuery.colorbox.resize();
    mp_cart.initCboxListeners();
} );

jQuery( document ).ready( function( $ ) {
    mp_cart.init();
} );
;var mp_checkout;

( function( $ ) {
	
	/**
	 * Fix jqueryui * bootstrap tooltip conflict
	 * @since 3.0
	 */
	 
	$.widget.bridge('mptooltip', $.ui.tooltip);
	
    mp_checkout = {
        /**
         * Initialize event listeners
         *
         * @since 3.0
         */
        initListeners: function() {
            this.initShippingAddressListeners();
			this.initAccountRegistrationListeners();
            this.initPaymentOptionListeners();
            this.initUpdateStateFieldListeners();
            this.initCardValidation();
            this.initCheckoutSteps();
            this.listenToLogin();
            $( document ).on( 'mp_checkout/step_changed', this.lastStep );
        },
        /**
         * Update state list/zipcode field when country changes
         *
         * @since 3.0
         */
        initUpdateStateFieldListeners: function() {
            $( '[name="billing[country]"], [name="shipping[country]"]' ).on( 'change', function() {
                var $this = $( this );
                var url = mp_i18n.ajaxurl + '?action=mp_update_states_dropdown';

                if ( $this.attr( 'name' ).indexOf( 'billing' ) == 0 ) {
                    var $state = $( '[name="billing[state]"]' );
                    var $zip = $( '[name="billing[zip]"]' );
                    var type = 'billing';
                    var $row = $state.closest( '.mp_checkout_fields' );
                } else {
                    var $state = $( '[name="shipping[state]"]' );
                    var $zip = $( '[name="shipping[zip]"]' )
                    var type = 'shipping';
                    var $row = $state.closest( '.mp_checkout_fields' );
                }

                var data = {
                    country: $this.val(),
                    type: type
                }

                $row.ajaxLoading( 'show' );

                $.post( url, data ).done( function( resp ) {
                    if ( resp.success ) {
                        $row.ajaxLoading( 'false' );
                        if ( resp.data.states ) {
                            $state.html( resp.data.states );
                            $state.trigger( 'change' ).closest( '.mp_checkout_column' ).show();
                        } else {
                            $state.closest( '.mp_checkout_column' ).hide();
                        }

                        if ( resp.data.show_zipcode ) {
                            $zip.closest( '.mp_checkout_column' ).show();
                        } else {
                            $zip.closest( '.mp_checkout_column' ).hide();
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
        showForm: function() {
            $( '#mp-checkout-form' ).show();
        },
        /**
         * Get a value from a hashed query string
         *
         * @since 3.0
         * @param string what The name of the variable to retrieve.
         * @param mixed defaultVal Optional, what to return if the variable doesn't exist. Defaults to false.
         * @return mixed
         */
        getHash: function( what, defaultVal ) {
            var hash = window.location.hash;

            if ( undefined === defaultVal ) {
                defaultVal = false;
            }

            if ( 0 > hash.indexOf( '#!' ) || undefined === defaultVal ) {
                return defaultVal;
            }

            var hashParts = hash.substr( 2 ).split( '&' ), hashPairs = { };

            $.each( hashParts, function( index, value ) {
                var tmp = value.split( '=' );
                hashPairs[ tmp[0] ] = tmp[1];
            } );

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
        errorSummary: function( action, count ) {
            var $section = $( '.mp_checkout_section' ).filter( '.current' ).find( '.mp_checkout_section_errors' );
            var $checkout = $( '#mp-checkout-form' );

            if ( undefined === $checkout.data( 'mp-submitted' ) ) {
                /* form hasn't been submitted so bail. fixes issue with error summary
                 being hidden when generated by PHP */
                return;
            }

            if ( 'show' == action ) {
                var errorVerb = ( count > 1 ) ? mp_checkout_i18n.error_plural : mp_checkout_i18n.error_singular;
                var errorString = mp_checkout_i18n.errors.replace( '%d', count ).replace( '%s', errorVerb );
                $section.html( errorString ).addClass( 'show' );
            } else {
                $section.removeClass( 'show' );
            }
        },
        /**
         * Execute when on the last step of checkout
         *
         * @since 3.0
         * @event mp_checkout/step_changed
         */
        lastStep: function( evt, $out, $in ) {
            var $checkout = $( '#mp-checkout-form' );

            if ( $in.next( '.mp_checkout_section' ).length == 0 ) {
                $checkout.addClass( 'last-step' );
            } else {
                $checkout.removeClass( 'last-step' );
            }
        },
        /**
         * Go to next step in checkout
         *
         * @since 3.0
         */
        nextStep: function() {
            var $current = $( '.mp_checkout_section' ).filter( '.current' );
            var $next = $current.next( '.mp_checkout_section' );
            this.changeStep( $current, $next );
        },
        /**
         * Change checkout steps
         *
         * @since 3.0
         * @param jQuery $out The jquery object being transitioned FROM
         * @param jQuery $in The jquery object being transitioned TO
         */
        changeStep: function( $out, $in ) {
            $out.find( '.mp_tooltip' ).mptooltip( 'close' );
            $out.find( '.mp_checkout_section_content' ).slideUp( 500, function() {
                $out.removeClass( 'current' );
                $in.find( '.mp_checkout_section_content' ).slideDown( 500, function() {
                    $in.addClass( 'current' );

                    /**
                     * Fires after a step change
                     *
                     * @since 3.0
                     * @param jQuery $out The jquery object being transitioned FROM
                     * @param jQuery $in The jquery object being transitioned TO
                     */
                    $( document ).trigger( 'mp_checkout/step_changed', [ $out, $in ] );

                    mp_checkout.initActivePaymentMethod();
                } );
            } );
        },
        /**
         * Initialize checkout steps
         *
         * @since 3.0
         */
        initCheckoutSteps: function() {
            var $checkout = $( ' #mp-checkout-form' );
            var formSubmitted = false;

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

            // Go to step when clicking on section heading
            $checkout.find( '.mp_checkout_section_heading-link' ).on( 'click', function( e ) {
                var $this = $( this );
                var $section = $this.closest( '.mp_checkout_section' );
                var $current = $section.nextAll( '.current' );

                if ( $current.length > 0 ) {
                    // section is before the current step - ok to proceed
                    mp_checkout.changeStep( $current, $section );
                }
            } );

            // Validate form
            $checkout.validate( {
                rules: {
                    shipping_method: "required",
                },
                onkeyup: false,
                onclick: false,
                ignore: function( index, element ) {
                    return ( $( element ).is( ':hidden' ) || $( element ).prop( 'disabled' ) );
                },
                highlight: function( element, errorClass ) {
                    $( element ).addClass( 'mp_form_input_error' ).prev( 'label' ).addClass( 'mp_form_label_error' );
                },
                unhighlight: function( element, errorClass, validClass ) {
                    var $tip = $( element ).siblings( '.mp_tooltip' );
                    if ( $tip.length > 0 ) {
                        $tip.mptooltip( 'close' );
                    }

                    $( element ).removeClass( 'mp_form_input_error' ).prev( 'label' ).removeClass( 'mp_form_label_error' );

                    if ( this.numberOfInvalids() == 0 ) {
                        mp_checkout.errorSummary( 'hide' );
                    }
                },
                submitHandler: function( form ) {
                    $checkout.data( 'mp-submitted', true );

                    var $form = $( form );
                    var $email = $form.find( '[name="mp_login_email"]' );
                    var $pass = $form.find( '[name="mp_login_password"]' );


                    if ( $form.valid() ) {
                        var checkout_as_guest = false;
                        if ($form.find('#is_checkout_as_guest').size() > 0) {
                            checkout_as_guest = true;
                        }
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
                            $( document ).trigger( 'mp_checkout_process_' + gateway, [ $form ] );
                        } else if ( $.trim( $email.val() ).length > 0 && $.trim( $pass.val() ).length > 0 && checkout_as_guest==false) {
                            var $btn = $( '#mp-button-checkout-login' );
                            $btn.ajaxLoading();

                            // Destroy any tooltips
                            if ( $( '#mp-login-tooltip' ).length > 0 ) {
                                $( '#mp-login-tooltip' ).remove();
                            }

                            var data = {
                                action: "mp_ajax_login",
                                email: $email.val(),
                                pass: $pass.val(),
                                mp_login_nonce: $form.find( '[name="mp_login_nonce"]' ).val()
                            };

                            $.post( mp_i18n.ajaxurl, data ).done( function( resp ) {
                                if ( resp.success ) {
                                    window.location.href = window.location.href;
                                } else {
                                    $btn.ajaxLoading( 'hide' );
                                    $email.before( '<a id="mp-login-tooltip"></a>' );
                                    $( '#mp-login-tooltip' ).mptooltip( {
                                        items: '#mp-login-tooltip',
                                        content: resp.data.message,
                                        tooltipClass: "error",
                                        open: function( event, ui ) {
                                            setTimeout( function() {
                                                $( '#mp-login-tooltip' ).mptooltip( 'destroy' );
                                            }, 4000 );
                                        },
                                        position: {
                                            of: $email,
                                            my: "center bottom-10",
                                            at: "center top"
                                        },
                                        show: 300,
                                        hide: 300
                                    } ).mptooltip( 'open' );
                                }
                            } );
                        } else {
                            var url = mp_i18n.ajaxurl + '?action=mp_update_checkout_data';
                            marketpress.loadingOverlay( 'show' );

                            $.post( url, $form.serialize() ).done( function( resp ) {
                                $.each( resp.data, function( index, value ) {
                                    $( '#' + index ).find( '.mp_checkout_section_content' ).html( value );
                                } );

                                if( resp.data.mp_checkout_nonce ){
                                    $( "input#mp_checkout_nonce" ).replaceWith( resp.data.mp_checkout_nonce );
                                }

                                mp_checkout.initCardValidation();
                                marketpress.loadingOverlay( 'hide' );
                                mp_checkout.nextStep();
                            } );
                        }
                    }
                },
                showErrors: function( errorMap, errorList ) {
                    if ( this.numberOfInvalids() > 0 ) {
                        mp_checkout.errorSummary( 'show', this.numberOfInvalids() );
                    }

                    $.each( errorMap, function( inputName, message ) {
                        var $input = $( '[name="' + inputName + '"]' );
                        var $tip = $input.siblings( '.mp_tooltip' );

                        if ( $tip.length == 0 ) {
                            $input.after( '<div class="mp_tooltip" />' );
                            $tip = $input.siblings( '.mp_tooltip' );
                            $tip.uniqueId().mptooltip( {
                                content: "",
                                items: "#" + $tip.attr( 'id' ),
                                tooltipClass: "error",
                                show: 300,
                                hide: 300
                            } );
                        }

                        $tip.mptooltip( 'option', 'content', message );
                        $tip.mptooltip( 'option', 'position', {
                            of: $input,
                            my: "center bottom-10",
                            at: "center top"
                        } );

                        $input.on( 'focus', function() {
                            if ( $input.hasClass( 'mp_form_input_error' ) ) {
                                //$tip.tooltip( 'open' );
                            }
                        } );

                        $input.on( 'blur', function() {
                            $tip.mptooltip( 'close' );
                        } );
                    } );

                    this.defaultShowErrors();
                }
            } );
        },
        /**
         * Initialize the active/selected payment method
         *
         * @since 3.0
         */
        initActivePaymentMethod: function() {
            var $input = $( 'input[name="payment_method"]' );
            if ( $input.filter( ':checked' ).length ) {
                $input.filter( ':checked' ).trigger( 'click' );
            } else {
                $input.eq( 0 ).trigger( 'click' );
            }
        },
        /**
         * Initialize credit card validation events/rules
         *
         * @since 3.0
         */
        initCardValidation: function() {
            $( '.mp-input-cc-num' ).payment( 'formatCardNumber' );
            $( '.mp-input-cc-exp' ).payment( 'formatCardExpiry' );
            $( '.mp-input-cc-cvc' ).payment( 'formatCardCVC' );

            // Validate card fullname
            $.validator.addMethod( 'cc-fullname', function( val, element ) {
                var pattern = /^([a-z]+)([ ]{1})([a-z]+)$/ig;
                return this.optional( element ) || pattern.test( val );
            }, mp_checkout_i18n.cc_fullname );

            // Validate card numbers
            $.validator.addMethod( 'cc-num', function( val, element ) {
                return this.optional( element ) || $.payment.validateCardNumber( val );
            }, mp_checkout_i18n.cc_num );

            // Validate card expiration
            $.validator.addMethod( 'cc-exp', function( val, element ) {
                var dateObj = $.payment.cardExpiryVal( val );
                return this.optional( element ) || $.payment.validateCardExpiry( dateObj.month, dateObj.year );
            }, mp_checkout_i18n.cc_exp );

            // Validate card cvc
            $.validator.addMethod( 'cc-cvc', function( val, element ) {
                return this.optional( element ) || $.payment.validateCardCVC( val );
            }, mp_checkout_i18n.cc_cvc );
        },
        /**
         * Init events related to toggling payment options
         *
         * @since 3.0
         * @access public
         */
        initPaymentOptionListeners: function() {
            $( '.mp_checkout_section' ).on( 'click change', 'input[name="payment_method"]', function() {
                var $this = $( this ),
                    $target = $( '#mp-gateway-form-' + $this.val() ),
                    $checkout = $( '#mp-checkout-form' ),
                    $submit = $checkout.find( ':submit' ).filter( ':visible' );

                if ( !$checkout.hasClass( 'last-step' ) ) {
                    return;
                }

                $target.show().siblings( '.mp_gateway_form' ).hide();

                if ( $target.find( '.mp_form_input.error' ).filter( ':visible' ).length > 0 ) {
                    $checkout.valid();
                } else {
                    mp_checkout.errorSummary( 'hide' );
                }

                if ( undefined === $submit.data( 'data-mp-original-html' ) ) {
                    $submit.data( 'data-mp-original-html', $submit.html() )
                }

                if ( 'true' == $this.attr( 'data-mp-use-confirmation-step' ) ) {
                    $submit.html( $submit.attr( 'data-mp-alt-html' ) );
                } else {
                    $submit.html( $submit.data( 'data-mp-original-html' ) );
                }
            } );

            this.initActivePaymentMethod();
        },
        /**
         * Enable/disable shipping address fields
         *
         * @since 3.0
         */
        toggleShippingAddressFields: function() {
            var $cb = $( 'input[name="enable_shipping_address"]' );
            var $shippingInfo = $( '#mp-checkout-column-shipping-info' );
            var $billingInfo = $( '#mp-checkout-column-billing-info' );

            if ( $cb.prop( 'checked' ) ) {
                $billingInfo.removeClass( 'fullwidth' );
                setTimeout( function() {
                    $shippingInfo.fadeIn( 500 );
                }, 550 );
            } else {
                $shippingInfo.fadeOut( 500, function() {
                    $billingInfo.addClass( 'fullwidth' );
                } );
            }
        },
		/**
         * Enable/disable registration fields
         *
         * @since 3.0
         */
        toggleRegistrationFields: function() {
            var $cb = $( 'input[name="enable_registration_form"]' );
            var $account_container = $( '#mp-checkout-column-registration' );

            if ( $cb.prop( 'checked' ) ) {
                $account_container.fadeIn( 500 );
            } else {
                $account_container.fadeOut( 500 );
            }
		},
		/**
         * Initialize events related to registration fields
         *
         * @since 3.0
         */
        initAccountRegistrationListeners: function() {
            var $enableRegistration = $( 'input[name="enable_registration_form"]' );

            // Enable account registration fields
            $enableRegistration.change( mp_checkout.toggleRegistrationFields );
        },
        /**
         * Initialize events related to shipping address fields
         *
         * @since 3.0
         */
        initShippingAddressListeners: function() {
            var $enableShippingAddress = $( 'input[name="enable_shipping_address"]' );

            // Enable billing address fields
            $enableShippingAddress.change( mp_checkout.toggleShippingAddressFields );

            // Copy billing field to shipping field (if shipping address isn't enabled)
            $( '[name^="billing["]' ).on( 'change keyup', function() {
                if ( $enableShippingAddress.is( ':checked' ) ) {
                    // Shipping address checkbox is checked - bail
                    return;
                }

                var $this = $( this );
                var name = $this.attr( 'name' );
                var $target = $( '[name="' + name.replace( 'billing', 'shipping' ) + '"]' );

                if ( $target.length == 0 ) {
                    // Input doesn't exist - bail
                    return;
                }

                $target.val( $this.val() ).trigger( 'change' );
            } );
        },
        /**
         * Trigger step change event
         *
         * @since 3.0
         */
        triggerStepChange: function() {
            var $current = $( '.mp_checkout_section' ).filter( '.current' );
            $( document ).trigger( 'mp_checkout/step_changed', [ $current, $current ] );
        },
        /**
         * Because we have 2 context in login pharse, so we will have to determine which button click to add/removerules
         *
         */
        listenToLogin: function() {
            //if login click, we will add those rules
            $( document ).on( 'click', '.mp_button-checkout-login', function() {
                $( 'input[name="mp_login_email"]' ).rules( 'add', {
                    required: true
                } );
                $( 'input[name="mp_login_password"]' ).rules( 'add', {
                    required: true
                } );
                var form = $(this).closest('form');
                form.find('#is_checkout_as_guest').remove();
                $( this ).closest( 'form' ).submit();
            } );
            //else, we have to remove the rules
            $( document ).on( 'click', '.mp_continue_as_guest', function( e ) {
                $( 'input[name="mp_login_email"]' ).rules( 'remove' );
                $( 'input[name="mp_login_password"]' ).rules( 'remove' );
                var form = $(this).closest('form');
                if (form.find('#is_checkout_as_guest').size() == 0) {
                    form.append($('<input id="is_checkout_as_guest"/>'));
                }
                //$( '.mp_checkout_section_errors' ).hide();
                $( this ).closest( 'form' ).submit();
            } )
            //our form is multiple next/pre button, so we unbind the enter trigger
            $( '#mp-checkout-form' ).on( 'keyup keypress', function( e ) {
                var code = e.keyCode || e.which;
                if ( code == 13 ) {
                    e.preventDefault();
                    return false;
                }
            } );
        }
    };
}( jQuery ) );

jQuery( document ).ready( function( $ ) {
    mp_checkout.showForm();
    mp_checkout.initListeners();
    mp_checkout.toggleShippingAddressFields();
	mp_checkout.toggleRegistrationFields();
    mp_checkout.triggerStepChange();
} );;( function( $ ) {
    $.fn.equalHeights = function( ) {
        var maxHeight = 0;
        this.each( function( ) {
            maxHeight = Math.max( $( this ).height( ), maxHeight );
        } );
        return this.each( function( ) {
            $( this ).height( maxHeight );
        } );
    }
}( jQuery ) );
( function( $ ) {
    /**
     * Preload loading icon
     *
     * @since 3.0
     */
    $( '<img>' ).get( 0 ).src = mp_i18n.loadingImage;
    /**
     * Add or remove cart ajax loading icon
     *
     * @since 3.0
     * @param string action Optional, either "show" or "hide". Defaults to "show".
     */
    $.fn.ajaxLoading = function( action ) {
        if ( typeof ( action ) == 'undefined' ) {
            var action = 'show';
        }

        return this.each( function( ) {
            if ( 'show' == action ) {
                $( this ).hide( ).after( '<img src="' + mp_i18n.loadingImage + '" alt="">' );
            } else {
                $( this ).show( ).next( 'img' ).remove( );
            }
        } );
    };
}( jQuery ) );
var marketpress = { };
( function( $ ) {
	
	$.widget.bridge('mptooltip', $.ui.tooltip);

    function equal_height( obj ) {
        var $this = $( obj );
        $this.equalHeights();
        //$this.find( '.mp_product_name' ).equalHeights();
        //$this.find( '.mp_product_meta' ).equalHeights();
        //$this.find( '.mp_product_details' ).equalHeights();
        //$this.find( '.mp_product' ).equalHeights();
        //$this.find( '.mp_product_name' ).equalHeights();
        //$this.find( '.mp_product_meta' ).equalHeights();
        //$this.find( '.mp_form-buy-product' ).addClass( 'sticky' );
        //$this.find( '.hmedia' ).equalHeights();
    }

    $( window ).resize( function( ) {
        $( '#mp-products.mp_products-grid' ).each( function( ) {
            var $this = $( this );
            //$this.find( '.mp_product_name' ).equalHeights();
            $this.find( '.mp_product_meta' ).equalHeights();
            //$this.find( '.mp_product_details' ).equalHeights();
            //$this.find( '.mp_product' ).equalHeights();
            //$this.find( '.mp_product_name' ).equalHeights();
            //$this.find( '.mp_product_meta' ).equalHeights();
            //$this.find( '.mp_form-buy-product' ).addClass( 'sticky' );
            //$this.find( '.hmedia' ).equalHeights();
        } );

        $( '#mp-related-products .mp_products-grid .mp_product_meta' ).each( function( ) {
            //equal_height( $( this ) );
            var $this = $( this );
            $this.find( '.mp_product_meta' ).equalHeights();
        } );

    } );


    marketpress = {
        /**
         * Show or hide the loading overlay
         *
         * @since 3.0
         * @param string action Either show/hide. Optional.
         */
        loadingOverlay: function( action ) {
            var $overlay = $( '#colorbox' ).is( ':visible' ) ? $( '#cboxLoadingOverlay' ) : $( '#mp-loading-overlay' );
            if ( action === undefined ) {
                var action = 'show';
            }

            if ( $overlay.length == 0 ) {
                $( 'body' ).append( '<div id="mp-loading-overlay" style="display:none"></div>' );
                $overlay = $( '#mp-loading-overlay' );
            }

            switch ( action ) {
                case 'show' :
                    $overlay.show( );
                    break;
                case 'hide' :
                    $overlay.hide( );
                    break;
            }
        },
        /**
         * Initialize tooltips
         *
         * @since 3.0
         */
        initToolTips: function( ) {
            $( document ).mptooltip( {
                "items": ".mp_tooltip-help, .mp_tooltip",
                "content": function( ) {
                    var $this = $( this );
                    if ( $this.is( '.mp_tooltip' ) ) {
                        $this = $this.next( '.mp_tooltip_content' );
                    }

                    return $this.html( );
                },
                "position": {
                    "my": "center bottom-10",
                    "at": "center top"
                },
                "hide": 300,
                "show": 300,
                "tooltipClass": "mp_tooltip-opened"
            } );
        },
        /**
         * Make each product in a product grid row the same height
         *
         * @since 3.0
         */
        equalizeProductGrid: function( ) {
            $( '#mp-products.mp_products-grid' ).each( function( ) {
                var $this = $( this );
                //$this.find( '.mp_product_name' ).equalHeights();
                $this.find( '.mp_product_meta' ).equalHeights();
                //$this.find( '.mp_product_details' ).equalHeights();
                //$this.find( '.mp_product' ).equalHeights();
                //$this.find( '.mp_product_name' ).equalHeights();
                //$this.find( '.mp_product_meta' ).equalHeights();
                //$this.find( '.mp_form-buy-product' ).addClass( 'sticky' );
                //$this.find( '.hmedia' ).equalHeights();
            } );

            $( '#mp-related-products .mp_products-grid' ).each( function( ) {
                var $this = $( this );
                $this.find( '.mp_product_meta' ).equalHeights();
                //$this.equalHeights();
            } );
        },
        getViewportSize: function() {
			
			function setMiniCartMaxHeight() {
				
				var viewportHeight = $(window).height(),
					//bodyHeight = $('body').height(),
					//documentHeight = $(document).height(),
					//getNonVisibleSize = (bodyHeight - viewportHeight),
					miniCart = $('#mp-floating-cart'),
					miniCartRibbon = miniCart.find('.mp_mini_cart_ribbon'),
					miniCartRibbonHeight = miniCartRibbon.height(),
					miniCartContent = miniCart.find('.mp_mini_cart_content'),
					miniCartContentHeight = miniCartContent.height(),
					miniCartHeight = (miniCartRibbonHeight + miniCartContentHeight),
					miniCartMaxHeight = (viewportHeight - (miniCartRibbonHeight * 2) - 100);
				
				if( miniCartHeight > viewportHeight || miniCartHeight > miniCartMaxHeight ) {
					
					miniCart.each(function() {
						var $this = $(this);
						
						$this.find('.mp_mini_cart_items').css({
							'margin-top' : '50px'
						});
						
						$this.find('.mp_button-mini-cart').css({
							"position" : "absolute",
							"top" : "15px",
							"left" : 0,
							"right" : 0,
							"margin" : "auto 30px"
						});
						
					});
					
				}
				
				miniCartContent.css({
					"max-height" : miniCartMaxHeight + 'px'
				});
				
			}
			
			setMiniCartMaxHeight();
			
			$(window).on('resize', function() {
				setMiniCartMaxHeight();
			});
			
        },
        /**
         * Initialize select2 elements
         *
         * @since 3.0
         */
		initSelect2: function( ) {
			$( 'select.mp_select2' ).not( '.select2-offscreen' ).mp_select2( {
				"dropdownCssClass": "mp_select2",
				"dropdownAutoWidth": 1,
				"minimumResultsForSearch": -1	// hide the search box
			} );
			$( 'select.mp_select2_search' ).not( '.select2-offscreen' ).mp_select2( {
				"dropdownCssClass": "mp_select2",
				"dropdownAutoWidth": 1
			} );
		},
        /**
         * Initialize order look up
         *
         * @since 3.0
         */
        initOrderLookup: function( ) {
            var $form = $( '#mp-order-lookup-form' );
            var $btn = $form.find( '[type="submit"]' );
            var $input = $form.find( 'input[type="text"]' );
            $form.on( 'submit', function( e ) {
                e.preventDefault( );
                if ( $btn.is( ':hidden' ) ) {
                    // Already searching for an order - bail
                    return;
                }

                var data = $form.serialize( );
                $btn.ajaxLoading( 'show' );
                $input.prop( 'disabled', true );
                $.post( $form.attr( 'action' ), data ).done( function( resp ) {
                    if ( resp.success ) {
                        window.location.href = resp.data.redirect_url;
                    } else {
                        var $tooltip = $input.prev( '.mp_tooltip' );
                        $btn.ajaxLoading( 'hide' );
                        $input.prop( 'disabled', false );
                        if ( $tooltip.length == 0 ) {
                            $input.before( '<div class="mp_tooltip"></div>' );
                            $tooltip = $input.prev( '.mp_tooltip' );
                            $tooltip.mptooltip( {
                                items: ".mp_tooltip",
                                tooltipClass: "error",
                                position: {
                                    of: $input,
                                    my: "center bottom-10",
                                    at: "center top"
                                },
                                hide: 300,
                                show: 300
                            } );
                        }

                        $tooltip.mptooltip( 'option', 'content', resp.data.error_message );
                        $tooltip.mptooltip( 'open' );
                    }
                } );
            } );
        },
        /**
         * Initialize content tabs on the single product template
         *
         * @since 3.0
         */
        initProductTabs: function( ) {

            $( 'body' ).on( 'click', '.mp_product_meta a.more-link', function( e ) {
                e.preventDefault( );
                // $( '#mp-single-product a.mp_product_tab_label_link.mp-product-overview' ).click( );
                $( '.mp-single-product a.mp_product_tab_label_link.mp-product-overview' ).click( );
                $( 'html, body' ).animate( {
                    scrollTop: $( "a.mp_product_tab_label_link.mp-product-overview" ).offset( ).top - 30
                }, 500 );
            } );
            $( '.mp_product_tab_label_link' ).click( function( e ) {
                e.preventDefault( );
                var $this = $( this ),
                    $tab = $this.parent( ),
                    $target = $( $this.attr( 'href' ) );
                $tab.addClass( 'current' ).siblings( ).removeClass( 'current' );
                //$target.show().siblings( '.mp_product_tab_content' ).hide();
                $target.addClass( 'mp_product_tab_content-current' ).siblings( '.mp_product_tab_content' ).removeClass( 'mp_product_tab_content-current' );
                //equal_heights( $( '#mp-related-products .mp_products-grid .mp_product_meta' ) );
            } );
            if ( window.location.hash.length > 0 ) {
                var $target = $( '[href="' + window.location.hash + '"]' );
                if ( $target.length > 0 ) {
                    $target.trigger( 'click' );
                }
            }
        },
        /**
         * Trigger events after an ajax request
         *
         * @since 3.0
         * @param string event The base event string.
         * @param object resp The ajax response object.
         * @param object $scope Optional, the scope for triggered events. Defaults to document.
         */
        ajaxEvent: function( event, resp, $scope ) {
            if ( $scope === undefined ) {
                var $scope = $( document );
            }

            var successEvent = event + '/success';
            var errorEvent = event + '/error';
            /**
             * Fires whether the response was successful or not
             *
             * @since 3.0
             * @param object The response data.
             */
            $scope.trigger( event, [ resp ] );
            if ( resp.success ) {
                /**
                 * Fires on success
                 *
                 * @since 3.0
                 * @param object The response data object.
                 */
                $scope.trigger( successEvent.replace( '//', '/' ), [ resp.data ] );
            } else {
                var message = ( resp.data === undefined ) ? '' : resp.data.message;
                /**
                 * Fires on error
                 *
                 * @since 3.0
                 * @param string Any applicable error message.
                 */
                $scope.trigger( errorEvent.replace( '//', '/' ), [ message ] );
            }
        },
        /**
         * Initialize product image lightbox
         *
         * @since 3.0
         */
        initImageLightbox: function( ) {
            $( '.mp_product_image_link' ).filter( '.mp_lightbox' ).colorbox( {
                maxWidth: "90%",
                maxHeight: "90%",
                close: "&times;"
            } );
        },
        /**
         * Initialize product filters/pagination
         *
         * @since 3.0
         */
        initProductFiltersPagination: function( ) {
            var $form = $( '#mp-products-filter-form' );
            $form.on( 'change', 'select', function( e ) {
                var $this = $( this );
                // Redirect if product category dropdown changed
                if ( 'product_category' == $this.attr( 'name' ) ) {
                    var val = $this.val( );
                    marketpress.loadingOverlay( 'show' );
                    if ( typeof ( mp_i18n.productCats[ val ] ) == 'undefined' ) {
                        window.location.href = mp_i18n.productsURL;
                    } else {
                        window.location.href = mp_i18n.productCats[ val ];
                    }

                    return;
                } else {
                    marketpress.updateProductList( );
                }
            } );
            /*$( '#mp_product_nav' ).parent( ).on( 'click', '#mp_product_nav a', function( e ) {
                e.preventDefault( );
                var $this = $( this );
                var href = $this.attr( 'href' );
                var query = marketpress.unserialize( href );
                $form.find( 'input[name="page"]' ).val( query.paged );
                marketpress.updateProductList( );
            } );*/
        },
        /**
         * Update product list
         *
         * @since 3.0
         * @access public
         */
        updateProductList: function( ) {
            var $form = $( '#mp-products-filter-form' );
            var data = $form.serialize( );
            var url = mp_i18n.ajaxurl + '?action=mp_update_product_list';
            marketpress.loadingOverlay( 'show' );
            $.post( url, data ).done( function( resp ) {
                marketpress.loadingOverlay( 'hide' );
                $( '.mp_listings_nav' ).remove( );
                $( '#mp-products' ).replaceWith( resp );
                mp_cart.initCartListeners( );
            } );
        },
        /**
         * Unserialize a string
         *
         * @since 3.0
         * @param string str A serialized string or a url containing a querystring
         * @return object
         */
        unserialize: function( str ) {
            if ( str.indexOf( '?' ) >= 0 ) {
                var strParts = str.split( '?' );
                str = strParts[1];
            }

            var dataPairs = str.split( '&' );
            var obj = { };
            $.each( dataPairs, function( index, value ) {
                var tmp = value.split( '=' );
                obj[ tmp[0] ] = tmp[1];
            } );
            return obj;
        },
        /**
         * Initialize global product filters/pagination
         *
         * @since 3.0
         */
        initGlobalProductFiltersPagination: function( ) {
            var $form = $( '#mp_global_product_list_refine' );
            $form.on( 'change', 'select', function( e ) {
                var $this = $( this );
                // Redirect if product category dropdown changed
                if ( 'product_category' == $this.attr( 'name' ) ) {
                    var val = $this.val( );
                    marketpress.loadingOverlay( 'show' );
                    if ( typeof ( mp_global.cat_urls[ val ] ) == 'undefined' ) {
                        window.location.href = mp_global.cat_url;
                    } else {
                        window.location.href = mp_global.cat_urls[ val ];
                    }

                    return;
                } else {
                    marketpress.updateGlobalProductList( );
                }
            } );
        },
        /**
         * Update global product list
         *
         * @since 3.0
         * @access public
         */
        updateGlobalProductList: function( ) {
            var $form = $( '#mp_global_product_list_refine' );
            var data = $form.serialize( );
            var url = mp_i18n.ajaxurl + '?action=mp_global_update_product_list';
            marketpress.loadingOverlay( 'show' );
            $.post( url, data ).done( function( resp ) {
                marketpress.loadingOverlay( 'hide' );
                $( '.mp_listings_nav' ).remove( );
                $( '.mp_global_product_list_widget .mp_products' ).replaceWith( resp );
                mp_cart.initCartListeners( );
            } );
        },
    };
}( jQuery ) );
jQuery( document ).ready( function( ) {
    marketpress.initSelect2( );
    marketpress.initProductTabs( );
    marketpress.initToolTips( );
    marketpress.initOrderLookup( );
    marketpress.initImageLightbox( );
    marketpress.initProductFiltersPagination( );
    marketpress.initGlobalProductFiltersPagination();
    marketpress.getViewportSize();
} );
window.onload = function( ) {
    marketpress.equalizeProductGrid( );
    //marketpress.getViewportSize();
}