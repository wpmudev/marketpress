jQuery( document ).ready( function( $ ) {
    /* Variations product name set */
    $( '.mp_variations_product_name' ).html( $( '#title' ).val() );

    $( '#title' ).keyup( function() {
        $( '.mp_variations_product_name' ).html( $( '#title' ).val() );
    } );

    $( '.repeat' ).each( function() {
        $( this ).repeatable_fields();
    } );

} );
