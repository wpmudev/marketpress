<?php

class MP_Import {
    
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
    
    /**
     * Construct the plugin object
     */
    public function __construct() {
        // register actions
        add_action( 'admin_menu', array( &$this, 'add_menu' ) );
    } // END public function __construct

    /**
    * Create an Nivo Lightbox menu entry in "Tools" called "Nivo Lightbox"
    * 
    */
    public function add_menu() {
        
        $managment_page = add_management_page( 
            __( 'MarketPress Import', 'mp' ), 
            __( 'MarketPress Import', 'mp' ), 
            'manage_options', 
            'marketpress_import', 
            array( &$this, 'tools_page' ) 
        );
        
        add_action( 'admin_head-' . $managment_page, array( &$this, 'admin_header' ) );
        
    } // END public function add_menu

    /**
    * Add Style to the acm admin page
    */
    public function admin_header() {             
        ?>
            <style type="text/css">
                #response li {
                    border-color: #DFDFDF;
                    border-style: solid;
                    border-width: 0 0 1px;
                    display: block;
                    float: none;
                }
                #response span {
                    float: none;
                }
                #response span.error {
                    color: red;
                }
            </style>
        <?php
    } // END public function admin_header

    /**
    * Display a tools page
    */
    public function tools_page() {
        
        if( isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'process' )
            $messages = $this->parse_csv(); 
        else
            $messages = array();

        // Render the tools template
        include mp_plugin_dir( 'includes/addons/mp-import-export/templates/import.php' );
        
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
    
} // END class MP_Import

$MP_Import = new MP_Import();
