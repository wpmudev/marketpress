<?php

if ( ! defined('WPMUDEV_METABOX_DIR') ) {
	define('WPMUDEV_METABOX_DIR', plugin_dir_path(__FILE__));
}

if ( ! defined('WPMUDEV_METABOX_URL') ) {
	define('WPMUDEV_METABOX_URL', plugin_dir_url(__FILE__));
}

require_once WPMUDEV_Metabox::class_dir('api.php');

/**
 * Stores the field scripts that have been printed (so we don't print them more than once)
 *
 * @global array
 * @name $printed_field_scripts
 */
$GLOBALS['wpmudev_metaboxes_printed_field_scripts'] = array();

class WPMUDEV_Metabox {
	/**
	 * Refers to the current version of the class
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	var $version = '1.0';
	
	/**
	 * Refers to the meta box's constructor arguments
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	var $args = array();
	
	/**
	 * Refers to the meta box's form fields
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	var $fields = array();
	
	/**
	 * Refers to the nonce action
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	var $nonce_action = '';

	/**
	 * Refers to the nonce field name
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	var $nonce_name = '';
	
	/**
	 * Refers to the metabox's current status (e.g. active or not)
	 *
	 * @since 1.0
	 * @access public
	 * @var bool
	 */
	var $is_active = null;
	
	/**
	 * If the current metabox is a settings page metabox
	 *
	 * @since 1.0
	 * @access public
	 * @var bool
	 */
	var $is_settings_metabox = null;
	
	/**
	 * Refers to any custom field validation messages
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	var $validation_messages = array();
	
	static $dir = '';
	static $url = '';
	
	/**
	 * Constructor function
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 *		An array of arguments. Optional.
	 *
	 *		@type string $id The id of the metabox (required)
	 *		@type string $title The title of the metabox (required)
	 *		@type string $post_type The post type to display the metabox on
	 *		@type array $screen_ids The screen ID(s) to display the metabox on
	 *		@type string $context The context of the metabox (advanced, normal, side)
	 *		@type string $priority The priority of the metabox (default, high, normal)
	 *		@type string $option_name If not a post metabox, enter the option name that will be used to retrieve/save the field's value (e.g. plugin_settings)
	 * }
	 */
	public function __construct( $args = array() ) {
		$this->args = wp_parse_args($args, array(
			'id' => '',
			'title' => '',
			'post_type' => '',
			'screen_ids' => array(),
			'context' => 'advanced',
			'priority' => 'default',
			'option_name' => '',
		));
		
		$this->load_fields();
		$this->nonce_action = 'wpmudev_metabox_' . str_replace('-', '_', $this->args['id']) . '_save_fields';
		$this->nonce_name = $this->nonce_action . '_nonce';

		add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_styles'));		
		add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
		add_action('add_meta_boxes_' . $this->args['post_type'], array(&$this, 'add_meta_boxes'));
		add_action('wpmudev_metaboxes_settings', array(&$this, 'maybe_render'));
		add_action('save_post', array(&$this, 'save_fields'));
		add_action('current_screen', array(&$this, 'maybe_save_settings_fields'));
		add_filter('postbox_classes_' . $this->args['post_type'] . '_' . $this->args['id'], array(&$this, 'add_meta_box_classes'));
	}
	
	/**
	 * Determine if settings fields should be saved
	 *
	 * @since 1.0
	 * @access public
	 */
	public function maybe_save_settings_fields() {
		if ( ! $this->is_active() || ! $this->is_settings_metabox() ) {
			return;
		}
		
		$this->save_fields($this->args['option_name']);
	}
	
	/**
	 * Determines if a metabox should be rendered
	 *
	 * @since 1.0
	 * @access public
	 */
	public function maybe_render() {
		if ( ! $this->is_active() ) {
			return false;
		}
  		
		$this->render($this->args['option_name']);
	}
	
	/**
	 * Adds classes to the metabox for easier styling
	 *
	 * @since 1.0
	 * @access public
	 * @filter postbox_classes_{$post_type}_{$id}
	 * @param array $classes
	 * @return array
	 */
	public function add_meta_box_classes( $classes ) {
		return array_merge($classes, array('wpmudev-postbox'));
	}

