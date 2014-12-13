<?php

if ( ! function_exists('get_field_value') ) :
	/**
	 * Gets a field's value
	 *
	 * @since 1.0
	 * @param string $name The field's name
	 * @param int $post_id (optional) Defaults to current post id
	 * @param bool $raw (optional) Whether or not to get the raw/unformatted value as saved in the db
	 * @uses $post
	 */
	function get_field_value( $name, $post_id = null, $raw = false ) {
		global $post;
		
		if ( is_null($post_id) ) {
			$post_id = $post->ID;
		}
		
		$class = get_post_meta($post_id, '_' . $name, true);

		if ( ! class_exists($class) ) {
			return false;
		}
		
		$field = new $class(array('name' => $name, 'value_only' => true));
		
		return $field->get_api_value($post_id, null, $raw);
	}
endif;

if ( ! function_exists( 'field_exists' ) ) :
	/**
	 * Check if a field exists given a field name
	 *
	 * @since 3.0
	 * @param string $name The name of the field. 
	 * @param int $post_id The post ID to check.
	 * @return bool
	 */
	function field_exists( $name, $post_id ) {
		$key = $name;
		if ( false === strpos( $name, '_' ) ) {
			$key = '_' . $name;
		}
		
		return ( metadata_exists( 'post', $post_id, $key ) );
	}
endif;

if ( ! function_exists('field_value') ) :	
	/**
	 * Displays a field's value
	 *
	 * @since 1.0
	 * @param string $name The field's name
	 * @param int $post_id (optional) Defaults to current post id
	 * @param bool $raw (optional) Whether or not to get the raw/unformatted value as saved in the db
	 * @uses $post
	 */
	function field_value( $name, $post_id = null, $raw = false ) {
		echo get_field_value($name, $post_id, $raw);
	}
endif;

if ( ! function_exists('update_field_value') ) :	
	/**
	 * Update a field's value
	 *
	 * @since 1.0
	 * @param string $name The field's name.
	 * @param mixed $value The field's new value.
	 * @param int $post_id (optional) Defaults to current post id.
	 * @uses $post
	 */
	function update_field_value( $name, $value, $post_id = null ) {
		global $post;
		
		if ( is_null( $post_id ) ) {
			$post_id = $post->ID;
		}
		
		$class = get_post_meta( $post_id, '_' . $name, true );

		if ( ! class_exists( $class ) ) {
			return false;
		}
		
		$field = new $class( array(
			'name' => $name,
			'value_only' => true,
		));
		
		$field->save_value( $post_id, $name, $value );
	}
endif;


if ( ! function_exists('array_replace_recursive') ) :
	/**
	 * Recursively replace one array with another. Provides compatibility for PHP version < 5.3
	 *
	 * @since 1.0
	 * @param array $array
	 * @param array $array1 The values from this array will overwrite the values from $array
	 * @return array
	 */
	function array_replace_recursive() {	  
	  // handle the arguments, merge one by one
	  $args = func_get_args();
	  $array = $args[0];
	  
	  if ( ! is_array($array) ) {
	    return $array;
	  }
	  
	  for ($i = 1; $i < count($args); $i++) {
	    if ( is_array($args[$i]) ) {
	      $array = recurse($array, $args[$i]);
	    }
	  }
	  
	  return $array;
	}

	function recurse( $array, $array1 ) {
    foreach ( $array1 as $key => $value ) {
      // create new key in $array, if it is empty or not an array
      if ( ! isset($array[$key]) || (isset($array[$key]) && ! is_array($array[$key])) ) {
        $array[$key] = array();
      }

      // overwrite the value in the base array
      if ( is_array($value) ) {
        $value = recurse($array[$key], $value);
      }
      
      $array[$key] = $value;
    }
    
    return $array;
  }
endif;