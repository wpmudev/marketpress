<?php

if ( ! function_exists('mp') ) :
	/**
	 * Returns the Marketpress instance
	 *
	 * @since 3.0
	 * @return object
	 */
	 
	function mp() {
		return Marketpress::get_instance();
	}
endif;

if ( ! function_exists('mp_get_states') ) :
	/**
	 * Gets the states/regions/provinces for a given country
	 *
	 * @since 3.0
	 * @param string $country The country code.
	 * @return array
	 */
	function mp_get_states( $country ) {
		if ( ! in_array($country, array('US','CA','GB','AU')) ) {
			return array();
		}
		
		switch ( $country ) {
			case 'US' :
				$list = mp()->usa_states;
			break;
			
			case 'CA' :
				$list = mp()->canadian_provinces;
			break;
			
			case 'GB' :
				$list = mp()->uk_counties;
			break;
			
			case 'AU' :
				$list = mp()->australian_states;
			break;
		}
		
		return $list;
	}
endif;

if ( ! function_exists('mp_get_dir_files') ) :
	/**
	 * Get all files from a given directory
	 *
	 * @since 3.0
	 * @param string $dir The full path of the directory
	 * @param string $ext Get only files with a given extension. Set to NULL to get all files.
	 * @return array or false if no files exist
	 */
	function mp_get_dir_files( $dir, $ext = 'php' ) {
		$myfiles = array();
		
		if ( ! is_null($ext) )
			$ext = '.' . $ext;
		
		if ( false === file_exists($dir) )
			return false;
		
		$dir = trailingslashit($dir);
		$files = glob($dir . '*' . $ext);
		
		return ( empty($files) ) ? false : $files;
	}
endif;

if ( ! function_exists('mp_include_dir') ) :
	/**
	 * Includes all files in a given directory
	 *
	 * @since 3.0
	 *
	 * @param string $dir The directory to work with
	 * @param string $ext Only include files with this extension
	 */
	function mp_include_dir( $dir, $ext = 'php' ) {
		if ( false === ($files = mp_get_dir_files($dir, $ext)) )
			return false;
		
		foreach ( $files as $file ) {
			include_once $file;
		}
	}
endif;

if ( ! function_exists('mp_get_current_screen') ) :
	/**
	 * Safely gets the $current_screen object even before the current_screen hook is fired
	 *
	 * @since 3.0
	 * @uses $current_screen, $hook_suffix, $pagenow, $taxnow, $typenow
	 * @return object
	 */
	function mp_get_current_screen() {
		global $current_screen, $hook_suffix, $pagenow, $taxnow, $typenow;
		
		if ( empty($current_screen) ) {
			//set current screen (not normally available here) - this code is derived from wp-admin/admin.php
			require_once ABSPATH . 'wp-admin/includes/screen.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			
			if ( isset($_GET['page']) ) {
				$plugin_page = wp_unslash($_GET['page']);
				$plugin_page = plugin_basename($plugin_page);
			}

			if ( isset($_REQUEST['post_type']) && post_type_exists($_REQUEST['post_type']) ) {
				$typenow = $_REQUEST['post_type'];
			} else {
				$typenow = '';
			}
			
			if ( isset($_REQUEST['taxonomy']) && taxonomy_exists($_REQUEST['taxonomy']) ) {
				$taxnow = $_REQUEST['taxonomy'];
			} else {
				$taxnow = '';
			}

			if ( isset($plugin_page) ) {
				if ( !empty($typenow) ) {
					$the_parent = $pagenow . '?post_type=' . $typenow;
				} else {
					$the_parent = $pagenow;
				}
					
				if ( ! $page_hook = get_plugin_page_hook($plugin_page, $the_parent) ) {
					$page_hook = get_plugin_page_hook($plugin_page, $plugin_page);
					// backwards compatibility for plugins using add_management_page
					if ( empty( $page_hook ) && 'edit.php' == $pagenow && '' != get_plugin_page_hook($plugin_page, 'tools.php') ) {
						// There could be plugin specific params on the URL, so we need the whole query string
						if ( ! empty($_SERVER[ 'QUERY_STRING' ]) ) {
							$query_string = $_SERVER[ 'QUERY_STRING' ];
						} else {
							$query_string = 'page=' . $plugin_page;
						}
							
						wp_redirect( admin_url('tools.php?' . $query_string) );
						exit;
					}
				}
				
				unset($the_parent);
			}
			
			$hook_suffix = '';
			if ( isset($page_hook) ) {
				$hook_suffix = $page_hook;
			} else if ( isset($plugin_page) ) {
				$hook_suffix = $plugin_page;
			} else if ( isset($pagenow) ) {
				$hook_suffix = $pagenow;
			}

			set_current_screen();
		}
		
		return get_current_screen();
	}
