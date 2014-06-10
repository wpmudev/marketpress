<?php

class MarketPress_Admin_Page_Importers {
	function __construct() {
		add_action('marketpress_admin_page_importers', array(&$this, 'settings'));
		add_filter('marketpress_settings_tabs', array(&$this, 'tab'));	
	}
	
	function tab( $tabs ) {
		$tabs['importers'] = array(
			'name' => __('Importers', 'mp'),
			'cart_only' => false,
			'order' => 7,
		);
		
		return $tabs;
	}
	
	function settings( $settings ) {
		
		?>
			<form id="mp-import-form" method="post" action="<?php echo add_query_arg(array()); ?>" enctype="multipart/form-data">
			<?php do_action('marketpress_add_importer'); ?>
			<?php wp_nonce_field('mp_settings_importers', 'mp_settings_importers_nonce'); ?>
		</form>
		<?php
	}
}

mp_register_admin_page('MarketPress_Admin_Page_Importers');