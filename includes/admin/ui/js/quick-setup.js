jQuery( document ).ready( function( $ ) {
	
	// Tabs
    $( '#mp-quick-setup-tabs' ).tabs();

    $( '#mp-quick-setup-tab-locations .mp_tab_navigation a' ).click( function( e ) {
        $( '#mp-quick-setup-tabs' ).tabs( { active: 1 } );
        e.preventDefault();
    } );

    $( '#mp-quick-setup-tab-currency-and-tax .mp_tab_navigation a' ).click( function( e ) {
        $( '#mp-quick-setup-tabs' ).tabs( { active: 2 } );
        e.preventDefault();
	} );
	
	// Fields
	$( ".mp_tab_content_locations" ).append( $( "#mp-quick-setup-wizard-location" ).html() );
	$( "#mp-quick-setup-wizard-location" ).remove();

	$( ".mp_tab_content_countries" ).append( $( "#mp-quick-setup-wizard-countries" ).html() );
	$( "#mp-quick-setup-wizard-countries" ).remove();
	
	$( ".mp_tab_content_currency" ).append( $( "#mp-quick-setup-wizard-currency" ).html() );
	$( "#mp-quick-setup-wizard-currency" ).remove();
	
	$( ".mp_tab_content_tax" ).append( $( "#mp-quick-setup-wizard-tax" ).html() );
	$( "#mp-quick-setup-wizard-tax" ).remove();
	
	$( ".mp_tab_content_system" ).append( $( "#mp-quick-setup-wizard-measurement-system" ).html() );
	$( "#mp-quick-setup-wizard-measurement-system" ).remove( ); 

} );