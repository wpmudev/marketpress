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
	/**
	 * Show or hide the loading overlay
	 *
	 * @since 3.0
	 * @param string action Either show/hide. Optional.
	 */
	marketpress.loadingOverlay = function( action ){
		var $overlay = $('#colorbox').is(':visible') ? $('#cboxLoadingOverlay') : $('#mp-loading-overlay');
		
		if ( action === undefined ) {
			var action = 'show';
		}
		
		if ( $overlay.length == 0 ) {
			$('body').append('<div id="mp-loading-overlay" style="display:none"></div>');
			$overlay = $('#mp-loading-overlay');
		}
		
		switch ( action ) {
			case 'show' :
				$overlay.show();
			break;
			
			case 'hide' :
				$overlay.hide();
			break;
		}
	};
	
	/**
	 * Make each product in a product grid row the same height
	 *
	 * @since 3.0
	 */
	marketpress.equalizeProductGrid = function(){
		$('.mp_grid_row').each(function(){
			$(this).find('.mp_one_product').equalHeights().find('.mp_price_buy').addClass('sticky');
		});
	};
	
	/**
	 * Initialize select2 elements
	 *
	 * @since 3.0
	 */
	marketpress.initSelect2 = function(){
		$('.mp_select2').select2({
			"dropdownAutoWidth" : 1,
			"minimumResultsForSearch" : -1	// hide the search box
		});
	};
	
	/**
	 * Initialize content tabs on the single product template
	 *
	 * @since 3.0
	 */	
	marketpress.initProductTabs = function(){
		$('.mp_product_tab_label_link').click(function(e){
			e.preventDefault();
			
			var $this = $(this),
					$tab = $this.parent(),
					$target = $($this.attr('href'));
					
			$tab.addClass('current').siblings().removeClass('current');
			$target.show().siblings('.mp_product_content').hide();
		});
	};
}(jQuery));

jQuery(document).ready(function(){
	marketpress.initSelect2();
	marketpress.initProductTabs();
});

window.onload = function(){
	marketpress.equalizeProductGrid();
}