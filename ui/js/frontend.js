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
                "show": 300
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
         * Init create account lightbox listeners
         *
         * @since 3.0
         */
        initCreateAccountLightboxListeners: function( ) {
            var $lb = $( '#mp-create-account-form' );
            var $emailLabel = $( 'label[for="mp-create-account-email"]' );
            var $emailInput = $( '#mp-create-account-email' );
            var $submitButton = $lb.find( '.mp_button-create-account' );
            if ( '#mp-create-account-lightbox' != window.location.hash || 0 == $lb.length ) {
                // Bail
                return false;
            }

            $( document ).ajaxSend( function( evt, jqxhr, settings ) {
                if ( settings.url.indexOf( 'action=mp_check_if_email_exists' ) < 0 ) {
                    return;
                }

                if ( $emailLabel.find( '.mp-loading-placeholder' ).length == 0 ) {
                    $emailLabel.append( '<span class="mp-loading-placeholder"></span>' );
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
            //$lb.find( 'form' ).validate( {
	        $lb.validate( {
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
    marketpress.initCreateAccountLightboxListeners( );
    marketpress.initGlobalProductFiltersPagination();
    marketpress.getViewportSize();
} );
window.onload = function( ) {
    marketpress.equalizeProductGrid( );
    //marketpress.getViewportSize();
}