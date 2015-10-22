<?php

class Mp_Export {
	
	public $products_columns = array();
	public $orders_columns = array();
	public $customers_columns = array();
	public $messages = array();
	public $errors = false;

	private $managment_page;
	
	/**
	 * Construct the plugin object
	 */
	public function __construct() {

		$this->products_columns = $this->get_products_columns();
		$this->orders_columns = $this->get_orders_columns();
		// $this->customers_columns = $this->get_customers_columns();

		// register actions
		add_action( 'admin_menu', array( &$this, 'add_menu' ) );

	} // END public function __construct

	/**
	* Get Products columns to export
	*/
	public function get_products_columns() {
		
		return array(
			'ID'                         => array( 'name' => __( 'ID', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_author'                => array( 'name' => __( 'Post Author', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_date'                  => array( 'name' => __( 'Post Date', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_date_gmt'              => array( 'name' => __( 'Post Date GMT', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_content'               => array( 'name' => __( 'Post Content', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_title'                 => array( 'name' => __( 'Post Title', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_excerpt'               => array( 'name' => __( 'Post Excerpt', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_status'                => array( 'name' => __( 'Post Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'comment_status'             => array( 'name' => __( 'Comment Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'ping_status'                => array( 'name' => __( 'Ping Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_password'              => array( 'name' => __( 'Post Password', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_name'                  => array( 'name' => __( 'Post Name', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'to_ping'                    => array( 'name' => __( 'To Ping', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'pinged'                     => array( 'name' => __( 'Pinged', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_modified'              => array( 'name' => __( 'Post Modified', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_modified_gmt'          => array( 'name' => __( 'Post Modified GMT', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_content_filtered'      => array( 'name' => __( 'Post Content Filtered', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_parent'                => array( 'name' => __( 'Post Parent', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'guid'                       => array( 'name' => __( 'GUID', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'menu_order'                 => array( 'name' => __( 'Menu Order', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_type'                  => array( 'name' => __( 'Post Type', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_mime_type'             => array( 'name' => __( 'Post Mime Type', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'comment_count'              => array( 'name' => __( 'Comment Count', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'_edit_last'                 => array( 'name' => __( 'Edit Last', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'_edit_lock'                 => array( 'name' => __( 'Edit Lock', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'_thumbnail_id'              => array( 'name' => __( 'Thumbnail ID', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'charge_shipping'            => array( 'name' => __( 'Charge Shipping', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_charge_shipping' ),
			'charge_tax'                 => array( 'name' => __( 'Charge Tax', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_charge_tax' ),
			'external_url'               => array( 'name' => __( 'External URL', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_external_url' ),
			'file_url'                   => array( 'name' => __( 'File URL', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_file_url' ),
			'has_sale'                   => array( 'name' => __( 'Has Sale', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_has_sale' ),
			'has_variation'              => array( 'name' => __( 'Has Variation', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_has_variation' ),
			'inv'                        => array( 'name' => __( 'Inv', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_inv' ),
			'inv_inventory'              => array( 'name' => __( 'Inv Inventory', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_inv_inventory' ),
			'inv_out_of_stock_purchase'  => array( 'name' => __( 'Inv Out Of Stock Purchase', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_inv_out_of_stock_purchase' ),
			'inventory'                  => array( 'name' => __( 'Inventory', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'inventory_tracking'         => array( 'name' => __( 'Inventory Tracking', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_inventory_tracking' ),
			'mp_product_images'          => array( 'name' => __( 'MP Product Images', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_sales_count'             => array( 'name' => __( 'MP Sales Count', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'per_order_limit'            => array( 'name' => __( 'Per Order Limit', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_per_order_limit' ),
			'product_images'             => array( 'name' => __( 'Product Images', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_product_images' ),
			'product_type'               => array( 'name' => __( 'Product Type', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_product_type' ),
			'regular_price'              => array( 'name' => __( 'Regular Price', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_regular_price' ),
			'related_products'           => array( 'name' => __( 'Related Products', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_related_products' ),
			'sale_price'                 => array( 'name' => __( 'Sale Price', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_sale_price' ),
			'sale_price_amount'          => array( 'name' => __( 'Sale Price Amount', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_sale_price_amount' ),
			'sale_price_end_date'        => array( 'name' => __( 'Sale Price End Date', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_sale_price_end_date' ),
			'sale_price_start_date'      => array( 'name' => __( 'Sale Price Start Date', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_sale_price_start_date' ),
			'sku'                        => array( 'name' => __( 'SKU', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_sku' ),
			'special_tax_rate'           => array( 'name' => __( 'Special Tax Rate', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_special_tax_rate' ),
			'variations_module'          => array( 'name' => __( 'Variation Module', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_variations_module' ),
			'weight'                     => array( 'name' => __( 'Weight', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_weight' ),
			'weight_extra_shipping_cost' => array( 'name' => __( 'Weight Extra Shipping Cost', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_weight_extra_shipping_cost' ),
			'weight_pounds'              => array( 'name' => __( 'Weight Pounds', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_weight_pounds' ),
			'weight_ounces'              => array( 'name' => __( 'Weight Ounces', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_weight_ounces' ),
		); 
		
	} // END public function get_products_columns

	/**
	* Get Orders columns to export
	*/
	public function get_orders_columns() {
		
		return array(
			'ID'                         => array( 'name' => __( 'ID', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_author'                => array( 'name' => __( 'Post Author', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_date'                  => array( 'name' => __( 'Post Date', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_date_gmt'              => array( 'name' => __( 'Post Date GMT', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_content'               => array( 'name' => __( 'Post Content', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_title'                 => array( 'name' => __( 'Post Title', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_excerpt'               => array( 'name' => __( 'Post Excerpt', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_status'                => array( 'name' => __( 'Post Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'comment_status'             => array( 'name' => __( 'Comment Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'ping_status'                => array( 'name' => __( 'Ping Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_password'              => array( 'name' => __( 'Post Password', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_name'                  => array( 'name' => __( 'Post Name', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'to_ping'                    => array( 'name' => __( 'To Ping', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'pinged'                     => array( 'name' => __( 'Pinged', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_modified'              => array( 'name' => __( 'Post Modified', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_modified_gmt'          => array( 'name' => __( 'Post Modified GMT', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_content_filtered'      => array( 'name' => __( 'Post Content Filtered', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_parent'                => array( 'name' => __( 'Post Parent', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'guid'                       => array( 'name' => __( 'GUID', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'menu_order'                 => array( 'name' => __( 'Menu Order', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_type'                  => array( 'name' => __( 'Post Type', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'post_mime_type'             => array( 'name' => __( 'Post Mime Type', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'comment_count'              => array( 'name' => __( 'Comment Count', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
			'_edit_last'                 => array( 'name' => __( 'Edit Last', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'_edit_lock'                 => array( 'name' => __( 'Edit Lock', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_paid_time'               => array( 'name' => __( 'Paid Time', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_cart_info'               => array( 'name' => __( 'Cart Info', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_cart_items'              => array( 'name' => __( 'Cart Items', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_shipping_info'           => array( 'name' => __( 'Shipping Info', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_billing_info'            => array( 'name' => __( 'Billing Info', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_payment_info'            => array( 'name' => __( 'Payment Info', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_order_total'             => array( 'name' => __( 'Order Total', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_shipping_total'          => array( 'name' => __( 'Shipping Total', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_shipping_tax'            => array( 'name' => __( 'Shipping Tax', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_tax_total'               => array( 'name' => __( 'Tax Total', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_tax_inclusive'           => array( 'name' => __( 'Tax Inclusive', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_tax_shipping'            => array( 'name' => __( 'Tax Shipping', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_order_items'             => array( 'name' => __( 'Order Items', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
			'mp_received_time'           => array( 'name' => __( 'Received Time', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		); 

	} // END public function get_orders_columns

	/**
	* Create MarketPress Export tools entry
	*/
	public function add_menu() {
		
		$this->managment_page = add_management_page( 
			__( 'MarketPress Export', 'mp' ), 
			__( 'MarketPress Export', 'mp' ), 
			'manage_options', 
			'marketpress_export', 
			array( &$this, 'tools_page' ) 
		);
		
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) ); 
		
	} // END public function add_menu

	/**
	* Add Style ans Script to the export page
	*/
	public function admin_enqueue_scripts( $hook ) {

		if( $this->managment_page !== $hook ) return;

		wp_enqueue_style( 'jquery-ui-structure', mp_plugin_url( 'includes/addons/mp-import-export/ui/css/jquery-ui.structure.css' ), false, MP_VERSION );
		wp_enqueue_style( 'jquery-ui-theme', mp_plugin_url( 'includes/addons/mp-import-export/ui/css/jquery-ui.theme.css' ), array( 'jquery-ui-structure' ), MP_VERSION );
		wp_enqueue_style( 'mp-export', mp_plugin_url( 'includes/addons/mp-import-export/ui/css/mp-export.css' ), array( 'jquery-ui-theme' ), MP_VERSION );
		wp_enqueue_script( 'jquery-ui' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'mp-export', mp_plugin_url( 'includes/addons/mp-import-export/ui/js/mp-export.js' ), array( 'jquery-ui-tabs' ), MP_VERSION );

	} // END public function admin_enqueue_scripts
	
	/**
	* Display a tools page
	*/
	public function tools_page() {

		$action = mp_get_request_value( 'action' );
		$types = mp_get_request_value( 'export-types' );

		if( $action === 'process' ) {
			switch ( $types ) {
				case 'products':
					$this->check_products_datas();

					if( ! $this->errors ) {
						$this->build_products_csv();
					}
					break;

				case 'orders':
					$this->check_orders_datas();

					if( ! $this->errors ) {
						$this->build_orders_csv();
					}
					break;

				case 'customers':
					$this->check_customers_datas();

					if( ! $this->errors ) {
						$this->build_customers_csv();
					}
					break;

				case 'all':
				default:
					$this->check_products_datas();
					$this->check_orders_datas();
					// $this->check_customers_datas();

					if( ! $this->errors ) {
						$products_file  = $this->build_products_csv( false );
						$orders_file    = $this->build_orders_csv( false );
						// $customers_file =  $this->build_customers_csv( false );

						$this->zip_it( array( $products_file, $orders_file/*, $customers_file*/ ), 'mp-export-all.zip' );
					}
					break;
			}			
		}

		// Render the tools template
		include mp_plugin_dir( 'includes/addons/mp-import-export/templates/export.php' );
		
	} // END public function tools_page
	
	/**
	* Display Products Export View
	*/
	public function products_export_view() {

		// Render the tools template
		include mp_plugin_dir( 'includes/addons/mp-import-export/templates/export-products.php' );
		
	} // END public function products_export_view
	
	/**
	* Display Orders Export View
	*/
	public function orders_export_view() {

		// Render the tools template
		include mp_plugin_dir( 'includes/addons/mp-import-export/templates/export-orders.php' );
		
	} // END public function orders_export_view

	/**
	* Prepare Products Fields for CSV File
	*/
	private function check_products_datas() {
		
		// $limit  = (int) mp_get_request_value( 'products-limit', 0 );
		// $offset = (int) mp_get_request_value( 'products-offset', 0 );

		// if( ! is_integer( $limit ) ) {
		// 	$this->messages[] = __( 'The products Limit should be an integer greater than -1.' );
		// 	$this->errors     = true;
		// }

		// if( ! is_integer( $offset ) ) {
		// 	$this->messages[] = __( 'The products Offset should be an integer greater than 0.' );
		// 	$this->errors     = true;
		// }

	} // END private function check_products_datas

	/**
	* Prepare Orders Fields for CSV File
	*/
	private function check_orders_datas() {
		
		// $limit  = (int) mp_get_request_value( 'products-limit', 0 );
		// $offset = (int) mp_get_request_value( 'products-offset', 0 );

		// if( ! is_integer( $limit ) ) {
		// 	$this->messages[] = __( 'The products Limit should be an integer greater than -1.' );
		// 	$this->errors     = true;
		// }

		// if( ! is_integer( $offset ) ) {
		// 	$this->messages[] = __( 'The products Offset should be an integer greater than 0.' );
		// 	$this->errors     = true;
		// }

	} // END private function check_products_datas

	/**
	* Build Products CSV File
	*/
	private function build_products_csv( $direct_download = true ) {

		$filename_search = array(
			'.csv',
			'%%timestamp%%',
			'%%month%%',
			'%%date%%',
		);

		$filename_replace = array(
			'',
			time(),
			date( 'm' ),
			date( 'Y-m-d' ),
		);

		$filename         = mp_get_request_value( 'products-file-name', 'mp-products-export' );
		$filename         = trim( str_replace( $filename_search, $filename_replace, $filename ) ) . '.csv';
		$filepath         = plugin_dir_path( __FILE__ ) . 'files/' . $filename;
		$fileurl          = plugin_dir_url( __FILE__ ) . 'files/' . $filename;
		$products_columns = mp_get_request_value( 'products-columns', array() );
		$custom_fields    = explode( "\n", mp_get_request_value( 'products-custom-fields', '' ) );

		// Column headings
		$column_headings = array();
		foreach ( $this->products_columns as $key => $field ) {
			// If column is required 
			if( $field['required'] ) {
				$column_headings[] = $key;
			} else {
				// if column is checked by user
				if( ! empty( $products_columns[ $key ] ) ) {
					$column_headings[] = $key;

					// if column has a WPMU_DEV_API_NAME
					if( ! empty( $field['WPMU_DEV_API_NAME'] ) ) {
						$column_headings[] = $field['WPMU_DEV_API_NAME'];
					}
				}
			}
		}

		if( $custom_fields !== array( '' ) ) {
			foreach ( $custom_fields as $custom_field ) {
				$custom_field = trim( $custom_field );
				if( ! empty( $custom_field ) ) {
					$column_headings[] = trim( $custom_field );
				}
			}
		}

		// The first line of CSV
		$fields_list = array(
			$column_headings
		);

		$products_query = array(
			'post_type'		 => array( MP_Product::get_post_type() ),
			'post_status'	 => 'publish',
			'posts_per_page' => mp_get_request_value( 'products-limit', -1 ),
			'offset'         => mp_get_request_value( 'products-offset', 0 ),
			'order'          => 'ASC',
		);
		$products_query = new WP_Query( $products_query );

		$products_count = 0;
		if( $products_query->have_posts() ) {
			while ( $products_query->have_posts() ) {
				$products_query->the_post();

				$line = array();

				// for each columns
				foreach ( $this->products_columns as $key => $field ) {
					// If column is required 
					if( $field['required'] ) {
						$line[] = $products_query->post->$key;
					} else {
						// if column is checked by user
						if( ! empty( $products_columns[ $key ] ) ) {
							$meta   = get_post_meta( $products_query->post->ID, $key, true );
							$line[] = maybe_serialize( $meta );

							// if column has a WPMU_DEV_API_NAME
							if( ! empty( $field['WPMU_DEV_API_NAME'] ) ) {
								$meta   = get_post_meta( $products_query->post->ID, $field['WPMU_DEV_API_NAME'], true );
								$line[] = maybe_serialize( $meta );
							}
						}
					}
				}

				foreach ( $custom_fields as $custom_field ) {
					if( ! empty( $custom_field ) ) {
						$meta   = get_post_meta( $products_query->post->ID, $custom_field, true );
						$line[] = maybe_serialize( $meta );
					}
				}

				$fields_list[] = $line;

				$products_count++;
			}
		}

		$handle = fopen( $filepath, 'w' );

		foreach ( $fields_list as $fields ) {
			fputcsv( $handle, $fields );
		}

		fclose( $handle );

		if( $direct_download ) {
			$this->messages[] = sprintf( __( '<a href="%s" class="button-primary">Click to download your CSV file.</a>', 'mp' ), $fileurl );
		}
		$this->messages[] = sprintf( __( 'There are %s columns in the products csv file.', 'mp' ), count( $column_headings ) );
		$this->messages[] = sprintf( _n( '%s product was exported.', '%s products were exported.', $products_count, 'mp' ), $products_count );

		return $filepath;
		
	} // END private function build_products_csv

	/**
	* Build Orders CSV File
	*/
	private function build_orders_csv( $direct_download = true ) {

		$filename_search = array(
			'.csv',
			'%%timestamp%%',
			'%%month%%',
			'%%date%%',
		);

		$filename_replace = array(
			'',
			time(),
			date( 'm' ),
			date( 'Y-m-d' ),
		);

		$filename       = mp_get_request_value( 'orders-file-name', 'mp-orders-export' );
		$filename       = trim( str_replace( $filename_search, $filename_replace, $filename ) ) . '.csv';
		$filepath       = plugin_dir_path( __FILE__ ) . 'files/' . $filename;
		$fileurl        = plugin_dir_url( __FILE__ ) . 'files/' . $filename;
		$orders_columns = mp_get_request_value( 'orders-columns', array() );
		$custom_fields  = explode( "\n", mp_get_request_value( 'orders-custom-fields', '' ) );

		// Column headings
		$column_headings = array();
		foreach ( $this->orders_columns as $key => $field ) {
			// If column is required 
			if( $field['required'] ) {
				$column_headings[] = $key;
			} else {
				// if column is checked by user
				if( ! empty( $orders_columns[ $key ] ) ) {
					$column_headings[] = $key;

					// if column has a WPMU_DEV_API_NAME
					if( ! empty( $field['WPMU_DEV_API_NAME'] ) ) {
						$column_headings[] = $field['WPMU_DEV_API_NAME'];
					}
				}
			}
		}

		if( $custom_fields !== array( '' ) ) {
			foreach ( $custom_fields as $custom_field ) {
				$custom_field = trim( $custom_field );
				if( ! empty( $custom_field ) ) {
					$column_headings[] = trim( $custom_field );
				}
			}
		}

		// The first line of CSV
		$fields_list = array(
			$column_headings
		);

		$orders_query = array(
			'post_type'		 => array( 'mp_order' ),
			'post_status'	 => get_post_stati( array( 'post_type' => 'mp_order' ), 'names' ),
			'posts_per_page' => mp_get_request_value( 'orders-limit', -1 ),
			'offset'         => mp_get_request_value( 'orders-offset', 0 ),
			'order'          => 'ASC',
		);
		$orders_query = new WP_Query( $orders_query );

		$orders_count = 0;
		if( $orders_query->have_posts() ) {
			while ( $orders_query->have_posts() ) {
				$orders_query->the_post();

				$line = array();

				// for each columns
				foreach ( $this->orders_columns as $key => $field ) {
					// If column is required 
					if( $field['required'] ) {
						$line[] = $orders_query->post->$key;
					} else {
						// if column is checked by user
						if( ! empty( $orders_columns[ $key ] ) ) {
							$meta   = get_post_meta( $orders_query->post->ID, $key, true );
							$line[] = maybe_serialize( $meta );

							// if column has a WPMU_DEV_API_NAME
							if( ! empty( $field['WPMU_DEV_API_NAME'] ) ) {
								$meta   = get_post_meta( $orders_query->post->ID, $field['WPMU_DEV_API_NAME'], true );
								$line[] = maybe_serialize( $meta );
							}
						}
					}
				}

				foreach ( $custom_fields as $custom_field ) {
					if( ! empty( $custom_field ) ) {
						$meta   = get_post_meta( $orders_query->post->ID, $custom_field, true );
						$line[] = maybe_serialize( $meta );
					}
				}

				$fields_list[] = $line;

				$orders_count++;
			}
		}

		$handle = fopen( $filepath, 'w' );

		foreach ( $fields_list as $fields ) {
			fputcsv( $handle, $fields );
		}

		fclose( $handle );

		if( $direct_download ) {
			$this->messages[] = sprintf( __( '<a href="%s" class="button-primary">Click to download your CSV file.</a>', 'mp' ), $fileurl );
		}
		$this->messages[] = sprintf( __( 'There are %s columns in the orders csv file.', 'mp' ), count( $column_headings ) );
		$this->messages[] = sprintf( _n( '%s order was exported.', '%s orders were exported.', $orders_count, 'mp' ), $orders_count );

		return $filepath;
		
	} // END private function build_orders_csv

	/**
	 * Creates a compressed zip file
	 */
	private function zip_it( $files = array(), $filename = '', $overwrite = true ) {
		
		$filepath = plugin_dir_path( __FILE__ ) . 'files/' . $filename;
		$fileurl  = plugin_dir_url( __FILE__ ) . 'files/' . $filename;

		//if the zip file already exists and overwrite is false, return false
		if( file_exists( $filepath ) && !$overwrite ) { return false; }
		
		//vars
		$valid_files = array();
		
		//if files were passed in...
		if( is_array( $files ) ) {
			//cycle through each file
			foreach( $files as $file ) {
				//make sure the file exists
				if( file_exists( $file ) ) {
					$valid_files[] = $file;
				}
			}
		}

		//if we have good files...
		if( count( $valid_files ) ) {
			//create the archive
			$zip = new ZipArchive();
			if( $zip->open( $filepath, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE ) !== true ) {
				return false;
			}

			//add the files
			foreach( $valid_files as $file ) {
				$zip->addFile( $file,  str_replace( plugin_dir_path( __FILE__ ) . 'files/', '', $file ) );
			}
			
			//close the zip -- done!
			$zip->close();

			$this->messages[] = sprintf( __( '<a href="%s" class="button-primary">Click to download your ZIP file.</a>', 'mp' ), $fileurl );
			
			//check to make sure the file exists
			return file_exists( $filepath );
		}
		else
		{
			return false;
		}

	} // END private function zip_it
	
} // END class Mp_Export

$Mp_Export = new Mp_Export();
