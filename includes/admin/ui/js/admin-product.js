jQuery( document ).ready( function( $ ) {

    $( '.mp-variation-row .mp-variation-attribute-name' ).live( 'keyup', function( e ) {
        if ( $( this ).val() == '' ) {
            $( this ).addClass( 'mp_variation_invalid' );
        } else {
            $( this ).removeClass( 'mp_variation_invalid' );
        }
    } );

    $( '.mp-variation-row .text-wrap' ).live( 'click', function( e ) {

        //if ( $( this ).val() == '' || $( this ).val() == '[]' ) {
        //  $( this ).parent().find( '.mp-variation-field-required' ).addClass( 'mp_variation_invalid' );
        //} else {
        $( this ).parent().find( '.mp-variation-field-required' ).removeClass( 'mp_variation_invalid' );
        //}
    } );

    $( 'textarea.variation_values' ).live( 'keyup', function( e ) {

        var keyCode = e.keyCode || e.which;

        if ( keyCode == '9' ) {

            // the prevent default allow you to stay in focus in the input and keep adding tags in
            //var press = jQuery.Event( "keyup" );
            //press.which = '13';
            //press.originalEvent = KeyboardEvent;
            // $( 'textarea.variation_values' ).val( $( 'textarea.variation_values' ).val() );
            //$( 'textarea.variation_values' ).trigger( press );
            //alert($( 'textarea.variation_values' ).val());
            e.preventDefault();
        }
    } );

    $( '#poststuff' ).append( '<div class="mp-admin-overlay"><div class="mp-variation-loading-spin"></div><div class="mp-variation-loading-message">' + mp_product_admin_i18n.creating_vatiations_message + '</div></div>' );

    $( '#mp-product-type-select' ).on( 'change', function() {
        if ( $( this ).val() == 'external' || $( this ).val() == 'digital' ) {
            $( '[name="charge_shipping"]' ).attr( 'checked', false );
        }
    } );

    /* $( '.mp_variations_select' ).live( 'change', function() {
     var has_variations = $( this );
     
     
     
     if ( $( '.mp_variations_box' ).length == 0 ) {//it's not auto-draft, hide variation content box
     
     if ( has_variations.val() == 'yes' ) {
     $( '#postdivrich' ).hide();
     exit;
     } else {
     
     $( '#postdivrich' ).css( 'opacity', '0' );
     $( '#postdivrich' ).css( 'visibility', 'hidden' );
     $( '#postdivrich' ).css( 'display', 'block' );
     
     $( 'html, body' ).animate( {
     scrollTop: $( ".meta-box-sortables.ui-sortable" ).offset().top + 50
     }, 100, function() {
     $( '#postdivrich' ).css( 'visibility', 'visible' );
     $( "#postdivrich" ).fadeTo( 400, 1, function() {
     } );
     } );
     
     
     exit;
     }
     } else {
     
     }
     } );*/

    function mp_variation_message( ) {
        $( '.mp-variation-loading-spin' ).css( {
            position: 'fixed',
            left: ( $( '.mp-admin-overlay' ).width( ) - $( '.mp-variation-loading-spin' ).outerWidth( ) ) / 2,
            top: ( $( '.mp-admin-overlay' ).height( ) - $( '.mp-variation-loading-spin' ).outerHeight( ) ) / 2
        } );
        var new_top = parseInt( $( '.mp-variation-loading-spin' ).css( 'top' ) );
        new_top = new_top + 50;
        $( '.mp-variation-loading-message' ).css( {
            position: 'absolute',
            left: ( $( '.mp-admin-overlay' ).width( ) - $( '.mp-variation-loading-message' ).outerWidth( ) ) / 2,
            top: new_top
        } );
    }

    $( window ).resize( function( ) {
        mp_variation_message();
    } );

    $( window ).resize( );
    /* Variations product name set */
    $( '.mp_variations_product_name' ).html( $( '#title' ).val( ) );
    $( '#title' ).keyup( function( ) {
        $( '.mp_variations_product_name' ).html( $( '#title' ).val( ) );
    } );

    $( '.repeat' ).each( function( ) {
        $( this ).repeatable_fields( );
    } );

	$( '.mp-variation-add-all' ).live( 'click', function( e ) {
		e.preventDefault();
		var $variation_tags_textarea = $( this ).parents( '.variation-row' ).find( 'textarea.variation_values' ),
		variation_tags = $( this ).parents( '.variation-row' ).find( '.mp_product_attributes_select option:selected' ).attr( 'data-tags' ),
		variation_tags_array = variation_tags.split( ',' ),
		existing_tags = $variation_tags_textarea.textext()[0].tags()._formData,
		all_tags = jQuery.grep(variation_tags_array, function( n, i ) {
			return $.inArray(n, existing_tags) === -1;
		});

		$variation_tags_textarea.textext()[0].tags().addTags( all_tags );
	} );

	$( '.mp_product_attributes_select' ).live( 'change', function( ) {
		var $variation_tags_textarea = $( this ).parents( '.variation-row' ).find( 'textarea.variation_values' );
		$variation_tags_textarea.textext()[0].input().unbind('getSuggestions');
		if ( $( this ).val( ) == '-1' ) {
			$( this ).parent( ).find( '.mp-variation-attribute-name' ).show( );
			$( this ).parent( ).find( '.mp-variation-add-all' ).hide( );
		} else {
			var variation_tags = $( this ).find( ':selected' ).attr( 'data-tags' );			
			$( this ).parent( ).find( '.mp-variation-attribute-name' ).hide( );
			if( variation_tags !== "" ) {
				$( this ).parent( ).find( '.mp-variation-add-all' ).show( );				
				var variation_tags_array = variation_tags.split( ',' );
				$variation_tags_textarea.textext()[0].input().bind('getSuggestions', function(e, data) {
				    var textext = $(e.target).textext()[0],
				        query = (data ? data.query : '') || '',
						existing_tags =textext.tags()._formData,
				        suggestions = jQuery.grep(variation_tags_array, function( n, i ) {
							return $.inArray(n, existing_tags) === -1;
						});
				    $(this).trigger(
				        'setSuggestions',
				        { result : textext.itemManager().filter(suggestions, query) }
				    );
				});
			}
		}
	} );

    $( '.select_attributes_filter a' ).live( 'click', function( event ) {
        $( '.select_attributes_filter a' ).removeClass( 'selected' );
        if ( $( this ).hasClass( 'selected' ) ) {
            $( this ).removeClass( 'selected' );
        } else {
            $( this ).addClass( 'selected' );
        }

//Select All link clicked
        if ( $( this ).hasClass( 'select_all_link' ) ) {
            $( '#cb-select-all' ).prop( "checked", true );
            $( '.check-column .check-column-box' ).prop( "checked", true );
        }

//Select None link clicked
        if ( $( this ).hasClass( 'select_none_link' ) ) {
            $( '#cb-select-all' ).prop( "checked", false );
            $( '.check-column .check-column-box' ).prop( "checked", false );
        }

//Variation filter clicked
        if ( !$( this ).hasClass( 'select_none_link' ) && !$( this ).hasClass( 'select_all_link' ) ) {
            var term_id = $( this ).parent( ).data( 'term-id' );
            $( '.check-column .check-column-box' ).prop( "checked", false );
            $( '.variation_term_' + term_id ).each( function( index ) {
                $( this ).closest( 'tr' ).find( '.check-column .check-column-box' ).prop( "checked", true );
            } );
        }

        event.preventDefault( );
    } );

    $( ".select_attributes_filter a" ).live( 'focus', function( event ) {
        $( this ).blur( );
    } );

    $( '#mp_make_combinations, #publishing-action #publish' ).live( 'click', function( event ) {//

        var caller_id = $( this ).attr( 'id' );

        if ( $( '.mp_variations_box' ).is( ":visible" ) ) {

            var variation_errors = 0;

            $( '.mp-variation-row .mp-variation-attribute-name' ).each( function( index ) {

                if ( $( this ).is( ":visible" ) ) {
                    if ( $( this ).val() == '' ) {
                        $( this ).addClass( 'mp_variation_invalid' );
                        variation_errors++;
                    } else {
                        $( this ).removeClass( 'mp_variation_invalid' );
                    }
                }

            } );

            $( '.mp-variation-row .text-wrap input[type="hidden"]' ).each( function( index ) {
                if ( $( this ).val() == '' || $( this ).val() == '[]' ) {
                    $( this ).parent().find( '.mp-variation-field-required' ).addClass( 'mp_variation_invalid' );
                    variation_errors++;
                } else {
                    $( this ).parent().find( '.mp-variation-field-required' ).removeClass( 'mp_variation_invalid' );
                }
            } );


            if ( variation_errors == 0 ) {

//alert($( '#original_publish' ).val());
                if ( $( '#original_publish' ).val() == 'Publish' ) {
//$( '.mp-admin-overlay' ).show();
                    $( '#save-post' ).removeAttr( 'disabled' );
                    //$( '#save-post' ).prop( 'disabled', false );
                    $( '#save-post' ).click();
                    //mp_variation_message();
                }

                if ( $( '#original_publish' ).val() == 'Update' ) {

//$( '.mp-admin-overlay' ).show();
                    if ( caller_id == 'mp_make_combinations' ) {
                        $( '#publish' ).removeAttr( 'disabled' );
                        //$( '#publish' ).prop( 'disabled', false );
                        $( '#publish' ).click();
                    }
                    //mp_variation_message();
                }
            } else {
                event.preventDefault();
                $( 'html, body' ).animate( {
                    scrollTop: $( ".mp_variations_title" ).offset().top + 50
                }, 100 );
            }

            if ( caller_id == 'mp_make_combinations' ) {
                event.preventDefault();
            }

        }

    } );

    $( '.mp-add-new-variation' ).click();

} );
/* INLINE EDIT */

