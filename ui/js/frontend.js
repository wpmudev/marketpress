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
	};
	
	marketpress.initSelect2 = function(){
		$('.mp_list_filter').find('select').select2({
			"dropdownAutoWidth" : true
		});
	}
}(jQuery));

jQuery(document).ready(function(){
	marketpress.equalizeProductGrid();
	marketpress.initSelect2();
});