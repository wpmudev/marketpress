<?php

if ( ! class_exists('WP_List_Table') ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class MP_Product_Attributes_List_Table extends WP_List_Table {
	function get_columns() {
		return array(
			'name' => __('Name', 'mp'),		
			'slug' => __('Slug', 'mp'),
		);
	}
	
	function get_sortable_columns() {
		return array();
	}
	
	function get_data() {
		$data = array();
		$atts = MP_Product_Attributes::get_instance()->get();
		
		foreach ( $atts as $att ) {
			$data[] = array(
				'ID' => $att->attribute_id,
				'name' => $att->attribute_name,
				'slug' => $att->attribute_slug,
			);
		}
		
		return $data;
	}
	
	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $this->get_data();
	}
	
	function column_name( $item ) {
		return '<a href="' . add_query_arg(array('action' => 'mp_edit_product_attribute', 'attribute_id' => $item['ID'])) . '">' . $item['name'] . '</a>';
	}
	
	function column_slug( $item ) {
		return $item['slug'];
	}	
}