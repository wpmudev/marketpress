(function($){
	// create a copy of the WP inline edit post function
	var wp_inline_edit = inlineEditPost.edit;
	
	// customize the function
	inlineEditPost.edit = function( id ) {
		// "call" the original WP edit function
		wp_inline_edit.apply(this, arguments);
		
		var post_id = 0;
		if ( typeof(id) == 'object' ) {
			post_id = parseInt(this.getId(id));
		}
		
		if ( post_id == 0 ) {
			return;
		}
		
		var $edit_row = $('#' + post_id),
				$content = $('#quick-edit-product-content-' + post_id).clone(),
				$target = $('#quick-edit-col-product-price').find('.inline-edit-col');
		
		$target.html($content.html());

		$target.on('input', 'input', function() {
			var price = parseFloat( $target.find("input[name='product_price']").val() );
			var sale_price = parseFloat( $target.find("input[name='product_sale_price']").val() );
			var percentage_discount = parseFloat( $target.find("input[name='product_sale_percentage_discount']").val() );

			switch($(this).attr('name')) {
				case 'product_price':
					var new_percentage = ( 100 - ( ( 100 / price ) * sale_price ) );
					if(isFinite(new_percentage) && new_percentage >= 0.0) {
						$target.find("input[name='product_sale_percentage_discount']").val( new_percentage.toFixed(2) );
					}else{
						$target.find("input[name='product_sale_percentage_discount']").val( '' );
					}
					break;
				case 'product_sale_price':
					var new_percentage = ( 100 - ( ( 100 / price ) * sale_price ) );
					if(isFinite(new_percentage) && new_percentage >= 0.0) {
						$target.find("input[name='product_sale_percentage_discount']").val( new_percentage.toFixed(2) );
					}else{
						$target.find("input[name='product_sale_percentage_discount']").val( '' );
					}
					break;
				case 'product_sale_percentage_discount':
					var new_sale_price = price - ( ( price / 100 ) * percentage_discount );
					if(isFinite(new_sale_price) && new_sale_price <= price && new_sale_price > 0) {
						$target.find("input[name='product_sale_price']").val( new_sale_price.toFixed(2) );
					}else{
						$target.find("input[name='product_sale_price']").val( '' );
					}
					break;
			}
		});
		
		$target.find("input[name='product_price']").trigger('input');

	}
	
	$('#the-list').on('click', '#bulk_edit', function(e){
		var $bulk_row = $('#bulk-edit'),
				post_ids = new Array(),
				price = $bulk_row.find('[name="product_price"]').val(),
				sale_price = $bulk_row.find('[name="product_sale_price"]').val(),
				nonce = $bulk_row.find('[name="bulk_edit_products_nonce"]').val()
		
		$bulk_row.find('#bulk-titles').children().each(function(){
			post_ids.push($(this).attr('id').replace( /^(ttle)/i, ''));
		});

		$.ajax({
			"url" : ajaxurl,
			"type" : "POST",
			"async" : false,
			"cache" : false,
			"data" : {
				"action" : "mp_bulk_edit_products",
				"post_ids" : post_ids,
				"price" : price,
				"sale_price" : sale_price,
				"nonce" : nonce,
			}
		});
	});
}(jQuery));