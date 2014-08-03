<?php

class MP_Store_Settings_Payments {
	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;
	
	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Store_Settings_Payments();
		}
		return self::$_instance;
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		$this->add_metaboxes();
		add_action('admin_footer', array(&$this, 'print_scripts'));
	}
	
	/**
	 * Prints the necessary javascript
	 *
	 * @since 3.0
	 * @access public
	 */
	public function print_scripts() {
		if ( get_current_screen()->id != 'store-settings_page_store-settings-payments' ) { return; }
		?>
		<script type="text/javascript">
		(function($){
			var hideInactiveGateways = function(){
				var allowedGateways = $('[name="gateways[allowed]"]').val().split(',');
				var $metaboxes = $('.wpmudev-postbox');
				var selectors = new Array('#mp-settings-payments');
				
				$.each(allowedGateways, function(index, value){
					selectors.push('#mp-settings-gateway-' + value);
				});
				
				$metaboxes.not(selectors.join(',')).hide();
			};
			
			$(window).load(function(){
				// Hide the inactive gateways. We do this window.onload instead of document.ready to avoid display issues (e.g. WYSIWYG not getting proper height)
				hideInactiveGateways();				
			});
			
			$(document).ready(function(){
				$('[name="gateways[allowed]"]').on('change', function(e){
					if ( e.added !== undefined ) {
						$('#mp-settings-gateway-' + e.added.id).slideDown(500);
					}
					
					if ( e.removed !== undefined ) {
						$('#mp-settings-gateway-' + e.removed.id).slideUp(500);
					}
				});
			});
		}(jQuery));
		</script>
		<?php
	}
	
	/**
	 * Add payment gateway settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-settings-payments',
			'screen_ids' => array('store-settings-payments', 'store-settings_page_store-settings-payments'),
			'title' => __('Payment Gateways', 'mp'),
			'option_name' => 'mp_settings',
			'order' => 1,
		));
		
		$gateways = MP_Gateway_API::get_gateways();
		$options = array();
		
		foreach ( $gateways as $slug => $gateway ) {
			$options[$slug] = $gateway[1];
		}
		
		$metabox->add_field('advanced_select', array(
			'name' => 'gateways[allowed]',
			'label' => array('text' => __('Enabled Gateways', 'mp')),
			'desc' => __('Choose the gateway(s) that you would like to be available for checkout.', 'mp'),
			'options' => $options,
		));
	}
}

MP_Store_Settings_Payments::get_instance();