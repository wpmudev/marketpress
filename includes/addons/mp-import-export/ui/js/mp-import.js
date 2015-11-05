
( function( $ ) {

	$( function() {

		var $thickboxLauncher = $( '#thickbox-launcher' ),
			file_path         = $( '#file_path' ).val(),
			lines_count       = $( '#lines_count' ).val(),
			import_datas      = function ( datas ) {
				$.post( mp_import_i18n.ajaxUrl, datas, function( response ) {

					response = $.parseJSON( response );
					
					$.each( response.messages, function(i) {
						$( '#TB_window #response-bottom' ).prepend( '<li>' + response.messages[i] + '</li>' );
					} );

					$( '#progress-bar' ).show().progressbar( {
						value: ( ( parseInt( response.line ) - 1 ) / parseInt( lines_count ) ) * 100
					} );

					if( ! response.done ) {
						import_datas( {
							action: 'mp_ie_import_datas',
							file_path: file_path,
							type: mp_import_i18n.type,
							from: mp_import_i18n.from,
							line: response.line
						} );
					}

				} );
			}
		;

		$( window ).load( function() {
			$thickboxLauncher.click();
		} );

		if( file_path !== '' ) {
			import_datas( {
				action: 'mp_ie_import_datas',
				file_path: file_path,
				type: mp_import_i18n.type,
				from: mp_import_i18n.from,
				line: 1
			} );
		}

		$( '#progress-bar' ).hide();

	} );

}( jQuery ) );