	/**
	 * Enqueues admin scripts
	 *
	 * @since 1.0
	 * @access public
	 */	
	public function admin_enqueue_scripts() {
		if ( ! $this->is_active() ) {
			return false;
		}
		
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-validate', $this->class_url('ui/js/jquery.validate.min.js'), array('jquery'), '1.12');
		wp_enqueue_script('wpmudev-metaboxes-admin', $this->class_url('ui/js/admin.js'), array('jquery', 'jquery-validate'), $this->version, true);
		
		$default_messages = array(
			'alphanumeric_error_msg' => __('Please enter only letters and numbers', 'wpmudev_metaboxes'),
		);
		
		wp_localize_script('wpmudev-metaboxes-admin', 'WPMUDEV_Metaboxes_Validation_Messages', array_merge($default_messages, $this->validation_messages));
	}
	
	/**
	 * Enqueues admin scripts
	 *
	 * @since 1.0
	 * @access public
	 */	
	public function admin_enqueue_styles() {
		if ( ! $this->is_active() ) {
			return false;
		}
		
		wp_enqueue_style('wpmudev-metaboxes-admin', $this->class_url('ui/css/admin.css'), array(), $this->version);
	}
	
	/**
	 * Gets the base class directory with optional path
	 *
	 * @since 1.0
	 * @access public
	 * @param string $path
	 * @return string
	 */
	public static function class_dir( $path = '' ) {
		return WPMUDEV_METABOX_DIR . ltrim($path, '/');
	}

	/**
	 * Gets the base class url with optional path
	 *
	 * @since 1.0
	 * @access public
	 * @param string $path
	 * @return string
	 */
	public static function class_url( $path = '' ) {
		return WPMUDEV_METABOX_URL . ltrim($path, '/');
	}

	/**
	 * Get all files from a given directory
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param string $dir The full path of the directory
	 * @param string $ext Get only files with a given extension. Set to NULL to get all files.
	 * @return array or false if no files exist
	 */
	public static function get_dir_files( $dir, $ext = 'php' ) {
		$myfiles = array();
		
		if ( ! is_null($ext) )
			$ext = '.' . $ext;
		
		if ( false === file_exists($dir) )
			return false;
		
		$dir = trailingslashit($dir);
		$files = glob($dir . '*' . $ext);
		
		return ( empty($files) ) ? false : $files;
	}

	/**
	 * Includes all files in a given directory
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param string $dir The directory to work with
	 * @param string $ext Only include files with this extension
	 */
	public static function include_dir( $dir, $ext = 'php' ) {
		if ( false === ($files = self::get_dir_files($dir, $ext)) )
			return false;
		
		foreach ( $files as $file ) {
			include_once $file;
		}
	}
		
	/**
	 * Load fields
	 *
	 * @since 1.0
	 * @access public
	 */
	public function load_fields() {
		require_once self::class_dir('class-wpmudev-field.php');
		self::include_dir(self::class_dir('fields'));	
	}	
		
	/**
	 * Adds the meta box to the appropriate screen
	 *
	 * @since 1.0
	 * @access public
	 * @param object $post
	 */
	public function add_meta_boxes( $post ) {
		add_meta_box($this->args['id'], $this->args['title'], array(&$this, 'render'), $this->args['post_type'], $this->args['context'], $this->args['priority']);
	}
	
	/**
	 * Renders the meta box
	 *
	 * @since 1.0
	 * @access public
	 * @param object/string post
	 */
	public function render( $post = null ) {
		wp_nonce_field($this->nonce_action, $this->nonce_name);
		
		if ( $post instanceof WP_Post ) {
			$post = $post->ID;
		}
		
		if ( $this->is_settings_metabox() ) {
			echo '<div id="poststuff">';
				echo '<div class="meta-box-sortables">';
					echo '<div class="postbox wpmudev-postbox">';
						echo '<div class="inside">';
							echo '<h3 class="hndle"><span>' . $this->args['title'] . '</span></h3>';
		}
		
							echo '<div class="wpmudev-fields clearfix">';
				
							foreach ( $this->fields as $field ) {
								echo '<div class="wpmudev-field ' . str_replace('_', '-', strtolower(get_class($field))) . '">';
									echo '<div class="wpmudev-field-label">' . $field->args['label']['text'] . (( strpos($field->args['class'], 'required') !== false ) ? '<span class="required">*</span>' : '') . '</div>';
									echo '<div class="wpmudev-field-desc">' . $field->args['desc'] . '</div>';
									$field->display($post);
								echo '</div>';
							}
				
							echo '</div>';
		
		if ( $this->is_settings_metabox() ) {
						echo '</div>';
					echo '</div>';
				echo '</div>';
			echo '</div>';
		}
	}
	
