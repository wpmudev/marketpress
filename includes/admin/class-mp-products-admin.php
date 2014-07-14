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
		//init metaboxes
		$this->init_variations_metabox();
		$this->init_attributes_metabox();	
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
	 * Initializes the product variation metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_variations_metabox() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-product-variations-metabox',
			'title' => __('Variations', 'mp'),
			'post_type' => 'product',
			'context' => 'normal',
		));
		$metabox->add_field('checkbox', array(
			'name' => 'has_variations',
			'message' => __('Does this product have variations such as color, size, etc?', 'mp'),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'variation_type',
			'label' => array('text' => __('Variation Type', 'mp')),
			'default_value' => 'physical',
			'options' => array(
				'physical' => __('Physical/Tangible Product', 'mp'),
				'digital' => __('Digital Download', 'mp'),				
				'external' => __('External/Affiliate Link', 'mp'),
			),
			'conditional' => array(
				'name' => 'has_variations',
				'value' => 1,
				'action' => 'show',
			),
		));
		$repeater = $metabox->add_field('repeater', array(
			'name' => 'variations',
			'layout' => 'rows',
			'add_row_label' => __('Add Variation', 'mp'),
			'conditional' => array(
				'name' => 'has_variations',
				'value' => 1,
				'action' => 'show',
			),
		));
		
		if ( $repeater instanceof WPMUDEV_Field ) {
			$repeater->add_sub_field('image', array(
				'name' => 'image',
				'label' => array('text' => __('Image', 'mp')),
			));
			$repeater->add_sub_field('text', array(
				'name' => 'sku',
				'label' => array('text' => __('SKU', 'mp')),
			));
			$repeater->add_sub_field('text', array(
				'name' => 'inventory',
				'label' => array('text' => __('Inventory Count', 'mp')),
				'desc' => __('Enter the quantity that you have available to sell. Leave blank if you do not want to track inventory.', 'mp'),
				'conditional' => array(
					'name' => 'variation_type',
					'value' => 'physical',
					'action' => 'show',
				),
			));
			$repeater->add_sub_field('text', array(
				'name' => 'regular_price',
				'label' => array('text' => __('Regular Price', 'mp')),
				'conditional' => array(
					'name' => 'variation_type',
					'value' => array('physical', 'digital'),
					'action' => 'show',
				),
			));
			$sale_price = $repeater->add_sub_field('complex', array(
				'name' => 'sale_price',
				'label' => array('text' => __('Sale Price', 'mp')),
				'conditional' => array(
					'name' => 'variation_type',
					'value' => array('physical', 'digital'),
					'action' => 'show',
				),												
			));
			$sale_price->add_field('text', array(
				'name' => 'price',
				'label' => array('text' => __('Price', 'mp')),
			));
			$sale_price->add_field('datepicker', array(
				'name' => 'start_date',
				'label' => array('text' => __('Start Date', 'mp')),
			));
			$sale_price->add_field('datepicker', array(
				'name' => 'end_date',
				'label' => array('text' => __('End Date (if applicable)', 'mp')),
			));
			$weight = $repeater->add_sub_field('complex', array(
				'name' => 'weight',
				'label' => array('text' => __('Weight', 'mp')),
				'conditional' => array(
					'name' => 'variation_type',
					'value' => 'physical',
					'action' => 'show',
				),				
			));
			$weight->add_field('text', array(
				'name' => 'pounds',
				'label' => array('text' => __('Pounds', 'mp')),
			));
			$weight->add_field('text', array(
				'name' => 'ounces',
				'label' => array('text' => __('ounces', 'mp')),
			));
			$repeater->add_sub_field('text', array(
				'name' => 'extra_shipping_cost',
				'label' => array('text' => __('Extra Shipping Cost', 'mp')),
				'default_value' => '0.00',
				'conditional' => array(
					'name' => 'variation_type',
					'value' => array('physical', 'digital'),
					'action' => 'show',
				),	
			));
			$repeater->add_sub_field('text', array(
				'name' => 'special_tax_rate',
				'label' => array('text' => __('Special Tax Rate', 'mp')),
				'default_value' => '0.00',
				'conditional' => array(
					'name' => 'variation_type',
					'value' => array('physical', 'digital'),
					'action' => 'show',
				),	
			));			
			$repeater->add_sub_field('file', array(
				'name' => 'file_url',
				'label' => array('text' => __('File URL', 'mp')),
				'conditional' => array(
					'name' => 'variation_type',
					'value' => 'digital',
					'action' => 'show',
				),	
			));
			$repeater->add_sub_field('wysiwyg', array(
				'name' => 'description',
				'label' => array('text' => __('Description', 'mp')),
				'desc' => __('If you would like the description to be different than the main product enter it here.', 'mp'),
			));
		}
	}
	
	/**
	 * Initializes the product attributes metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_attributes_metabox() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-product-attributes-metabox',
			'title' => __('Attributes', 'mp'),
			'post_type' => 'product',
			'context' => 'normal',
		));		
	}
}

MP_Products_Screen::get_instance();