(function($){
	$(document).ready(function(){
		updateProductPostmeta();
	});
	
	var updateProductPostmeta = function(){
		var $form = $('#mp-update-product-postmeta-form'),
				$page = $form.find('[name="page"]'),
				$progressbar = {};
		
		// reset page number (just in case)
		$page.val('1');
				
		$form.submit(function(e){
			e.preventDefault();
			
			if ( $form.find('[type="submit"]').length > 0 ) {
				$form.find('[type="submit"]').replaceWith('<div id="mp-update-product-postmeta-progressbar"><div class="progress-label">' + mp_db_update.progressbar.label_text + '</div></div>');
				$progressbar = $form.find('#mp-update-product-postmeta-progressbar');
				$progressbar.progressbar({
					"value" : false,
					"change" : function(){
						$progressbar.find('.progress-label').text($progressbar.progressbar('value') + '%');
					},
					"complete" : function(){
						$progressbar.find('.progress-label').text(mp_db_update.progressbar.complete_text);
					}
				});
			}
			
			$.ajax({
				"url" : ajaxurl,
				"cache" : false,
				"async" : true,
				"data" : $form.serialize(),
				"type" : "POST"
			}).done(function(resp){
				if ( resp.success ) {
					$progressbar.progressbar('value', resp.data.updated);
					$page.val(parseInt($page.val()) + 1);
					
					if ( ! resp.data.is_done ) {
						$form.submit();
					}
				} else {
					alert(mp_db_update.error_text);
				}
			})
		});	
	};
}(jQuery));
