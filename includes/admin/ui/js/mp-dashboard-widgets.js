/* INLINE EDIT */

jQuery( document ).ready( function( $ ) {

    $.fn.selectRange = function( start, end ) {
        return this.each( function() {
            if ( this.setSelectionRange ) {
                this.focus();
                this.setSelectionRange( start, end );
            } else if ( this.createTextRange ) {
                var range = this.createTextRange();
                range.collapse( true );
                range.moveEnd( 'character', end );
                range.moveStart( 'character', start );
                range.select();
            }
        } );
    };

    $.fn.inlineEdit = function( replaceWith, connectWith ) {
        var inline_icon_edit = '<span class="inline-edit-icon"><i class="fa fa-pencil fa-lg"></i></span>';

        $( this ).hover( function( ) {
            $( this ).append( inline_icon_edit );
        }, function( ) {
            $( this ).find( '.inline-edit-icon' ).remove( );
        } );

        $( this ).on( 'click', function( ) {

            var orig_val = $( this ).html();
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
            var len = $( replaceWith ).val().length * 2;//has to be * 2 because how Opera counts carriage returns

            $( replaceWith ).selectRange( len, len );

            replaceWith.blur( function( ) {

                if ( $( this ).val( ) != "" ) {
                    connectWith.val( $( this ).val() ).change( );
                    if ( data_type == 'number' ) {
                        var numeric_value = $( this ).val();
                        numeric_value = numeric_value.replace( ",", "" );
                        if ( $.isNumeric( numeric_value ) ) {
                            elem.text( numeric_value );
                        } else {
                            elem.text( 0 );
                        }
                        save_inline_post_data( post_id, data_meta, numeric_value, data_sub_meta );
                    } else {
                        elem.text( $( this ).val( ) );
                        save_inline_post_data( post_id, data_meta, $( this ).val( ), data_sub_meta );
                    }
                } else {
                    elem.text( data_default );
                    save_inline_post_data( post_id, data_meta, '', data_sub_meta );
                }

                $( this ).remove( );
                elem.show( );
            } );
        } );
    };

    $( ".original_value" ).each( function( index ) {
        $( this ).inlineEdit( $( '<input name="temp" class="mp_inline_temp_value" type="text" value="" />' ), $( 'input.editable_value' ) );//' + $.trim( $( this ).html( ) ) + '
    } );

    $( ".mp_inline_temp_value" ).live( 'keyup', function( e ) {
        if ( e.keyCode == 13 ) {
            $( this ).blur( );
        }
        e.preventDefault( );
    } );

    $( '#mp-product-price-inventory-variants-metabox' ).keydown( function( event ) {//window
        if ( event.keyCode == 13 ) {
            event.preventDefault();
            return false;
        }
    } );


    function save_inline_post_data( post_id, meta_name, meta_value, sub_meta ) {
        $( '.mp-dashboard-widget-low-stock-wrap-overlay' ).show();
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
                var form = $( 'form#inventory_threshhold_form' );
                $.post(
                    mp_product_admin_i18n.ajaxurl, form.serialize()
                    ).done( function( data, status ) {

                    $( '.mp-dashboard-widget-low-stock-wrap-overlay' ).hide();
                    var response = $.parseJSON( data );

                    if ( response.status_message !== '' ) {
                        $( '.mp-dashboard-widget-low-stock-wrap' ).html( response.output );
                        $( '.low_stock_value' ).html( response.low_stock_value );

                        $( ".original_value" ).each( function( index ) {
                            $( this ).inlineEdit( $( '<input name="temp" class="mp_inline_temp_value" type="text" value="" />' ), $( 'input.editable_value' ) );//' + $.trim( $( this ).html( ) ) + '
                        } );
                    }
                } );
            }
        } );
    }

    jQuery( '.mp_bulk_price' ).on( 'keyup', function() {
        if ( jQuery( '.mp_bulk_price' ).val() == '' || isNaN( jQuery( '.mp_bulk_price' ).val() ) ) {
            jQuery( '.mp_price_controls .save-bulk-form' ).attr( 'disabled', true );
        } else {
            jQuery( '.mp_price_controls .save-bulk-form' ).attr( 'disabled', false );
        }
    } );

    jQuery( '.mp_bulk_inventory' ).on( 'keyup', function() {
        if ( jQuery( '.mp_bulk_inventory' ).val() !== '' && isNaN( jQuery( '.mp_bulk_inventory' ).val() ) ) {
            jQuery( '.mp_inventory_controls .save-bulk-form' ).attr( 'disabled', true );
        } else {
            jQuery( '.mp_inventory_controls .save-bulk-form' ).attr( 'disabled', false );
        }
    } );

    //Price controls
    jQuery( '.mp_popup_controls.mp_price_controls a.save-bulk-form' ).on( 'click', function( e ) {

        var global_price_set = jQuery( '.mp_bulk_price' ).val();
        parent.jQuery.colorbox.close();

        $( '.check-column-box:checked' ).each( function( ) {
            $( this ).closest( 'tr' ).find( '.field_subtype_price' ).html( global_price_set );
            $( this ).closest( 'tr' ).find( '.editable_value_price' ).val( global_price_set );
            save_inline_post_data( $( this ).val(), 'regular_price', global_price_set, '' );
        } );

        return false;
        e.preventDefault();
    } );

    //Inventory controls
    jQuery( '.mp_popup_controls.mp_inventory_controls a.save-bulk-form' ).on( 'click', function( e ) {

        var global_inventory_set = jQuery( '.mp_bulk_inventory' ).val();

        if ( global_inventory_set == '' || isNaN( global_inventory_set ) ) {
            global_inventory_set = '&infin;';
        }

        parent.jQuery.colorbox.close();

        $( '.check-column-box:checked' ).each( function( ) {
            $( this ).closest( 'tr' ).find( '.field_subtype_inventory' ).html( global_inventory_set );
            $( this ).closest( 'tr' ).find( '.editable_value_price' ).val( global_inventory_set );

            save_inline_post_data( $( this ).val(), 'inventory', global_inventory_set, '' );
        } );

        return false;
        e.preventDefault();
    } );

    //Delete controls
    jQuery( '.mp_popup_controls.mp_delete_controls a.delete-bulk-form' ).on( 'click', function( e ) {

        parent.jQuery.colorbox.close();

        $( '.check-column-box:checked' ).each( function( ) {
            $( this ).closest( 'tr' ).remove();
            save_inline_post_data( $( this ).val(), 'delete', '', '' );
        } );


        if ( $( '.check-column-box' ).length == 0 ) {
            save_inline_post_data( $( '[name="post_ID"]' ).val(), 'delete_variations', '', '' );

            if ( $( '#original_publish' ).val( ) == 'Publish' ) {
                $( '#save-post' ).removeAttr( 'dasabled' );
                //alert('published click!');
                $( '#save-post' ).click( );
            }

            if ( $( '#original_publish' ).val( ) == 'Update' ) {
                $( '#publish' ).removeAttr( 'dasabled' );
                $( '#publish' ).click( );
            }
        }

        return false;
        e.preventDefault();
    } )

    /* Close thickbox window on link / cancel click */
    $( '.mp_popup_controls a.cancel' ).live( 'click', function( e ) {
        parent.jQuery.colorbox.close();
        return false;
        e.preventDefault();
    } );

    $( "a.open_ajax" ).live( 'click', function( e ) {
        $.colorbox( {
            href: mp_product_admin_i18n.ajaxurl + '?action=mp_variation_popup&variation_id=' + ( $( this ).attr( 'data-popup-id' ) ),
            opacity: .7,
            inline: false,
            width: 400,
            height: 460,
            title: $( this ).closest( 'tr' ).find( '.field_more .variation_name' ).html(),
            onClosed: function() {
                $.colorbox.remove();
            },
            onLoad: function() {

            }
        } );



        e.preventDefault();
        //$.colorbox.remove
        // return false;
    } );

    $( '#variant_add' ).live( 'click', function( e ) {
        var url = mp_product_admin_i18n.ajaxurl + '?action=ajax_add_new_variant';

        $.post( url, {
            action: 'ajax_add_new_variant',
            parent_post_id: $( '#post_ID' ).val(),
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
                        onClosed: function() {
                            $.colorbox.remove();
                            //tinyMCE.execCommand("mceRepaint");
                        },
                        onLoad: function() {

                        }
                    } );
                } else {
                    alert( 'An error occured while trying to create a new variation post' );
                }
            }

        } );
        e.preventDefault();
    } );

    $( 'body' ).live( 'mp-variation-popup-loaded', function() {

        $( '#variation_popup a.remove_popup_image' ).on( 'click', function( e ) {

            var placeholder_image = $( '#variation_popup .mp-variation-image img' );

            var post_id = $( '#variation_id' ).val();
            var table_placeholder_image = $( '#post-' + post_id ).find( '.mp-variation-image img' );

            table_placeholder_image.attr( 'src', mp_product_admin_i18n.placeholder_image );
            table_placeholder_image.attr( 'width', 30 );
            table_placeholder_image.attr( 'height', 30 );

            placeholder_image.attr( 'src', mp_product_admin_i18n.placeholder_image );
            placeholder_image.attr( 'width', 75 );
            placeholder_image.attr( 'height', 75 );

            save_inline_post_data( post_id, '_thumbnail_id', '', '' );
            e.preventDefault();


        } );

        $( '#variation_popup .mp-variation-image img' ).on( 'click', function() {
            var placeholder_image = $( this );
            var post_id = $( '#variation_id' ).val();
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

        $( '#file_url_button' ).on( 'click', function() {

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

        $( '.fieldset_check' ).each( function() {
            var controller = $( this ).find( '.has_controller' );
            if ( controller.is( ':checked' ) ) {
                $( this ).find( '.has_area' ).show();
            } else {
                $( this ).find( '.has_area' ).hide();
            }
        } );


        $( '.mp-date' ).each( function() {
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

        var variation_content_type = $( "input[name='variation_content_type']:checked" ).val();

        if ( variation_content_type == 'html' ) {
            $( '.variation_description_button' ).show();
            $( '.variation_content_type_plain' ).hide();
        } else {//plain text
            $( '.variation_description_button' ).hide();
            $( '.variation_content_type_plain' ).show();
        }

        $( "input[name='variation_content_type']" ).live( 'change', function() {
            var variation_content_type = $( "input[name='variation_content_type']:checked" ).val();
            if ( variation_content_type == 'html' ) {
                $( '.variation_description_button' ).show();
                $( '.variation_content_type_plain' ).hide();
            } else {//plain text
                $( '.variation_description_button' ).hide();
                $( '.variation_content_type_plain' ).show();
            }
        } );

    } );

    $( '.has_controller' ).live( 'change', function() {
        var parent_holder = $( this ).closest( '.fieldset_check' );
        var controller = $( this );
        if ( controller.is( ':checked' ) ) {
            parent_holder.find( '.has_area' ).show();
        } else {
            parent_holder.find( '.has_area' ).hide();
        }
    } );


    $( '#variation_popup input, #variation_popup textarea, #variation_popup select' ).live( 'change', function( e ) {
        // Setup form validation on the #register-form element

        $( "#variation_popup" ).validate( {
        } );

        $( '.mp-numeric' ).each( function() {
            $( this ).rules( 'add', {
                number: true,
                messages: {
                    number: mp_product_admin_i18n.message_valid_number_required
                }
            } );
        } );

        $( '.mp-required' ).each( function() {
            $( this ).rules( 'add', {
                required: true,
                messages: {
                    required: mp_product_admin_i18n.message_input_required
                }
            } );
        } );


    } );

    $( '#save-variation-popup-data, .variation_description_button' ).live( 'click', function( e ) {
        var form = $( 'form#variation_popup' );

        $( '.mp_ajax_response' ).attr( 'class', 'mp_ajax_response' );
        $( '.mp_ajax_response' ).html( mp_product_admin_i18n.saving_message );

        $.post(
            //ajax_nonce: mp_product_admin_i18n.ajax_nonce
            //action: 'save_inline_post_data',
            mp_product_admin_i18n.ajaxurl, form.serialize()
            ).done( function( data, status ) {
            var response = $.parseJSON( data );

            if ( response.status_message !== '' ) {
                $( '.mp_ajax_response' ).html( response.status_message );
                $( '.mp_ajax_response' ).attr( 'class', 'mp_ajax_response' );
                $( '.mp_ajax_response' ).addClass( 'mp_ajax_response_' + response.status );
                if ( $( '#new_variation' ).val() == 'yes' ) {
                    //window.opener.location.reload( false );
                    parent.location.reload()
                }
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
            e.preventDefault();
        }
    } );


    $( '#mp_dashboard_widget_inventory_threshhold' ).live( 'change', function( e ) {

        $( '.mp-dashboard-widget-low-stock-wrap-overlay' ).show();

        var form = $( 'form#inventory_threshhold_form' );
        $( '.mp_ajax_response' ).attr( 'class', 'mp_ajax_response' );
        $( '.mp_ajax_response' ).html( mp_product_admin_i18n.saving_message );
        $.post(
            //ajax_nonce: mp_product_admin_i18n.ajax_nonce
            //action: 'save_inline_post_data',
            mp_product_admin_i18n.ajaxurl, form.serialize( )
            ).done( function( data, status ) {
            $( '.mp-dashboard-widget-low-stock-wrap-overlay' ).hide();
            var response = $.parseJSON( data );

            if ( response.status_message !== '' ) {
                $( '.mp_ajax_response' ).html( response.status_message );
                $( '.mp_ajax_response' ).attr( 'class', 'mp_ajax_response' );
                $( '.mp_ajax_response' ).addClass( 'mp_ajax_response_' + response.status );
                $( '.mp-dashboard-widget-low-stock-wrap' ).html( response.output );
                $( '.low_stock_value' ).html( response.low_stock_value );

                $( ".original_value" ).each( function( index ) {
                    $( this ).inlineEdit( $( '<input name="temp" class="mp_inline_temp_value" type="text" value="" />' ), $( 'input.editable_value' ) );//' + $.trim( $( this ).html( ) ) + '
                } );
            }

            if ( status == 'success' ) {
                //console.log( response );
            } else {
                //alert( 'fail!' );
                //an error occured
            }
        } );
        e.preventDefault();
    } );

} );
