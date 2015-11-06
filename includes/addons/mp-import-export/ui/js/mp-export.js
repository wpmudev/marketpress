
( function( $ ) {

	$( function() {

		var $tabs               = $( '#export-tabs' ),
			$productsTab        = $tabs.find( '.tab-products' ),
			$ordersTab          = $tabs.find( '.tab-orders' ),
			$customersTab       = $tabs.find( '.tab-customers' ),
			$exportTypes        = $( '#export-types' ),
			$submitBtn          = $( '.mp-submit' ),
			$nextBtn            = $( '.mp-next' ),
			$prevBtn            = $( '.mp-prev' ),
			$thickboxLauncher   = $( '#thickbox-launcher' ),
			$zipFileNameWrapper = $( '#zip-file-name-wrapper' ),
			$productsColumnsAll = $( '#products-columns-all' ),
			$ordersColumnsAll   = $( '#orders-columns-all' )
		;

		$( window ).load( function() {
			$thickboxLauncher.click();
		} );

		$tabs.tabs();
		
		// Disabled Customers Tab and option
		$tabs.tabs({
			disabled: [ 2 ]
		});
		$exportTypes.find( '[value="customers"]' ).attr( 'disabled', 'disabled' );

		$submitBtn.hide();

		$exportTypes.on( 'change', function() {
			var val = $( this ).val();

			switch( val ) {
				case 'all':
					$tabs.tabs( 'option', 'active', 0 );
					$productsTab.show();
					$ordersTab.show();
					$customersTab.show();
					$prevBtn.hide();
					$nextBtn.show();
					$submitBtn.hide();
					$zipFileNameWrapper.show();
					break;
				case 'products':
					$tabs.tabs( 'option', 'active', 0 );
					$productsTab.show();
					$ordersTab.hide();
					$customersTab.hide();
					$submitBtn.show();
					$prevBtn.hide();
					$nextBtn.hide();
					$zipFileNameWrapper.hide();
					break;
				case 'orders':
					$tabs.tabs( 'option', 'active', 1 );
					$productsTab.hide();
					$ordersTab.show();
					$customersTab.hide();
					$submitBtn.show();
					$prevBtn.hide();
					$nextBtn.hide();
					$zipFileNameWrapper.hide();
					break;
				case 'customers':
					$tabs.tabs( 'option', 'active', 2 );
					$productsTab.hide();
					$ordersTab.hide();
					$customersTab.show();
					$submitBtn.show();
					$prevBtn.hide();
					$nextBtn.hide();
					$zipFileNameWrapper.hide();
					break;
			}
		} );

		$nextBtn.on( 'click', function(e) {
			var activeTab = $tabs.tabs( 'option', 'active' );
			
			$tabs.tabs( 'option', 'active', activeTab + 1 );
		} );

		$prevBtn.on( 'click', function(e) {
			var activeTab = $tabs.tabs( 'option', 'active' );
			
			$tabs.tabs( 'option', 'active', activeTab - 1 );
		} );

		$tabs.on( 'tabsactivate', function( event, ui ) {
			var activeTab = $tabs.tabs( 'option', 'active' );
			
			switch( activeTab ) {
				case 0: 
					$submitBtn.hide();
					$nextBtn.show();
					$prevBtn.hide();
					break;
				case 1:
					// $submitBtn.hide();
					// $nextBtn.show();
					// $prevBtn.show();
					$submitBtn.show();
					$nextBtn.hide();
					$prevBtn.show();
					break;
				case 2:
					$submitBtn.show();
					$nextBtn.hide();
					$prevBtn.show();
					break;
			}
		} );

		$productsColumnsAll.change( function() {
			if( $( this ).attr( 'checked' ) === 'checked' ) {
				$( '[id^="products-columns-"]' ).attr( 'checked', 'checked' );
			} 
			else {
				$( '[id^="products-columns-"]' ).removeAttr( 'checked' );
			}
		} );

		$ordersColumnsAll.change( function() {
			if( $( this ).attr( 'checked' ) === 'checked' ) {
				$( '[id^="orders-columns-"]' ).attr( 'checked', 'checked' );
			} 
			else {
				$( '[id^="orders-columns-"]' ).removeAttr( 'checked' );
			}
		} );

	} );

}( jQuery ) );
