<?php

class MP_Store_Settings_Notifications {
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
			self::$_instance = new MP_Store_Settings_Notifications();
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
	 * Init metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-admin-settings-notifications',
			'page_slugs' => array('store-settings-notifications', 'store-settings_page_store-settings-notifications'),
			'title' => __('Admin Notification Settings', 'mp'),
			'option_name' => 'mp_settings',
		));
		$metabox->add_field('text', array(
			'name' => 'store_email',
			'label' => array('text' => __('Notification Email', 'mp')),
			'validation' => array(
				'email' => 1,
			),
		));
		
		$new_order = $metabox->add_field('complex', array(
			'name' => 'email[admin_order]',
			'label' => array('text' => __('New Order', 'mp')),
			'layout' => 'rows',
		));
		
		if ( $new_order instanceof WPMUDEV_Field ) {
			$new_order->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Subject', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$new_order->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
				'validation' => array(
					'required' => true,
				),
			));
		}
		
		$customer_metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-customer-settings-notifications',
			'page_slugs' => array('store-settings-notifications', 'store-settings_page_store-settings-notifications'),
			'title' => __('Customer Notification Settings', 'mp'),
			'option_name' => 'mp_settings',
		));

		$new_order_section = $customer_metabox->add_field('section', array(
			'name' => 'new_order_section',
			'title' => __('New Order', 'mp'),
			'subtitle' => __('These codes will be replaced with order details: CUSTOMERNAME, ORDERID, ORDERINFO, SHIPPINGINFO, PAYMENTINFO, TOTAL, TRACKINGURL. No HTML allowed.<br/>For orders placed with manual payment, emails set here will be overriden by the one configured in manual payments settings, under payments settings page', 'mp'),
			'before_field' => '<div id="new_order_tabs_wrapper">',
		));

		$new_order_tab_labels = $customer_metabox->add_field('tab_labels', array(
			'name' => 'new_order_tabs_labels',
			'tabs' => array(
				array(
					"active"=> true,
					"label"=> __('Physical orders', 'mp'),
					"slug"=>"new_order_tab"
				),
				array(
					"active" => false,
					"label" => __( 'Digital downloads orders', 'mp' ),
					"slug" => "new_order_downloads_tab"
				),
				array(
					"active" => false,
					"label" => __( 'Mixed orders', 'mp' ),
					"slug" => "new_order_mixed_tab",
				),
			),
		));

		$new_order_tab = $customer_metabox->add_field('tab', array(
			'name' => 'new_order_tab',
			'slug' => 'new_order_tab',
		));

		$new_order = $customer_metabox->add_field('complex', array(
			'name' => 'email[new_order]',
			'label' => array('text' => __('New Order', 'mp')),
			'layout' => 'rows',
		));
		
		if ( $new_order instanceof WPMUDEV_Field ) {
			$new_order->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Subject', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$new_order->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
				'validation' => array(
					'required' => true,
				),
			));
		}

		$new_order_downloads_tab = $customer_metabox->add_field('tab', array(
			'name' => 'new_order_downloads_tab',
			'slug' => 'new_order_downloads_tab',
		));

		$new_order_downloads = $customer_metabox->add_field('complex', array(
			'name' => 'email[new_order_downloads]',
			'label' => array('text' => __('New Order - Digital downloads only orders', 'mp')),
			'layout' => 'rows',
		));
		
		if ( $new_order_downloads instanceof WPMUDEV_Field ) {
			$new_order_downloads->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Subject', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$new_order_downloads->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
				'validation' => array(
					'required' => true,
				),
			));
		}

		$new_order_mixed_tab = $customer_metabox->add_field('tab', array(
			'name' => 'new_order_mixed_tab',
			'slug' => 'new_order_mixed_tab',
		));

		$new_order_mixed = $customer_metabox->add_field('complex', array(
			'name' => 'email[new_order_mixed]',
			'label' => array('text' => __('New Order - Mixed', 'mp')),
			'layout' => 'rows',
			'after_field' => '</div>', // close the #new_order_tabs_wrapper div
		));
		
		if ( $new_order_mixed instanceof WPMUDEV_Field ) {
			$new_order_mixed->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Subject', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$new_order_mixed->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
				'validation' => array(
					'required' => true,
				),
			));
		}

		$order_shipped_section = $customer_metabox->add_field('section', array(
			'name' => 'order_shipped_section',
			'title' => __('Order Shipped', 'mp'),
			'subtitle' => __('These codes will be replaced with order details: CUSTOMERNAME, ORDERID, ORDERINFO, SHIPPINGINFO, PAYMENTINFO, TOTAL, TRACKINGURL. No HTML allowed.', 'mp'),
			'before_field' => '<div id="order_shipped_tabs_wrapper">',
		));

		$order_shiped_tab_labels = $customer_metabox->add_field('tab_labels', array(
			'name' => 'tabs_labels',
			'tabs' => array(
				array(
					"active"=> true,
					"label"=> __('Physical orders', 'mp'),
					"slug"=>"order_shipped_tab"
				),
				array(
					"active" => false,
					"label" => __( 'Digital downloads orders', 'mp' ),
					"slug" => "order_shipped_downloads_tab"
				),
				array(
					"active" => false,
					"label" => __( 'Mixed orders', 'mp' ),
					"slug" => "order_shipped_mixed_tab",
				),
			),
		));

		$order_shipped_tab = $customer_metabox->add_field('tab', array(
			'name' => 'order_shipped_tab',
			'slug' => 'order_shipped_tab',
		));

		$order_shipped = $customer_metabox->add_field('complex', array(
			'name' => 'email[order_shipped]',
			'label' => array('text' => __('Order Shipped', 'mp')),
			'layout' => 'rows',
		));
		
		if ( $order_shipped instanceof WPMUDEV_Field ) {
			$order_shipped->add_field('text', array(
				'name' => 'subject',
				'label' => array('text' => __('Subject', 'mp')),
				'validation' => array(
					'required' => true,
				),
			));
			$order_shipped->add_field('textarea', array(
				'name' => 'text',
				'label' => array('text' => __('Text', 'mp')),
				'custom' => array('rows' => 15),
				'validation' => array(
					'required' => true,
				),
			));
		}

		$order_shipped_downloads_tab = $customer_metabox->add_field('tab', array(
			'name' => 'order_shipped_downloads_tab',
			'slug' => 'order_shipped_downloads_tab',
		));

		$order_shipped_downloads = $customer_metabox->add_field( 'complex', array(
			'name' => 'email[order_shipped_downloads]',
			'label' => array( 'text' => __( 'Order Shipped - Digital downloads only orders', 'mp' ) ),
			'layout' => 'rows',
		) );
		
		if ( $order_shipped_downloads instanceof WPMUDEV_Field ) {
			$order_shipped_downloads->add_field( 'text', array(
				'name' => 'subject',
				'label' => array( 'text' => __( 'Subject', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$order_shipped_downloads->add_field( 'textarea', array(
				'name' => 'text',
				'label' => array( 'text' => __( 'Text', 'mp' ) ),
				'custom' => array( 'rows' => 15 ),
				'validation' => array(
					'required' => true,
				),
			) );
		}

		$order_shipped_mixed_tab = $customer_metabox->add_field('tab', array(
			'name' => 'order_shipped_mixed_tab',
			'slug' => 'order_shipped_mixed_tab',
		));

		$order_shipped_mixed = $customer_metabox->add_field( 'complex', array(
			'name' => 'email[order_shipped_mixed]',
			'label' => array( 'text' => __( 'Order Shipped - Mixed orders (with both digital and physical products)', 'mp' ) ),
			'layout' => 'rows',
			'after_field' => '</div>', // close the #order_shipped_tabs_wrapper div
		) );
		
		if ( $order_shipped_mixed instanceof WPMUDEV_Field ) {
			$order_shipped_mixed->add_field( 'text', array(
				'name' => 'subject',
				'label' => array( 'text' => __( 'Subject', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$order_shipped_mixed->add_field( 'textarea', array(
				'name' => 'text',
				'label' => array( 'text' => __( 'Text', 'mp' ) ),
				'custom' => array( 'rows' => 15 ),
				'validation' => array(
					'required' => true,
				),
			) );
		}

		// This field is outside the #order_shipped_tabs_wrapper div, so it won't be appended to the last tab
		$customer_metabox->add_field('checkbox', array(
			'name' => 'email_registration_email',
			'message' => __('Yes', 'mp'),
			'label' => array( 'text' => __('Notification to registration email instead of billing email?', 'mp')),
		));
	}
}

MP_Store_Settings_Notifications::get_instance();