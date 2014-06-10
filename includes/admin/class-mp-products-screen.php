<?php

class MP_Products_Screen {
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
			self::$_instance = new MP_Products_Screen();
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
		//remove add-new submenu item from store admin menu
		add_action('admin_menu', array(&$this, 'remove_menu_items'), 999);
		//print scripts for setting the active admin menu item when on the product tag page
		add_action('admin_footer', array(&$this, 'print_product_tag_scripts'));
		//print scripts for setting the active admin menu item when on the product category page
		add_action('admin_footer', array(&$this, 'print_product_category_scripts'));		
		//init metaboxes
		$this->init_metaboxes();		
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
	$('#menu-posts-product, #menu-posts-product > a').removeClass('wp-menu-open wp-has-current-submenu');
	$('#toplevel_page_store-settings, #toplevel_page_store-settings > a').addClass('wp-menu-open wp-has-current-submenu');
	$('a[href="edit-tags.php?taxonomy=product_tag&post_type=product"]').addClass('current').parent().addClass('current');
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
	$('#menu-posts-product, #menu-posts-product > a').removeClass('wp-menu-open wp-has-current-submenu');
	$('#toplevel_page_store-settings, #toplevel_page_store-settings > a').addClass('wp-menu-open wp-has-current-submenu');
	$('a[href="edit-tags.php?taxonomy=product_category&post_type=product"]').addClass('current').parent().addClass('current');
});
</script>
		<?php
	}
	
	/**
	 * Remove add-new submenu item from store admin menu
	 *
	 * @since 3.0
	 * @access public
	 */
	public function remove_menu_items() {
		remove_submenu_page('edit.php?post_type=product', 'post-new.php?post_type=product');
	}
	
	/**
	 * Initializes the coupon metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-product-variations-metabox',
			'title' => __('Variations'),
			'post_type' => 'product',
			'context' => 'normal',
		));
		$metabox->add_field('checkbox', array(
			'name' => 'has_variations',
			'message' => __('Does this product have variations?', 'mp'),
		));
		$repeater = $metabox->add_field('repeater', array(
			'name' => 'variations',
		));
	}
}

MP_Products_Screen::get_instance();