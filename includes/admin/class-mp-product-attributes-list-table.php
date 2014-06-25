<?php

if ( ! class_exists('WP_List_Table') ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class MP_Product_Attributes_List_Table extends WP_List_Table {
	function get_columns() {
		return array(
			'slug' => __('Slug', 'mp'),
			'name' => __('Name', 'mp'),
		);
	}
	
	function get_sortable_columns() {
		return array(
			'slug' => 'mp_attribute_slug',
			'name' => 'mp_attribute_name',
		);
	}
	
	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = array();
	}
	
	function column_default( $item, $column_name ) {
		return '';
	}
}