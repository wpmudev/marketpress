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
		if ( is_null( self::$_instance ) ) {
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
		require_once mp_plugin_dir( 'includes/addons/mp-coupons/class-mp-coupon.php' );

		$this->_install();

		add_action( 'init', array( &$this, 'register_post_type' ) );
		add_action( 'switch_blog', array( &$this, 'get_applied' ) );

		if ( ! is_admin() || mp_doing_ajax() ) {
			$this->get_applied();

			if ( mp_cart()->is_global ) {
				add_filter( 'mp_cart/after_cart_store_html', array( &$this, 'coupon_form_cart' ), 10, 3 );
			} else {
				add_filter( 'mp_cart/after_cart_html', array( &$this, 'coupon_form_cart' ), 10, 3 );
			}

			add_filter( 'mp_product/get_price', array( &$this, 'product_price' ), 10, 2 );
			add_filter( 'mp_cart/product_total', array( &$this, 'product_total' ), 10, 2 );
			add_filter( 'mp_cart/total', array( &$this, 'cart_total' ), 10, 3 );

			add_filter( 'mp_cart/tax_total', array( &$this, 'tax_total' ), 10, 3 );

			add_filter( 'mp_cart/cart_meta/product_total', array( &$this, 'cart_meta_product_total' ), 10, 2 );
			add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_css_frontend' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_js_frontend' ), 25 );
			add_action( 'mp_cart/after_empty_cart', array( &$this, 'remove_all_coupons' ), 10, 1 );
			add_action( 'mp_cart/after_remove_item', array( &$this, 'check_items_in_cart' ), 10 );
			add_action( 'mp_order/new_order', array( &$this, 'process_new_order' ), 10, 1 );
			add_action( 'mp_cart/before_remove_item', array( &$this, 'check_coupons' ), 10, 2 );

			add_filter( 'mp_coupon_total_value', array( &$this, 'max_discount' ), 10, 1 );
		}

		if ( is_admin() ) {
			add_filter( 'manage_mp_coupon_posts_columns', array( &$this, 'product_coupon_column_headers' ) );
			add_action( 'manage_mp_coupon_posts_custom_column', array( &$this, 'product_coupon_column_data' ), 10, 2 );
			add_filter( 'manage_edit-mp_coupon_sortable_columns', array( &$this, 'product_coupon_sortable_columns' ) );

			if ( mp_doing_ajax() ) {
				add_action( 'wp_ajax_mp_coupons_remove', array( &$this, 'ajax_remove_coupon' ) );
				add_action( 'wp_ajax_nopriv_mp_coupons_remove', array( &$this, 'ajax_remove_coupon' ) );
				add_action( 'wp_ajax_mp_coupons_apply', array( &$this, 'ajax_apply_coupon' ) );
				add_action( 'wp_ajax_nopriv_mp_coupons_apply', array( &$this, 'ajax_apply_coupon' ) );

				return;
			}

			// Add menu items
			add_action( 'admin_menu', array( &$this, 'add_menu_items' ), 9 );
			// Modify coupon list table columns/data
			add_action( 'pre_get_posts', array( &$this, 'sort_product_coupons' ) );
			// Custom css/javascript
			add_action( 'admin_print_styles', array( &$this, 'print_css' ) );
			add_action( 'admin_print_footer_scripts', array( &$this, 'print_js' ) );
			// On coupon save update post title to equal coupon code field
			add_filter( 'wp_insert_post_data', array( &$this, 'save_coupon_data' ), 99, 2 );
			// Init metaboxes
			add_action( 'init', array( &$this, 'init_metaboxes' ) );
			if ( mp_get_get_value( 'addon', null ) == 'MP_Coupons_Addon' ) {
				//addon settings
				//added by hoang, for fix the settings showup in every addon
				add_action( 'init', array( &$this, 'init_settings_metaboxes' ) );
			}

			// Get coupon code value
			add_filter( 'wpmudev_field/before_get_value/coupon_code', array( &$this, 'get_coupon_code_value' ), 10, 4 );
			add_action( 'user_has_cap', array( &$this, 'user_has_cap' ), 10, 4 );

			add_filter( 'post_row_actions', array( &$this, 'remove_row_actions' ), 10, 2 );
		}
	}

	function remove_row_actions( $actions, $post ) {
		global $current_screen, $post;

		if ( $post->post_type == 'mp_order' ) {
			unset( $actions['edit'] );
			unset( $actions['inline hide-if-no-js'] );
		}

		if ( $current_screen->post_type != 'mp_coupon' ) {
			return $actions;
		}

		unset( $actions['view'] );

		return $actions;
	}

	/**
	 * Convert an array of coupon IDs to objects
	 *
	 * @since 3.0
	 * @access protected
	 * @uses $wpdb
	 *
	 * @param array $coupons
	 *
	 * @return array
	 */
	protected function _convert_to_objects( $coupons ) {
		foreach ( $coupons as $coupon ) {
			$this->_coupons_applied_objects[ $coupon ] = new MP_Coupon( $coupon );
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
		$db_build = mp_get_setting( 'coupons->build', 0 );

		if ( $this->build == $db_build ) {
			return;
		}

		if ( false === get_option( 'mp_coupons' ) ) {
			add_option( 'mp_coupons', array() );
		}

		if ( $db_build < 1 ) {
			$this->_update_coupon_schema();
		}

		mp_update_setting( 'coupons->build', $this->build );
	}

	/**
	 * Updates the coupon schema.
	 *
	 * @since 3.0
	 * @access protected
	 */
	public function _update_coupon_schema() {
		$coupons = get_option( 'mp_coupons' );

		if ( empty( $coupons ) ) {
			//no coupons to update
			return false;
		}

		//include WPMUDEV Metaboxes/Fields
		include_once mp_plugin_dir( 'includes/wpmudev-metaboxes/class-wpmudev-field.php' );
		mp_include_dir( mp_plugin_dir( 'includes/wpmudev-metaboxes/fields' ) );

		foreach ( $coupons as $code => $coupon ) {
			$type = isset( $coupon['applies_to']['type'] ) ? $coupon['applies_to']['type'] : 'all';
			$id   = isset( $coupon['applies_to']['id'] ) ? $coupon['applies_to']['id'] : '';

			$metadata = array(
				'discount'     => array(
					'type'  => 'WPMUDEV_Field_Text',
					'value' => ( $coupon['discount_type'] == 'pct' ) ? $coupon['discount'] . '%' : $coupon['discount'],
				),
				'max_uses'     => array(
					'type'  => 'WPMUDEV_Field_Text',
					'value' => $coupon['uses'],
				),
				'applies_to'   => array(
					'type'  => 'WPMUDEV_Field_Radio_Group',
					'value' => $type,
				),
				'applies_to'   => array(
					'type'  => 'WPMUDEV_Field_Radio_Group',
					'value' => 'item',
				),
				'category'     => array(
					'type'  => 'WPMUDEV_Field_Taxonomy_Select',
					'value' => ( $type == 'category' ) ? $id : '',
				),
				'product'      => array(
					'type'  => 'WPMUDEV_Field_Post_Select',
					'value' => ( $type == 'product' ) ? $id : '',
				),
				'start_date'   => array(
					'type'  => 'WPMUDEV_Field_Datepicker',
					'value' => date( 'Y-m-d', $coupon['start'] ),
				),
				'has_end_date' => array(
					'type'  => 'WPMUDEV_Field_Checkbox',
					'value' => ( empty( $coupon['end'] ) ) ? '0' : '1',
				),
				'end_date'     => array(
					'type'  => 'WPMUDEV_Field_Datepicker',
					'value' => ( empty( $coupon['end'] ) ) ? '' : date( 'Y-m-d', $coupon['end'] ),
				),
			);

			$post_id = wp_insert_post( array(
				'post_title'   => strtoupper( $code ),
				'post_content' => '',
				'post_status'  => 'publish',
				'post_type'    => 'mp_coupon',
			) );

			foreach ( $metadata as $name => $data ) {
				$type  = $data['type'];
				$field = new $type( array( 'name' => $name, 'value_only' => true ) );
				$field->save_value( $post_id, $name, $data['value'], true );
			}
		}

		delete_option( 'mp_coupons' );
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
			mp_update_session_value( "mp_cart_coupons->{$blog_id}", $this->_coupons_applied );
		} else {
			mp_update_session_value( 'mp_cart_coupons', $this->_coupons_applied );
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
		if ( ! $this->has_applied() && ! $cart->is_global ) {
			return $html;
		}

		$coupons = $this->get_applied_as_objects();

		$html .= '
			<div class="mp_cart_resume_item mp_cart_resume_item-coupons">
				<span class="mp_cart_resume_item_label">' . __( 'Coupon Discounts', 'mp' ) . '</span>
				<span class="mp_cart_resume_item_amount mp_cart_resume_item_amount-total">' . mp_format_currency( '', $this->get_total_discount_amt() ) . '</span>
				<ul class="mp_cart_resume_coupons_list">';

		foreach ( $coupons as $coupon ) {
			$html .= '
					<li class="mp_cart_coupon">
						<span class="mp_cart_resume_item_label">' . $coupon->post_title . ( ( $cart->is_editable ) ? ' <a class="mp_cart_coupon_remove_item" href="javascript:mp_coupons.remove(' . $coupon->ID . ', ' . $cart->get_blog_id() . ');">(' . __( 'Remove', 'mp' ) . ')</a>' : '' ) . '</span>
						<span class="mp_cart_resume_item_amount">' . $coupon->discount_amt( false ) . '</span>
					</li><!-- end mp_cart_coupon -->';
		}

		$html .= '
				</ul><!-- end mp_cart_resume_coupons_list -->
			</div><!-- end mp_cart_resume_item_coupons -->';

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
		
		if( isset( $_total[ 'product_original' ] ) ){
			$total = $_total[ 'product_original' ];
		}
		elseif( $cart instanceof MP_Cart ){
			$total = $cart->product_original_total();
		}

		$coupon_discount = $this->get_total_discount_amt();		

		if ( abs( $this->get_total_discount_amt() ) >= $total ) {
			$total = $total + ( - 1 * $total );
		} else {
			$total = $total + $this->get_total_discount_amt();
		}

		if ( ! mp_get_setting( 'tax->tax_inclusive' ) ) {		
			$total = ( $total + (float) $cart->tax_total()  );
		}														
		$total = ( $total + (float) $cart->shipping_total());

		return floatval( $total );
	}

	public function tax_total( $tax_amount, $total, $cart ) {

		$total = (int) $cart->product_original_total() + (int) $cart->shipping_total();

		if ( abs( $this->get_total_discount_amt() ) >= $total ) {
			$total_pre = $total + ( - 1 * $total );
		} else {
			$total_pre = $total + $this->get_total_discount_amt();
		}

		$tax_rate   = mp_tax_rate();
		if ( mp_get_setting( 'tax->tax_inclusive' ) ) {
			$cart_price = $total_pre;					
			$total_pre = $total_pre / (1 + $tax_rate);		
		} else {											
			$cart_price = $total_pre * ( 1 + $tax_rate );
		}

		$tax_amount = (float) $cart_price - (float) $total_pre;

		return number_format( $tax_amount, 2 );
	}

	/**
	 * When an item is removed from the cart, validate applied coupons to ensure they are still valid
	 *
	 * @since 3.0
	 * @access public
	 * @action mp_cart/before_item_removed
	 * @global $switched
	 */
	public function check_coupons( $item_id, $blog_id ) {
		global $switched;

		if ( $blog_id != get_current_blog_id() ) {
			switch_to_blog( $blog_id );
		}

		$coupons = $this->get_applied_as_objects();
		foreach ( $coupons as $coupon ) {
			if ( ! $coupon->is_valid('remove_item') ) {
				$this->remove_coupon( $coupon->ID );
			}
		}

		if ( $switched ) {
			restore_current_blog();
		}
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
		if ( $cart->is_editable && mp_addons()->is_addon_enabled( 'MP_Coupons_Addon' ) ) {
			$html .= '
				<div id="mp-coupon-form-store-' . $cart->get_blog_id() . '" class="mp_form mp_coupon_form' . ( ( $cart->is_global ) ? ' mp_coupon_form-store' : '' ) . '">
					<div class="mp_form_content">
						<h3 class="mp_sub_title">' . mp_get_setting( 'coupons->form_title', __( 'Have a coupon code?', 'mp' ) ) . '</h3>
					</div>
					<div class="mp_form_group">
						<div class="mp_form_group_input">
							<input type="text" name="mp_cart_coupon[' . $cart->get_blog_id() . ']" class="mp_form_input" value="">
						</div>
						<div class="mp_form_group_btn">
					  		<button type="button" class="mp_button mp_button-check">' . __( 'Apply Code', 'mp' ) . '</button>
					  	</div>
				    </div>' .
			         do_shortcode( wpautop( mp_get_setting( 'coupons->help_text', __( 'More than one code? That\'s OK! Just be sure to enter one at a time.', 'mp' ) ) ) ) . '
				</div><!-- end mp-coupon-form-store-' . $cart->get_blog_id() . ' -->';
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
		$post = get_post( $post_id );

		return ( get_post_status( $post_id ) == 'auto-draft' ) ? '' : $post->post_name;
	}

	public function max_discount( $discount_value ) {

		remove_filter( 'mp_coupon_total_value', array( &$this, 'max_discount' ), 10 );

		$cart = new MP_Cart();

		$total = ( (float) $cart->product_total() + (float) $cart->tax_total() + (float) $cart->shipping_total() );

		if ( abs( $discount_value ) >= $total ) {
			$discount_value = - 1 * $total;
		}

		return $discount_value;
	}

	/**
	 * Get total discount amount
	 *
	 * @since 3.0
	 * @access public
	 * @return float
	 */
	public function get_total_discount_amt() {
		$amt  = 0;
		$cart = new MP_Cart();

		$blog_ids = $cart->get_blog_ids();

		while ( 1 ) {
			if ( $cart->is_global ) {
				$blog_id = array_shift( $blog_ids );
				$cart->set_id( $blog_id );
			}

			$coupons = $this->get_applied_as_objects();

			foreach ( $coupons as $coupon ) {
				$amt += $coupon->discount_amt( false, false );
			}

			if ( ( $cart->is_global && false === current( $blog_ids ) ) || ! $cart->is_global ) {
				$cart->reset_id();
				break;
			}
		}

		return apply_filters( 'mp_coupon_total_value', (float) $amt );
	}

	/**
	 * Determine if there are applied coupons
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function has_applied() {
		return ( ! empty( $this->_coupons_applied ) );
	}

	/**
	 * Save the coupon data
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_insert_post_data
	 *
	 * @param array $data
	 * @param array $post
	 *
	 * @return array
	 */
	public function save_coupon_data( $data, $post ) {
		if ( $data['post_type'] != 'mp_coupon' || empty( $_POST['coupon_code'] ) ) {
			return $data;
		}

		$code = preg_replace( '/[^A-Z0-9_-]/', '', strtoupper( $_POST['coupon_code'] ) );

		$data['post_title']  = $code;
		$data['post_status'] = 'publish';
		$data['post_name']   = '';

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
		$applied       = $this->get_applied_as_objects();
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
		$metabox = new WPMUDEV_Metabox( array(
			'id'        => 'mp-coupons-metabox',
			'title'     => __( 'Coupon Settings', 'mp' ),
			'post_type' => 'mp_coupon',
			'context'   => 'normal',
		) );
		$metabox->add_field( 'text', array(
			'name'       => 'coupon_code',
			'desc'       => __( 'Letters and Numbers only.', 'mp' ),
			'validation' => array( 'required' => true, 'alphanumeric' => true ),
			'style'      => 'text-transform:uppercase',
			'label'      => array( 'text' => __( 'Coupon Code', 'mp' ) ),
		) );
		$metabox->add_field( 'text', array(
			'name'                      => 'discount',
			'desc'                      => __( 'If you would like to give a percentage-based discount make sure to include the percent (%) symbol. Otherwise, the discount will be applied as a fixed amount off.', 'mp' ),
			'validation'                => array( 'required' => true, 'custom' => '[0-9%.]' ),
			'custom_validation_message' => __( 'Value must either be a decimal number or a percentage', 'mp' ),
			'label'                     => array( 'text' => __( 'Discount Amount', 'mp' ) ),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'          => 'discount_type',
			'label'         => array( 'text' => __( 'How should the discount amount be applied?', 'mp' ) ),
			'default_value' => 'item',
			'options'       => array(
				'item'     => __( 'Apply to each applicable item and quantity ordered', 'mp' ),
				'subtotal' => __( 'Apply to each applicable item once per cart', 'mp' )
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'  => 'can_be_combined',
			'label' => array( 'text' => __( 'Can this coupon be combined with other coupons?', 'mp' ) ),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'allowed_coupon_combos',
			'label'       => array( 'text' => __( 'Select combinable coupons', 'mp' ) ),
			'desc'        => __( 'Leave blank to allow all other coupons.', 'mp' ),
			'multiple'    => true,
			'conditional' => array(
				'name'   => 'can_be_combined',
				'value'  => '1',
				'action' => 'show',
			),
			'query'       => array(
				'post_type' => 'mp_coupon'
			)
		) );
		$metabox->add_field( 'text', array(
			'name'       => 'max_uses',
			'desc'       => __( 'Enter the maximum number of times this coupon can be used.', 'mp' ),
			'class'      => 'digits',
			'label'      => array( 'text' => __( 'Max Uses', 'mp' ) ),
			'validation' => array(
				'digits' => true,
				'min'    => 0,
			),
		) );

		//Allow for the user to define the minimum number of products the cart has to have
		$metabox->add_field( 'checkbox', array(
			'name'  => 'product_count_limited',
			'label' => array( 'text' => __( 'Can this coupon be limited to a number of products in the cart?', 'mp' ) ),
		) );
		
		$metabox->add_field( 'text', array(
			'name'       => 'min_products',
			'desc'       => __( 'Enter the minimum number of products in the cart that this coupon can be used.', 'mp' ),
			'class'      => 'digits',
			'label'      => array( 'text' => __( 'Mimimum number of products', 'mp' ) ),
			'validation' => array(
				'digits' => true,
				'min'    => 0,
			),
			'conditional' => array(
				'name'   => 'product_count_limited',
				'value'  => '1',
				'action' => 'show',
			),
		) );

		//Option to only allow logged in users to use this
		$metabox->add_field( 'radio_group', array(
			'name'          => 'require_login',
			'label'         => array( 'text' => __( 'Require Login', 'mp' ) ),
			'desc'			=> __( 'Should this coupon only be available to logged in users?', 'mp' ),
			'default_value' => 'no',
			'options'       => array(
				'no'      => __( 'No', 'mp' ),
				'yes' 	  => __( 'Yes', 'mp' )
			),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'          => 'applies_to',
			'label'         => array( 'text' => __( 'Applies To', 'mp' ) ),
			'orientation'   => 'horizontal',
			'default_value' => 'all',
			'options'       => array(
				'all'      => __( 'All Products', 'mp' ),
				'category' => __( 'Category', 'mp' ),
				'product'  => __( 'Product', 'mp' ),
				'user'     => __( 'User', 'mp' ),
			),
		) );

		$metabox->add_field( 'post_select', array(
			'name'        => 'product',
			'validation'  => array( 'required' => true ),
			'multiple'    => true,
			'placeholder' => __( 'Select Products', 'mp' ),
			'query'       => array( 'post_type' => MP_Product::get_post_type(), 'posts_per_page' => 20 ),
			'label'       => array( 'text' => __( 'Product', 'mp' ) ),
			'conditional' => array(
				'name'   => 'applies_to',
				'value'  => 'product',
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'taxonomy_select', array(
			'name'        => 'category',
			'validation'  => array( 'required' => true ),
			'multiple'    => true,
			'placeholder' => __( 'Select Category', 'mp' ),
			'taxonomy'    => 'product_category',
			'label'       => array( 'text' => __( 'Category', 'mp' ) ),
			'conditional' => array(
				'name'   => 'applies_to',
				'value'  => 'category',
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'user_select', array(
			'name'        => 'user',
			'validation'  => array( 'required' => true ),
			'label'       => array( 'text' => __( 'User', 'mp' ) ),
			'conditional' => array(
				'name'   => 'applies_to',
				'value'  => 'user',
				'action' => 'show',
			),
		) );
		
		//Paul Kevin
		//Allow also category assigning to a user
		$metabox->add_field( 'taxonomy_select', array(
			'name'        => 'user_category',
			'multiple'    => true,
			'placeholder' => __( 'Select Category', 'mp' ),
			'desc'		  => __( 'Optionally limit the user to some categories', 'mp' ),
			'taxonomy'    => 'product_category',
			'label'       => array( 'text' => __( 'Category', 'mp' ) ),
			'conditional' => array(
				'name'   	=> 'applies_to',
				'value'  	=> 'user',
				'action' 	=> 'show',
				'operator' 	=> 'AND',
			)
		) );
		//End Condition

		$metabox->add_field( 'datepicker', array(
			'name'          => 'start_date',
			'validation'    => array( 'required' => true ),
			'label'         => array( 'text' => __( 'Start Date', 'mp' ) ),
			'default_value' => date( 'Y-m-d' ),
		) );

		$metabox->add_field( 'checkbox', array(
			'name'    => 'has_end_date',
			'label'   => array( 'text' => __( 'Does coupon have an end date?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
		) );

		$metabox->add_field( 'datepicker', array(
			'name'        => 'end_date',
			'label'       => array( 'text' => __( 'End Date', 'mp' ) ),
			'conditional' => array(
				'name'   => 'has_end_date',
				'value'  => '1',
				'action' => 'show',
			),
		) );
	}

	/**
	 * Init settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 * @action init
	 */
	public function init_settings_metaboxes() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-coupons-settings-metabox',
			'title'       => __( 'Coupons Settings', 'mp' ),
			'page_slugs'  => array( 'store-settings-addons' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'text', array(
			'name'          => 'coupons[form_title]',
			'label'         => array( 'text' => __( 'Form Title', 'mp' ) ),
			'default_value' => __( 'Have a coupon code?', 'mp' ),
		) );
		$metabox->add_field( 'wysiwyg', array(
			'name'          => 'coupons[help_text]',
			'label'         => array( 'text' => __( 'Help Text', 'mp' ) ),
			'default_value' => __( 'More than one code? That\'s OK! Just be sure to enter one at a time.', 'mp' ),
		) );
	}

	/**
	 * Changes the sort order of product coupons
	 *
	 * @since 3.0
	 * @access public
	 * @action pre_get_posts
	 *
	 * @param object $query
	 */
	public function sort_product_coupons( $query ) {
		if ( $query->get( 'post_type' ) != 'mp_coupon' || get_current_screen()->id != 'edit-mp_coupon' ) {
			//bail
			return;
		}

		switch ( get_query_var( 'orderby' ) ) {
			case 'product_coupon_discount' :
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'meta_key', 'discount_amount' );
				break;

			case 'product_coupon_used' :
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'meta_key', 'times_used' );
				break;
		}
	}

	/**
	 * Defines the product coupon sortable columns
	 *
	 * @since 3.0
	 * @access public
	 * @action manage_edit-product_coupon_sortable_columns
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function product_coupon_sortable_columns( $columns ) {
		return array_merge( $columns, array(
			'discount' => 'product_coupon_discount',
			'used'     => 'product_coupon_used',
		) );
	}

	/**
	 * Prints applicable CSS
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_print_styles
	 */
	public function print_css() {
		if ( get_current_screen()->post_type != 'product_coupon' ) {
			return;
		}
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
		if ( get_current_screen()->id != 'mp_coupon' ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
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
		add_submenu_page( 'edit.php?post_type=' . MP_Product::get_post_type(), __( 'Coupons', 'mp' ), __( 'Coupons', 'mp' ), apply_filters( 'mp_coupons_capability', 'edit_mp_coupons' ), 'edit.php?post_type=mp_coupon' );
	}

	/**
	 * Apply a coupon
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param MP_Coupon $coupon The coupon object to apply.
	 */
	public function apply_coupon( $coupon ) {
		if ( ! $coupon instanceof MP_Coupon ) {
			return false;
		}

		if ( ! in_array( $coupon->ID, $this->_coupons_applied ) ) {
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
		$coupon_code = mp_get_post_value( 'coupon_code' );
		$blog_id     = mp_get_post_value( 'blog_id' );

		if ( false === $coupon_code ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid coupon code', 'mp' ),
			) );
		}

		if ( mp_cart()->is_global ) {
			mp_cart()->set_id( $blog_id );
			switch_to_blog( $blog_id );
		}

		$coupon = new MP_Coupon( $coupon_code );

		if ( ! $coupon->is_valid() ) {
			wp_send_json_error( array(
				'message' => __( 'Coupon can\'t be applied to this cart', 'mp' ),
			) );
		}

		$this->apply_coupon( $coupon );

		wp_send_json_success( array(
			'products'  => $coupon->get_products(),
			'cart_meta' => mp_cart()->cart_meta( false ),
		) );
	}

	/**
	 * Remove coupon (ajax)
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_cart_remove_coupon, wp_ajax_nopriv_mp_cart_remove_coupon
	 */
	public function ajax_remove_coupon() {
		$coupon_id = mp_get_post_value( 'coupon_id' );
		$blog_id   = mp_get_post_value( 'blog_id' );

		if ( mp_cart()->is_global ) {
			mp_cart()->set_id( $blog_id );
		}

		if ( $this->remove_coupon( $coupon_id ) ) {
			$coupon   = new MP_Coupon( $coupon_id );
			$products = $coupon->get_products();

			wp_send_json_success( array(
				'products' => $products,
				'cartmeta' => mp_cart()->cart_meta( false ),
			) );
		}

		wp_send_json_error( array(
			'message' => __( 'An error occurred while removing your coupon. Please try again.', 'mp' ),
		) );
	}

	/**
	 * Register post type
	 *
	 * @since 3.0
	 * @access public
	 */
	public function register_post_type() {
		register_post_type( 'mp_coupon', array(
			'labels'             => array(
				'name'               => __( 'Coupons', 'mp' ),
				'singular_name'      => __( 'Coupon', 'mp' ),
				'menu_name'          => __( 'Manage Coupons', 'mp' ),
				'all_items'          => __( 'Coupons', 'mp' ),
				'add_new'            => __( 'Create New', 'mp' ),
				'add_new_item'       => __( 'Create New Coupon', 'mp' ),
				'edit_item'          => __( 'Edit Coupon', 'mp' ),
				'edit'               => __( 'Edit', 'mp' ),
				'new_item'           => __( 'New Coupon', 'mp' ),
				'view_item'          => __( 'View Coupon', 'mp' ),
				'search_items'       => __( 'Search Coupons', 'mp' ),
				'not_found'          => __( 'No Coupons Found', 'mp' ),
				'not_found_in_trash' => __( 'No Coupons found in Trash', 'mp' ),
				'view'               => __( 'View Coupon', 'mp' )
			),
			'capability_type'    => array( 'mp_coupon', 'mp_coupons' ),
			'capabilities'       => array(
				'publish_posts'       => 'publish_mp_coupons',
				'edit_posts'          => 'edit_mp_coupons',
				'edit_others_posts'   => 'edit_others_mp_coupons',
				'delete_posts'        => 'delete_mp_coupons',
				'delete_others_posts' => 'delete_others_mp_coupons',
				'read_private_posts'  => 'read_private_mp_coupons',
				'edit_post'           => 'edit_mp_coupon',
				'delete_post'         => 'delete_mp_coupon',
				'read_post'           => 'read_mp_coupon',
			),
			'map_meta_cap'        => true,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'hierarchical'        => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array( '' ),
			'publicly_queryable'  => true,
			'exclude_from_search' => true
		) );
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
	 * Check items in cart. If cart is empty, remove all coupons
	 *
	 * @since 3.0
	 * @access public
	 */
	public function check_items_in_cart() {
		$cart = mp_cart();

		if( ! $cart->has_items() ) {
			$this->remove_all_coupons();
		}
	}

	/**
	 * Remove a given coupon
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param int $coupon_id The coupon ID to remove.
	 *
	 * @return bool
	 */
	public function remove_coupon( $coupon_id ) {
		if ( false !== ( $key = array_search( $coupon_id, $this->_coupons_applied ) ) ) {
			unset( $this->_coupons_applied[ $key ] );
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
		$action = mp_get_request_value( 'action' );

		/*if (
			mp_is_shop_page( 'cart' ) ||
			mp_is_shop_page( 'checkout' ) ||
			! empty( $_POST['is_cart_page'] ) || 
			( ! empty( $action ) && (
				strpos( $action, 'mp_process_checkout_return_') !== false ||
				$action === 'mp_process_checkout' || 
				$action === 'mp_update_checkout_data' || 
				$action === 'mp_coupons_apply' || 
				$action === 'mp_coupons_remove'
			) )
		) {*/
			$coupons = $this->get_applied_as_objects();

			foreach ( $coupons as $coupon ) {
				
				$products = $coupon->get_products( true );

				if ( in_array( $product->ID, $products ) ) {
					
					// Do not change lowest price after each coupon (this change the product total)
					if( ! isset( $price['before_coupon'] ) || empty( $price['before_coupon'] ) ) {
						$price['before_coupon'] = $price['lowest'];
					}
					
					// Get price after coupon
					$coupon_discount = $coupon->get_price( $price['before_coupon'] );
					$coupon_discount = $price['before_coupon'] - $coupon_discount;
					
					// Get amount of the coupon
					$lowest = $price['before_coupon'] - $coupon_discount;
					
					// Check if we have another coupon
					if( isset( $price['after_coupon'] ) && ! empty( $price['after_coupon'] ) ) {
						// If we already have another coupon applied we just remove current coupon from the price instead of recalculating price
						$price['after_coupon'] = $price['after_coupon'] - $coupon_discount;
					} else {
						// No coupon applied 
						$price['after_coupon'] = $lowest;
					}

					if ( $coupon->get_meta( 'discount_type' ) == 'item' ) {
						$price['lowest'] = $price['coupon'] = $price['sale']['amount'] = $price['after_coupon'];
					} else {
						$price['coupon'] = $coupon->get_price( $price['lowest'] );
					}
				}
			}
		//}

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
		
		$total = (float) mp_cart()->product_original_total() + (float) $this->get_total_discount_amt();	
		
		return (float) round( $total, 2 );
	}

	/**
	 * Defines the column headers for the product coupon list table
	 *
	 * @since 3.0
	 * @access public
	 * @action manage_product_coupon_posts_columns
	 *
	 * @param array $columns The default columns as specified by WP
	 *
	 * @return array
	 */
	public function product_coupon_column_headers( $columns ) {
		return array(
			'cb'          => '<input type="checkbox">',
			'title'       => __( 'Code', 'mp' ),
			'discount'    => __( 'Discount', 'mp' ),
			'used'        => __( 'Used', 'mp' ),
			'remaining'   => __( 'Remaining Uses', 'mp' ),
			'req_login'   => __( 'Requires Login', 'mp' ),
			'valid_dates' => __( 'Valid Dates', 'mp' ),
			'applies_to'  => __( 'Applies To', 'mp' ),
		);
	}

	/**
	 * Defines the list table data for product coupons
	 *
	 * @since 3.0
	 * @access public
	 * @action manage_product_coupon_posts_custom_column
	 *
	 * @param string $column The current column name
	 * @param int $post_id The current post id
	 */
	public function product_coupon_column_data( $column, $post_id ) {
		$coupon = new MP_Coupon( $post_id );

		switch ( $column ) {
			//! Discount
			case 'discount' :
				$coupon->discount_formatted();
				break;

			//! Remaining Uses
			case 'remaining' :
				$coupon->remaining_uses();
				break;
				
			//Check if login is required
			case 'req_login' :
				$require_login  = $coupon->get_meta( 'require_login' );
				echo ucfirst( $require_login );
				break;

			//! Used
			case 'used' :
				$coupon->meta( 'times_used', 0 );
				break;

			//! Valid Dates
			case 'valid_dates' :
				echo $coupon->get_meta( 'start_date' ) . ' &mdash;<br />';

				if ( ( $end = $coupon->get_meta( 'end_date' ) ) && $coupon->get_meta( 'has_end_date' ) ) {
					echo $end;
				} else {
					_e( 'No end', 'mp' );
				}
				break;

			case 'applies_to' :
				$applies_to = $coupon->get_meta( 'applies_to' );
				echo ucwords( str_replace( '_', ' ', $applies_to ) );
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
		if ( ! mp_is_shop_page( array( 'cart', 'checkout' ) ) ) {
			return;
		}

		wp_enqueue_style( 'mp-coupons', mp_plugin_url( 'includes/addons/mp-coupons/ui/css/mp-coupons.css' ), array(), MP_VERSION );
	}

	/**
	 * Enqueue frontend scripts
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_print_scripts
	 */
	public function enqueue_js_frontend() {
		if ( ! mp_is_shop_page( 'cart' ) ) {
			return;
		}

		wp_enqueue_script( 'mp-coupons', mp_plugin_url( 'includes/addons/mp-coupons/ui/js/mp-coupons.js' ), array(
			'jquery',
			'mp-cart'
		), MP_VERSION );
		wp_localize_script( 'mp-coupons', 'mp_coupons_i18n', array(
			'ajaxurl'  => admin_url( 'admin-ajax.php' ),
			'messages' => array(
				'required' => __( 'Please enter a code', 'mp' ),
				'added'    => __( 'Coupon added successfully', 'mp' ),
			),
		) );
	}

	/**
	 * Get all coupons from db
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_all() {
		if ( ! is_null( $this->_coupons ) ) {
			return $this->_coupons;
		}

		$this->_coupons = get_posts( array(
			'post_type'      => 'mp_coupon',
			'posts_per_page' => - 1,
		) );

		return $this->_coupons;
	}

	/**
	 * Get applied coupons from session
	 *
	 * @since 3.0
	 * @access public
	 * @action switch_blog
	 * @return array
	 */
	public function get_applied() {
		if ( is_multisite() ) {
			$blog_id                = mp_cart()->get_blog_id();
			$this->_coupons_applied = mp_get_session_value( "mp_cart_coupons->{$blog_id}", array() );
		} else {
			$this->_coupons_applied = mp_get_session_value( 'mp_cart_coupons', array() );
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

		return $this->_convert_to_objects( $applied );
	}

	public function user_has_cap( $allcaps, $caps, $args, $user ) {
		//check does this user is admin
		$role_caps = $user->get_role_caps();
		if ( ! isset( $role_caps['manage_options'] ) ) {
			return $allcaps;
		}
		//we need to check this is for only coupon post type
		$post_type = get_post_type_object( 'mp_coupon' );
		if ( ! is_object( $post_type ) ) {
			return $allcaps;
		}
		$pt_cap = (array) $post_type->cap;
		//do manualy map
		foreach ( $caps as $cap ) {
			if ( $found = array_search( $cap, $pt_cap ) ) {
				if ( isset( $role_caps[ $found ] ) && $role_caps[ $found ] == true ) {
					$allcaps[ $cap ] = true;
				}
			}
		}

		return $allcaps;
	}
}

MP_Coupons_Addon::get_instance();

if ( ! function_exists( 'mp_coupons_addon' ) ) :

	/**
	 * Get the MP_Coupons instance
	 *
	 * @since 3.0
	 * @return MP_Coupons
	 */
	function mp_coupons_addon() {
		return MP_Coupons_Addon::get_instance();
	}


endif;
