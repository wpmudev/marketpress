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
	 * Preload loading icon
	 *
	 * @since 3.0
	 */
	$( '<img />' ).get( 0 ).src = mp_i18n.loadingImage;
	
	/**
	 * Add or remove cart ajax loading icon
	 *
	 * @since 3.0
	 * @param string action Optional, either "show" or "hide". Defaults to "show".
	 */
	$.fn.ajaxLoading = function( action ){
		if ( typeof( action ) == 'undefined' ) {
			var action = 'show';	
		}
		
		return this.each(function(){
			if ( 'show' == action ) {
				$( this ).hide().after( '<img src="' + mp_i18n.loadingImage + '" alt="" />' );
			} else {
				$( this ).show().next( 'img' ).remove();
			}
		});
	};
}(jQuery));

var marketpress = {};
(function($){
	marketpress = {
		/**
		 * Show or hide the loading overlay
		 *
		 * @since 3.0
		 * @param string action Either show/hide. Optional.
		 */
		loadingOverlay : function( action ){
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
		},
		
		/**
		 * Initialize tooltips
		 *
		 * @since 3.0
		 */
		initToolTips : function(){
			$( document ).tooltip({
				"items" : ".mp-tooltip-help, .mp-has-tooltip",
				"content" : function() {
					var $this = $( this );
					
					if ( $this.is( '.mp-has-tooltip' ) ) {
						$this = $this.next( '.mp-tooltip-content' );
					}
					
					return $this.html();
				},
				"position" : {
					"my" : "center bottom-10",
					"at" : "center top"
				},
				"hide" : 300,
				"show" : 300
			});
		},
		
		/**
		 * Make each product in a product grid row the same height
		 *
		 * @since 3.0
		 */
		equalizeProductGrid : function(){
			$('.mp_grid_row').each(function(){
				var $this = $(this);
				
				$this.find('.mp_product_detail').equalHeights();
				$this.find('.mp_one_product').equalHeights();
				$this.find('.mp_buy_form').addClass('sticky');
			});
		},
		
		/**
		 * Initialize select2 elements
		 *
		 * @since 3.0
		 */
		initSelect2 : function(){
			$('select.mp_select2').not('.select2-offscreen').select2({
				"dropdownAutoWidth" : 1,
				"minimumResultsForSearch" : -1	// hide the search box
			});
			
			$('select.mp_select2_search').not('.select2-offscreen').select2({
				"dropdownAutoWidth" : 1
			});
		},
		
		/**
		 * Initialize order look up
		 *
		 * @since 3.0
		 */
		initOrderLookup : function(){
			var $form = $( '#mp-order-lookup-form' );
			var $btn = $form.find( '[type="submit"]' );
			var $input = $form.find( 'input' );
			
			$form.on( 'submit', function( e ) {
				e.preventDefault();
				
				if ( $btn.is( ':hidden' ) ) {
					// Already searching for an order - bail
					return;
				}
				
				var data = $form.serialize();
				
				$btn.ajaxLoading( 'show' );
				$input.prop( 'disabled', true );
				
				$.post( $form.attr( 'action' ), data ).done( function( resp ) {
					if ( resp.success ) {
						window.location.href = resp.data.redirect_url;
					} else {
						var $tooltip = $input.prev( '.mp-tooltip' );
						
						$btn.ajaxLoading( 'hide' );
						$input.prop( 'disabled', false );
						
						if ( $tooltip.length == 0 ) {
							$input.before( '<div class="mp-tooltip"></div>' );
							$tooltip = $input.prev( '.mp-tooltip' );
							$tooltip.tooltip( {
								items : ".mp-tooltip",
								tooltipClass : "error",
								position : {
									of : $input,
									my : "center bottom-10",
									at : "center top"
								},
								hide : 300,
								show : 300
							} );
						}
						
						$tooltip.tooltip( 'option', 'content', resp.data.error_message );
						$tooltip.tooltip( 'open' );
					}
				} );
			} );
		},
		
		/**
		 * Initialize content tabs on the single product template
		 *
		 * @since 3.0
		 */	
		initProductTabs : function(){
			$('.mp_product_tab_label_link').click(function(e){
				e.preventDefault();
				
				var $this = $(this),
						$tab = $this.parent(),
						$target = $($this.attr('href'));
						
				$tab.addClass('current').siblings().removeClass('current');
				$target.show().siblings('.mp_product_content').hide();
			});
		},
		
		/**
		 * Trigger events after an ajax request
		 *
		 * @since 3.0
		 * @param string event The base event string.
		 * @param object resp The ajax response object.
		 * @param object $scope Optional, the scope for triggered events. Defaults to document.
		 */
		ajaxEvent : function( event, resp, $scope ) {
			if ( $scope === undefined ) {
				var $scope = $(document);
			}
			
			var successEvent = event + '/success';
			var errorEvent = event + '/error';
			
			/**
			 * Fires whether the response was successful or not
			 *
			 * @since 3.0
			 * @param object The response data.
			 */
			$scope.trigger(event, [ resp ]);
			
			if ( resp.success ) {
				/**
				 * Fires on success
				 *
				 * @since 3.0
				 * @param object The response data object.
				 */
				$scope.trigger(successEvent.replace('//', '/'), [ resp.data ]);
			} else {
				var message = ( resp.data === undefined ) ? '' : resp.data.message;
				
				/**
				 * Fires on error
				 *
				 * @since 3.0
				 * @param string Any applicable error message.
				 */
				$scope.trigger(errorEvent.replace('//', '/'), [ message ]);				
			}
		}
	};
}(jQuery));

jQuery(document).ready(function(){
	marketpress.initSelect2();
	marketpress.initProductTabs();
	marketpress.initToolTips();
	marketpress.initOrderLookup();
});

window.onload = function(){
	marketpress.equalizeProductGrid();
}