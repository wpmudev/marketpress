jQuery( document ).ready( function( $ ) {
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


    } );

} );