endif;

if ( ! function_exists('mp_get_field') ) :
	/**
	 * Gets a fields value
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The name of the field
	 * @param string $type The type of field
	 * @param int $post_id (optional) The post ID to retrieve the value from - defaults to current post id
	 * @return mixed
	 */
	function mp_get_field( $name, $type, $post_id = null ) {
		if ( ! class_exists($type) || empty($name) ) {
			return false;
		}
		
		if ( is_null($post_id) ) {
			$post_id = get_the_ID();	
		}
		
		if ( empty($post_id) ) {
			return false;
		}
		
		$field = new $type(array('name' => $name));
		$value = $field->get_value($post_id);
	}
endif;

if ( ! function_exists('mp_admin') ) :
	/**
	 * Returns the MP_Admin instance
	 *
	 * @since 3.0
	 * @return object
	 */
	 
	function mp_admin() {
		return MP_Admin::get_instance();
	}
endif;

if ( ! function_exists('mp_public') ) :
	/**
	 * Returns the MP_Public instance
	 *
	 * @since 3.0
	 * @return object
	 */
	 
	function mp_public() {
		return MP_Public::get_instance();
	}
endif;

if ( ! function_exists('mp_doing_ajax') ) :
	/**
	 * Checks if an ajax action is currently being executed
	 *
	 * @since 3.0
	 * @uses DOING_AJAX
	 * @return bool
	 */
	function mp_doing_ajax() {
		return ( defined('DOING_AJAX') && DOING_AJAX );
	}
endif;

