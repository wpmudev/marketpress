(function( $ ) {
	'use strict';

	$(document).ready(function () {

		var mp_data = mp_vars;

		$("select#mp-export-product-type").mp_select2({
			placeholder: mp_data.all_products,
			allowClear: false
		});

		$('.mp-export-products-wrapper').on('submit', 'form', function (e) {
			e.preventDefault();

			var data = $(this).serialize();
			window.console.log(mp_data.ajax_url);

			$.ajax({
				type: 'POST',
				url: mp_data.ajax_url,
				data: {
					_ajax_nonce: mp_data.nonce,
					action: 'mp_export_products',
					data: data
				},
				success: function (data) {
					// Reload page to update shipping methods
					//window.location.reload();
				}
			})
		});

	});

})( jQuery );