<?php

class MP_Store_Settings_Admin {
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
			self::$_instance = new MP_Store_Settings_Admin();
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
		mp_include_dir(mp_plugin_dir('includes/admin/store-settings/'));
		
		//print scripts for setting the active admin menu item when on the product tag page
		add_action('admin_footer', array(&$this, 'print_product_tag_scripts'));
		//print scripts for setting the active admin menu item when on the product category page
		add_action('admin_footer', array(&$this, 'print_product_category_scripts'));		

		if ( mp_get_get_value('action') == 'mp_add_product_attribute' || mp_get_get_value('action') == 'mp_edit_product_attribute' ) {
			MP_Product_Attributes_Admin::add_product_attribute_metaboxes();
			add_action('wpmudev_metabox/before_save_fields/mp-store-settings-product-attributes-add', array('MP_Product_Attributes_Admin', 'save_product_attribute'));
			add_action('store-settings_page_store-settings-productattributes', array(&$this, 'display_settings_form'));
			
			if ( mp_get_get_value('action') == 'mp_edit_product_attribute' ) {
				add_filter('wpmudev_field/get_value', array('MP_Product_Attributes_Admin', 'get_product_attribute_value'), 10, 4);	
			}
		} else {
			$screen_ids = array(
				'toplevel_page_store-settings',
				'store-settings_page_store-settings-presentation',
				'store-settings_page_store-settings-notifications',
				'store-settings_page_store-settings-shipping',
				'store-settings_page_store-settings-payments',
				'store-settings_page_store-settings-importers'
			);
			
			foreach ( $screen_ids as $screen_id ) {
				add_action($screen_id, array(&$this, 'display_settings_form'));
			}
			
			// Product attributes list
			add_action('store-settings_page_store-settings-productattributes', array('MP_Product_Attributes_Admin', 'display_product_attributes'));		
		}
	}

	/**
	 * Print scripts for setting the active admin menu item when on the product tag page
	 *
	 * @since 3.0
	 * @access public
	 */
	public function print_product_tag_scripts() {
		if ( mp_get_current_screen()->id != 'edit-product_tag' ) { return false; }
		?>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('#menu-posts-<?php echo mp_get_product_post_type(); ?>, #menu-posts-<?php echo mp_get_product_post_type(); ?> > a').removeClass('wp-menu-open wp-has-current-submenu');
	$('#toplevel_page_store-settings, #toplevel_page_store-settings > a').addClass('wp-menu-open wp-has-current-submenu');
	$('a[href="edit-tags.php?taxonomy=product_tag&post_type=<?php echo mp_get_product_post_type(); ?>"]').addClass('current').parent().addClass('current');
});
</script>
		<?php
	}

	/**
	 * Print scripts for setting the active admin menu item when on the product category page
	 *
	 * @since 3.0
	 * @access public
	 */
	public function print_product_category_scripts() {
		if ( mp_get_current_screen()->id != 'edit-product_category' ) { return false; }
		?>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('#menu-posts-<?php echo mp_get_product_post_type(); ?>, #menu-posts-<?php echo mp_get_product_post_type(); ?> > a').removeClass('wp-menu-open wp-has-current-submenu');
	$('#toplevel_page_store-settings, #toplevel_page_store-settings > a').addClass('wp-menu-open wp-has-current-submenu');
	$('a[href="edit-tags.php?taxonomy=product_category&post_type=<?php echo mp_get_product_post_type(); ?>"]').addClass('current').parent().addClass('current');
});
</script>
		<?php
	}
	
	/**
	 * Gets an appropriate message by it's key
	 *
	 * @since 3.0
	 * @access public
	 */
	public function get_message_by_key( $key ) {
		$messages = array(
			'mp_product_attribute_added' => __('Product attribute added successfully.', 'mp'),
			'mp_product_attribute_updated' => __('Product attribute updated successfully.', 'mp'),
		);
		
		return ( isset($messages[$key]) ) ? $messages[$key] : sprintf(__('An appropriate message for key "%s" could not be found.', 'mp'), $key);
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
			
			case 'store-settings_page_store-settings-notifications' :
				$title .= __('Notifications', 'mp');
			break;
			
			case 'store-settings_page_store-settings-shipping' :
				$title .= __('Shipping', 'mp');
			break;
			
			case 'store-settings_page_store-settings-payments' :
				$title .= __('Payments', 'mp');
			break;
			
			case 'store-settings_page_store-settings-shortcodes' :
				$title .= __('Short Codes', 'mp');
			break;
			
			case 'store-settings_page_store-settings-importers' :
				$title .= __('Importers', 'mp');
			break;
			
			case 'store-settings_page_store-settings-productattributes' :
				$title = ( mp_get_get_value('action') == 'mp_add_product_attribute' ) ? __('Add Product Attribute', 'mp') : __('Edit Product Attribute', 'mp');
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
 	if ( $message_key = mp_get_get_value('mp_message') ) : ?>
 	<div class="updated"><p><?php echo $this->get_message_by_key($message_key); ?></p></div>
 	<?php
 	endif; ?> 	
	<div class="mp-settings">
	 	<form id="mp-main-form" method="post" action="<?php echo add_query_arg(array()); ?>">
			<?php
			/**
			 * Render WPMUDEV Metabox settings
			 *
			 * @since 3.0
			 */
			do_action('wpmudev_metabox/render_settings_metaboxes'); ?>
		</form>
	</div>
</div>
	<?php
	}
}

MP_Store_Settings_Admin::get_instance();