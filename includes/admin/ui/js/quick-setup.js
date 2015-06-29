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

$( ".mp-tab-countries" ).append( $( "#mp-quick-setup-wizard-countries" ).html() );
$( "#mp-quick-setup-wizard-countries" ).remove();

$( ".mp-tab-tax" ).append( $( "#mp-quick-setup-wizard-tax" ).html() );
$( "#mp-quick-setup-wizard-tax" ).remove();

$( ".mp-tab-locations" ).append( $( "#mp-quick-setup-wizard-location" ).html() );
$( "#mp-quick-setup-wizard-location" ).remove();

$( ".mp-tab-currency" ).append( $( "#mp-quick-setup-wizard-currency" ).html() );
$( "#mp-quick-setup-wizard-currency" ).remove();

$( ".mp-tab-measurement-system" ).append( $( "#mp-quick-setup-wizard-measurement-system" ).html() );
$( "#mp-quick-setup-wizard-measurement-system" ).remove( ); 


} );