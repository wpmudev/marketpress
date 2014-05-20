<?php

require_once WPMUDEV_Metabox::class_dir('api.php');

/**
 * Stores the field scripts that have been printed (so we don't print them more than once)
 *
 * @global array
 * @name $printed_field_scripts
 */
$GLOBALS['printed_field_scripts'] = array();

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
	 * Constructor function
	 *
	 * @since 1.0
	 * @access public
	 */
	public function __construct( $args = array() ) {
		$this->args = wp_parse_args($args, array(
			'id' => '',
			'title' => '',
			'post_type' => '',
			'context' => 'advanced',
			'priority' => 'default',
			'scripts' => array(),
			'stylesheets' => array(),
		));
		
		$this->load_fields();
		$this->nonce_action = 'wpmudev_metabox_' . str_replace('-', '_', $this->args['id']) . '_save_fields';
		$this->nonce_name = $this->nonce_action . '_nonce';

		add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_styles'));		
		add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
		add_action('add_meta_boxes_' . $this->args['post_type'], array(&$this, 'add_meta_boxes'));
		add_action('save_post', array(&$this, 'save_fields'));
		add_filter('postbox_classes_' . $this->args['post_type'] . '_' . $this->args['id'], array(&$this, 'add_meta_box_classes'));
	}
	
	/**
	 * Adds classes to the metabox for easier styling
	 *
	 * @since 3.0
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
		if ( get_current_screen()->post_type != $this->args['post_type'] ) {
			return;
		}
		
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-validate', $this->class_url('ui/js/jquery.validate.min.js', array('jquery'), '1.12'));
		wp_enqueue_script('wpmudev-metaboxes-admin', $this->class_url('ui/js/admin.js'), array('jquery', 'jquery-validate'), $this->version);
		
		wp_localize_script('wpmudev-metaboxes-admin', 'WPMUDEV_Metaboxes', array(
			'alphanumeric_error_msg' => __('Please enter only letters and numbers', 'wpmudev_metaboxes'),
			'discount_error_msg' => __('An invalid discount amount was entered', 'wpmudev_metaboxes'),
		));
	}
	
	/**
	 * Enqueues admin scripts
	 *
	 * @since 1.0
	 * @access public
	 */	
	public function admin_enqueue_styles() {
		if ( get_current_screen()->post_type != $this->args['post_type'] ) {
			return;
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
		return plugin_dir_path(__FILE__) . ltrim($path, '/');
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
		return plugin_dir_url(__FILE__) . ltrim($path, '/');
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
	 * @param object post
	 */
	public function render( $post ) {
		wp_nonce_field($this->nonce_action, $this->nonce_name);
		
		echo '<div class="wpmudev-fields clearfix">';
		
		foreach ( $this->fields as $field ) {
			echo '<div class="wpmudev-field">';
				echo '<div class="wpmudev-field-label">' . $field->args['label']['text'] . (( strpos($field->args['class'], 'required') !== false ) ? '<span class="required">*</span>' : '') . '</div>';
				echo '<div class="wpmudev-field-desc">' . $field->args['desc'] . '</div>';
				$field->display($post->ID);
			echo '</div>';
		}
		
		echo '</div>';
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
			//bail - nonce is not set or could not be verified
			return;
		}
		
		//for plugins to tie into
		do_action('wpmudev_metaboxes_save_fields', $post_id);
	}
	
	/**
	 * Adds a form field to the meta box
	 *
	 * @since 1.0
	 * @access public
	 * @param string $type
	 * @param array $args
	 */
	public function add_field( $type, $args = array() ) {
		$class = apply_filters('wpmudev_metabox_add_field', 'WPMUDEV_Field_' . ucfirst($type), $type, $args);
		
		//only load metaboxes on appropriate post page and for assigned post type (Note: get_current_screen() doesn't work here)
		$uri = $_SERVER['REQUEST_URI'];
		if ( strpos($uri, 'post.php') === false && strpos($uri, 'post-new.php') === false ) {
			return false;
		} elseif ( isset($_GET['post']) && get_post_type($_GET['post']) != $this->args['post_type'] ) {
			return false;
		} elseif ( isset($_GET['post_type']) && $_GET['post_type'] != $this->args['post_type'] ) {
			return false;
		}
		
		if ( ! class_exists($class) ) {
			return false;	
		}
		
		$args['echo'] = false;
		$this->fields[] = new $class($args);
	}
}