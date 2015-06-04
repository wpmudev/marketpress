<?php

/**
 * @author: Hoang Ngo
 */
class MP_PDF_Invoice {
	const PDF_INVOICE = 'invoice', PDF_SLIP = 'slip';

	private $settings;

	/**
	 * @since 3.0
	 * @access public
	 */
	public function __construct() {
		if ( ! class_exists( 'DOMPDF' ) ) {
			require_once dirname( __FILE__ ) . '/vendors/dompdf/dompdf_config.inc.php';
		}
		$this->settings = mp_get_setting( 'pdf_invoice' );
	}

	/**
	 * @param $order_id
	 * @param string $type
	 *
	 * @since 3.0
	 *
	 * @return bool
	 */
	public static function show_button( $order_id, $type = self::PDF_INVOICE ) {
		$order = new MP_Order( $order_id );
		if ( $order->exists() == false ) {
			return false;
		}
		//if current user is not the owner of this order or not admin, false
		if ( get_current_user_id() == $order->post_author || current_user_can( 'manage_options' ) ) {
			$wpnonce = wp_create_nonce( $order_id );
			//build html
			$http_params = array(
				'action'   => 'mp_invoice_pdf_generate',
				'wpnonce'  => $wpnonce,
				'order_id' => $order->post_name,
				'type'     => $type
			);
			$http_params = apply_filters( 'mp_pdf_invoice_button_params', $http_params, $type, $order );
			$html        = sprintf( '<a target="_blank" href="%s" class="button">%s</a>',
				add_query_arg( $http_params, admin_url( 'admin-ajax.php' ) ),
				$type == self::PDF_INVOICE ? __( "PDF Invoice", "mp" ) : __( "PDF Packing Slip", "mp" )
			);

			return apply_filters( 'mp_pdf_invoice_button', $html, $http_params, $type, $order );
		}
	}

	/**
	 * This function will create invoice pdf base on order passed
	 *
	 * @since 3.0
	 * @access public
	 */
	public function generate_pdf( $order_id, $type = self::PDF_INVOICE, $download = false ) {
		$order = new MP_Order( $order_id );
		//if order not exist, just return false
		if ( $order->exists() == false ) {
			return false;
		}

		$html = $this->build_pdf_content( $order, $type );

		//generate pdf generator
		$dompdf = new DOMPDF();
		$dompdf->load_html( $html );
		$dompdf->set_paper( 'letter', 'portrait' );
		$dompdf->render();

		if ( $download ) {
			//check does the runtime path exist
			$runtime = $this->create_runtime_dir();
			if ( ! is_dir( $runtime ) || ! is_writable( $runtime ) ) {
				die ( sprintf( __( "The dir %s not exist or not writeable", "mp" ), $runtime ) );
			}
			if ( $type == self::PDF_INVOICE ) {
				$file = 'invoice_' . $order->post_name . '.pdf';
			} else {
				$file = 'packing_' . $order->post_name . '.pdf';
			}
			$tmp_pdf = $runtime . '/' . $file;
			file_put_contents( $tmp_pdf, $dompdf->output() );

			header( "Content-Type: application/octet-stream" );

			header( "Content-Disposition: attachment; filename=" . urlencode( $file ) );
			header( "Content-Type: application/octet-stream" );
			header( "Content-Type: application/download" );
			header( "Content-Description: File Transfer" );
			header( "Content-Length: " . filesize( $tmp_pdf ) );
			flush(); // this doesn't really matter.
			$fp = fopen( $tmp_pdf, "r" );
			while ( ! feof( $fp ) ) {
				echo fread( $fp, 65536 );
				flush(); // this is essential for large downloads
			}
			fclose( $fp );
			unlink( $tmp_pdf );
		} else {
			$dompdf->stream( "invoice_" . $order->post_name . ".pdf", array( "Attachment" => false ) );
		}
	}

