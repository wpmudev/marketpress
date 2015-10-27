jQuery( document ).ready( function( $ ) {
    jQuery( '#wpbody-content .wrap h2' ).html( $( '#variation_title' ).val() );
    jQuery( '#wpbody-content .wrap h2' ).show();
} );