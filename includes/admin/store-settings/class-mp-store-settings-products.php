<?php

class MP_Store_Settings_Products {

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
			self::$_instance = new MP_Store_Settings_Products();
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
		$this->init_general_settings();
		$this->init_stock_settings();
		$this->init_digital_products_settings();
	}
	
	/**
	 * Init the general settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_general_settings() {}
	
	/**
	 * Init the general settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_stock_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-settings-products-stock',
			'page_slugs'	 => array( 'store-settings-products', 'store-settings_page_store-settings-products' ),
			'title'			 => __( 'Stock Settings', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		
		$metabox->add_field( 'text', array(
			'name'		 => 'inventory_threshhold',
			'label'		 => array( 'text' => __( 'Inventory Warning Threshold', 'mp' ) ),
			'desc'		 => __( 'At what low stock count do you want to be warned for products you have enabled inventory tracking for?', 'mp' ),
			'style'		 => 'width:50px;',
			'validation' => array(
				'required'	 => true,
				'digits'	 => true,
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'inventory_remove',
			'label'		 => array( 'text' => __( 'Hide Out of Stock Products?', 'mp' ) ),
			'desc'		 => __( 'This will set the product to draft if inventory of all variations is gone.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
	}
	
	/**
	 * Init digital products settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_digital_products_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-settings-products-digital',
			'page_slugs'	 => array( 'store-settings-products', 'store-settings_page_store-settings-products' ),
			'title'			 => __( 'Digital Products Settings', 'mp' ),
			'option_name'	 => 'mp_settings',
		) );
		
		$metabox->add_field( 'checkbox', array(
			'name'		 => 'download_order_limit',
			'label'		 => array( 'text' => __( 'Limit Digital Products Per-order?', 'mp' ) ),
			'desc'		 => __( 'This will prevent multiples of the same downloadable product form being added to the cart.', 'mp' ),
			'message'	 => __( 'Yes', 'mp' ),
		) );
		
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'details_collection',
			'label'			 => array( 'text' => __( 'Details Collection', 'mp' ) ),
			'default_value'	 => 'full',
			'orientation'	 => 'horizontal',
			'options'		 => array(
				'full'		 => __( 'Full billing info', 'mp' ),
				'contact'		 => __( 'Only contact details', 'mp' ),
			),
		) );
		
	}
	
}

MP_Store_Settings_Products::get_instance();