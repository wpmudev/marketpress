<?php

class MP_Coupons {
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
			self::$_instance = new MP_Coupons();
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
		if ( is_admin() ) {
			// Add menu items
			add_action('admin_menu', array(&$this, 'add_menu_items'), 9);
			// Modify coupon list table columns/data
			add_filter('manage_mp_coupon_posts_columns', array(&$this, 'product_coupon_column_headers'));
			add_action('manage_mp_coupon_posts_custom_column', array(&$this, 'product_coupon_column_data'), 10, 2);
			add_filter('manage_edit-mp_coupon_sortable_columns', array(&$this, 'product_coupon_sortable_columns'));
			add_action('pre_get_posts', array(&$this, 'sort_product_coupons'));
			// Custom css/javascript
			add_action('admin_print_styles', array(&$this, 'print_css'));
			add_action('admin_print_footer_scripts', array(&$this, 'print_js'));
			// On coupon save update post title to equal coupon code field
			add_filter('wp_insert_post_data', array(&$this, 'save_coupon_data'), 99, 2);
			// Init metaboxes
			add_action('init', array(&$this, 'init_metaboxes'));
			add_action('init', array(&$this, 'init_settings_metaboxes'));
			// Get coupon code value
			add_filter('wpmudev_field/before_get_value/coupon_code', array(&$this, 'get_coupon_code_value'), 10, 4);
		} else {
			add_filter('mp_cart/after_cart_html', array(&$this, 'coupon_form_cart'), 10, 3);
			add_action('wp_print_styles', array(&$this, 'print_css_frontend'));
			add_action('wp_footer', array(&$this, 'print_js_frontend'), 25);
		}
	}
	
	/**
	 * Display the coupon form
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_cart/after_cart_html
	 */
	public function coupon_form_cart( $html, $cart, $display_args ) {
		$html .= '
			<div id="mp-coupon-form">
				<h3>' . mp_get_setting('coupons->form_title', __('Have a coupon code?', 'mp')) . '</h3>
				<input type="text" name="mp_cart_coupon" value="" />
				<button type="submit" class="mp-button mp-button-check">Apply Code</button>' .
				wpautop(mp_get_setting('coupons->help_text', __('More than one code? That\'s OK! Please note that some codes can\'t be used with others, just be sure to enter one at a time.', 'mp'))) . '
			</div>';
			
		return $html;
	}
	
	/**
	 * Get coupon code value
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field_get_value_coupon_code
	 */
	public function get_coupon_code_value( $value, $post_id, $raw, $field ) {
		return ( get_post_status($post_id) == 'auto-draft' ) ? '' : get_the_title($post_id);
	}
	
	/**
	 * Save the coupon data
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_insert_post_data
	 * @param array $data
	 * @param array $post
	 * @return array
	 */
	public function save_coupon_data( $data, $post ) {
		if ( $data['post_type'] != 'mp_coupon' || empty($_POST['coupon_code']) ) {
			return $data;
		}
		
		$data['post_title'] = strtoupper($_POST['coupon_code']);
		$data['post_status'] = 'publish';
		
		return $data;
	}
	
	/**
	 * Initializes the coupon metaboxes
	 *
	 * @since 3.0
	 * @access public
	 * @action init
	 */
	public function init_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-coupons-metabox',
			'title' => __('Coupon Settings'),
			'post_type' => 'mp_coupon',
			'context' => 'normal',
		));
		$metabox->add_field('text', array(
			'name' => 'coupon_code',
			'desc' => __('Letters and Numbers only.', 'mp'),
			'validation' => array('required' => true, 'alphanumeric' => true),
			'style' => 'text-transform:uppercase',
			'label' => array('text' => __('Coupon Code', 'mp')),
		));
		$metabox->add_field('text', array(
			'name' => 'discount',
			'desc' => __('If you would like to give a percentage-based discount make sure to include the percent (%) symbol. Otherwise, the discount will be applied as a fixed amount off.', 'mp'),
			'validation' => array('required' => true, 'custom' => '[0-9%.]'),
			'custom_validation_message' => __('Value must either be a decimal number or a percentage', 'mp'),
			'label' => array('text' => __('Discount Amount', 'mp')),
		));
		$metabox->add_field('text', array(
			'name' => 'max_uses',
			'desc' => __('Enter the maximum number of times this coupon can be used.', 'mp'),
			'class' => 'digits',
			'label' => array('text' => __('Max Uses', 'mp')),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'applies_to',
			'label' => array('text' => __('Applies To', 'mp')),
			'orientation' => 'horizontal',
			'default_value' => 'all',
			'options' => array(
				'all' => __('All Products', 'mp'),
				'category' => __('Category', 'mp'),
				'product' => __('Product', 'mp'),
				'user_role' => __('User Role', 'mp'),
			),
		));
		$metabox->add_field('post_select', array(
			'name' => 'product',
			'validation' => array('required' => true),
			'multiple' => true,
			'placeholder' => __('Select Products', 'mp'),
			'query' => array('post_type' => MP_Product::get_post_type(), 'posts_per_page' => 20),
			'label' => array('text' => __('Product', 'mp')),
			'conditional' => array(
				'name' => 'applies_to',
				'value' => 'product',
				'action' => 'show',
			),
		));	
		$metabox->add_field('taxonomy_select', array(
			'name' => 'category',
			'validation' => array('required' => true),
			'multiple' => true,
			'placeholder' => __('Select Category', 'mp'),
			'taxonomy' => 'product_category',
			'label' => array('text' => __('Category', 'mp')),
			'conditional' => array(
				'name' => 'applies_to',
				'value' => 'category',
				'action' => 'show',
			),			
		));
		$metabox->add_field('user_role_select', array(
			'name' => 'user_role',
			'validation' => array('required' => true),
			'label' => array('text' => __('User Role', 'mp')),
			'conditional' => array(
				'name' => 'applies_to',
				'value' => 'user_role',
				'action' => 'show',
			),						
		));
		$metabox->add_field('datepicker', array(
			'name' => 'start_date',
			'validation' => array('required' => true),
			'label' => array('text' => __('Start Date', 'mp')),
			'default_value' => date('Y-m-d'),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'indefinite',
			'label' => array('text' => __('Does coupon have an end date?', 'mp')),
			'message' => __('Yes', 'mp'),
		));
		$metabox->add_field('datepicker', array(
			'name' => 'end_date',
			'label' => array('text' => __('End Date', 'mp')),
			'conditional' => array(
				'name' =>	'indefinite',
				'value' => '1',
				'action' => 'show',
			),
		));	
	}
	
	/**
	 * Init settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 * @action init
	 */
	public function init_settings_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-coupons-settings-metabox',
			'title' => __('Coupons Settings'),
			'page_slugs' => array('store-settings-addons'),
			'option_name' => 'mp_settings',
		));
		$metabox->add_field('text', array(
			'name' => 'coupons[form_title]',
			'label' => array('text' => __('Form Title', 'mp')),
			'default_value' => __('Have a coupon code?', 'mp'),
		));
		$metabox->add_field('wysiwyg', array(
			'name' => 'coupons[help_text]',
			'label' => array('text' => __('Help Text', 'mp')),
			'default_value' => __('More than one code? That\'s OK! Please note that some codes can\'t be used with others, just be sure to enter one at a time.', 'mp'),
		));
	}
	
	/**
	 * Changes the sort order of product coupons
	 *
	 * @since 3.0
	 * @access public
	 * @action pre_get_posts
	 * @param object $query
	 */
	public function sort_product_coupons( $query ) {
		if ( $query->get('post_type') != 'mp_coupon' || get_current_screen()->id != 'edit-mp_coupon' ) {
			//bail
			return;
		}
		
		switch ( get_query_var('orderby') ) {
			case 'product_coupon_discount' :
				$query->set('orderby', 'meta_value_num');
				$query->set('meta_key', 'discount_amount'); 
			break;
			
			case 'product_coupon_used' :
				$query->set('orderby', 'meta_value_num');
				$query->set('meta_key', 'times_used'); 
			break;
		}
	}
	
	/**
	 * Defines the product coupon sortable columns
	 *
	 * @since 3.0
	 * @access public
	 * @action manage_edit-product_coupon_sortable_columns
	 * @param array $columns
	 * @return array
	 */
	public function product_coupon_sortable_columns( $columns ) {
		return array_merge($columns, array(
			'discount' => 'product_coupon_discount',
		 	'used' => 'product_coupon_used',
		));
	}
	
	/**
	 * Prints applicable CSS
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_print_styles
	 */
	public function print_css() {
		if ( get_current_screen()->post_type != 'product_coupon' ) return;
		?>
<style type="text/css">
	#misc-publishing-actions,
	#minor-publishing-actions {
		display: none;
	}
	
	input#title,
	.row-title {
		text-transform: uppercase;
	}
	
	.tablenav .actions {
		display: none;
	}
	
	.tablenav .bulkactions {
		display: block;
	}
	
	th.manage-column {
		width: 20%;
	}
