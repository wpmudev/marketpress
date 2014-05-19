<?php

if ( ! function_exists('get_field_value') ) :
	/**
	 * Gets a field's value
	 *
	 * @since 1.0
	 * @param string $name The field's name
	 * @param int $post_id
	 * @param bool $raw (optional) Whether or not to get the raw/unformatted value as saved in the db
	 */
	function get_field_value( $name, $post_id, $raw = false ) {
		$class = get_post_meta($post_id, '_' . $name, true);

		if ( ! class_exists($class) ) {
			return false;
		}
		
		$field = new $class(array('name' => $name, 'value_only' => true));
		
		return $field->get_value($post_id, $raw);
	}
endif;

if ( ! function_exists('field_value') ) :	
	/**
	 * Displays a field's value
	 *
	 * @since 1.0
	 * @param string $name The field's name
	 * @param int $post_id
	 * @param bool $raw (optional) Whether or not to get the raw/unformatted value as saved in the db
	 */
	function field_value( $name, $post_id, $raw = false ) {
		echo get_field_value($name, $post_id, $raw);
	}
endif;