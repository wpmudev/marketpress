<?php

class MP_Store_Settings_Cart_Checkout {

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
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Store_Settings_Cart_Checkout();
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
		add_action('init', array(&$this, 'init_metaboxes'));
	}
	
	/**
	 * Initialize metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_minicart_settings();
		$this->init_misc_settings();
	}
	
	/**
	 * Init the cart/checkout settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_minicart_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-cart-checkout-mini-cart',
			'page_slugs'  => array( 'store-settings-cart-checkout', 'store-settings_page_store-settings-cart-checkout' ),
			'title'       => __( 'Mini-Cart Settings', 'mp' ),
			'option_name' => 'mp_settings',
		) );
		
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'disable_minicart',
			'label'		 => array( 'text' => __( 'Disable Mini Cart?', 'mp' ) ),
			'desc'		 => __( 'This option hide floating Mini Cart in top right corner.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
			'default_value' => false,
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_product_image',
			'label'         => array( 'text' => __( 'Show product image on Mini Cart?', 'mp' ) ),
			'desc'          => __( 'Do you want to display the product image on floating Mini Cart.', 'mp' ),
			'message'       => __( 'Yes', 'mp' ),
			'default_value' => true,
			'conditional'   => array(
				'name'   => 'disable_minicart',
				'value'  => 1,
				'action' => 'hide',
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_product_qty',
			'label'         => array( 'text' => __( 'Show product quantity on Mini Cart?', 'mp' ) ),
			'desc'          => __( 'Do you want to display the product quantity on floating Mini Cart.', 'mp' ),
			'message'       => __( 'Yes', 'mp' ),
			'default_value' => true,
			'conditional'   => array(
				'name'   => 'disable_minicart',
				'value'  => 1,
				'action' => 'hide',
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_product_price',
			'label'         => array( 'text' => __( 'Show product price on Mini Cart?', 'mp' ) ),
			'desc'          => __( 'Do you want to display the product price on floating Mini Cart.', 'mp' ),
			'message'       => __( 'Yes', 'mp' ),
			'conditional'   => array(
				'name'   => 'disable_minicart',
				'value'  => 1,
				'action' => 'hide',
			),
		) );
	}
	
	/**
	 * Init misc settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_misc_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-settings-cart-checkout-misc',
			'page_slugs'	 => array( 'store-settings-cart-checkout', 'store-settings_page_store-settings-cart-checkout' ),
			'title'			 => __( 'Miscellaneous Settings', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'special_instructions',
			'label'		 => array( 'text' => __( 'Show Special Instructions Field?', 'mp' ) ),
			'desc'		 => __( 'Enabling this field will display a textbox on the shipping checkout page for users to enter special instructions for their order. Useful for product personalization, etc.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
			'default_value' => false,
		) );
	}
	
}

MP_Store_Settings_Cart_Checkout::get_instance();