	/**
	 * @param $order_id
	 * @param string $type
	 *
	 * @return bool|string
	 * @since 3.0
	 */
	public function generate_pdf_file( $order_id, $type = self::PDF_INVOICE ) {
		$order = new MP_Order( $order_id );
		//if order not exist, just return false
		if ( $order->exists() == false ) {
			return false;
		}

		$html = $this->build_pdf_content( $order, $type );

		//generate pdf generator
		$dompdf = new DOMPDF();
		$dompdf->load_html( $html );
		$dompdf->set_paper( 'letter', 'portrait' );
		$dompdf->render();

		$runtime = $this->create_runtime_dir();
		if ( ! is_dir( $runtime ) || ! is_writable( $runtime ) ) {
			return false;
		}
		if ( $type == self::PDF_INVOICE ) {
			$file = 'invoice_' . $order->post_name . '.pdf';
		} else {
			$file = 'packing_' . $order->post_name . '.pdf';
		}
		$tmp_pdf = $runtime . '/' . $file;
		file_put_contents( $tmp_pdf, $dompdf->output() );

		return $tmp_pdf;
	}

	/**
	 * @param MP_Order $order
	 * @param string $type
	 *
	 * @return mixed|string
	 * @since 3.0
	 * @access private
	 */
	private function build_pdf_content( MP_Order $order, $type = self::PDF_INVOICE ) {
		$billing  = $order->get_address( 'billing' );
		$shipping = $order->get_address( 'shipping' );

		$order_details = array();
		$cart          = $order->get_cart();
		if ( $type == self::PDF_INVOICE ) {
			foreach ( $cart->get_items() as $key => $qty ) {
				$product         = new MP_Product( $key );
				$order_details[] = sprintf( '<tr><td>%s</td><td>%s</td><td>%s</td></tr>',
					$product->title( false ),
					$qty, $product->display_price( false )
				);
			}
			//times for the subtotal
			$order_details[] = sprintf( '<tr><td class="no-bg">%s</td><td class="no-bg">%s</td><td>%s</td></tr>',
				'', __( "Subtotal", "mp" ), $cart->product_total( true ) );
			$order_details[] = sprintf( '<tr><td class="no-bg">%s</td><td class="no-bg">%s</td><td>%s</td></tr>',
				'', __( "Shipping", "mp" ), $cart->shipping_total( true ) );
			$order_details[] = sprintf( '<tr><td class="no-bg">%s</td><td class="no-bg">%s</td><td>%s</td></tr>',
				'', __( "Total", "mp" ), $cart->total( true ) );
		} elseif ( $type == self::PDF_SLIP ) {
			//as packing, we don't show price data
			foreach ( $cart->get_items() as $key => $qty ) {
				$product         = new MP_Product( $key );
				$order_details[] = sprintf( '<tr><td>%s</td><td>%s</td></tr>',
					$product->title( false ),
					$qty
				);
			}
		}

		$order_details = implode( '', $order_details );
		//prepare logo
		$logo = '';
		if ( ! empty( $this->settings['template_logo'] ) && function_exists( 'gd_info' ) ) {
			$logo = sprintf( '<img height="80" src="%s"/>', $this->settings['template_logo'] );
		}

		$template = $this->settings['template'];
		if ( empty( $template ) ) {
			//use the default
			$template = dirname( __FILE__ ) . '/templates/default';
		}

		switch ( $type ) {
			case self::PDF_INVOICE :
				$template = $template . '/invoice.php';
				break;
			case self::PDF_SLIP:
				$template = $template . '/packing.php';
				break;
		}

		ob_start();
		include $template;

		$html = ob_get_clean();
		$data = array(
			'{{order_id}}'      => $order->get_id(),
			'{{billing}}'       => $billing,
			'{{shipping}}'      => $shipping,
			'{{order_details}}' => $order_details,
			'{{logo}}'          => $logo
		);
		$data = apply_filters( 'mp_pdf_invoice_params', $data );

		foreach ( $data as $key => $val ) {
			$html = str_replace( $key, $val, $html );
		}

		return $html;
	}

	/**
	 * @return string
	 * @since 3.0
	 */
	private function create_runtime_dir() {
		$wpdir        = wp_upload_dir();
		$runtime_path = $wpdir['basedir'] . '/mp_pdf_invoice';
		if ( ! is_dir( $runtime_path ) ) {
			mkdir( $runtime_path, 0777, true );
		}

		return $runtime_path;
	}
}