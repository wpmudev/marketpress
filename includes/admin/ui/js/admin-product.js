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

    $( '#mp_make_combinations' ).live( 'click', function( event ) {


    } );

} );