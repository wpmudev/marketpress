<?php

class MP_Coupons_Addon {
	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;
	
	/**
	 * Refers to all of the coupons
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_coupons = null;
	
	/**
	 * Refers to the applied coupons
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_coupons_applied = array();

	/**
	 * Refers to the applied coupons as objects
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_coupons_applied_objects = array();
	
	/**
	 * Refers to the build of the addon
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	public $build = 1;

	
	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Coupons_Addon();
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
		require_once mp_plugin_dir('includes/addons/mp-coupons/class-mp-coupon.php');
		
		$this->_install();
		
		add_action( 'init', array( &$this, 'register_post_type' ) );
		
		if ( ! is_admin() || mp_doing_ajax() ) {
			$this->get_applied();
			
			add_filter('mp_cart/after_cart_html', array(&$this, 'coupon_form_cart'), 10, 3);
			add_filter('mp_product/get_price', array(&$this, 'product_price'), 10, 2);
			add_filter('mp_cart/product_total', array(&$this, 'product_total'), 10, 2);
			add_filter('mp_cart/total', array(&$this, 'cart_total'), 10, 3);
			add_filter('mp_cart/cart_meta/product_total', array(&$this, 'cart_meta_product_total'), 10, 2);
			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_css_frontend'));
			add_action('wp_enqueue_scripts', array(&$this, 'enqueue_js_frontend'), 25);
			add_action( 'mp_cart/after_empty_cart', array( &$this, 'remove_all_coupons' ), 10, 1 );
			add_action( 'mp_order/new_order', array(& $this, 'process_new_order' ), 10, 1 );
		}
		
		if ( is_admin() ) {
			add_filter('manage_mp_coupon_posts_columns', array(&$this, 'product_coupon_column_headers'));
			add_action('manage_mp_coupon_posts_custom_column', array(&$this, 'product_coupon_column_data'), 10, 2);
			add_filter('manage_edit-mp_coupon_sortable_columns', array(&$this, 'product_coupon_sortable_columns'));

			if ( mp_doing_ajax() ) {
				add_action('wp_ajax_mp_coupons_remove', array(&$this, 'ajax_remove_coupon'));
				add_action('wp_ajax_nopriv_mp_coupons_remove', array(&$this, 'ajax_remove_coupon'));								
				add_action('wp_ajax_mp_coupons_apply', array(&$this, 'ajax_apply_coupon'));
				add_action('wp_ajax_nopriv_mp_coupons_apply', array(&$this, 'ajax_apply_coupon'));				
				return;
			}
			
			// Add menu items
			add_action('admin_menu', array(&$this, 'add_menu_items'), 9);
			// Modify coupon list table columns/data
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
		}
	}
	
	/**
	 * Convert an array of coupon IDs to objects
	 *
	 * @since 3.0
	 * @access protected
	 * @uses $wpdb
	 * @param array $coupons
	 * @return array
	 */
	protected function _convert_to_objects( $coupons ) {
		foreach ( $coupons as $coupon ) {
			$this->_coupons_applied_objects[$coupon] = new MP_Coupon($coupon);
		}
		
		return $this->_coupons_applied_objects;
	}
		