jQuery( document ).ready( function( $ ) {

    $.fn.selectRange = function( start, end ) {
        return this.each( function( ) {
            if ( this.setSelectionRange ) {
                this.focus( );
                this.setSelectionRange( start, end );
            } else if ( this.createTextRange ) {
                var range = this.createTextRange( );
                range.collapse( true );
                range.moveEnd( 'character', end );
                range.moveStart( 'character', start );
                range.select( );
            }
        } );
    };
    $.fn.inlineEdit = function( replaceWith, connectWith ) {
        var inline_icon_edit = '<span class="inline-edit-icon"><i class="fa fa-pencil fa-lg"></i></span>';
        $( this ).hover( function( ) {
            $( this ).append( inline_icon_edit );
            $( this ).parent( ).find( '.currency' ).hide( );
        }, function( ) {
            $( this ).find( '.inline-edit-icon' ).remove( );
            if ( $( this ).parent( ).find( '.currency' ).hasClass( '.no_currency' ) ) {
                //Currency shouln't be shown
            } else {
                $( this ).parent( ).find( '.currency' ).show( );
            }
        } );

        $( this ).on( 'click', function( ) {

            var orig_val = $( this ).html( );
            orig_val = orig_val.replace( inline_icon_edit, "" );
            $( replaceWith ).val( $.trim( orig_val ) );
            var post_id = $( this ).closest( 'tr' ).find( '.check-column .check-column-box' ).val( );
            var data_meta = $( this ).attr( 'data-meta' );
            var data_sub_meta = $( this ).attr( 'data-sub-meta' );
            var data_type = $( this ).closest( 'td' ).attr( 'data-field-type' );
            var data_default = $( this ).attr( 'data-default' );
            var elem = $( this );
            elem.hide( );
            elem.after( replaceWith );
            replaceWith.focus( );
            var len = $( replaceWith ).val( ).length * 2; //has to be * 2 because how Opera counts carriage returns

            $( replaceWith ).selectRange( len, len );
            replaceWith.blur( function( ) {

                if ( $( this ).val( ) != "" ) {

                    $( this ).parent( ).find( '.currency' ).removeClass( '.no_currency' );
                    $( this ).parent( ).find( '.currency' ).show();
                    connectWith.val( $( this ).val( ) ).change( );
                    if ( data_type == 'number' ) {
                        var numeric_value = $( this ).val( ).trim( );
                        numeric_value = numeric_value.replace( ",", "." ); //convert comma to dot

                        // If the user enters a percentage, calculate the sale price
                        if( data_meta == 'sale_price_amount' && numeric_value.indexOf('%') == numeric_value.length -1 && parseFloat( numeric_value ) <= 100 ) {
                            numeric_value = parseFloat( numeric_value );
                            var original_value = parseFloat( elem.closest( 'tr' ).find( 'span.original_value[data-meta="regular_price"]' ).html( ) );
                            numeric_value = original_value - ( ( numeric_value / 100 ) * original_value );
                            numeric_value = '' + numeric_value;
                        }

                        numeric_value = numeric_value.replace( /[^\d.-]/g, '' ); //remove any non numeric value

                        if ( $.isNumeric( numeric_value ) ) {
                            elem.text( numeric_value );
                        } else {
                            if(numeric_value === '-' || numeric_value === '∞') {
								if( numeric_value === '∞' ) { 
									elem.text( '∞' )
								} else {
									elem.text( '-' )
								}
								numeric_value = '';
							} else {
								elem.text( 0 );
							}
                        }
						
						if ( $( this ).parent().hasClass( 'field_editable' ) && $( this ).parent().hasClass( 'field_editable_sale_price_amount' ) ) {
                            if( numeric_value !== '' ) {
								var reg_price = $( this ).parent().parent();
								reg_price.find( '.field_editable.field_editable_price' ).addClass( 'mp_strikethrough' );
							}
                        }

                        save_inline_post_data( post_id, data_meta, numeric_value, data_sub_meta );
                    } else {
                        elem.text( $( this ).val( ) );
                        save_inline_post_data( post_id, data_meta, $( this ).val( ), data_sub_meta );
                    }
                } else {

                    if ( $( this ).parent().hasClass( 'field_editable' ) && $( this ).parent().hasClass( 'field_editable_sale_price_amount' ) ) {
                        var reg_price = $( this ).parent().parent();
                        reg_price.find( '.field_editable.field_editable_price' ).removeClass( 'mp_strikethrough' );
                    }

                    $( this ).parent( ).find( '.currency' ).addClass( '.no_currency' );
                    $( this ).parent( ).find( '.currency' ).hide();
                    elem.text( data_default );
                    save_inline_post_data( post_id, data_meta, '', data_sub_meta );
                }

                $( this ).remove( );
                elem.show( );
            } );
        } );
    };
    $( ".original_value" ).each( function( index ) {
        $( this ).inlineEdit( $( '<input name="temp" class="mp_inline_temp_value" type="text" value="" />' ), $( 'input.editable_value' ) ); //' + $.trim( $( this ).html( ) ) + '
    } );
    $( ".mp_inline_temp_value" ).live( 'keyup', function( e ) {
        if ( e.keyCode == 13 ) {
            $( this ).blur( );
        }
        e.preventDefault( );
    } );
    $( '.mp_variations_table_box [name="selected_variation[]"]' ).live('keydown', function( e ) {
		if ( e.keyCode == 9 ) {
			e.preventDefault( );
			var parentContainer = $( this ).parent( 'th' );
			var nextContainer = $( this ).parent( 'th' ).next().next( 'td.field_editable' );
			nextContainer.find( '.original_value' ).trigger( 'click' );
			
           $( this ).blur( );
        }
    });
	$( ".mp_inline_temp_value" ).live( 'keydown', function( e ) {
		if ( e.keyCode == 9 ) {
			e.preventDefault( );
			
			var parentContainer = $( this ).parent( );
			var nextContainer = $( this ).parent( ).next( 'td' );
			nextContainer.find( '.original_value' ).trigger( 'click' );
			
            $( this ).blur( );
        }
	});
    $( '#mp-product-price-inventory-variants-metabox' ).keydown( function( event ) {//window
        if ( event.keyCode == 13 ) {
            event.preventDefault( );
            return false;
        }
    } );
    $( '#variant_bulk_doaction' ).click( function( ) {
        var selected_variant_bulk_action = $( '.variant_bulk_selected' ).val( );
        var checked_variants = $( ".check-column-box:checked" ).length;
        if ( selected_variant_bulk_action == 'variant_update_prices' ) {

            if ( checked_variants > 0 ) {
                var mp_bulk_price_start_val = 0;
                $( '.check-column-box:checked' ).each( function( ) {
                    mp_bulk_price_start_val = $.trim( $( this ).closest( 'tr' ).find( '.original_value.field_subtype_price' ).html( ) );
                    mp_bulk_price_start_val = mp_bulk_price_start_val.replace( ",", "" )
                } );
                if ( checked_variants > 1 ) {
                    $( '#mp_bulk_price_title' ).html( mp_product_admin_i18n.bulk_update_prices_multiple_title );
                } else {
                    $( '#mp_bulk_price_title' ).html( mp_product_admin_i18n.bulk_update_prices_single_title );
                }

                $( '.mp_variants_selected' ).html( checked_variants );
                if ( $( '.mp_bulk_price' ).val( ) == '' ) {
                    $( '.mp_bulk_price' ).val( mp_bulk_price_start_val );
                }

                $.colorbox( {
                    href: $( '#mp_bulk_price' ),
                    inline: true,
                    opacity: .7,
                    width: 380,
                    height: 235,
                    title: $( '#mp_bulk_price_title' ).html( )
                } );
            }
        }

        if ( selected_variant_bulk_action == 'variant_update_inventory' ) {

            if ( checked_variants > 0 ) {
                var mp_bulk_inventory_start_val = 0;
                $( '.check-column-box:checked' ).each( function( ) {
                    mp_bulk_inventory_start_val = $.trim( $( this ).closest( 'tr' ).find( '.original_value.field_subtype_inventory' ).html( ) );
                    mp_bulk_inventory_start_val = mp_bulk_inventory_start_val.replace( ",", "" );
                } );
                if ( isNaN( mp_bulk_inventory_start_val ) ) {
                    mp_bulk_inventory_start_val = 10; //example value
                }

                if ( checked_variants > 1 ) {
                    $( '#mp_bulk_inventory_title' ).html( mp_product_admin_i18n.bulk_update_inventory_multiple_title );
                } else {
                    $( '#mp_bulk_inventory_title' ).html( mp_product_admin_i18n.bulk_update_inventory_single_title );
                }

                $( '.mp_variants_selected' ).html( checked_variants );
                if ( $( '.mp_bulk_inventory' ).val( ) == '' ) {
                    $( '.mp_bulk_inventory' ).val( mp_bulk_inventory_start_val );
                }

                $.colorbox( {
                    href: $( '#mp_bulk_inventory' ),
                    inline: true,
                    opacity: .7,
                    width: 420,
                    height: 235,
                    title: $( '#mp_bulk_inventory_title' ).html( )
                } );
            }
        }

        if ( selected_variant_bulk_action == 'variant_update_images' ) {

            if ( checked_variants > 0 ) {

                wp.media.string.props = function( props, attachment )
                {
                    //console.log( props );
                    //placeholder_image.attr( 'src', props.url );
                    //placeholder_image.attr( 'width', 30 );
                    //placeholder_image.attr( 'height', 30 );
                    //save_inline_post_data( post_id, '_thumbnail_id', props.id, '' );

                    $( '.check-column-box:checked' ).each( function( ) {

                        var placeholder_image = $( this ).closest( 'tr' ).find( '.mp-variation-image img' );
                        var post_id = $( this ).closest( 'tr' ).find( '.mp-variation-image' ).attr( 'data-post-image-id' );
                        placeholder_image.attr( 'src', attachment.url );
                        placeholder_image.attr( 'width', 30 );
                        placeholder_image.attr( 'height', 30 );
                        save_inline_post_data( post_id, '_thumbnail_id', attachment.id, '' );
                    } );
                    //save_inline_post_data( post_id, '_thumbnail_id', attachment.id, '' );
                }

                wp.media.editor.send.attachment = function( props, attachment )
                {
                    $( '.check-column-box:checked' ).each( function( ) {

                        var placeholder_image = $( this ).closest( 'tr' ).find( '.mp-variation-image img' );
                        var post_id = $( this ).closest( 'tr' ).find( '.mp-variation-image' ).attr( 'data-post-image-id' );
                        placeholder_image.attr( 'src', attachment.url );
                        placeholder_image.attr( 'width', 30 );
                        placeholder_image.attr( 'height', 30 );
                        save_inline_post_data( post_id, '_thumbnail_id', attachment.id, '' );
                    } );
                };
                wp.media.editor.open( this );
                return false;
            }
        }

        if ( selected_variant_bulk_action == 'variant_delete' ) {

            if ( checked_variants > 0 ) {

                if ( checked_variants > 1 ) {
                    $( '#mp_bulk_delete_title' ).html( mp_product_admin_i18n.bulk_delete_multiple_title );
                } else {
                    $( '#mp_bulk_delete_title' ).html( mp_product_admin_i18n.bulk_delete_single_title );
                }

                $( '.mp_variants_selected' ).html( checked_variants );
                $.colorbox( {
                    href: $( '#mp_bulk_delete' ),
                    inline: true,
                    opacity: .7,
                    width: 380,
                    height: 235,
                    title: $( '#mp_bulk_delete_title' ).html( )
                } );
            }
        }

    } );
    $( '.mp-variation-image img' ).on( 'click', function( ) {

        var placeholder_image = $( this );
        var post_id = $( this ).closest( 'td' ).attr( 'data-post-image-id' );
        wp.media.string.props = function( props, attachment )
        {
            //console.log( props );
            //placeholder_image.attr( 'src', props.url );
            //placeholder_image.attr( 'width', 30 );
            //placeholder_image.attr( 'height', 30 );
            //save_inline_post_data( post_id, '_thumbnail_id', props.id, '' );
            placeholder_image.attr( 'src', attachment.url );
            placeholder_image.attr( 'width', 30 );
            placeholder_image.attr( 'height', 30 );
            save_inline_post_data( post_id, '_thumbnail_id', attachment.id, '' );
        }

        wp.media.editor.send.attachment = function( props, attachment )
        {
            //console.log(attachment.id);
            placeholder_image.attr( 'src', attachment.url );
            placeholder_image.attr( 'width', 30 );
            placeholder_image.attr( 'height', 30 );
            save_inline_post_data( post_id, '_thumbnail_id', attachment.id, '' );
        };
        wp.media.editor.open( this );
        return false;
    } );
    function save_inline_post_data( post_id, meta_name, meta_value, sub_meta ) {
        var data = {
            action: 'save_inline_post_data',
            post_id: post_id,
            meta_name: meta_name,
            meta_sub_name: sub_meta,
            meta_value: meta_value,
            ajax_nonce: mp_product_admin_i18n.ajax_nonce
        }

        $.post(
            mp_product_admin_i18n.ajaxurl, data
            ).done( function( data, status ) {
            if ( status == 'success' ) {
                //alert( 'success!' );
            } else {
                //alert( 'fail!' );
                //an error occured
            }
        } );
    }

    jQuery( '.mp_bulk_price' ).on( 'keyup', function( ) {
        if ( jQuery( '.mp_bulk_price' ).val( ) == '' || isNaN( jQuery( '.mp_bulk_price' ).val( ) ) ) {
            jQuery( '.mp_price_controls .save-bulk-form' ).attr( 'disabled', true );
        } else {
            jQuery( '.mp_price_controls .save-bulk-form' ).attr( 'disabled', false );
        }
    } );
    jQuery( '.mp_bulk_inventory' ).on( 'keyup', function( ) {
        if ( jQuery( '.mp_bulk_inventory' ).val( ) !== '' && isNaN( jQuery( '.mp_bulk_inventory' ).val( ) ) ) {
            jQuery( '.mp_inventory_controls .save-bulk-form' ).attr( 'disabled', true );
        } else {
            jQuery( '.mp_inventory_controls .save-bulk-form' ).attr( 'disabled', false );
        }
    } );
    //Price controls
    jQuery( '.mp_popup_controls.mp_price_controls a.save-bulk-form' ).on( 'click', function( e ) {
        //LINK can't disabled, so we have to check
        if ( $( this ).attr( 'disabled' ) == 'disabled' ) {
            e.preventDefault();
            return false;
        }

        var global_price_set = jQuery( '.mp_bulk_price' ).val( );
        parent.jQuery.colorbox.close( );
        $( '.check-column-box:checked' ).each( function( ) {
            $( this ).closest( 'tr' ).find( '.field_subtype_price' ).html( global_price_set );
            $( this ).closest( 'tr' ).find( '.editable_value_price' ).val( global_price_set );
            save_inline_post_data( $( this ).val( ), 'regular_price', global_price_set, '' );
        } );
        return false;
        e.preventDefault( );
    } );
    //Inventory controls
    jQuery( '.mp_popup_controls.mp_inventory_controls a.save-bulk-form' ).on( 'click', function( e ) {
        //LINK can't disabled, so we have to check
        if ( $( this ).attr( 'disabled' ) == 'disabled' ) {
            e.preventDefault();
            return false;
        }

        var global_inventory_set = jQuery( '.mp_bulk_inventory' ).val( );
        if ( global_inventory_set == '' || isNaN( global_inventory_set ) ) {
            global_inventory_set = '&infin;';
        }

        parent.jQuery.colorbox.close( );
        $( '.check-column-box:checked' ).each( function( ) {
            $( this ).closest( 'tr' ).find( '.field_subtype_inventory' ).html( global_inventory_set );
            $( this ).closest( 'tr' ).find( '.editable_value_price' ).val( global_inventory_set );
            save_inline_post_data( $( this ).val( ), 'inventory', global_inventory_set, '' );
        } );
        return false;
        e.preventDefault( );
    } );
    //Delete controls
    jQuery( '.mp_popup_controls.mp_delete_controls a.delete-bulk-form' ).on( 'click', function( e ) {
		e.preventDefault( );
		
        parent.jQuery.colorbox.close( );
        $( '.check-column-box:checked' ).each( function( ) {
            $( this ).closest( 'tr' ).remove( );
            save_inline_post_data( $( this ).val( ), 'delete', '', '' );
        } );
        if ( $( '.check-column-box' ).length == 0 ) {
            save_inline_post_data( $( '[name="post_ID"]' ).val( ), 'delete_variations', '', '' );
			setInterval(function(){ 
				$( '#publish' ).removeAttr( 'disabled' );
				$( '#publish' ).click( );
			}, 500);
        }
        return false;
       
    } )

    /* Close thickbox window on link / cancel click */
    $( '.mp_popup_controls a.cancel' ).live( 'click', function( e ) {
        parent.jQuery.colorbox.close( );
        return false;
        e.preventDefault( );
    } );
    $( "a.open_ajax" ).live( 'click', function( e ) {
        $.colorbox( {
            href: mp_product_admin_i18n.ajaxurl + '?action=mp_variation_popup&variation_id=' + ( $( this ).attr( 'data-popup-id' ) ),
            opacity: .7,
            inline: false,
            //width: 400,
            //height: 460,
            title: $( this ).closest( 'tr' ).find( '.field_more .variation_name' ).html( ),
            onClosed: function( ) {
                $.colorbox.remove( );
            },
            onOpen: function( ) {
            },
            onLoad: function( ) {
                $( '#cboxClose' ).hide();
            },
            onComplete: function( ) {
                $( '#cboxClose' ).show();
            }
        } );
        e.preventDefault( );
        //$.colorbox.remove
        // return false;
    } );
    $( '#variant_add' ).live( 'click', function( e ) {
        var url = mp_product_admin_i18n.ajaxurl + '?action=ajax_add_new_variant';
        $.post( url, {
            action: 'ajax_add_new_variant',
            parent_post_id: $( '#post_ID' ).val( ),
        } ).done( function( data, status ) {
            var response = jQuery.parseJSON( data );
            if ( response ) {
                if ( response.type == true ) {
                    $.colorbox( {
                        href: mp_product_admin_i18n.ajaxurl + '?action=mp_variation_popup&variation_id=' + response.post_id + '&new_variation',
                        opacity: .7,
                        inline: false,
                        width: 400,
                        height: 460,
                        title: '',
                        onClosed: function( ) {
                            $.colorbox.remove( );
                            //tinyMCE.execCommand("mceRepaint");
                        },
                        onLoad: function( ) {

                        }
                    } );
                } else {
                    alert( 'An error occured while trying to create a new variation post' );
                }
            }

        } );
        e.preventDefault( );
    } );
    $( 'body' ).live( 'mp-variation-popup-loaded', function( ) {

        $( '#variation_popup a.remove_popup_image' ).on( 'click', function( e ) {

            var placeholder_image = $( '#variation_popup .mp-variation-image img' );
            var post_id = $( '#variation_id' ).val( );
            var table_placeholder_image = $( '#post-' + post_id ).find( '.mp-variation-image img' );
            table_placeholder_image.attr( 'src', mp_product_admin_i18n.placeholder_image );
            table_placeholder_image.attr( 'width', 30 );
            table_placeholder_image.attr( 'height', 30 );
            placeholder_image.attr( 'src', mp_product_admin_i18n.placeholder_image );
            placeholder_image.attr( 'width', 75 );
            placeholder_image.attr( 'height', 75 );
            save_inline_post_data( post_id, '_thumbnail_id', '', '' );
            e.preventDefault( );
        } );
        $( '#variation_popup .mp-variation-image img' ).on( 'click', function( ) {
            var placeholder_image = $( this );
            var post_id = $( '#variation_id' ).val( );
            var table_placeholder_image = $( '#post-' + post_id ).find( '.mp-variation-image img' );
            wp.media.string.props = function( props, attachment )
            {
                table_placeholder_image.attr( 'src', attachment.url );
                table_placeholder_image.attr( 'width', 30 );
                table_placeholder_image.attr( 'height', 30 );
                placeholder_image.attr( 'src', attachment.url );
                placeholder_image.attr( 'width', 75 );
                placeholder_image.attr( 'height', 75 );
                save_inline_post_data( post_id, '_thumbnail_id', attachment.id, '' );
            }

            wp.media.editor.send.attachment = function( props, attachment )
            {
                table_placeholder_image.attr( 'src', attachment.url );
                table_placeholder_image.attr( 'width', 30 );
                table_placeholder_image.attr( 'height', 30 );
                placeholder_image.attr( 'src', attachment.url );
                placeholder_image.attr( 'width', 75 );
                placeholder_image.attr( 'height', 75 );
                save_inline_post_data( post_id, '_thumbnail_id', attachment.id, '' );
            };
            wp.media.editor.open( this );
            return false;
        } );
        $( '#file_url_button' ).on( 'click', function( ) {

            var field = $( this ).closest( '#file_url' );
            wp.media.string.props = function( props, attachment )
            {
                $( '#file_url' ).val( attachment.url );
            }

            wp.media.editor.send.attachment = function( props, attachment )
            {
                $( '#file_url' ).val( attachment.url );
            };
            wp.media.editor.open( this );
            return false;
        } );
        $( '.fieldset_check' ).each( function( ) {
            var controller = $( this ).find( '.has_controller' );
            if ( controller.is( ':checked' ) ) {
                $( this ).find( '.has_area' ).show( );
            } else {
                $( this ).find( '.has_area' ).hide( );
            }
        } );
        $( '.mp-date' ).each( function( ) {
            var $this = $( this );
            $this.datepicker( {
                "dateFormat": "yy-mm-dd", //mp_product_admin_i18n.date_format,
            } ).keyup( function( e ) {
                if ( e.keyCode == 8 || e.keyCode == 46 ) {
                    $.datepicker._clearDate( this );
                }
            } );
            ;
        } );
        var variation_content_type = $( "input[name='variation_content_type']:checked" ).val( );
        if ( variation_content_type == 'html' ) {
            $( '.variation_description_button' ).show( );
            $( '.variation_content_type_plain' ).hide( );
        } else {//plain text
            $( '.variation_description_button' ).hide( );
            $( '.variation_content_type_plain' ).show( );
        }

        $( "input[name='variation_content_type']" ).live( 'change', function( ) {
            var variation_content_type = $( "input[name='variation_content_type']:checked" ).val( );
            if ( variation_content_type == 'html' ) {
                $( '.variation_description_button' ).show( );
                $( '.variation_content_type_plain' ).hide( );
            } else {//plain text
                $( '.variation_description_button' ).hide( );
                $( '.variation_content_type_plain' ).show( );
            }
        } );

        $target = $('#variation_popup');

        // Set a 10% discount automatically and avoid validation messages
        //$target.on( 'change', 'input[name="has_sale"]', function() {
        //    if( $( this ).is( ":checked" ) && !isFinite( percentage_discount ) ) {
        //        var percentage_discount = parseFloat( $target.find("input[name='sale_price\\[percentage\\]']").val() );
        //        $target.find("input[name='sale_price\\[percentage\\]']").val( '10' ).trigger("input");
        //    }
        //});

        $target.on('input', 'input', function() {
            var price = parseFloat( $target.find("input[name='regular_price']").val() );
            var sale_price = parseFloat( $target.find("input[name='sale_price\\[amount\\]']").val() );
            var percentage_discount = parseFloat( $target.find("input[name='sale_price\\[percentage\\]']").val() );

            switch($(this).attr('name')) {
                case 'regular_price':
                    var new_percentage = ( 100 - ( ( 100 / price ) * sale_price ) );
                    if(isFinite(new_percentage) && new_percentage >= 0.0) {
                        $target.find("input[name='sale_price\\[percentage\\]']").val( new_percentage.toFixed(2) );
                    }else{
                        $target.find("input[name='sale_price\\[percentage\\]']").val( '' );
                    }
                    break;
                case 'sale_price[amount]':
                    var new_percentage = ( 100 - ( ( 100 / price ) * sale_price ) );
                    if(isFinite(new_percentage) && new_percentage >= 0.0) {
                        $target.find("input[name='sale_price\\[percentage\\]']").val( new_percentage.toFixed(2) );
                    }else{
                        $target.find("input[name='sale_price\\[percentage\\]']").val( '' );
                    }
                    break;
                case 'sale_price[percentage]':
                    var new_sale_price = price - ( ( price / 100 ) * percentage_discount );
                    if(isFinite(new_sale_price) && new_sale_price <= price && new_sale_price > 0) {
                        $target.find("input[name='sale_price\\[amount\\]']").val( new_sale_price.toFixed(2) );
                    }else{
                        $target.find("input[name='sale_price\\[amount\\]']").val( '' );
                    }
                    break;
            }
        });
        $target.find("input[name='regular_price']").trigger('input');

        $( "#variation_popup" ).validate( {
            messages: {
                required: mp_product_admin_i18n.message_input_required
            }
        } );
        $( '.mp-numeric' ).each( function( ) {
            $( this ).rules( 'add', {
                number: true,
                messages: {
                    number: mp_product_admin_i18n.message_valid_number_required
                }
            } );
        } );
        $( '.mp-required' ).each( function( ) {
            $( this ).rules( 'add', {
                required: true,
                messages: {
                    required: mp_product_admin_i18n.message_input_required
                }
            } );
        } );

        $( '#variation_popup input, #variation_popup textarea, #variation_popup select' ).live( 'keypress', function( e ) {
        
            $( '#save-variation-popup-data' ).toggleClass( "disabled", !$( 'form#variation_popup' ).valid() );
        
        } );

    } );
    
    $( '.has_controller' ).live( 'change', function( ) {
        var parent_holder = $( this ).closest( '.fieldset_check' );
        var controller = $( this );
        if ( controller.is( ':checked' ) ) {
            parent_holder.find( '.has_area' ).show( );
        } else {
            parent_holder.find( '.has_area' ).hide( );
            if( controller.attr( 'name' ) == 'has_per_order_limit' ) $( "#per_order_limit" ).val( '' );
        }

    } );
    
    $( '#save-variation-popup-data, .variation_description_button' ).live( 'click', function( e ) {
        var form = $( 'form#variation_popup' );
        if( !form.valid() ) {
            e.preventDefault( );
            return;
        }

        $( '.mp_ajax_response' ).attr( 'class', 'mp_ajax_response' );
        $( '.mp_ajax_response' ).html( mp_product_admin_i18n.saving_message );
        $.post(
            //ajax_nonce: mp_product_admin_i18n.ajax_nonce
            //action: 'save_inline_post_data',
            mp_product_admin_i18n.ajaxurl, form.serialize( )
            ).done( function( data, status ) {
            var response = $.parseJSON( data );
            if ( response.status_message !== '' ) {
                $( '.mp_ajax_response' ).html( response.status_message );
                $( '.mp_ajax_response' ).attr( 'class', 'mp_ajax_response' );
                $( '.mp_ajax_response' ).addClass( 'mp_ajax_response_' + response.status );
                if ( response.status == 'success' ) {
                    parent.jQuery.colorbox.close();
                }
                if ( $( '#new_variation' ).val( ) == 'yes' ) {
                    //window.opener.location.reload( false );
                }
                // reload page on both new variation and update variation, as there's no way to dinamically update the variations table
                parent.location.reload( );
            }

            if ( status == 'success' ) {
                //console.log( response );
            } else {
                //alert( 'fail!' );
                //an error occured
            }
        } );
        if ( $( this ).attr( 'id' ) == 'variation_description_button' ) {

        } else {
            e.preventDefault( );
        }
    } );
    
    $target = $('#mp-product-price-inventory-variants-metabox');
    $target.on('input', 'input', function() {
        var price = parseFloat( $target.find("input[name='regular_price']").val() );
        var sale_price = parseFloat( $target.find("input[name='sale_price\\[amount\\]']").val() );
        var percentage_discount = parseFloat( $target.find("input[name='sale_price\\[percentage\\]']").val() );

        switch($(this).attr('name')) {
            case 'regular_price':
                var new_percentage = ( 100 - ( ( 100 / price ) * sale_price ) );
                if(isFinite(new_percentage) && new_percentage >= 0.0) {
                    $target.find("input[name='sale_price\\[percentage\\]']").val( new_percentage.toFixed(2) );
                }else{
                    $target.find("input[name='sale_price\\[percentage\\]']").val( '' );
                }
                break;
            case 'sale_price[amount]':
                var new_percentage = ( 100 - ( ( 100 / price ) * sale_price ) );
                if(isFinite(new_percentage) && new_percentage >= 0.0) {
                    $target.find("input[name='sale_price\\[percentage\\]']").val( new_percentage.toFixed(2) );
                }else{
                    $target.find("input[name='sale_price\\[percentage\\]']").val( '' );
                }
                break;
            case 'sale_price[percentage]':
                var new_sale_price = price - ( ( price / 100 ) * percentage_discount );
                if(isFinite(new_sale_price) && new_sale_price <= price && new_sale_price > 0) {
                    $target.find("input[name='sale_price\\[amount\\]']").val( new_sale_price.toFixed(2) );
                }else{
                    $target.find("input[name='sale_price\\[amount\\]']").val( '' );
                }
                break;
        }
    });
    $target.find("input[name='regular_price']").trigger('input');

    // Set default variant action
    $('#mp-product-price-inventory-variants-metabox').on('click', 'tr:not(".default") a.set-default', function(event) {
    	event.preventDefault();
    	$this = $( this );
    	post_id = $this.attr('data-post-id');
    	meta_name = 'default_variation';
    	meta_value = $this.attr('data-child-id');
    	var data = {
            action: 'save_inline_post_data',
            post_id: post_id,
            meta_name: meta_name,
            meta_value: meta_value,
            ajax_nonce: mp_product_admin_i18n.ajax_nonce
        }

		$this.children('.fa').addClass('fa-pulse');
		$.post(
			mp_product_admin_i18n.ajaxurl, 
			data
		).done( function( data, status ) {
			$this.children('.fa').removeClass('fa-pulse');
			if ( status == 'success' ) {
				$this.parents('tr').addClass('default').siblings('tr').removeClass('default');
			}
		});
    });

} );
