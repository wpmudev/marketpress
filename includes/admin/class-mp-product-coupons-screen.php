<?php

class MP_Product_Coupons_Screen {
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
			self::$_instance = new MP_Product_Coupons_Screen();
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
		//add menu items
		add_action('admin_menu', array(&$this, 'add_menu_items'), 9);
		//change the "enter title here" text
		add_filter('enter_title_here', array(&$this, 'enter_title_here'));
		//modify coupon list table columns/data
		add_filter('manage_product_coupon_posts_columns', array(&$this, 'product_coupon_column_headers'));
		add_action('manage_product_coupon_posts_custom_column', array(&$this, 'product_coupon_column_data'), 10, 2);
		add_filter('manage_edit-product_coupon_sortable_columns', array(&$this, 'product_coupon_sortable_columns'));
		add_action('pre_get_posts', array(&$this, 'sort_product_coupons'));
		//custom css/javascript
		add_action('admin_print_styles', array(&$this, 'print_css'));
		add_action('admin_print_footer_scripts', array(&$this, 'print_js'));
		//on coupon save update post title to equal coupon code field
		add_filter('wp_insert_post_data', array(&$this, 'save_coupon_data'), 99, 2);
		//init metaboxes
		add_action('admin_init', array(&$this, 'init_metaboxes'));
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
		if ( $data['post_type'] != 'product_coupon' || empty($_POST['coupon_code']) ) {
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
	 */
	public function init_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-coupons-metabox',
			'title' => __('Coupon Settings'),
			'post_type' => 'product_coupon',
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
			'fields' => array(
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
			'query' => array('post_type' => 'product', 'posts_per_page' => 20),
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
	 * Changes the sort order of product coupons
	 *
	 * @since 3.0
	 * @access public
	 * @action pre_get_posts
	 * @param object $query
	 */
	public function sort_product_coupons( $query ) {
		if ( $query->get('post_type') != 'product_coupon' || get_current_screen()->id != 'edit-product_coupon' ) {
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
	 * Prints applicable javascript
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_print_footer_scripts
	 */
	public function print_js() {
		if ( get_current_screen()->id != 'product_coupon' ) return;
		?>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$('#menu-posts-product, #menu-posts-product > a')
			.addClass('wp-menu-open wp-has-current-submenu')
			.find('a[href="edit.php?post_type=product_coupon"]').parent().addClass('current');
	});
</script>
		<?php
	}
	
	/**
	 * Changes the "enter title here" text when editing/adding coupons
	 *
	 * @since 3.0
	 * @access public
	 * @action enter_title_here
	 * @param string $title The default title
	 * @return string
	 */
	public function enter_title_here( $title ) {
		if ( get_current_screen()->post_type != 'product_coupon' ) {
			return $title;
		}
		
		return __('Enter coupon code here', 'mp');
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
		add_submenu_page('edit.php?post_type=product', __('Coupons', 'mp'), __('Coupons', 'mp'), apply_filters('mp_coupons_capability', 'edit_coupons'), 'edit.php?post_type=product_coupon');
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
				$end = get_field_value('end_date', $post_id, true);
				echo date_i18n(get_option('date_format'), get_field_value('start_date', $post_id, true)) . ' &mdash;<br />';
				
				if ( $end ) {
					echo date_i18n(get_option('date_format'), $end);
				} else {
					_e('No end', 'mp');
				}
			break;
			
			case 'applies_to' :
				$applies_to = get_field_value('applies_to', $post_id);
				echo ucwords(str_replace('_', ' ', $applies_to));
		}
	}
}

MP_Product_Coupons_Screen::get_instance();