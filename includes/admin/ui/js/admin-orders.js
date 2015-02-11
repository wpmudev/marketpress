( function( $ ) {
	jQuery(document).ready(function($){
		var setActiveAdminMenu = function(){
			$('#menu-posts-product, #menu-posts-product > a, #menu-posts-mp_product, #menu-posts-mp_product > a')
				.addClass('wp-menu-open wp-has-current-submenu')
				.find('a[href="edit.php?post_type=mp_order"]').parent().addClass('current');
		};
		
		var modifyBulkActionsInput = function(){
			var $select = $('select[name="action"],select[name="action2"]');
			var options = mp_admin_orders.bulk_actions;
					
			$select.find('option').remove();
			
			$.each(options, function(key, value){
				$select.append('<option value="' + key + '">' + value + '</option>');
			});
		};
		
		var initCopyBillingAddress = function() {
			$( '#mp-order-copy-billing-address' ).on( 'click', function( e ) {
				e.preventDefault();
				
				$( '[name^="mp[billing_info]"]' ).each( function() {
					var $this = $( this );
					var name = $this.attr( 'name' );
					var targetName = name.replace( 'billing_info', 'shipping_info' );
					var $targetField = $( '[name="' + targetName + '"]' );
					
					$targetField.val( $this.val() );
				} );
			} );
		};
		
		var initSelect2Fields = function() {
			$( 'select.mp-select2' ).not( '.select2-offscreen' ).select2( {
				dropdownAutoWidth : false,
				width : "element"
			} );
		};
		
		var initUpdateStatesDropdown = function() {
			$( '[name="mp[billing_info][country]"], [name="mp[shipping_info][country]"]' ).on( 'change', function() {
				var $this = $( this );
				var url = ajaxurl + '?action=mp_update_states_dropdown';
				
				if ( $this.attr( 'name' ).indexOf( 'billing_info' ) >= 0 ) {
					var $target = $( '[name="mp[billing_info][state]"]' );
					var type = 'billing';
				} else {
					var $target = $( '[name="mp[shipping_info][state]"]' );
					var type = 'shipping';
				}
				
				var data = {
					country : $this.val(),
					type : type
				}
				
				$target.select2( 'destroy' ).hide().next( 'img' ).show();
				$this.select2( 'disable' );
						
				$.post( url, data ).done( function( resp ) {
					$this.select2( 'enable' );
					
					if ( resp.success ) {
						if ( resp.data.states ) {
							$target.html( resp.data.states ).show().next( 'img' ).hide();
							$target.closest( 'tr' ).show();
							initSelect2Fields();
						} else {
							$target.closest( 'tr' ).hide();
						}
					}
				} );
			} );			
		};
		
		var initCustomerInfoLightbox = function() {
			$( '.column-mp_orders_name' ).find( 'a' ).click( function() {
				$.colorbox( {
					href : $( this ).parent().find( '.mp-customer-info-lb' ),
					inline : true,
					maxWidth : "90%",
					opacity : .7,
					width : 640
				} );
			} );
		};
		
		setActiveAdminMenu();
		modifyBulkActionsInput();
		initCopyBillingAddress();
		initSelect2Fields();
		initUpdateStatesDropdown();
		initCustomerInfoLightbox();
	});
}( jQuery ) );