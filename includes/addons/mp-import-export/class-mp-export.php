<?php

class MP_Export {
	
	public $products_columns  = array();
	public $orders_columns    = array();
	public $customers_columns = array();
	public $messages          = array();
	public $errors            = false;

	private $managment_page;
	
	/**
	 * Construct the plugin object
	 */
	public function __construct() {

		$this->products_columns = mp_get_products_csv_columns();
		$this->orders_columns   = mp_get_orders_csv_columns();
		// $this->customers_columns = mp_get_customers_csv_columns();

		// register actions
		add_action( 'admin_menu', array( &$this, 'add_menu' ) );

	} // END public function __construct

	/**
	* Create MarketPress Export tools entry
	*/
	public function add_menu() {
		
		$this->managment_page = add_submenu_page( 
			'edit.php?post_type=' . MP_Product::get_post_type(),
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
		$types  = mp_get_request_value( 'export-types' );

		if( $action === 'process' ) {
			$check_datas = "check_{$types}_datas";

			$this->$check_datas();

			if( ! $this->errors ) {
				$build_csv = "build_{$types}_csv";

				$this->$build_csv();
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
	* Prepare all Fields for CSV File
	*/
	private function check_all_datas() {
		
		$this->check_products_datas();
		$this->check_orders_datas();
		// $this->check_customers_datas();

	} // END private function check_all_datas

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
	* Prepare Customers Fields for CSV File
	*/
	private function check_customers_datas() {
		
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
	* Build All CSV Files
	*/
	private function build_all_csv() {

		$products_file  = $this->build_products_csv( false );
		$orders_file    = $this->build_orders_csv( false );
		// $customers_file =  $this->build_customers_csv( false );

		$this->zip_it( array( $products_file, $orders_file/*, $customers_file*/ ), 'mp-export-all.zip' );

	} // END private function build_all_csv

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
							switch ( $key ) {
								case 'tags':
									$tags = wp_get_object_terms( $products_query->post->ID, 'product_tag', array( 'fields' => 'names' ) );
									$line[] = implode( ',', $tags );
									break;

								case 'categories':
									$categories = wp_get_object_terms( $products_query->post->ID, 'product_category', array( 'fields' => 'names' ) );
									$line[] = implode( ',', $categories );
									break;

								case 'mp_product_images':
									$images = explode( ',', get_post_meta( $products_query->post->ID, $key, true ) );
									foreach ( $images as $key1 => $id ) {
										$images[ $key1 ] = wp_get_attachment_url( (int) trim( $id ) );
									}
									$line[] = implode( ',', $images );
									break;
								
								default:
									$meta   = get_post_meta( $products_query->post->ID, $key, true );
									$line[] = maybe_serialize( $meta );

									// if column has a WPMU_DEV_API_NAME
									if( ! empty( $field['WPMU_DEV_API_NAME'] ) ) {
										$meta   = get_post_meta( $products_query->post->ID, $field['WPMU_DEV_API_NAME'], true );
										$line[] = maybe_serialize( $meta );
									}
									break;
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

		$this->messages[] = sprintf( __( 'There are %s columns in the products csv file.', 'mp' ), count( $column_headings ) );
		$this->messages[] = sprintf( _n( '%s product was exported.', '%s products were exported.', $products_count, 'mp' ), $products_count );
		if( $direct_download ) {
			$this->messages[] = sprintf( '<a href="%s" class="button-primary">%s</a>', $fileurl, __( 'Click to download your CSV file.', 'mp' ) );
		}

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
			'post_status'	 => array_keys( mp_get_request_value( 'orders-stati' ) ),
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

		$this->messages[] = sprintf( __( 'There are %s columns in the orders csv file.', 'mp' ), count( $column_headings ) );
		$this->messages[] = sprintf( _n( '%s order was exported.', '%s orders were exported.', $orders_count, 'mp' ), $orders_count );
		if( $direct_download ) {
			$this->messages[] = sprintf( '<a href="%s" class="button-primary">%s</a>', $fileurl, __( 'Click to download your CSV file.', 'wds' ) );
		}

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
			
			$error = $zip->open( $filepath, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE );
			if( $error !== true ) {
				$this->messages[] = sprintf( __( 'Could not create the zip file: %s', 'mp' ), $error );
				return false;
			}

			//add the files
			foreach( $valid_files as $file ) {
				$zip->addFile( $file,  str_replace( plugin_dir_path( __FILE__ ) . 'files/', '', $file ) );
			}
			
			//close the zip -- done!
			$zip->close();

			$this->messages[] = sprintf( '<a href="%s" class="button-primary">%s</a>', $fileurl, __( 'Click to download your ZIP file.', 'mp' ) );
			
			//check to make sure the file exists
			return file_exists( $filepath );
		}
		else
		{
			return false;
		}

	} // END private function zip_it
	
} // END class MP_Export

$MP_Export = new MP_Export();
