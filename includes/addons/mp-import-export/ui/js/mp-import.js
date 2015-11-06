
( function( $ ) {

	$( function() {

		var $thickboxLauncher = $( '#thickbox-launcher' ),
			file_path         = $( '#file_path' ).val(),
			lines_count       = $( '#lines_count' ).val(),
			items_by_step     = parseInt( $( '#items_by_step' ).val() ),
			$import_from      = $( '#import-from' ),
			$import_version   = $( '#import-version' ),
			import_datas      = function ( datas ) {
				$.post( mp_import_i18n.ajaxUrl, datas, function( response ) {

					response = $.parseJSON( response );
					
					$.each( response.messages, function(i) {
						$( '#TB_window #response-bottom' ).prepend( '<li>' + response.messages[i] + '</li>' );
					} );

					$( '#progress-bar' ).show().progressbar( {
						value: ( ( ( parseInt( response.step ) - 1 ) * items_by_step ) / parseInt( lines_count ) ) * 100
					} );

					if( ! response.done ) {
						import_datas( {
							action: 'mp_ie_import_datas',
							file_path: file_path,
							type: mp_import_i18n.type,
							from: mp_import_i18n.from,
							step: response.step,
							items_by_step: items_by_step
						} );
					}

				} );
			}
		;

		$import_from.on( 'change', function() {
			$import_version.find( 'option' ).hide();
			$import_version.find( 'option.' + $( this ).val() ).show();
			$import_version.find( 'option.' + $( this ).val() ).eq(0).attr( 'selected', 'selected' );
		} ).trigger( 'change' );

		$( window ).load( function() {
			$thickboxLauncher.click();
		} );

		if( file_path !== '' ) {
			import_datas( {
				action: 'mp_ie_import_datas',
				file_path: file_path,
				type: mp_import_i18n.type,
				from: mp_import_i18n.from,
				step: 1,
				items_by_step: items_by_step
			} );
		}

		$( '#progress-bar' ).hide();

	} );

}( jQuery ) );
