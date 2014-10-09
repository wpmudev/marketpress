var marketpress = {};

(function($){
	$.fn.equalHeights = function(){
		var maxHeight = 0;
		
		this.each(function(){
			maxHeight = Math.max($(this).height(), maxHeight);
		});
		
		return this.each(function(){
			$(this).height(maxHeight);
		});
	}
}(jQuery));

(function($){
	marketpress.equalizeProductGrid = function(){
		$('.mp_grid_row').each(function(){
			$(this).find('.mp_one_product').equalHeights().find('.mp_price_buy').addClass('sticky');
		});
	}
	
	marketpress.initSelect2 = function(){
		$('.mp_select2').select2({
			"dropdownAutoWidth" : 1,
			"minimumResultsForSearch" : -1	// hide the search box
		});
	}
	
	marketpress.initToggleVariations = function(){
		$('.mp_variant_image_link').mouseover(function(e){
			e.preventDefault();
			
			var $this = $(this),
					newSrc = $this.find('.mp_variant_alt_image').find('img').attr('src'),
					newText = $this.find('.mp_variant_alt_content').html();
			
			$this.addClass('selected').siblings().removeClass('selected');
			$('.mp_product_image_link').attr('href', $this.attr('href')).find('img').attr('src', newSrc);
			$('.mp_product_content_text').html(newText);
		});
	}
}(jQuery));

jQuery(document).ready(function(){
	marketpress.initSelect2();
	marketpress.initToggleVariations();
});

window.onload = function(){
	marketpress.equalizeProductGrid();
}