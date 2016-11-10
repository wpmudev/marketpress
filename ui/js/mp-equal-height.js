( function( $ ) {
    $.fn.equalHeights = function( ) {
        var maxHeight = 0;
        this.each( function( ) {
            maxHeight = Math.max( $( this ).height( ), maxHeight );
        } );
        return this.each( function( ) {
            $( this ).height( maxHeight );
        } );
    }

	function equal_height( obj ) {
        var $this = $( obj );
        $this.equalHeights();
    }
	

    $( window ).resize( function( ) {
        $( '#mp-products.mp_products-grid' ).each( function( ) {
            var $this = $( this );
            $this.find( '.mp_product_meta' ).equalHeights();
        } );

        $( '#mp-related-products .mp_products-grid .mp_product_meta' ).each( function( ) {
            //equal_height( $( this ) );
            var $this = $( this );
            $this.find( '.mp_product_meta' ).equalHeights();
        } );

    } );
	
	$( window ).load(function() {
		$( '#mp-products.mp_products-grid' ).each( function( ) {
			var $this = $( this );
			$this.find( '.mp_product_meta' ).equalHeights();
		} );

		$( '#mp-related-products .mp_products-grid' ).each( function( ) {
			var $this = $( this );
			$this.find( '.mp_product_meta' ).equalHeights();
			//$this.equalHeights();
		} );
	} );
}( jQuery ) );