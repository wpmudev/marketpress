<?php

/**
 * @author: Hoang Ngo
 */
require_once dirname( __FILE__ ) . '/class-mp-pdf-invoice.php';

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
			$this->view_settings();
		}
		add_action( 'add_meta_boxes_mp_order', array( &$this, 'add_meta_box' ) );
		add_action( 'wp_ajax_mp_invoice_pdf_generate', array( &$this, 'generate_pdf' ) );
		add_action( 'wp_ajax_nopriv_mp_invoice_pdf_generate', array( &$this, 'generate_pdf' ) );
		add_filter( 'mp_order/details', array( &$this, 'pdf_buttons_order_status' ), 99, 2 );
		add_filter( 'mp_order/sendmail_attachments', array( &$this, 'mp_order_sendmail_attachments' ), 20, 3 );
	}

	/**
	 * @since 3.0
	 */
	public function generate_pdf() {
		$order_id = mp_get_get_value( 'order_id', null );
		if ( $order_id == null ) {
			die( __( "Invalid ID", "mp" ) );
		}
		//check does order exist
		$order = new MP_Order( $order_id );
		if ( $order->exists() == false ) {
			die( __( "Order not exist!", "mp" ) );
		}
		//check nonce
		if ( ! wp_verify_nonce( mp_get_get_value( 'wpnonce' ), $order->ID ) ) {
			die( __( "Invalid Request", "mp" ) );
		}
		//check does order belong to right
		if ( get_current_user_id() == $order->post_author || current_user_can( 'manage_options' ) ) {
			$gen      = new MP_PDF_Invoice();
			$settings = mp_get_setting( 'pdf_invoice' );
			$gen->generate_pdf( $order->get_id(), mp_get_get_value( 'type', MP_PDF_Invoice::PDF_INVOICE ), $settings['download'] == 'download' ? true : false );
			die;
		} else {
			//user stil not loggin
			$orders = mp_get_order_history();
			if ( is_array( $orders ) ) {
				$order = new MP_Order( $order_id );
				if ( $order->exists() ) {
					$found = false;
					foreach ( $orders as $key => $val ) {
						if ( $val['id'] == $order->ID ) {
							//this order belonged to this user
							$found = true;
							break;
						}
					}
					if ( $found == true ) {
						$gen      = new MP_PDF_Invoice();
						$settings = mp_get_setting( 'pdf_invoice' );
						$gen->generate_pdf( $order->get_id(), mp_get_get_value( 'type', MP_PDF_Invoice::PDF_INVOICE ), $settings['download'] == 'download' ? true : false );
					} else {
						die( __( "You can't download this order invoice", "mp" ) );
					}
				} else {
					die( __( "You can't download this order invoice", "mp" ) );
				}
			} else {
				die( __( "You can't download this order invoice", "mp" ) );
			}
		}
	}

	function mp_order_sendmail_attachments( $attachments, MP_Order $order, $contex ) {
		$settings = mp_get_setting( 'pdf_invoice' );
		$attach   = $settings['attach_to'];
		$gen      = new MP_PDF_Invoice();
		$files    = array();
		switch ( $contex ) {
			case 'new_order_client':
				if ( isset( $attach['customer_new_order'] ) && $attach['customer_new_order'] ) {
					$invoice = $gen->generate_pdf_file( $order->get_id(), MP_PDF_Invoice::PDF_INVOICE );
					$packing = $gen->generate_pdf_file( $order->get_id(), MP_PDF_Invoice::PDF_SLIP );
					$files   = array( $invoice, $packing );
				}
				break;
			case 'new_order_admin':
				if ( isset( $attach['admin_new_order'] ) && $attach['admin_new_order'] ) {
					$invoice = $gen->generate_pdf_file( $order->get_id(), MP_PDF_Invoice::PDF_INVOICE );
					$packing = $gen->generate_pdf_file( $order->get_id(), MP_PDF_Invoice::PDF_SLIP );
					$files   = array( $invoice, $packing );
				}
				break;
			case 'order_shipped_client':
				if ( isset( $attach['customer_shipped_order'] ) && $attach['customer_shipped_order'] ) {
					$invoice = $gen->generate_pdf_file( $order->get_id(), MP_PDF_Invoice::PDF_INVOICE );
					$packing = $gen->generate_pdf_file( $order->get_id(), MP_PDF_Invoice::PDF_SLIP );
					$files   = array( $invoice, $packing );
				}
				break;
		}
		if ( $settings['quit_on_free'] == 1 ) {
			//check does the order is 0
			if ( $order->get_cart()->total( false ) == 0 ) {
				$files = array();
			}
		}

		$attachments = array_merge( $attachments, $files );

		return $attachments;
	}

	/**
	 * @since 3.0
	 */
	public function add_meta_box() {
		add_meta_box( 'mp-order-pdf-metabox', __( 'Export PDF', 'mp' ), array(
			&$this,
			'show_ipn_button_on_order_admin_detail'
		), 'mp_order', 'side', 'high' );
	}

	/**
	 * @param $html
	 * @param $order
	 *
	 * @since 3.0
	 */
	public function pdf_buttons_order_status( $html, $order ) {

		$buttons =
			'<section id="mp-pdf-invoice" class="mp_orders_invoice">' . MP_PDF_Invoice::show_button( $order->ID, MP_PDF_Invoice::PDF_INVOICE ) . '&nbsp;' .
			'</section><!-- end mp-pdf-invoice -->';

		return $html . $buttons;
	}

	/**
	 * @param $post
	 *
	 * @since 3.0
	 */
	public function show_ipn_button_on_order_admin_detail( $post ) {
		echo MP_PDF_Invoice::show_button( $post->ID, MP_PDF_Invoice::PDF_INVOICE ) . '&nbsp;';
		echo MP_PDF_Invoice::show_button( $post->ID, MP_PDF_Invoice::PDF_SLIP );
	}

	/**
	 * @since 3.0
	 */
	public function view_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-invoice-pdf-general-metabox',
			'title'       => __( 'General Settings', 'mp' ),
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
				//'admin_shipped_order'    => __( "Admin Order Shipped email", "mp" ),
				'customer_shipped_order' => __( "Customer Order Shipped email", "mp" )
			),
			'label'   => array( 'text' => __( 'Attach invoice to', 'mp' ) )
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'pdf_invoice[quit_on_free]',
			'label'   => array( 'text' => __( "Disable for free products", "mp" ) ),
			'message' => __( "Disable automatic creation/attachment of invoices when only free products are ordered", "mp" )
		) );

		$metabox   = new WPMUDEV_Metabox( array(
			'id'          => 'mp-invoice-pdf-template-metabox',
			'title'       => __( 'Template Settings', 'mp' ),
			'page_slugs'  => array( 'store-settings-addons' ),
			'option_name' => 'mp_settings',
		) );
		$templates = $this->scan_templates();
		$metabox->add_field( 'select', array(
			'name'    => 'pdf_invoice[template]',
			'label'   => array( 'text' => __( "Choose a template", 'mp' ) ),
			'options' => $templates
		) );
		$metabox->add_field( 'file', array(
			'name'  => 'pdf_invoice[template_logo]',
			'label' => array( 'text' => __( "Shop header/logo", "mp" ) ),
		) );
		/*$metabox->add_field( 'text', array(
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
		) );*/
	}

	private function scan_templates() {
		$path      = dirname( __FILE__ ) . '/templates/';
		$templates = array();
		foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ) ) as $filename ) {
			// filter out "." and ".."
			if ( $filename->isDir() || strtolower( $filename->getExtension() ) != 'php' ) {
				continue;
			}
			$path = $filename->getRealPath();
			$meta = get_file_data( $path, array(
				'Name'        => 'Name',
				'Author'      => 'Author',
				'Description' => 'Description'
			), 'mp_pdf_invoice' );

			if ( ! empty( $meta['Name'] ) ) {
				//$meta['path'] = dirname( $path );
				$templates[ dirname( $path ) ] = $meta['Name'];
			}
		}

		return $templates;
	}
}

if ( ! function_exists( 'mppdf' ) ) {
	function mppdf() {
		return MP_PDF_Invoice_Addon::get_instance();
	}
}
mppdf();
