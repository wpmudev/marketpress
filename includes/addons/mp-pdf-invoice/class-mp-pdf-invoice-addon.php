<?php

/**
 * @author: Hoang Ngo
 */
class MP_PDF_Invoice_Addon {

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
			self::$_instance = new MP_PDF_Invoice_Addon();
		}

		return self::$_instance;
	}

	/**
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		//we will use every hook lower than init
		add_action( 'init', array( &$this, 'init' ) );
	}

	/**
	 * Init all the needed
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init() {
		if ( mp_get_get_value( 'addon', null ) == 'MP_PDF_Invoice_Addon' ) {
			//addon settings
			add_action( 'wp_loaded', array( &$this, 'view_settings' ) );
		}
	}

	/**
	 * @since 3.0
	 */
	public function view_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-invoice-pdf-general-metabox',
			'title'       => __( 'General Settings' ),
			'page_slugs'  => array( 'store-settings-addons' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'select', array(
			'name'          => 'pdf_invoice[download]',
			'options'       => array(
				'download' => __( "Download the PDF", 'mp' ),
				'new_tab'  => __( "Open the PDF in a new browser tab/window", 'mp' )
			),
			'label'         => array( 'text' => __( 'How do you want to view the PDF?', 'mp' ) ),
			'default_value' => __( 'download', 'mp' ),
		) );
		$metabox->add_field( 'checkbox_group', array(
			'name'    => 'pdf_invoice[attach_to]',
			'options' => array(
				'admin_new_order'        => __( "Admin New Order email", "mp" ),
				'customer_new_order'     => __( "Customer New Order email", "mp" ),
				'admin_shipped_order'    => __( "Admin Order Shipped email", "mp" ),
				'customer_shipped_order' => __( "Customer Order Shipped email", "mp" )
			),
			'label'   => array( 'text' => __( 'Attach invoice to', 'mp' ) )
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'pdf_invoice[quit_on_free]',
			'label'   => array( 'text' => __( "Disable for free products", "mp" ) ),
			'message' => __( "Disable automatic creation/attachment of invoices when only free products are ordered", "mp" )
		) );

		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-invoice-pdf-template-metabox',
			'title'       => __( 'Template Settings' ),
			'page_slugs'  => array( 'store-settings-addons' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'select', array(
			'name'    => 'pdf_invoice[template]',
			'label'   => array( 'text' => __( "Choose a template", 'mp' ) ),
			'options' => array()
		) );
		$metabox->add_field( 'file', array(
			'name'  => 'pdf_invoice[template_logo]',
			'label' => array( 'text' => __( "Shop header/logo", "mp" ) ),
		) );
		$metabox->add_field( 'text', array(
			'name'  => 'pdf_invoice[shop_name]',
			'label' => array( 'text' => __( "Shop name", "mp" ) )
		) );
		$metabox->add_field( 'textarea', array(
			'name'  => 'pdf_invoice[shop_address]',
			'label' => array( 'text' => __( "Shop Address", "mp" ) )
		) );
		$metabox->add_field( 'textarea', array(
			'name'  => 'pdf_invoice[footer]',
			'label' => array( 'text' => __( "Footer", "mp" ) )
		) );
	}
}

if ( ! function_exists( 'mppdf' ) ) {
	function mppdf() {
		return MP_PDF_Invoice_Addon::get_instance();
	}
}
mppdf();