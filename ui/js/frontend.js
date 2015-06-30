( function( $ ) {
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
    $( '<img />' ).get( 0 ).src = mp_i18n.loadingImage;
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
                $( this ).hide( ).after( '<img src="' + mp_i18n.loadingImage + '" alt="" />' );
            } else {
                $( this ).show( ).next( 'img' ).remove( );
            }
        } );
    };
}( jQuery ) );
var marketpress = { };
( function( $ ) {

    function equal_height( obj ) {
        var $this = $( obj );
        $this.equalHeights();
        $this.find( '.mp_product_detail' ).equalHeights();
        $this.find( '.mp_one_product' ).equalHeights();
        $this.find( '.mp_form-buy' ).addClass( 'sticky' );
        $this.find( '.hmedia' ).equalHeights();
    }

    $( window ).resize( function( ) {
        $( '#mp_product_list .mp_grid_row' ).each( function( ) {
            var $this = $( this );
            $this.find( '.mp_product_detail' ).equalHeights();
            $this.find( '.mp_one_product' ).equalHeights();
            $this.find( '.mp_forn-buy' ).addClass( 'sticky' );
            $this.find( '.hmedia' ).equalHeights();
        } );

        $( '#mp-related-products .mp_grid_row' ).each( function( ) {
            equal_height( $( this ) );
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
            $( document ).tooltip( {
                "items": ".mp-tooltip-help, .mp-has-tooltip",
                "content": function( ) {
                    var $this = $( this );
                    if ( $this.is( '.mp-has-tooltip' ) ) {
                        $this = $this.next( '.mp-tooltip-content' );
                    }

                    return $this.html( );
                },
                "position": {
                    "my": "center bottom-10",
                    "at": "center top"
                },
                "hide": 300,
                "show": 300
            } );
        },
        /**
         * Make each product in a product grid row the same height
         *
         * @since 3.0
         */
        equalizeProductGrid: function( ) {
            $( '#mp_product_list .mp_grid_row' ).each( function( ) {
                var $this = $( this );
                $this.find( '.mp_product_detail' ).equalHeights();
                $this.find( '.mp_one_product' ).equalHeights();
                $this.find( '.mp_form-buy' ).addClass( 'sticky' );
                $this.find( '.hmedia' ).equalHeights();
            } );

            $( '#mp-related-products .mp_grid_row' ).each( function( ) {
                var $this = $( this );
                //$this.equalHeights();
            } );
        },
        /**
         * Initialize select2 elements
         *
         * @since 3.0
         */
        initSelect2: function( ) {
            $( 'select.mp_select2' ).not( '.select2-offscreen' ).select2( {
	            "dropdownCssClass": "mp_select2",
                "dropdownAutoWidth": 1,
                "minimumResultsForSearch": -1	// hide the search box
            } );
            $( 'select.mp_select2_search' ).not( '.select2-offscreen' ).select2( {
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
                        var $tooltip = $input.prev( '.mp-tooltip' );
                        $btn.ajaxLoading( 'hide' );
                        $input.prop( 'disabled', false );
                        if ( $tooltip.length == 0 ) {
                            $input.before( '<div class="mp-tooltip"></div>' );
                            $tooltip = $input.prev( '.mp-tooltip' );
                            $tooltip.tooltip( {
                                items: ".mp-tooltip",
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

                        $tooltip.tooltip( 'option', 'content', resp.data.error_message );
                        $tooltip.tooltip( 'open' );
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

            $( '.mp_product_meta a.more-link' ).live( 'click', function( e ) {
                e.preventDefault( );
                $( '#mp-single-product a.mp_product_tab_label_link.mp-product-overview' ).click( );
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
                $target.show( ).siblings( '.mp_product_tab_content' ).hide( );
                equal_height( $( '#mp-related-products .mp_grid_row' ) );
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
                close: "x"
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
                $( '.mp_products_nav' ).remove( );
                $( '#mp_product_list' ).replaceWith( resp );
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
         * Init create account lightbox listeners
         *
         * @since 3.0
         */
        initCreateAccountLightboxListeners: function( ) {
            var $lb = $( '#mp-create-account-lightbox' );
            var $emailLabel = $( 'label[for="mp-create-account-email"]' );
            var $emailInput = $( '#mp-create-account-email' );
            var $submitButton = $lb.find( ':submit' );
            if ( '#mp-create-account' != window.location.hash || 0 == $lb.length ) {
                // Bail
                return false;
            }

            $( document ).ajaxSend( function( evt, jqxhr, settings ) {
                if ( settings.url.indexOf( 'action=mp_check_if_email_exists' ) < 0 ) {
                    return;
                }

                if ( $emailLabel.find( '.mp-loading-placeholder' ).length == 0 ) {
                    $emailLabel.append( '&nbsp;&nbsp;&nbsp;<span class="mp-loading-placeholder"></span>' );
                }

                $emailLabel.find( '.mp-loading-placeholder' ).ajaxLoading( 'show' );
                $emailInput.prop( 'disabled', true );
                $submitButton.prop( 'disabled', true );
            } );
            $( document ).ajaxComplete( function( evt, jqxhr, settings ) {
                if ( settings.url.indexOf( 'action=mp_check_if_email_exists' ) < 0 ) {
                    return;
                }

                $emailLabel.find( '.mp-loading-placeholder' ).ajaxLoading( 'hide' );
                $emailInput.prop( 'disabled', false );
                $submitButton.prop( 'disabled', false );
            } );
            $lb.find( 'form' ).validate( {
                highlight: function( ) {
                    setTimeout( function( ) {
                        $.colorbox.resize( );
                    }, 100 )
                },
                onkeyup: false, // don't validate on keyup as this will send ajax requests on every key stroke!
                submitHandler: function( form ) {
                    var $form = $( form );
                    marketpress.loadingOverlay( 'show' );
                    $.post( $form.attr( 'action' ), $form.serialize( ) ).done( function( resp ) {
                        if ( resp.success ) {
                            window.location.reload( );
                        } else {
                            marketpress.loadingOverlay( 'hide' );
                            alert( resp.data.message );
                        }
                    } );
                },
                unhighlight: function( ) {
                    setTimeout( function( ) {
                        $.colorbox.resize( );
                    }, 100 )
                }
            } );
            $.colorbox( {
                close: "x",
                escKey: false,
                href: $lb,
                inline: true,
                overlayClose: false,
                width: 450
            } );
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
                //we don't filter by taxonomy in global, so just bypass
                marketpress.updateGlobalProductList( );
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
                $( '#mp_product_nav' ).remove( );
                $( '#mp_product_list' ).replaceWith( resp );
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
    marketpress.initCreateAccountLightboxListeners( );
    marketpress.initGlobalProductFiltersPagination();
} );
window.onload = function( ) {
    marketpress.equalizeProductGrid( );
}