	/**
	 * Saves the metabox fields to the database
	 *
	 * @since 1.0
	 * @access public
	 * @action save_post
	 * @param int $post_id The current post ID
	 */
	public function save_fields( $post_id ) {
		if ( ! isset($_POST[$this->nonce_name]) || (isset($_POST[$this->nonce_name]) && ! wp_verify_nonce($_POST[$this->nonce_name], $this->nonce_action)) ) {
			// Bail - nonce is not set or could not be verified
			return;
		}

		// Make sure we only run once
		if ( wp_cache_get('save_fields', 'wpmudev_metaboxes') ) {
			return;
		}
		wp_cache_set('save_fields', true, 'wpmudev_metaboxes');
		
		// Avoid infinite loops later (e.g. when calling wp_insert_post, etc)
		remove_action('save_post', array(&$this, 'save_fields'));
		
		// Make sure $post_id isn't a revision
		if ( wp_is_post_revision($post_id) ) {
			return;
		}
		
		/**
		 * Fires after the appropriate nonce's have been verified for fields to tie into.
		 *
		 * @since 3.0
		 * @param int $post_id Post ID.
		 */
		do_action('wpmudev_metaboxes_save_fields', $post_id);
	}
	
	/**
	 * Checks if the metabox is a settings metabox
	 *
	 * @since 1.0
	 * @access public
	 * @return bool
	 */
	public function is_settings_metabox() {
		if ( ! is_null($this->is_settings_metabox) ) {
			return $this->is_settings_metabox;
		}

		$current_screen = $this->get_current_screen();
		
		$this->is_settings_metabox = false;
		if ( in_array($current_screen->id, $this->args['screen_ids']) ) {
			$this->is_settings_metabox = true;
		}
		
		return $this->is_settings_metabox;
	}
	
	/**
	 * Safely gets the $current_screen object even before the current_screen hook is fired
	 *
	 * @since 1.0
	 * @access public
	 * @uses $current_screen, $hook_suffix, $pagenow, $taxnow, $typenow
	 * @return object
	 */
	public function get_current_screen() {
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
				if ( ! empty($typenow) ) {
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
	
	/**
	 * Checks if the current metabox is active for the current page
	 *
	 * @since 1.0
	 * @access public
	 * @return bool
	 */
	public function is_active() {
		if ( ! is_null($this->is_active) ) {
			return $this->is_active;
		}
		
		//only load metaboxes on appropriate post page and for assigned post type or screen_id
		$this->is_active = true;
		if ( $this->args['post_type'] != $this->get_current_screen()->id && ! in_array($this->get_current_screen()->id, $this->args['screen_ids']) ) {
			$this->is_active = false;
		}
		
		if ( in_array($this->get_current_screen()->id, $this->args['screen_ids']) ) {
			$this->is_settings_metabox = true;
		}
		
		return $this->is_active;	
	}
	
	/**
	 * Adds a form field to the meta box
	 *
	 * @since 1.0
	 * @access public
	 * @param string $type
	 * @param array $args
	 * @return WPMUDEV_Field
	 */
	public function add_field( $type, $args = array() ) {		
		$class = apply_filters('wpmudev_metabox_add_field', 'WPMUDEV_Field_' . ucfirst($type), $type, $args);
				
		if ( ! class_exists($class) || ! $this->is_active() ) {
			return false;	
		}
		
		if ( ! empty($args['custom_validation_message']) ) {
			$this->validation_messages = array_merge($this->validation_messages, array($args['name'] => $args['custom_validation_message']));
		}
		
		$args['echo'] = false;
		$field = new $class($args);
		$field->metabox = $this;
		$this->fields[] = $field;
		
		return $field;
	}
}