</style>		
		<?php
	}
	
	/**
	 * Print frontend styles
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_print_styles
	 */
	public function print_css_frontend() {
		if ( ! mp_is_shop_page('cart') ) { return; }
		?>
<style type="text/css">
input[name="mp_cart_coupon"] {
	text-transform: uppercase;
}
#mp-coupon-form {
	border-bottom: 2px solid #eaeaea;
	border-top: 2px solid #eaeaea;
	padding-bottom: 25px;
	margin-bottom: 25px;
}
#mp-coupon-form p {
	margin: 20px 0 0;
	padding: 0;
} 
</style>
		<?php
	}
	
	/**
	 * Prints applicable javascript
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_print_footer_scripts
	 */
	public function print_js() {
		if ( get_current_screen()->id != 'mp_coupon' ) return;
		?>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$('#menu-posts-product, #menu-posts-product > a, #menu-posts-mp_product, #menu-posts-mp_product > a')
			.addClass('wp-menu-open wp-has-current-submenu')
			.find('a[href="edit.php?post_type=mp_coupon"]').parent().addClass('current');
	});
</script>
		<?php
	}

	/**
	 * Print frontend scripts
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_print_scripts
	 */
	public function print_js_frontend() {
		if ( ! mp_is_shop_page('cart') ) { return; }
		?>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('#mp-cart-form').submit(function(e){
		var $this = $(this),
				$couponCode = $this.find('[name="mp_cart_coupon"]'),
				couponCode = $couponCode.val().toUpperCase().replace(/[^A-Z0-9]/g, '');
		
		if ( couponCode.length > 0 ) {
			e.preventDefault();
			mp_cart.applyCoupon(couponCode);
		}
	});
});
</script>
		<?php
	}
		
	/**
	 * Adds menu items to the admin menu
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_menu
	 */
	public function add_menu_items() {
		//manage coupons
		add_submenu_page('edit.php?post_type=' . MP_Product::get_post_type(), __('Coupons', 'mp'), __('Coupons', 'mp'), apply_filters('mp_coupons_capability', 'edit_coupons'), 'edit.php?post_type=mp_coupon');
	}
	
	/**
	 * Defines the column headers for the product coupon list table
	 *
	 * @since 3.0
	 * @access public
	 * @action manage_product_coupon_posts_columns
	 * @param array $columns The default columns as specified by WP
	 * @return array
	 */
	public function product_coupon_column_headers( $columns ) {
		return array(
			'cb' => '<input type="checkbox" />',
			'title' => __('Code', 'mp'),
			'discount' => __('Discount', 'mp'),
		 	'used' => __('Used', 'mp'),
			'remaining' => __('Remaining Uses', 'mp'),
			'valid_dates' => __('Valid Dates', 'mp'),
			'applies_to'	=> __('Applies To', 'mp'),
		);		
	}
	
	/**
	 * Defines the list table data for product coupons
	 *
	 * @since 3.0
	 * @access public
	 * @action manage_product_coupon_posts_custom_column
	 * @param string $column The current column name
	 * @param int $post_id The current post id
	 */
	public function product_coupon_column_data( $column, $post_id ) {
		switch ( $column ) {
			//! Discount
			case 'discount' :
				field_value('discount', $post_id);
			break;
			
			//! Remaining Uses
			case 'remaining' :
				echo (float) get_field_value('max_uses', $post_id) - (float) get_post_meta($post_id, 'times_used', true);
			break;
			
			//! Used
			case 'used' :
				echo (float) get_post_meta($post_id, 'times_used', true);
			break;
			
			//! Valid Dates
			case 'valid_dates' :
				$end = get_field_value('end_date', $post_id);
				echo get_field_value('start_date', $post_id) . ' &mdash;<br />';
				
				if ( $end ) {
					echo $end;
				} else {
					_e('No end', 'mp');
				}
			break;
			
			case 'applies_to' :
				$applies_to = get_field_value('applies_to', $post_id);
				echo ucwords(str_replace('_', ' ', $applies_to));
		}
	}
	
	/**
	 * Get applied coupons from session
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_applied() {
		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			$coupons = mp_get_session_value("mp_cart_coupons->{$blog_id}", array());
		} else {
			$coupons = mp_get_session_value('mp_cart_coupons', array());
		}
		
		return $coupons;
	}
	
	/**
	 * Get all coupons from db
	 *
	 * @since 3.0
	 * @access public
	 */
	public function get_all() {
		$coupons = get_posts(array(
			'post_type' => 'mp_coupon',
			'posts_per_page' => -1,
		));
	}
}

MP_Coupons::get_instance();