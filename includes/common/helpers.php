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

if ( ! function_exists('mp_push_to_array') ) :
	/**
	 * Pushes a value with a given array with given key
	 *
	 * @since 1.0
	 * @access public
	 * @param array $array The array to work with.
	 * @param string $key_string
	 * @param mixed $value
	 */
	function mp_push_to_array( &$array, $key_string, $value ) {
    $keys = explode('->', $key_string);
    $branch = &$array;
    
    while ( count($keys) ) {
    	$key = array_shift($keys);
    	
	    if ( ! is_array($branch) ) {
		  	$branch = array();
	    }	
	        
	    $branch = &$branch[$key];
    }
    
    $branch = $value;
	}
endif;

if ( ! function_exists('mp_country_list') ) :
	/**
	 * Gets the country list without the popular countries
	 *
	 * @since 3.0
	 * @return array
	 */
	function mp_country_list() {
		$sorted = array();
		$countries = mp()->countries;
		 
		foreach ( $countries as $code => $country ) {
			if ( ! in_array($code, mp()->popular_countries) ) {
			 	$sorted[$code] = $country;
			}
		}
		
		return $sorted;
	}
endif;

if ( ! function_exists('mp_popular_country_list') ) :
	/**
	 * Gets the popular country list
	 *
	 * @since 3.0
	 * @return array
	 */
	function mp_popular_country_list() {
		$sorted = array();
		$countries = mp()->popular_countries;
		
		/**
		 * Filter the popular countries list
		 *
		 * @since 3.0
		 * @param array $countries The default popular countries.
		 */
		$countries = apply_filters('mp_popular_country_list', $countries);
		
		foreach ( $countries as $code => $country ) {
			$sorted[$code] = $country;
		}
		
		asort($sorted);
		
		return $sorted;
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
		$list = array();
		
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

if ( ! function_exists('mp_get_theme_list') ) :
	/**
	 * Get a list of MP themes
	 *
	 * @since 3.0
	 * @access public
	 */
	 function mp_get_theme_list() {
		$theme_list = array();
		$theme_dirs = array(mp_plugin_dir('ui/themes'), WP_CONTENT_DIR . '/marketpress-styles/');
		
		foreach ( $theme_dirs as $theme_dir ) {
			$themes = mp_get_dir_files($theme_dir, 'css');
			
			if ( ! $themes ) {
				continue;
			}
			
			$allowed_themes = $themes;
			if ( is_multisite() && ! is_network_admin() ) {
				$allowed_themes = mp_get_network_setting('allowed_themes');
			}
			
			foreach ( $themes as $theme ) {
				$theme_data = get_file_data($theme, array('name' => 'MarketPress Style'));
				$key = basename($theme, '.css');
				
				if ( $name = mp_arr_get_value('name', $theme_data) ) {
					if ( is_multisite() && ! is_network_admin() ) {
						if ( $permissions = mp_arr_get_value($key, $allowed_themes) ) {
							$level = str_replace('psts_level_', '', $permissions);
							
							if ( $permissions != 'full' || ! mp_is_pro_site(false, $level) ) {
								continue;
							}
						}
					}
					
					if ( is_multisite() && is_network_admin() ) {
						$theme_list[basename($theme, '.css')] = array('path' => $theme, 'name' => $name);
					} else {
						$theme_list[basename($theme, '.css')] = $name;
					}
				}
			}
		}
		
		asort($theme_list);
		
		return $theme_list;		 
	 }
endif;

if ( ! function_exists('mp_is_pro_site') ) :
	/**
	 * Check if the is_pro_site() function exists and if so calls it
	 *
	 * @since 3.0
	 */
	function mp_is_pro_site( $blog_id = false, $level = false ) {
		if ( ! function_exists('is_pro_site') ) {
			return true;
		}
		
		return is_pro_site($blog_id, $level);
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
		$files = array_filter($files, create_function('$filepath', 'return is_readable($filepath);'));
		
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

if ( ! function_exists('mp_doing_autosave') ) :
	/**
	 * Checks if an autosave action is currently being executed
	 *
	 * @since 3.0
	 * @uses DOING_AUTOSAVE
	 * @return bool
	 */
	function mp_doing_autosave() {
		return ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE );
	}
endif;


if ( ! function_exists('array_replace_recursive') ) :
	/**
	 * Recursively replace one array with another. Provides compatibility for PHP version < 5.3
	 *
	 * @since 3.0
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

if ( ! function_exists('debug_to_console') ) :
	/**
	 * Send a log to the browser console
	 *
	 * @since 3.0
	 * @access public
	 */
	function debug_to_console( $data ) {
		if ( is_array($data) || is_object($data) )
		{
			echo "<script>if ( typeof(window.console) !== 'undefined' ) console.log('PHP: " . json_encode($data) . "');</script>";
		} else {
			echo "<script>if ( typeof(window.console) !== 'undefined' ) console.log('PHP: " . $data . "');</script>";
		}
	}
endif;

if ( ! function_exists('mp_arr_get_value') ) :
	/**
	 * Safely retrieve a value from an array
	 *
	 * @since 3.0
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
		$value = mp_arr_search($array, $key);
		
		return ( is_null($value) ) ? $default : $value;
	}
endif;


if ( ! function_exists('mp_get_cookie_value') ) :
	/**
	 * Safely retreives a value from the $_COOKIE array
	 *
	 * @since 3.0
	 * @uses mp_arr_get_value()
	 *	 
	 * @param string $key (e.g. key1->key2->key3)
	 * @param mixed $default The default value to return if $key is not found within $array
	 * @return mixed
	 */	 
	function mp_get_cookie_value( $key, $default = false ) {
		return stripslashes(mp_arr_get_value($key, $_COOKIE, $default));
	}
endif;

if ( ! function_exists('mp_get_get_value') ) :
	/**
	 * Safely retreives a value from the $_GET array
	 *
	 * @since 3.0
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
	 * @since 3.0
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

if ( ! function_exists('mp_get_session_value') ) :
	/**
	 * Safely retreives a value from the $_SESSION array
	 *
	 * @since 3.0
	 * @uses mp_arr_get_value()
	 *	 
	 * @param string $key (e.g. key1->key2->key3)
	 * @param mixed $default The default value to return if $key is not found within $array
	 * @return mixed
	 */	 
	function mp_get_session_value( $key, $default = false ) {
		return ( session_id() == '' ) ? $default : mp_arr_get_value($key, $_SESSION, $default);
	}
endif;

if ( ! function_exists('mp_get_setting') ) :
	/*
	 * Safely retrieves a setting
	 *
	 * An easy way to get to our settings array without undefined indexes
	 *
	 * @since 3.0
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
		$setting = mp_arr_get_value($key, $settings, $default);
		
		return apply_filters('mp_setting_' . implode('', $keys), $setting, $default);
	}
endif;

if ( ! function_exists('mp_get_network_setting') ) :
	/*
	 * Safely retrieves a network setting
	 *
	 * An easy way to get to our settings array without undefined indexes
	 *
	 * @since 3.0
	 * @uses mp_arr_search()
	 *
	 * @param string $key A setting key, or -> separated list of keys to go multiple levels into an array
	 * @param mixed $default Returns when setting is not set
	 * @return mixed
	 */
	function mp_get_network_setting( $key, $default = null ) {
		$settings = wp_cache_get('network_settings', 'marketpress');
		if ( ! $settings ) {
			$settings = get_site_option('mp_network_settings', $default, false);
			wp_cache_set('network_settings', $settings, 'marketpress');
		}
		
		$keys = explode('->', $key);
		$keys = array_map('trim', $keys);
		$setting = mp_arr_get_value($key, $settings, $default);
		
		return apply_filters('mp_network_setting_' . implode('', $keys), $setting, $default);
	}
endif;


if ( ! function_exists('mp_arr_search') ) :
	/**
	 * Searches an array multidimensional array for a specific path (if it exists)
	 *
	 * @since 3.0
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

if ( ! function_exists('mp_update_setting') ) :
	/**
	 * Update a specific setting
	 *
	 * @since 3.0
	 *
	 * @param string $key The key to update
	 * @param mixed $value The value to use
	 * @return bool
	 */
	function mp_update_setting( $key, $value ) {
		$settings = get_option('mp_settings');
		mp_push_to_array($settings, $key, $value);
		return update_option('mp_settings', $settings);
	}
endif;

if ( ! function_exists('mp_update_network_setting') ) :
	/**
	 * Update a specific network setting
	 *
	 * @since 3.0
	 *
	 * @param string $key The key to update
	 * @param mixed $value The value to use
	 * @return bool
	 */
	function mp_update_network_setting( $key, $value ) {
		$settings = get_site_option('mp_network_settings');
		mp_push_to_array($settings, $key, $value);
		return update_site_option('mp_network_settings', $settings);
	}
endif;


if ( ! function_exists('mp_plugin_url') ) :
	/**
	 * Returns a url with given path relative to the plugin's root
	 *
	 * @since 3.0
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
	 * @since 3.0
	 *
	 * @param string $path
	 * @return string
	 */
	 
	function mp_plugin_dir( $path = '' ) {
		return mp()->plugin_dir($path);
	}
endif;

if ( ! function_exists('mp_array_map_recursive') ) :
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

if ( ! function_exists('mp_array_to_attributes') ) :
	/**
	 * Convert an array of attributes to an html string
	 *
	 * @since 3.0
	 * @param array $attributes
	 * @return string
	 */
	function mp_array_to_attributes( $attributes ) {
		$atts = '';
		foreach ( $attributes as $key => $val ) {
			$atts .= ' ' . $key . '="' . esc_attr($val) . '"';
		}
		
		return $atts;
	}
endif;

if ( ! function_exists('mp_is_main_site') ) :
	/**
	 * Checks if the current blog is the main site
	 *
	 * @since 3.0
	 * @uses $wpdb
	 */
	function mp_is_main_site() {
		global $wpdb;
		
		if ( MP_ROOT_BLOG !== false ) {
			return $wpdb->blogid == MP_ROOT_BLOG;
		} else {
			return is_main_site();
		}
	}
endif;

if ( ! function_exists('mp_main_site_id') ) :
	/**
	 * Get the main site id
	 *
	 * @since 3.0
	 * @uses $current_site
	 */
	function mp_main_site_id() {
		global $current_site;
		
		if ( MP_ROOT_BLOG !== false ) {
			return MP_ROOT_BLOG;
		} else {
			return $current_site->blog_id;
		}
	}
endif;

if ( ! function_exists('mp_get_store_caps') ) :
	/**
	 * Get store capabilities
	 *
	 * @since 3.0
	 * @return array
	 */
	function mp_get_store_caps() {
		if ( $store_caps = wp_cache_get('store_caps', 'marketpress') ) {
			return $store_caps;
		}
		
		$store_caps = array('manage_store_settings' => 'manage_store_settings');
		$taxonomies = array('product_category', 'product_tag');
		$pts = array('product', 'mp_product', 'product_coupon', 'mp_order');
		
		foreach ( $taxonomies as $tax_slug ) {
			if ( ! taxonomy_exists($tax_slug) ) {
				continue;
			}
			
			$tax = get_taxonomy($tax_slug);
			foreach ( $tax->cap as $cap ) {
				$store_caps[$cap] = $cap;
			}
		}
		
		foreach ( $pts as $pt_slug ) {
			if ( ! post_type_exists($pt_slug) ) {
				continue;
			}
			
			$pt = get_post_type_object($pt_slug);
			foreach ( $pt->cap as $cap ) {
				$store_caps[$cap] = $cap;
			}
		}
		
		wp_cache_set('store_caps', $store_caps, 'marketpress');
		
		return $store_caps;		
	}
endif;