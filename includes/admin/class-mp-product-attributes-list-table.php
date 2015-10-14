<?php

if ( ! class_exists('WP_List_Table') ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class MP_Product_Attributes_List_Table extends WP_List_Table {
	function __construct() {
		global $status, $page;
                
    //Set parent defaults
    parent::__construct(array(
    	'singular' => 'product_attribute',		//singular name of the listed records
			'plural' => 'product_attributes',	//plural name of the listed records
			'ajax' => false									//does this table support ajax?
    ));	
	}
	
	function get_columns() {
		return array(
			'cb' => '<input type="checkbox" />',
			'name' => __('Name', 'mp'),		
			'slug' => __('Slug', 'mp'),
		);
	}
	
	function get_sortable_columns() {
		return array();
	}
	
	function get_bulk_actions() {
		return array(
			'delete' => __('Delete', 'mp'),
		);
	}
	
	function get_data() {
		$data = array();
		$mp_product_atts = MP_Product_Attributes::get_instance();
		$atts = $mp_product_atts->get();
		
		foreach ( $atts as $att ) {
			$data[] = array(
				'ID' => $att->attribute_id,
				'name' => $att->attribute_name,
				'slug' => MP_Product_Attributes::SLUGBASE . $att->attribute_id,
			);
		}
		
		return $data;
	}
	
	function process_bulk_actions() {
		if ( $this->current_action() == 'delete' && ($ids = mp_get_get_value('product_attribute')) ) {
			$ids = array_filter($ids, create_function('$id', 'return is_numeric($id);'));
			MP_Product_Attributes::get_instance()->delete($ids);
			echo '<div class="updated"><p>' . __('Product attribute(s) deleted successfully.', 'mp') . '</p></div>';
		}
	}
	
	function prepare_items() {
		$this->process_bulk_actions();
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $this->get_data();
	}
	
	function column_cb( $item ) {
		return '<input type="checkbox" name="product_attribute[]" value="' . $item['ID'] . '" />';
	}
	
	function column_name( $item ) {
		return '<a href="' . add_query_arg(array('action' => 'mp_edit_product_attribute', 'attribute_id' => $item['ID'])) . '">' . $item['name'] . '</a>';
	}
	
	function column_slug( $item ) {
		return $item['slug'];
	}	
}