	/**
	 * Install
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _install() {
		$db_build = mp_get_setting('coupons->build', 0);
		
		if ( $this->build == $db_build ) {
			return;
		}
		
		if ( false === get_option('mp_coupons') ) {
			add_option('mp_coupons', array());
		}
		
		if ( $db_build < 1 ) {
			$this->_update_coupon_schema();
		}
		
		mp_update_setting('coupons->build', $this->build);
	}
		
	/**
	 * Updates the coupon schema.
	 *
	 * @since 3.0
	 * @access protected
	 */
	public function _update_coupon_schema() {
		$coupons = get_option('mp_coupons');
		
		if ( empty($coupons) ) {
			//no coupons to update
			return false;
		}
		
		//include WPMUDEV Metaboxes/Fields
		include_once mp_plugin_dir('includes/wpmudev-metaboxes/class-wpmudev-field.php');
		mp_include_dir(mp_plugin_dir('includes/wpmudev-metaboxes/fields'));
		
		foreach ( $coupons as $code => $coupon ) {
			$type = isset($coupon['applies_to']['type']) ? $coupon['applies_to']['type'] : 'all';
			$id = isset($coupon['applies_to']['id']) ? $coupon['applies_to']['id'] : '';
			
			$metadata = array(
				'discount' => array(
					'type' => 'WPMUDEV_Field_Text',
					'value' => ( $coupon['discount_type'] == 'pct' ) ? $coupon['discount'] . '%' : $coupon['discount'],
				),
				'max_uses' => array(
					'type' => 'WPMUDEV_Field_Text',
					'value' => $coupon['uses'],
				),
				'applies_to' => array(
					'type' => 'WPMUDEV_Field_Radio_Group',
					'value' => $type,
				),
				'applies_to' => array(
					'type' => 'WPMUDEV_Field_Radio_Group',
					'value' => 'item',
				),
				'category' => array(
					'type' => 'WPMUDEV_Field_Taxonomy_Select',
					'value' => ( $type == 'category' ) ? $id : '',
				),
				'product' => array(
					'type' => 'WPMUDEV_Field_Post_Select',
					'value' => ( $type == 'product' ) ? $id : '',
				),
				'start_date' => array(
					'type' => 'WPMUDEV_Field_Datepicker',
					'value' => date('Y-m-d', $coupon['start']),
				),
				'has_end_date' => array(
					'type' => 'WPMUDEV_Field_Checkbox',
					'value' => ( empty($coupon['end']) ) ? '0' : '1', 
				),
				'end_date' => array(
					'type' => 'WPMUDEV_Field_Datepicker',
					'value' =>  ( empty($coupon['end']) ) ? '' : date('Y-m-d', $coupon['end']),
				),
			);
			
			$post_id = wp_insert_post(array(
				'post_title' => strtoupper($code),
				'post_content' => '',
				'post_status' => 'publish',
				'post_type' => 'mp_coupon',
			));
			
			foreach ( $metadata as $name => $data ) {
				$type = $data['type'];
				$field = new $type(array('name' => $name, 'value_only' => true));
				$field->save_value($post_id, $name, $data['value'], true);
			}
		}
		
		delete_option('mp_coupons');
	}
	
