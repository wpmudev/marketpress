<?php

class Mp_Export {
	
	public $columns = array(
		'ID',
		'Titre',
		'Nom du Jardin Associé (si PDV)',
		'Type (Jardin ou PDV)',
		'Description',
		'Adresse',
		'CP',
		'Ville',
		'URL du Site',
	);

	private $managment_page;
	
	/**
	 * Construct the plugin object
	 */
	public function __construct() {
		// register actions
		add_action( 'admin_menu', array( &$this, 'add_menu' ) );
	} // END public function __construct

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
		
		if( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'process' )
			$messages = $this->parse_csv(); 
		else
			$messages = array();

		// Render the tools template
		include mp_plugin_dir( 'includes/addons/mp-import-export/templates/export.php' );
		
	} // END public function tools_page

	/**
	* Parse CSV FILE
	*/
	public function parse_csv() {

		//$field_separator = isset( $_POST['field_separator'] ) && !empty( $_POST['field_separator'] ) ? trim( $_POST['field_separator'] ) : ';';
		$field_separator = ';';
		//$text_separator = isset( $_POST['text_separator'] ) && !empty( $_POST['text_separator'] ) ? trim( $_POST['text_separator'] ) : '"';
		$text_separator = '"';
		$datafile = $_FILES['datafile'];
		$messages = array();
		
		if ( $datafile['error'] != UPLOAD_ERR_OK || !is_uploaded_file( $datafile['tmp_name'] ) )
			$messages[] = __( '<span class="error">Please choose a data file to upload.</span>', 'mp' );
		else {
			$handle = fopen( $datafile['tmp_name'], 'r' );
			
			$row = 0;
			
			while ( ( $datas = fgetcsv( $handle, 4096, $field_separator, $text_separator ) ) !== false ) {
				
				if( $row > 0 && count( $datas ) == count( $this->columns ) ) {
					
					$ID = mp_sanitize_data( $datas[0], 'int' );
					$title = mp_sanitize_data( $datas[1] );
					$associated_garden = mp_sanitize_data( $datas[2] );
					$type = mp_sanitize_data( $datas[3] );
					$description = mp_sanitize_data( $datas[4] );
					$address = mp_sanitize_data( $datas[5] );
					$zipcode = mp_sanitize_data( $datas[6] );
					$town = mp_sanitize_data( $datas[7] );
					$url = mp_sanitize_data( $datas[8], 'url' );

					if ( empty( $ID ) )
						$messages[] = mp_get_error( $this->columns[0], $row + 1 );
					elseif ( empty( $title ) )
						$messages[] = mp_get_error( $this->columns[1], $row + 1 );
					elseif ( empty( $type ) )
						$messages[] = mp_get_error( $this->columns[3], $row + 1 );
					elseif ( empty( $description ) )
						$messages[] = mp_get_error( $this->columns[4], $row + 1 );
					elseif ( empty( $address ) )
						$messages[] = mp_get_error( $this->columns[5], $row + 1 );
					elseif ( empty( $zipcode ) )
						$messages[] = mp_get_error( $this->columns[6], $row + 1 );
					elseif ( empty( $town ) )
						$messages[] = mp_get_error( $this->columns[7], $row + 1 );
					elseif ( empty( $url ) )
						$messages[] = mp_get_error( $this->columns[6], $row + 1 );
					else
						$messages[] = mp_add_external_garden( $ID, $title, $associated_garden, $type, $description, $address, $zipcode, $town, $url, $gmap_api_key );
					
				} 
				elseif( count( $datas ) != count( $this->columns ) ) {
					$messages[] = sprintf( __( '<span class="error">Your csv structure is not correct.<br />Wrong number of datas in row %1$d.</span>', 'mp' ), ( $row + 1 ) );
					break;                
				}
				// 1ère ligne (en-tête)
				else
					// Pour chaque colonne, on vérifie que le nom est bon
					foreach( $datas as $key => $data )
						if( mp_sanitize_data( $data ) != $this->columns[$key] ) {
							$messages[] = sprintf( __( '<span class="error">Your csv structure is not correct.<br />Unknown column "%1$s".</span>', 'mp' ), mp_sanitize_data( $data ) );
							break 2;
						}
				
				$row++;
			}
		}
		
		return $messages;        
		
	} // END public function parse_csv
	
} // END class Mp_Export

$Mp_Export = new Mp_Export();
