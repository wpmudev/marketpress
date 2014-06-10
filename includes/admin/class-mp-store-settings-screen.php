<?php

class MP_Store_Settings_Screen {
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
			self::$_instance = new MP_Store_Settings_Screen();
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
		$this->product_settings_metaboxes();
		add_action('store-settings_page_store-settings-productattributes', array(&$this, 'display_settings_form'));
	}
	
	/**
	 * Displays the product settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */	
	public function product_settings_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-store-settings-product-attributes',
			'title' => __('Product Attributes', 'mp'),
			'screen_ids' => array('store-settings_page_store-settings-productattributes'),
		));
		$atts = $metabox->add_field('repeater', array(
			'name' => 'product_attributes',
			'option_name' => 'mp_settings',
		));
		$atts->add_sub_field('text', array(
			'name' => 'attribute_name'
		));
	}
	
	/**
	 * Displays the settings form/metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function display_settings_form() {
		$updated = false;
		$title = __('Store Settings', 'mp') . ': ';
		
		switch ( mp_get_current_screen()->id ) {
			case 'store-settings_page_store-settings-presentation' :
				$title .= __('Presentation', 'mp');
			break;
			
			case 'store-settings_page_store-settings-messaging' :
				$title .= __('Messaging', 'mp');
			break;
			
			case 'store-settings_page_store-settings-shipping' :
				$title .= __('Shipping', 'mp');
			break;
			
			case 'store-settings_page_store-settings-payments' :
				$title .= __('Payment', 'mp');
			break;
			
			case 'store-settings_page_store-settings-shortcodes' :
				$title .= __('Short Codes', 'mp');
			break;
			
			case 'store-settings_page_store-settings-productattributes' :
				$title = __('Product Attributes', 'mp');
			break;
			
			case 'store-settings_page_store-settings-importers' :
				$title .= __('Importers', 'mp');
			break;
			
			default :
				$title .= __('General', 'mp');
			break;
		}
		?>
<div class="wrap mp-wrap">
	<div class="icon32"><img src="<?php echo mp_plugin_url('ui/images/settings.png'); ?>" /></div>
	<h2 class="mp-settings-title"><?php echo $title; ?></h2>
	<div class="clear"></div>
	<?php
 	if ( $updated ) : ?>
 	<div class="updated fade"><p><?php _e('Settings saved.', 'mp'); ?></p></div>
 	<?php
 	endif; ?> 	
	<div class="mp-settings">
	 	<form id="mp-main-form" method="post" action="<?php echo add_query_arg(array()); ?>">
			<?php
			do_action('wpmudev_metaboxes_settings'); ?>
			<p class="submit">
				<input class="button-primary" type="submit" name="submit_settings" value="<?php _e('Save Changes', 'mp') ?>" />
			</p>
		</form>
	</div>
</div>
	<?php
	}
}

MP_Store_Settings_Screen::get_instance();