if ( ! function_exists('array_replace_recursive') ) :
	/**
	 * Recursively replace one array with another. Provides compatibility for PHP version < 5.3
	 *
	 * @since 3.0.0
	 * @param array $array
	 * @param array $array1 The values from this array will overwrite the values from $array
	 * @return array
	 */
	function array_replace_recursive() {
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
endif;

if ( ! function_exists('mp_arr_get_value') ) :
	/**
	 * Safely retrieve a value from an array
	 *
	 * @since 3.0.0
	 * @uses mp_arr_search()
	 *
	 * @param string $key (e.g. key1->key2->key3)
	 * @param array $array The $array to work with
	 * @param mixed $default The default value to return if $key is not found within $array
	 * @return mixed
	 */
	
	function mp_arr_get_value( $key, $array, $default = false ) {
		$keys = explode('->', $key);
		$keys = array_map('trim', $keys);
		
		if ( count($keys) > 0 ) {
			$value = mp_arr_search($array, $key);
		} else {
			$value = isset($array[$keys[0]]) ? $array[$keys[0]] : null;
		}
		
		return ( is_null($value) ) ? $default : $value;
	}
endif;

if ( ! function_exists('mp_get_get_value') ) :
	/**
	 * Safely retreives a value from the $_GET array
	 *
	 * @since 3.0.0
	 * @uses mp_arr_get_value()
	 *	 
	 * @param string $key (e.g. key1->key2->key3)
	 * @param mixed $default The default value to return if $key is not found within $array
	 * @return mixed
	 */	 
	
	function mp_get_get_value( $key, $default = false ) {
		return mp_arr_get_value($key, $_GET, $default);
	}
endif;

if ( ! function_exists('mp_get_post_value') ) :
	/**
	 * Safely retreives a value from the $_POST array
	 *
	 * @since 3.0.0
	 * @uses mp_arr_get_value()
	 *	 
	 * @param string $key (e.g. key1->key2->key3)
	 * @param mixed $default The default value to return if $key is not found within $array
	 * @return mixed
	 */	 
	
	function mp_get_post_value( $key, $default = false ) {
		return mp_arr_get_value($key, $_POST, $default);
	}
endif;

if ( ! function_exists('mp_get_setting') ) :
	/*
	 * Safely retrieves a setting
	 *
	 * An easy way to get to our settings array without undefined indexes
	 *
	 * @since 3.0.0
	 * @uses mp_arr_search()
	 *
	 * @param string $key A setting key, or -> separated list of keys to go multiple levels into an array
	 * @param mixed $default Returns when setting is not set
	 * @return mixed
	 */
	 
	function mp_get_setting( $key, $default = null ) {
		$settings = get_option('mp_settings');
		
		$keys = explode('->', $key);
		$keys = array_map('trim', $keys);
		
		if ( count($keys) > 0 ) {
			$setting = mp_arr_search($settings, $key);
		} else {
			$setting = isset($settings[$keys[0]]) ? $settings[$keys[0]] : null;
		}
		
		if ( is_null($setting) )
			$setting = $default;
		
		return apply_filters('mp_setting_' . implode('', $keys), $setting, $default);
	}
endif;

if ( ! function_exists('mp_arr_search') ) :
	/**
	 * Searches an array multidimensional array for a specific path (if it exists)
	 *
	 * @since 3.0.0
	 *
	 * @param array $array The array we want to search
	 * @param string $path The path we want to check for (e.g. key1->key2->key3 = $array[key1][key2][key3])
	 * @return mixed
	 */
	 
function mp_arr_search( $array, $path ) {
	$keys = explode('->', $path);
	$keys = array_map('trim', $keys);
	
	for ( $i = $array; ($key = array_shift($keys)) !== null; $i = $i[$key] ) {
    if ( ! isset($i[$key]) ) {
    	return null;
    }
  }
  
  return $i;
}
endif;

if ( ! function_exists('mp_toggle_plugin') ) :
	/**
	 * Activates/deactivates a given gateway/shipping plugin
	 *
	 * @since 3.0.0
	 *
	 * @param string $code The gateway code/ID
	 * @param string $type The type of the plugin
	 * @param string $subtype The subtype of the plugin
	 * @param bool $activate Activate the plugin? False will deactivate.
	 */
	
	function mp_toggle_plugin( $code, $type, $subtype, $activate ) {
		global $mp_gateway_plugins, $mp_gateway_active_plugins, $mp_shipping_plugins, $mp_shipping_active_plugins;
		
		if ( $activate ) {
			switch ( $type ) {
				case 'gateway' :
					if ( mp_arr_get_value("$code->0", $mp_gateway_plugins, false) && class_exists($mp_gateway_plugins[$code][0]) ) {
						$mp_gateway_active_plugins[$code] = new $mp_gateway_plugins[$code][0];
					}
				break;
			
				case 'shipping' :
					if ( mp_arr_get_value("$code->0", $mp_shipping_plugins, false) && class_exists($mp_shipping_plugins[$code][0]) ) {
						$mp_shipping_active_plugins[$subtype][$code] = new $mp_shipping_plugins[$code][0];
					}
				break;
			}
		} else {
			switch ( $type ) {
				case 'gateway' :
					unset($mp_gateway_active_plugins[$code]);
				break;
				
				case 'shipping' :
					unset($mp_shipping_active_plugins[$subtype][$code]);
				break;
			}
		}
	}
endif;

if ( ! function_exists('mp_update_setting') ) :
	/**
	 * Update a specific setting
	 *
	 * @since 3.0.0
	 *
	 * @param string $key The key to update
	 * @param mixed $value The value to use
	 * @return bool
	 */

	function mp_update_setting( $key, $value ) {
		$settings = get_option('mp_settings');
		$settings[$key] = $value;
		return update_option('mp_settings', $settings);
	}
endif;

if ( ! function_exists('mp_format_currency') ) :
	/**
	 * Formats currency
	 *
	 * @since 3.0.0
	 *
	 * @param string $currency The currency code to use for formatting (defaults to value set in currency settings)
	 * @param float $amount The amount to format
	 * @return string
	 */
	 
	function mp_format_currency( $currency = '', $amount = false ) {
		
		
		$currencies = apply_filters('mp_currencies', mp()->currencies);
		
		if ( ! $currency )
			$currency = mp_get_setting('currency', 'USD');

		// get the currency symbol
		$symbol = $currencies[$currency][1];
		// if many symbols are found, rebuild the full symbol
		$symbols = array_map('trim', explode(', ', $symbol));
		if (is_array($symbols)) {
			$symbol = "";
			foreach ($symbols as $temp) {
				$symbol .= '&#x'.$temp.';';
			}
		} else {
			$symbol = '&#x'.$symbol.';';
		}

		//check decimal option
		if ( mp_get_setting('curr_decimal') === '0' ) {
			$decimal_place = 0;
			$zero = '0';
		} else {
			$decimal_place = 2;
			$zero = '0.00';
		}

		//format currency amount according to preference
		if ( $amount ) {
			if ( mp_get_setting('curr_symbol_position') == 1 || ! mp_get_setting('curr_symbol_position') )
				return $symbol . number_format_i18n($amount, $decimal_place);
			
			if ( mp_get_setting('curr_symbol_position') == 2 )
				return $symbol . ' ' . number_format_i18n($amount, $decimal_place);
			
			if ( mp_get_setting('curr_symbol_position') == 3 )
				return number_format_i18n($amount, $decimal_place) . $symbol;
				
			if ( mp_get_setting('curr_symbol_position') == 4 )
				return number_format_i18n($amount, $decimal_place) . ' ' . $symbol;
		} else if ( $amount === false ) {
			return $symbol;
		} else {
			if ( mp_get_setting('curr_symbol_position') == 1 || ! mp_get_setting('curr_symbol_position') )
				return $symbol . $zero;
			
			if ( mp_get_setting('curr_symbol_position') == 2 )
				return $symbol . ' ' . $zero;
			
			if ( mp_get_setting('curr_symbol_position') == 3 )
				return $zero . $symbol;
			
			if ( mp_get_setting('curr_symbol_position') == 4 )
				return $zero . ' ' . $symbol;
		}
		
		return $symbol;
	}
endif;

if ( ! function_exists('mp_display_currency') ) :
	/**
	 * Round and display currency with padded zeros
	 *
	 * @since 3.0.0
	 *
	 * @param float $amount
	 * @return string
	 */
	 
	function mp_display_currency( $amount ) {
		if ( mp_get_setting('curr_decimal') === '0' )
			return number_format( round( $amount ), 0, '.', '');
		else
			return number_format( round( $amount, 2 ), 2, '.', '');
	}
endif;

if ( ! function_exists('mp_format_date') ) :
	/**
	 * Translates a gmt timestamp into local timezone for display
	 *
	 * @since 3.0.0
	 *
	 * @param int $gmt_timestamp
	 * @return string
	 */
	 
	function mp_format_date( $gmt_timestamp ) {
		return date_i18n(get_option('date_format') . ' - ' . get_option('time_format'), $gmt_timestamp + (get_option('gmt_offset') * HOUR_IN_SECONDS));
	}
endif;

if ( ! function_exists('mp_before_tax_price') ) :
	/**
	 * Returns the before tax price for a given amount based on a bunch of foreign tax laws.
	 *
	 * @since 3.0.0
	 *
	 * @param float $tax_price The price including taxes
	 * @param int $product_id
	 * @return float
	 */
	 
	function mp_before_tax_price( $tax_price, $product_id = false ) {
		//if tax inclusve pricing is turned off just return given price
		if ( ! mp_get_setting('tax->tax_inclusive') )
			return $tax_price;

		if ( $product_id && get_post_meta($product_id, 'mp_is_special_tax', true) ) {
			$rate = get_post_meta($product_id, 'mp_special_tax', true);
		} else {
			//figure out rate in case its based on a canadian base province
			$rate =	('CA' == mp_get_setting('tax->base_country')) ? mp_get_setting('tax->canada_rate'.mp_get_setting('base_province')) : mp_get_setting('tax->rate');
		}

		return $tax_price / ($rate + 1); //do not round this to avoid rounding errors in tax calculation
	}
endif;

if ( ! function_exists('mp_is_valid_zip') ) :
	/**
	 * Checks if a given zip is valid for a given country
	 *
	 * @since 3.0.0
	 *
	 * @param string $zip The zip code to check
	 * @param string $country The country to check
	 * @return bool
	 */
	 
	function mp_is_valid_zip( $zip, $country ) {
		
		
		if ( array_key_exists($country, mp()->countries_no_postcode) )
			//given country doesn't use post codes so zip is always valid
			return true;
		
		if ( empty($zip) )
			//no post code provided
			return false;
			
		if ( strlen($zip) < 3 )
			//post code is too short - see http://wp.mu/8wg
			return false;
			
		return true;
	}
endif;

if ( ! function_exists('mp_plugin_url') ) :
	/**
	 * Returns a url with given path relative to the plugin's root
	 *
	 * @since 3.0.0
	 *
	 * @param string $path
	 * @return string
	 */
	 
	function mp_plugin_url( $path = '' ) {
		return mp()->plugin_url($path);
	}
endif;

if ( ! function_exists('mp_plugin_dir') ) :
	/**
	 * Returns a path with given path relative to the plugin's root
	 *
	 * @since 3.0.0
	 *
	 * @param string $path
	 * @return string
	 */
	 
	function mp_plugin_dir( $path = '' ) {
		return mp()->plugin_dir($path);
	}
endif;

if ( !  function_exists('mp_array_map_recursive') ) :
	/**
	 * Execute a give function on each element in a given array
	 * @param string $func The function name to execute
	 * @param array $array The array to execute on
	 * @return array
	 */
	 
	function mp_array_map_recursive( $func, $array ) {
		foreach ( $array as $key => $val ) {
			$array[$key] = ( is_array($array[ $key ]) ) ? mp_array_map_recursive($func, $array[ $key ]) : $func($val);
    }
    
    return $array;
	}
endif;