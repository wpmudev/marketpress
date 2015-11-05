<?php

class MP_Import {
	
	public $messages      = array();
	public $errors        = false;
	public $uploaded_file = '';
	public $lines_count   = 0;
	public $done          = false;

	private $managment_page;
	
	/**
	 * Construct the plugin object
	 */
	public function __construct() {

		// register actions
		add_action( 'admin_menu', array( &$this, 'add_menu' ) );

		if ( mp_doing_ajax() ) {
			add_action( 'wp_ajax_mp_ie_import_datas', array( &$this, 'ajax_import_datas' ) );
		}


	} // END public function __construct

	/**
	* Create menu entry
	* 
	*/
	public function add_menu() {
		
		$this->managment_page = add_submenu_page( 
			'edit.php?post_type=' . MP_Product::get_post_type(),
			__( 'MarketPress Import', 'mp' ), 
			__( 'MarketPress Import', 'mp' ), 
			'manage_options', 
			'marketpress_import', 
			array( &$this, 'tools_page' ) 
		);
		
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) ); 
		
	} // END public function add_menu

	/**
	* Ajax action to import datas
	* 
	*/
	public function ajax_import_datas() {
		
		$file_path   = mp_get_request_value( 'file_path' );
		$line        = (int) mp_get_request_value( 'line' );
		$type        = mp_get_request_value( 'type' );
		$from        = mp_get_request_value( 'from' );

		$import_datas = "import_{$from}_{$type}_datas";
		$this->$import_datas( $file_path, $line );

		die( json_encode( array(
			'file_path' => $file_path,
			'messages' => $this->done ? array( __( 'Import is over.', 'mp' ) ) : $this->messages,
			'line'     => $line + 1,
			'done'     => $this->done,
		) ) );
		
	} // END public function ajax_import_datas

	/**
	* Add Style ans Script to the export page
	*/
	public function admin_enqueue_scripts( $hook ) {

		if( $this->managment_page !== $hook ) return;

		wp_enqueue_style( 'jquery-ui-structure', mp_plugin_url( 'includes/addons/mp-import-export/ui/css/jquery-ui.structure.css' ), false, MP_VERSION );
		wp_enqueue_style( 'jquery-ui-theme', mp_plugin_url( 'includes/addons/mp-import-export/ui/css/jquery-ui.theme.css' ), array( 'jquery-ui-structure' ), MP_VERSION );
		wp_enqueue_style( 'mp-import', mp_plugin_url( 'includes/addons/mp-import-export/ui/css/mp-import.css' ), array( 'jquery-ui-theme' ), MP_VERSION );
		wp_enqueue_script( 'jquery-ui' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-progressbar' );
		wp_enqueue_script( 'mp-import', mp_plugin_url( 'includes/addons/mp-import-export/ui/js/mp-import.js' ), array( 'jquery-ui-progressbar' ), MP_VERSION );
		wp_localize_script( 'mp-import', 'mp_import_i18n', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'type'    => mp_get_request_value( 'import-types' ),
			'from'    => mp_get_request_value( 'import-from' ),
			'line'    => mp_get_request_value( 'import-from' ),
		) );

	} // END public function admin_enqueue_scripts

	/**
	* Display a tools page
	*/
	public function tools_page() {

		$action = mp_get_request_value( 'action' );
		$type   = mp_get_request_value( 'import-types' );
		$from   = mp_get_request_value( 'import-from' );

		if( $action === 'process' ) {
			$import_datas = "import_{$from}_{$type}_datas";
			$this->upload_file();
			$this->$import_datas( $this->uploaded_file, 0 );
			
			// if( ! $this->errors ) {
			// 	$import = "import_{$from}_{$type}";

			// 	$this->$import();
			// }
		}

		// Render the tools template
		include mp_plugin_dir( 'includes/addons/mp-import-export/templates/import.php' );
		
	} // END public function tools_page

	/**
	* Import 3.0 Products Fields from CSV File
	*/
	private function import_30_products_datas( $file_path, $line ) {
		
		$this->import_30_datas( $file_path, $line, mp_get_products_csv_columns() );

	} // END private function import_30_products_datas

	/**
	* Import 2.9 Products Fields from CSV File
	*/
	private function import_29_products_datas( $file_path, $line ) {
		
		if( ! $file_path ) {
			return false;
		}
		
		$columns         = mp_get_29_products_csv_columns();
		$field_separator = ! empty( $_POST['field-separator'] ) ? stripslashes( trim( $_POST['field-separator'] ) ) : ',';
		$text_separator  = ! empty( $_POST['text-separator'] ) ? stripslashes( trim( $_POST['text-separator'] ) ) : '"';
		$handle          = fopen( $file_path, 'r' );
		$row             = 0;
		$new_columns     = array();

		while ( ( $datas = fgetcsv( $handle, 0, $field_separator, $text_separator ) ) !== false ) {
			
			if( $row === 0 ) {
				// Verify that each required column exists in the csv
				foreach( $columns as $key => $data ) {
					if( $data['required'] && ! in_array( $key, $datas ) ) {
						$this->messages[] = sprintf( '<span class="error">%s<br />%s</span>', __( 'Your csv structure is not correct.', 'mp' ), sprintf( __( 'Column is missing: "%s".', 'mp' ), $key ) );
						break 2;
					}
				}

				// Register new Products Columns
				foreach( $datas as $key => $data ) {
					$data = trim( $data );

					if( 
						array_key_exists( $data, $columns ) &&
						$columns[ $data ]['required']
					) {
						$new_columns[ $key ] = array(
							'required' => true,
							'name'     => $data,
						);
					}
					else {
						$new_columns[ $key ] = array(
							'required' => false,
							'name'     => $data,
						);
					}
				}
					
				$count_datas = count( $datas );
			}
			elseif( $line === $row && count( $datas ) !== count( $new_columns ) ) {
				$this->messages[] = sprintf( '<span class="error">%s<br />%s</span>', __( 'Your csv structure is not correct.', 'mp' ), sprintf( __( 'Wrong number of datas in row %s.', 'mp' ), ( $row + 1 ) ) );
				break;
			}
			elseif( $line === $row ) {
				$required = array();
				$metas    = array();
				$tags     = array();
				$cats     = array();
				foreach ( $datas as $key => $value ) {
					$value = trim( $value );

					if( $new_columns[ $key ]['required'] ) {
						$required[ $columns[ $new_columns[ $key ]['name'] ]['3.0_name'] ] = $value;
						// $required[ $new_columns[ $key ]['name'] ] = $value;
					}
					else {
						if( array_key_exists( $new_columns[ $key ]['name'], $columns ) ) {
							foreach ( (array) $columns[ $new_columns[ $key ]['name'] ]['3.0_name'] as $value1 ) {
								$metas[ $value1 ] = $value;

								switch ( $value1 ) {
									case 'sale_price_amount':
										$metas['sale_price'] = array (
											0 => 'sale_price_amount',
											1 => 'sale_price_start_date',
											2 => 'sale_price_end_date',
										);
										$metas['_sale_price'] = 'WPMUDEV_Field_Complex';
										break;
									
									case 'external_url':
										if( ! empty( $value ) ) {											
											$metas['product_type'] = 'external';
											$metas['_product_type'] = 'WPMUDEV_Field_Select';
										}
										break;
									
									case 'tags':
										$tags = explode( ',', $value );
										break;
									
									case 'categories':
										$cats = explode( ',', $value );
										break;
									
									case 'inv_inventory':
										$metas['inv'] = array (
											0 => 'inv_inventory',
											1 => 'inv_out_of_stock_purchase',
										);
										$metas['_inv'] = 'WPMUDEV_Field_Complex';
										break;
									
									case 'file_url':
										if( ! empty( $value ) ) {											
											$url        = $value;
											$tmp        = download_url( $url );
											$file_array = array(
												'name'     => basename( $url ),
												'tmp_name' => $tmp
											);

											// Check for download errors
											if ( is_wp_error( $tmp ) ) {
												@unlink( $file_array[ 'tmp_name' ] );
												$this->messages[] = $tmp->get_error_message();
												break;
											}

											$id = media_handle_sideload( $file_array, 0 );
											// Check for handle sideload errors.
											if ( is_wp_error( $id ) ) {
												@unlink( $file_array['tmp_name'] );
												$this->messages[] = $id->get_error_message();
												break;
											}

											$metas['product_type']  = 'digital';
											$metas['_product_type'] = 'WPMUDEV_Field_Select';
											$metas['file_url']      = wp_get_attachment_url( $id );
										}
										break;
									
									case 'mp_product_images':
										if( ! empty( $value ) ) {											
											$url        = $value;
											$tmp        = download_url( $url );
											$file_array = array(
												'name'     => basename( $url ),
												'tmp_name' => $tmp
											);

											// Check for download errors
											if ( is_wp_error( $tmp ) ) {
												@unlink( $file_array[ 'tmp_name' ] );
												$this->messages[] = $tmp->get_error_message();
												break;
											}

											$id = media_handle_sideload( $file_array, 0 );
											// Check for handle sideload errors.
											if ( is_wp_error( $id ) ) {
												@unlink( $file_array['tmp_name'] );
												$this->messages[] = $id->get_error_message();
												break;
											}

											$metas['mp_product_images'] = $id;
										}
										break;
								}
							}

							$wpmu_dev_api_val = (array) $columns[ $new_columns[ $key ]['name'] ]['WPMU_DEV_API_VAL'];
							foreach ( (array) $columns[ $new_columns[ $key ]['name'] ]['WPMU_DEV_API_NAME'] as $key2 => $value2 ) {
								if( ! empty( $value2 ) ) {
									$metas[ $value2 ] = $wpmu_dev_api_val[ $key2 ];
								}
							}
						}
						else {
							$metas[ $new_columns[ $key ]['name'] ] = $value;
						}
					}
				}

				$this->messages[] = mp_ie_add_29_post( $required, $metas, $cats, $tags );
				return;
			}
			
			$row++;
		}

		fclose( $handle );

		if( $line === 0 ) {
			$this->lines_count = $row - 1;
			$this->messages[]  = sprintf( __( 'There is %s fields on %s posts to process in your CSV.', 'mp' ), $count_datas, $this->lines_count );
			return;
		}

		if( $line >= $row ) {
			$this->done = true;
		}

		return;

	} // END private function import_29_products_datas

	/**
	* Import 3.0 Orders Fields from CSV File
	*/
	private function import_30_orders_datas() {
		
		$this->import_30_datas( mp_get_orders_csv_columns() );

	} // END private function import_30_orders_datas

	/**
	* Import 3.0 Datas from CSV File
	*/
	private function import_30_datas( $file_path, $line, $columns ) {

		if( ! $file_path ) {
			return false;
		}

		$field_separator = ! empty( $_POST['field-separator'] ) ? stripslashes( trim( $_POST['field-separator'] ) ) : ',';
		$text_separator  = ! empty( $_POST['text-separator'] ) ? stripslashes( trim( $_POST['text-separator'] ) ) : '"';
		$handle          = fopen( $file_path, 'r' );
		$row             = 0;
		$new_columns     = array();

		while ( ( $datas = fgetcsv( $handle, 0, $field_separator, $text_separator ) ) !== false ) {
			
			if( $row === 0 ) {
				// Verify that each required column exists in the csv
				foreach( $columns as $key => $data ) {
					if( $data['required'] && ! in_array( $key, $datas ) ) {
						$this->messages[] = sprintf( '<span class="error">%s<br />%s</span>', __( 'Your csv structure is not correct.', 'mp' ), sprintf( __( 'Column is missing: "%s".', 'mp' ), $key ) );
						break 2;
					}
				}

				// Register new Products Columns
				foreach( $datas as $key => $data ) {
					$data = trim( $data );

					if( 
						array_key_exists( $data, $columns ) &&
						$columns[ $data ]['required']
					) {
						$new_columns[ $key ] = array(
							'required' => true,
							'name'     => $data,
						);								
					}
					else {
						$new_columns[ $key ] = array(
							'required' => false,
							'name'     => $data,
						);
					}

					// if( 
					// 	array_key_exists( $data, $columns )
					// ) {
					// 	$this->messages[] = sprintf( __( '%s key exists', 'mp' ), $data );
					// 	if( $columns[ $data ]['required'] ) {
					// 		$new_columns[ $key ] = array(
					// 			'required' => true,
					// 			'name'     => $data,
					// 		);								
					// 	} else {
					// 		$new_columns[ $key ] = array(
					// 			'required' => false,
					// 			'name'     => $data,
					// 		);								
					// 	}
					// }
					// else if ( 
					// 	! empty( $columns[ ltrim( $data, '_' ) ] ) && 
					// 	$columns[ ltrim( $data, '_' ) ]['WPMU_DEV_API_NAME'] === $data
					// ) {
					// 	$this->messages[] = sprintf( __( '%s key is a "WPMU_DEV_API" column', 'mp' ), $data );
					// 	$new_columns[ $key ] = array(
					// 		'required' => false,
					// 		'name'     => $data,
					// 	);
					// }
					// else {
					// 	$this->messages[] = sprintf( __( '%s key is a custom column', 'mp' ), $data );
					// 	$new_columns[ $key ] = array(
					// 		'required' => false,
					// 		'name'     => $data,
					// 	);
					// }
					
					$count_datas = count( $datas );
				}
			}
			elseif( $line === $row && count( $datas ) !== count( $new_columns ) ) {
				$this->messages[] = sprintf( '<span class="error">%s<br />%s</span>', __( 'Your csv structure is not correct.', 'mp' ), sprintf( __( 'Wrong number of datas in row %s.', 'mp' ), ( $row + 1 ) ) );
				break;
			}
			elseif( $line === $row ) {
				$required = array();
				$metas    = array();
				$tags     = array();
				$cats     = array();
				foreach ( $datas as $key => $value ) {
					if( $new_columns[ $key ]['required'] ) {
						$required[ $new_columns[ $key ]['name'] ] = $value;
					}
					else {
						if( array_key_exists( $new_columns[ $key ]['name'], $columns ) ) {
							switch ( $new_columns[ $key ]['name'] ) {
								case 'tags':
									$tags = explode( ',', $value );
									break;
								
								case 'categories':
									$cats = explode( ',', $value );
									break;

								case 'file_url':
									if( ! empty( $value ) ) {											
										$url        = $value;
										$tmp        = download_url( $url );
										$file_array = array(
											'name'     => basename( $url ),
											'tmp_name' => $tmp
										);

										// Check for download errors
										if ( is_wp_error( $tmp ) ) {
											@unlink( $file_array[ 'tmp_name' ] );
											$this->messages[] = $tmp->get_error_message();
											break;
										}

										$id = media_handle_sideload( $file_array, 0 );
										// Check for handle sideload errors.
										if ( is_wp_error( $id ) ) {
											@unlink( $file_array['tmp_name'] );
											$this->messages[] = $id->get_error_message();
											break;
										}

										$metas[ $new_columns[ $key ]['name'] ] = wp_get_attachment_url( $id );
									}
									break;
								
								case 'mp_product_images':
									if( ! empty( $value ) ) {
										$product_images = array();
										
										foreach ( explode( ',', $value ) as $url ) {
											$tmp        = download_url( $url );
											$file_array = array(
												'name'     => basename( $url ),
												'tmp_name' => $tmp
											);

											// Check for download errors
											if ( is_wp_error( $tmp ) ) {
												@unlink( $file_array[ 'tmp_name' ] );
												$this->messages[] = $tmp->get_error_message();
												break;
											}

											$id = media_handle_sideload( $file_array, 0 );
											// Check for handle sideload errors.
											if ( is_wp_error( $id ) ) {
												@unlink( $file_array['tmp_name'] );
												$this->messages[] = $id->get_error_message();
												break;
											}

											$product_images[] = $id;
										}											
										$metas[ $new_columns[ $key ]['name'] ] = implode( ',', $product_images );
									}
									break;

								default:
									$metas[ $new_columns[ $key ]['name'] ] = $value;
									break;
							}
						}
						else {
							$metas[ $new_columns[ $key ]['name'] ] = $value;							
						}
					}
				}

				$this->messages[] = mp_ie_add_post( $required, $metas, $cats, $tags );
				return;
			}
			
			$row++;
		}

		fclose( $handle );

		if( $line === 0 ) {
			$this->lines_count = $row - 1;
			$this->messages[]  = sprintf( __( 'There is %s fields on %s posts to process in your CSV.', 'mp' ), $count_datas, $this->lines_count );
			return;
		}

		if( $line >= $row ) {
			$this->done = true;
		}

		return;

	} // END private function import_30_datas

	/**
	* Upload the CSV File
	*/
	private function upload_file() {
		
		$datafile = $_FILES['datafile'];
		
		if ( $datafile['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file( $datafile['tmp_name'] ) ) {
			$this->messages[] = sprintf( '<span class="error">%s</span>', __( 'Please choose a data file to upload.', 'mp' ) );
			return false;
		}
		else {
			$upload_dir  = wp_upload_dir();
			$upload_path = $upload_dir['basedir'] . '/marketpress/import/';
			if( ! file_exists( $upload_path ) ) {
				wp_mkdir_p( $upload_path );
			}

			$uploaded_file = move_uploaded_file( $datafile['tmp_name'], $upload_path . $datafile['name'] );

			if ( ! $uploaded_file ) {
				$this->messages[] = __( 'The file cannot be save on your server.', 'mp' );
				return false;
			}

			$this->messages[] = __( 'The CSV file has been successfully uploaded on your server.', 'mp' );

			$this->uploaded_file = $upload_path . $datafile['name'];
			return true;
		}

	} // END private function upload_file
	
} // END class MP_Import

$MP_Import = new MP_Import();