	/**
	 * Update coupon session data
	 *
	 * @since 3.0
	 * @access protected
	 */
	protected function _update_session() {
		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			mp_update_session_value("mp_cart_coupons->{$blog_id}", $this->_coupons_applied);
		} else {
			mp_update_session_value('mp_cart_coupons', $this->_coupons_applied);
		}
	}
	
	/**
	 * Filter the cart product total
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_cart/cart_meta/product_total
	 * @return string
	 */
	public function cart_meta_product_total( $html, $cart ) {
		if ( ! $this->has_applied() ) {
			return $html;
		}
		
		$coupons = $this->get_applied_as_objects();
		
		$html .= '
			<div id="mp-cart-meta-line-coupons" class="mp-cart-meta-line clearfix">
				<strong class="mp-cart-meta-line-label">' . __('Coupon Discounts', 'mp') . '</strong>
				<span class="mp-cart-meta-line-amount">' . mp_format_currency('', $this->get_total_discount_amt()) . '</span>
				<div class="clear"></div>
				<ul id="mp-cart-meta-line-coupons-list">';
		
		foreach ( $coupons as $coupon ) {
			$html .= '
					<li class="mp-cart-coupon clearfix">
						<strong class="mp-cart-meta-line-label">' . $coupon->post_title . (( $cart->is_editable ) ? ' <a class="mp-cart-coupon-remove-link" href="javascript:mp_coupons.remove(' . $coupon->ID . ')">(' . __('Remove', 'mp') . ')</a>' : '') . '</strong>
						<span class="mp-cart-meta-line-amount">' . $coupon->discount_amt(false) . '</span>
					</li>';
		}
		
		$html .= '
				</ul>
			</div>';

		return $html;
	}
	
	/**
	 * Filter the cart total
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_cart/total
	 * @return float
	 */
	public function cart_total( $total, $_total, $cart ) {
		return floatval($total + $this->get_total_discount_amt());
	}
	
	/**
	 * Display the coupon form
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_cart/after_cart_html
	 * @return string
	 */
	public function coupon_form_cart( $html, $cart, $args ) {
		if ( $cart->is_editable ) {
			$html .= '
				<div id="mp-coupon-form">
					<h3>' . mp_get_setting('coupons->form_title', __('Have a coupon code?', 'mp')) . '</h3>
					<span class="mp-cart-input">
						<input type="text" name="mp_cart_coupon" class="mp-input-small" value="" />
					</span>
					<button type="submit" class="mp-button mp-button-check">Apply Code</button>' .
					wpautop(mp_get_setting('coupons->help_text', __('More than one code? That\'s OK! Just be sure to enter one at a time.', 'mp'))) . '
				</div>';
		}
		
		return $html;
	}
	
	/**
	 * Get coupon code value
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field_get_value_coupon_code
	 * @return string
	 */
	public function get_coupon_code_value( $value, $post_id, $raw, $field ) {
		$post = get_post($post_id);
		return ( get_post_status($post_id) == 'auto-draft' ) ? '' : $post->post_name;
	}
	
	/**
	 * Get total discount amount
	 *
	 * @since 3.0
	 * @access public
	 * @return float
	 */
	public function get_total_discount_amt() {
		$coupons = $this->get_applied_as_objects();
		$amt = 0;
		
		foreach ( $coupons as $coupon ) {
			$amt += $coupon->discount_amt(false, false);
		}
		
		return (float) $amt;
	}
	
	/**
	 * Determine if there are applied coupons
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	 public function has_applied() {
		 return ( ! empty($this->_coupons_applied) );
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
		
		$code = preg_replace('/[^A-Z0-9_-]/', '', strtoupper($_POST['coupon_code']));
		
		$data['post_title'] = $code;
		$data['post_status'] = 'publish';
		$data['post_name'] = '';
		
		return $data;
	}
	
	/**
	 * Process coupons when a new order is created
	 *
	 * @since 3.0
	 * @access public
	 * @action mp_order/new_order
	 */
	public function process_new_order( $order ) {
		$applied = $this->get_applied_as_objects();
		$discount_info = array();
		
		foreach ( $applied as $applied ) {
			$discount_info[ $applied->get_code() ] = $applied->discount_amt( false, false );
			$applied->use_coupon();
		}
		
		add_post_meta( $order->ID, 'mp_discount_info', $discount_info, true );
		
		// Remove all coupons from session
		$this->remove_all_coupons();
	}
	
	/**
	 * Initialize the coupon metaboxes
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
		$metabox->add_field('radio_group', array(
			'name' => 'discount_type',
			'label' => array('text' => __('How should the discount amount be applied?', 'mp')),
			'default_value' => 'item',	
			'options' => array(
				'item' => __('Apply to each applicable item and quantity ordered', 'mp'),
				'subtotal' => __('Apply to each applicable item once per cart', 'mp')
			),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'can_be_combined',
			'label' => array('text' => __('Can this coupon be combined with other coupons?', 'mp')),
		));
		$metabox->add_field('post_select', array(
			'name' => 'allowed_coupon_combos',
			'label' => array('text' => __('Select combinable coupons', 'mp')),
			'desc' => __('Leave blank to allow all other coupons.', 'mp'),
			'multiple' => true,
			'conditional' => array(
				'name' => 'can_be_combined',
				'value' => '1',
				'action' => 'show',
			),
		));
		$metabox->add_field('text', array(
			'name' => 'max_uses',
			'desc' => __('Enter the maximum number of times this coupon can be used.', 'mp'),
			'class' => 'digits',
			'label' => array('text' => __('Max Uses', 'mp')),
			'validation' => array(
				'digits' => true,
				'min' => 0,
			),
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
				'user' => __('User', 'mp'),
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
		$metabox->add_field('user_select', array(
			'name' => 'user',
			'validation' => array('required' => true),
			'label' => array('text' => __('User', 'mp')),
			'conditional' => array(
				'name' => 'applies_to',
				'value' => 'user',
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
			'name' => 'has_end_date',
			'label' => array('text' => __('Does coupon have an end date?', 'mp')),
			'message' => __('Yes', 'mp'),
		));
		$metabox->add_field('datepicker', array(
			'name' => 'end_date',
			'label' => array('text' => __('End Date', 'mp')),
			'conditional' => array(
				'name' =>	'has_end_date',
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
			'default_value' => __('More than one code? That\'s OK! Just be sure to enter one at a time.', 'mp'),
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
	 * Apply a coupon
	 *
	 * @since 3.0
	 * @access public
	 * @param MP_Coupon $coupon The coupon object to apply.
	 */
	public function apply_coupon( $coupon ) {
		if ( ! $coupon instanceof MP_Coupon ) {
			return false;
		}
		
		if ( ! in_array($coupon->ID, $this->_coupons_applied) ) {
			$this->_coupons_applied[] = $coupon->ID;
			$this->_update_session();
		}
	}

	/**
	 * Apply coupon (ajax)
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_cart_apply_coupon, wp_ajax_nopriv_mp_cart_apply_coupon
	 */
	public function ajax_apply_coupon() {
		$coupon_code = mp_get_post_value('coupon_code');

		if ( false === $coupon_code ) {
			wp_send_json_error(array(
				'message' => __('Invalid coupon code', 'mp'),
			));
		}

		$coupon = new MP_Coupon($coupon_code);
		
		if ( ! $coupon->is_valid() ) {
			wp_send_json_error(array(
				'message' => __('Coupon can\'t be applied to this cart', 'mp'),
			));
		}
		
		if ( $products = $coupon->get_products() ) {
			$this->apply_coupon( $coupon );
			wp_send_json_success(array(
				'products' => $products,
				'cart_meta' => mp_cart()->cart_meta(false),
			));
		}
		
		wp_send_json_error(array(
			'message' => __('No applicable products could be found in cart', 'mp'),
		));
	}
	
	/**
	 * Remove coupon (ajax)
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_cart_remove_coupon, wp_ajax_nopriv_mp_cart_remove_coupon
	 */
	public function ajax_remove_coupon(){
		$coupon_id = mp_get_post_value('coupon_id');
		
		if ( $this->remove_coupon($coupon_id) ) {
			$coupon = new MP_Coupon($coupon_id);
			$products = $coupon->get_products();
			
			wp_send_json_success(array(
				'products' => $products,
				'cartmeta' => mp_cart()->cart_meta(false),
			));
		}
		
		wp_send_json_error(array('message' => __('An error occurred while removing your coupon. Please try again.', 'mp')));
	}
	
	/**
	 * Register post type
	 *
	 * @since 3.0
	 * @access public
	 */
	public function register_post_type() {
		register_post_type('mp_coupon', array(
			'labels' => array(
				'name' => __('Coupons', 'mp'),
				'singular_name' => __('Coupon', 'mp'),
				'menu_name' => __('Manage Coupons', 'mp'),
				'all_items' => __('Coupons', 'mp'),
				'add_new' => __('Create New', 'mp'),
				'add_new_item' => __('Create New Coupon', 'mp'),
				'edit_item' => __('Edit Coupon', 'mp'),
				'edit' => __('Edit', 'mp'),
				'new_item' => __('New Coupon', 'mp'),
				'view_item' => __('View Coupon', 'mp'),
				'search_items' => __('Search Coupons', 'mp'),
				'not_found' => __('No Coupons Found', 'mp'),
				'not_found_in_trash' => __('No Coupons found in Trash', 'mp'),
				'view' => __('View Coupon', 'mp')
			),
			'capability_type' => array('mp_coupon', 'mp_coupons'),
			'map_meta_cap' => true,
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => false,
			'supports' => array(''),
		));		
	}
	
	/**
	 * Remove all coupons
	 *
	 * @since 3.0
	 * @access public
	 * @action mp_cart/after_empty_cart
	 */
	public function remove_all_coupons() {
		$this->_coupons_applied = array();
		$this->_update_session();
	}
	
	/**
	 * Remove a given coupon
	 *
	 * @since 3.0
	 * @access public
	 * @param int $coupon_id The coupon ID to remove.
	 * @return bool
	 */
	public function remove_coupon( $coupon_id ) {
		if ( false !== ($key = array_search($coupon_id, $this->_coupons_applied)) ) {
			unset($this->_coupons_applied[$key]);
			$this->_update_session();
			return true;
		}
		
		return false;
	}
	
	/**
	 * Change the product price to reflect coupon value
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_product/get_price
	 * @return array
	 */
	public function product_price( $price, $product ) {
		$coupons = $this->get_applied_as_objects();
		
		foreach ( $coupons as $coupon ) {
			$products = $coupon->get_products(true);
			if ( in_array($product->ID, $products) ) {
				$price['before_coupon'] = $price['lowest'];
					
				if ( $coupon->get_meta('discount_type') == 'item' ) {
					$price['lowest'] = $price['coupon'] = $price['sale']['amount'] = $coupon->get_price($price['lowest']);
				} else {
					$price['coupon'] = $coupon->get_price($price['lowest']);
				}
			}
		}
		
		return $price;
	}
	 
	/**
	 * Filter the product total
	 *
	 * @since 3.0
	 * @access public
	 * @filter mp_cart/product_total
	 * @return float
	 */
	public function product_total( $total, $items ) {
		$total = 0;
		foreach ( $items as $item ) {
			$price = $item->get_price();
			$total += (mp_arr_get_value('before_coupon', $price, mp_arr_get_value('lowest', $price, 0)) * $item->qty);
		}
		
		return (float) $total;
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
		$coupon = new MP_Coupon($post_id);
		
		switch ( $column ) {
			//! Discount
			case 'discount' :
				$coupon->discount_formatted();
			break;
			
			//! Remaining Uses
			case 'remaining' :
				$coupon->remaining_uses();
			break;
			
			//! Used
			case 'used' :
				$coupon->meta('times_used', 0);
			break;
			
			//! Valid Dates
			case 'valid_dates' :
				echo $coupon->get_meta('start_date') . ' &mdash;<br />';
				
				if ( ($end = $coupon->get_meta('end_date')) && $this->get_meta('has_end_date') ) {
					echo $end;
				} else {
					_e('No end', 'mp');
				}
			break;
			
			case 'applies_to' :
				$applies_to = $coupon->get_meta('applies_to');
				echo ucwords(str_replace('_', ' ', $applies_to));
			break;
		}
	}

	/**
	 * Enqueue frontend styles
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_enqueue_scripts
	 */
	public function enqueue_css_frontend() {
		if ( ! mp_is_shop_page(array('cart', 'checkout')) ) {
			return;
		}
		
		wp_enqueue_style('mp-coupons', mp_plugin_url('includes/addons/mp-coupons/ui/css/mp-coupons.css'), array(), MP_VERSION);
	}
	
	/**
	 * Enqueue frontend scripts
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_print_scripts
	 */
	public function enqueue_js_frontend() {
		if ( ! mp_is_shop_page('cart') ) {
			return;
		}
		
		wp_enqueue_script('mp-coupons', mp_plugin_url('includes/addons/mp-coupons/ui/js/mp-coupons.js'), array('jquery', 'mp-cart'), MP_VERSION);
		wp_localize_script('mp-coupons', 'mp_coupons_i18n', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'messages' => array(
				'required' => __('Please enter a code', 'mp'),
				'added' => __('Coupon added successfully', 'mp'),
			),
		));
	}
	
	/**
	 * Get all coupons from db
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_all() {
		if ( ! is_null($this->_coupons) ) {
			return $this->_coupons;
		}
		
		$this->_coupons = get_posts(array(
			'post_type' => 'mp_coupon',
			'posts_per_page' => -1,
		));
		
		return $this->_coupons;
	}

	/**
	 * Get applied coupons from session
	 *
	 * @since 3.0
	 * @access protected
	 * @return array
	 */
	public function get_applied() {
		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			$this->_coupons_applied = mp_get_session_value("mp_cart_coupons->{$blog_id}", array());
		} else {
			$this->_coupons_applied = mp_get_session_value('mp_cart_coupons', array());
		}
		
		return $this->_coupons_applied;
	}
	
	/**
	 * Get applied coupons as objects
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_applied_as_objects() {
		$applied = $this->get_applied();
		return $this->_convert_to_objects($applied);
	}
}

MP_Coupons_Addon::get_instance();

if ( ! function_exists('mp_coupons') ) :
	/**
	 * Get the MP_Coupons instance
	 *
	 * @since 3.0
	 * @return MP_Coupons
	 */
	function mp_coupons() {
		return MP_Coupons::get_instance();
	}
endif;