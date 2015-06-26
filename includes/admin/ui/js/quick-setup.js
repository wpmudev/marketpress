jQuery( document ).ready( function( $ ) {
    $( '#mp-quick-setup-tabs' ).tabs();

    $( '#tabs-1 .mp-tab-navigation a' ).click( function( e ) {
        $( '#mp-quick-setup-tabs' ).tabs( { active: 1 } );
        e.preventDefault();
    } );

    $( '#tabs-2 .mp-tab-navigation a' ).click( function( e ) {
        $( '#mp-quick-setup-tabs' ).tabs( { active: 2 } );
        e.preventDefault();
    } );

    $( ".mp-tab-locations" ).append( $( "#mp-quick-setup-wizard-location" ).html() );
    $( "#mp-quick-setup-wizard-location" ).hide();
} );