jQuery( document ).ready( function( $ ) {

    $( '#poststuff' ).append( '<div class="mp-admin-overlay"><div class="mp-variation-loading-spin"></div><div class="mp-variation-loading-message">' + mp_product_admin_i18n.creating_vatiations_message + '</div></div>' );

    function mp_variation_message() {

        $( '.mp-variation-loading-spin' ).css( {
            position: 'fixed',
            left: ( $( '.mp-admin-overlay' ).width() - $( '.mp-variation-loading-spin' ).outerWidth() ) / 2,
            top: ( $( '.mp-admin-overlay' ).height() - $( '.mp-variation-loading-spin' ).outerHeight() ) / 2
        } );

        var new_top = parseInt( $( '.mp-variation-loading-spin' ).css( 'top' ) );
        new_top = new_top + 50;

        $( '.mp-variation-loading-message' ).css( {
            position: 'absolute',
            left: ( $( '.mp-admin-overlay' ).width() - $( '.mp-variation-loading-message' ).outerWidth() ) / 2,
            top: new_top
        } );
    }

    $( window ).resize( function() {
        mp_variation_message();
    } );
    $( window ).resize();

    /* Variations product name set */
    $( '.mp_variations_product_name' ).html( $( '#title' ).val() );

    $( '#title' ).keyup( function() {
        $( '.mp_variations_product_name' ).html( $( '#title' ).val() );
    } );

    $( '.repeat' ).each( function() {
        $( this ).repeatable_fields();
    } );

    $( '.mp_product_attributes_select' ).live( 'change', function() {
        if ( $( this ).val() == '-1' ) {
            $( this ).parent().find( '.mp-variation-attribute-name' ).show();
        } else {
            $( this ).parent().find( '.mp-variation-attribute-name' ).hide();
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
            var term_id = $( this ).parent().data( 'term-id' );
            $( '.check-column .check-column-box' ).prop( "checked", false );
            $( '.variation_term_' + term_id ).each( function( index ) {
                $( this ).closest( 'tr' ).find( '.check-column .check-column-box' ).prop( "checked", true );
            } );
        }

        event.preventDefault();
    } );

    $( ".select_attributes_filter a" ).live( 'focus', function( event ) {
        $( this ).blur();
    } );

    $( '#mp_make_combinations' ).live( 'click', function( event ) {

//alert($( '#original_publish' ).val());
        if ( $( '#original_publish' ).val() == 'Publish' ) {
            //$( '.mp-admin-overlay' ).show();
            $( '#save-post' ).removeAttr( 'dasabled' );
            //$( '#save-post' ).prop( 'disabled', false );
            $( '#save-post' ).click();
            //mp_variation_message();
        }

        if ( $( '#original_publish' ).val() == 'Update' ) {
            //$( '.mp-admin-overlay' ).show();
            $( '#publish' ).removeAttr( 'dasabled' );
            //$( '#publish' ).prop( 'disabled', false );
            $( '#publish' ).click();
            //mp_variation_message();
        }

        //$( 'form#post' ).submit();

        event.preventDefault();
    } );


    /*$( '#mp_make_combinations' ).live( 'click', function( event ) {
     $( '.mp-admin-overlay' ).show();
     mp_variation_message();
     
     var data = $( 'form#post' ).serialize();
     data['action'] = 'save_init_product_variations';
     
     $.post(
     mp_product_admin_i18n.ajaxurl, data
     ).done( function( data, status ) {
     if(status == 'success'){
     $( '.mp-admin-overlay' ).hide();
     }else{
     //an error occured
     }
     } );
     
     event.preventDefault();
     } );*/
    $( '.mp-add-new-variation' ).click();

    //$( '.variation-row' ).css( 'border-bottom', '1px' );
    //$( '.variation-row:last-child' ).css( 'border-bottom', '0px' );    

} );