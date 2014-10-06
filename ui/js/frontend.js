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
		$('.mp_list_filter').find('select').select2({
			"dropdownAutoWidth" : true,
			"minimumResultsForSearch" : -1	// hide the search box
		});
	}
	
	marketpress.initProductOptionsMenu = function(){
		$('.mp_link_buynow').filter('.has_variations').hoverIntent(function(){
			var $this = $(this);
			$this.addClass('transitioning');
			setTimeout(function(){
				$this.addClass('visible');
			}, 100);
		},function(){
			var $this = $(this);
			$this.removeClass('visible');
			setTimeout(function(){
				$this.removeClass('transitioning');
			}, 300);
		});
	}
}(jQuery));

jQuery(document).ready(function(){
	marketpress.initSelect2();
	marketpress.initProductOptionsMenu();
});

window.onload = function(){
	marketpress.equalizeProductGrid();
}