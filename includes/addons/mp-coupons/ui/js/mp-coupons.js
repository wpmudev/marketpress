( function( $ ) {
    /**
     * Apply a coupon code to the cart
     *
     * @since 3.0
     * @param string couponCode The coupon code to apply.
     * @param int The store ID that the coupon comes from.
     * @param object $couponForm The coupon form being applied.
     */
    mp_cart.applyCoupon = function( couponCode, storeID, $couponForm ) {
        if ( 'undefined' == typeof ( couponCode ) ) {
            return false;
        }

        marketpress.loadingOverlay( 'show' );

        // To avoid race conditions, we use the AjaxQ plugin
        $.ajaxq( 'applycoupon', {
            url: mp_cart_i18n.ajaxurl + "?action=mp_coupons_apply",
            type: "POST",
            data: {
                blog_id: storeID,
                coupon_code: "'" + couponCode + "'"
            }
        } )
            .done( function( resp ) {
                marketpress.loadingOverlay( 'hide' );
                marketpress.ajaxEvent( 'mp_cart/apply_coupon', resp, $couponForm );
            } );
    }
}( jQuery ) );

var mp_coupons = { };
( function( $ ) {
    mp_coupons = {
        /**
         * Remove a coupon
         *
         * @since 3.0
         * @param int couponID The internal ID (post ID) of the coupon to remove.
         * @param int storeID The store ID (blog ID) of the coupon to remove.
         */
        remove: function( couponID, storeID ) {
            marketpress.loadingOverlay( 'show' );

            // To avoid race conditions, we use the AjaxQ plugin
            $.ajaxq( 'removecoupon', {
                url: mp_coupons_i18n.ajaxurl + "?action=mp_coupons_remove",
                type: "POST",
                data: {
                    blog_id: storeID,
                    coupon_id: couponID
                }
            } )
                .done( function( resp ) {
                    marketpress.loadingOverlay( 'hide' );
                    marketpress.ajaxEvent( 'mp_coupons/remove', resp );

                    if ( resp.success ) {
                        /*$.each( resp.data.products, function( key, val ) {
                         $( '#mp-cart-item-' + key.escapeSelector() ).replaceWith( val );
                         });*/
                        $( '#mp-cart-resume' ).replaceWith( resp.data.cartmeta );
                        marketpress.initSelect2();
                    } else {
                        $( '#mp-coupon-tooltip-' + storeID )
                            .tooltip( 'option', 'content', resp.data.message )
                            .tooltip( 'option', 'tooltipClass', 'error' )
                            .tooltip( 'open' );
                    }
                } );
        },
        /**
         * Init coupon form listeners
         *
         * @since 3.0
         */
        initCouponFormListeners: function() {
            $( '.mp_coupon_form' ).each( function() {
                var $couponForm = $( this );
                var $couponCode = $couponForm.find( '[name^="mp_cart_coupon"]' );
                var storeID = $couponCode.attr( 'name' ).replace( /[^0-9]/ig, '' );
                var tipID = 'mp-coupon-tooltip-' + storeID;

                // Create tooltip for future use
                $couponCode.before( '<div id="' + tipID + '" />' );
                $( '#' + tipID ).tooltip( {
                    items: "#" + tipID,
                    tooltipClass: "error",
                    content: "",
                    position: {
                        of: $couponCode,
                        my: "center bottom-10",
                        at: "center top"
                    },
                    show: 400,
                    hide: 400
                } );
                var $tooltip = $( '#' + tipID );

                $couponForm
                    // User clicked "apply coupon"
                    .on( 'click', 'button', function( e ) {
                        var couponCode = $couponCode.val().toUpperCase().replace( /[^A-Z0-9]/g, '' );

                        $tooltip.tooltip( 'close' );

                        if ( couponCode.length > 0 ) {
                            mp_cart.applyCoupon( couponCode, storeID, $couponForm );
                        } else {
                            $tooltip
                                .tooltip( 'option', 'content', mp_coupons_i18n.messages.required )
                                .tooltip( 'option', 'tooltipClass', 'error' )
                                .tooltip( 'open' );
                        }
                    } )
                    // An error occurred when applying coupon
                    .on( 'mp_cart/apply_coupon/error', function( e, message ) {
                        $tooltip
                            .tooltip( 'option', 'content', message )
                            .tooltip( 'option', 'tooltipClass', 'error' )
                            .tooltip( 'open' );
                    } )
                    // Coupon was applied successfully
                    .on( 'mp_cart/apply_coupon/success', function( e, data ) {
                        $couponCode.val( '' );

                        /*$.each( data.products, function( index, value ) {
                            $( '#mp-cart-item-' + index.escapeSelector() ).replaceWith( value );
                        } );*/
                        $( '#mp-cart-resume' ).replaceWith( data.cart_meta );
                        marketpress.initSelect2();
                        $tooltip
                            .on( 'tooltipopen.mp_coupons', function( e, ui ) {
                                setTimeout( function() {
                                    $tooltip.tooltip( 'close' );
                                    $tooltip.off( 'tooltipopen.mp_coupons' );
                                }, 4000 );
                            } )
                            .tooltip( 'option', 'content', mp_coupons_i18n.messages.added )
                            .tooltip( 'option', 'tooltipClass', 'success' )
                            .tooltip( 'open' );
                    } );
            } );
        }
    };
}( jQuery ) );

jQuery( document ).ready( function( $ ) {
    mp_coupons.initCouponFormListeners();
} );