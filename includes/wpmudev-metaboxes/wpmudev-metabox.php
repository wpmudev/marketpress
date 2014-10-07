<?php

if ( ! defined('WPMUDEV_METABOX_DIR') ) {
	define('WPMUDEV_METABOX_DIR', plugin_dir_path(__FILE__));
}

if ( ! defined('WPMUDEV_METABOX_URL') ) {
	define('WPMUDEV_METABOX_URL', plugin_dir_url(__FILE__));
}

if ( ! defined('WPMUDEV_METABOX_VERSION') ) {
	define('WPMUDEV_METABOX_VERSION', '1.0');
}

WPMUDEV_Metabox::load_fields();
require_once WPMUDEV_Metabox::class_dir('api.php');

/**
 * Stores the field scripts that have been printed (so we don't print them more than once)
 *
 * @global array
 * @name $printed_field_scripts
 */
$GLOBALS['wpmudev_metaboxes_printed_field_scripts'] = array();

// Save the state of a given metabox
add_action('wp_ajax_wpmudev_metabox_save_state', array('WPMUDEV_Metabox', 'ajax_save_state'));
// Add metaboxes below title
add_action('edit_form_after_title', array('WPMUDEV_Metabox', 'add_metaboxes_below_title'));

class WPMUDEV_Metabox {
	/**
	 * Refers to if the metabox fields are saved
	 *
	 * @since 1.0
	 * @access public
	 * @var bool
	 */
	var $fields_saved = false;
	
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
	
	/**
	 * Refers to the base directory
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	public static $dir = '';
	
	/**
	 * Refers to the base url
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	public static $url = '';
	
	/**
	 * Refers to the metaboxes that are registered to the current screen
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	public static $metaboxes = array();
	
	/**
	 * The number of metaboxes that have been displayed
	 *
	 * @since 1.0
	 * @access public
	 * @var int
	 */
	public static $did_metabox_count = 0;
	
	/**
	 * Save the state of the metabox (open or closed)
	 *
	 * @since 1.0
	 * @access public
	 * @action wp_ajax_wpmudev_metabox_save_state
	 */
	public static function ajax_save_state() {
		$option = 'wpmudev_metabox_states';
		$data = get_option($option, array());
		$id = isset($_POST['id']) ? $_POST['id'] : null;
		$is_closed = isset($_POST['closed']) ? $_POST['closed'] : null;
		
		if ( is_null($id) || is_null($is_closed) ) {
			return;
		}
		
		if ( $is_closed == 'true' ) {
			$is_closed = true;
		} else {
			$is_closed = false;
		}
		
		self::push_to_array($data, $id, $is_closed);
		
		// is_network_admin() doesn't work for ajax calls - see https://core.trac.wordpress.org/ticket/22589
		if ( is_multisite() && preg_match('#^' . network_admin_url() . '#i', $_SERVER['HTTP_REFERER']) ) {
			update_site_option($option, $data);
		} else {
			update_option($option, $data);
		}
		
		die;
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
	 * Constructor function
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 *		An array of arguments. Optional.
	 *
	 *		@type string $class The class of the metabox.
	 *		@type string $id The id of the metabox (required).
	 *		@type string $title The title of the metabox (required).
	 *		@type string $desc The description of the metabox.
	 *		@type string $post_type The post type to display the metabox on.
	 *		@type array $page_slugs The page slugs to display the metabox on - for screens other than post-edit.php and post-new.php.
	 *		@type string $context The context of the metabox (advanced, normal, side or below_title).
	 *		@type string $priority The priority of the metabox (default, high, normal).
	 *		@type string $option_name If not a post metabox, enter the option name that will be used to retrieve/save the field's value (e.g. plugin_settings).
	 *		@type string $site_option_name If not a post metabox, enter the site option name that will be used to retrieve/save the field's value (e.g. plugin_settings).
	 *		@type int $order Display order for settings metaboxes. If this is not entered metaboxes will be rendered in the order they logically show up in the code. Defaults to 10.
	 *		@type bool $show_submit_button Display a submit button below the metabox or not.
	 *		@type string submit_button_text The text for the submit button.
	 *		@type array $conditional {
	 *			Conditionally hide/show this field if another field value is a certain value.
	 *
	 *			Example 1: array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show');
	 *			Example 2: array('operator' => 'AND', array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show'), array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show'));
	 *			Example 3: array('operator' => 'OR', array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show'), array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show'));
	 *
	 *			@type string $name The name of the field to do the comparison on.
	 *			@type string $value The value to check against. Use "-1" to check for a checkbox being unchecked.
	 *			@type string $action The action to perform (show/hide).
	 *		}
	 * }
	 */
	public function __construct( $args = array() ) {
		$this->args = array_replace_recursive(array(
			'class' => 'postbox wpmudev-postbox',
			'id' => '',
			'title' => '',
			'desc' => null,
			'post_type' => '',
			'page_slugs' => array(),
			'context' => 'advanced',
			'priority' => 'default',
			'option_name' => '',
			'site_option_name' => '',
			'order' => 10,
			'conditional' => array(),
			'custom' => array(),
			'show_submit_button' => true,
			'submit_button_text' => __('Save Changes', 'wpmudev_metaboxes'),
		), $args);
		
		$this->nonce_action = 'wpmudev_metabox_' . str_replace('-', '_', $this->args['id']) . '_save_fields';
		$this->nonce_name = $this->nonce_action . '_nonce';
	
		$this->localize();
		$this->init_conditional_logic();
		$this->add_html_classes();
		
		add_action('admin_enqueue_scripts', array(&$this, 'maybe_enqueue_styles_scripts'));		
		add_action('add_meta_boxes_' . $this->args['post_type'], array(&$this, 'add_meta_boxes'), $this->args['order']);
		add_action('wpmudev_metabox/render_settings_metaboxes', array(&$this, 'maybe_render'), $this->args['order']);
		add_action('save_post', array(&$this, 'maybe_save_fields'));
		add_filter('postbox_classes_' . $this->args['post_type'] . '_' . $this->args['id'], array(&$this, 'add_meta_box_classes'));
		add_action('admin_notices', array(&$this, 'admin_notices'));
		add_action('network_admin_notices', array(&$this, 'admin_notices'));
		add_action('init', array(&$this, 'maybe_save_settings_fields'), 99);
	}
	
	/**
	 * Allow metaboxes to be rendered directly underneath the title field
	 *
	 * @since 3.0
	 * @access public
	 * @action edit_form_after_title
	 * @uses $post, $wp_meta_boxes;
	 */
	public static function add_metaboxes_below_title() {
		global $post, $wp_meta_boxes;
		do_meta_boxes(get_current_screen(), 'below_title', $post);
		unset($wp_meta_boxes[get_post_type($post)]['below_title']);
	}
	
	/**
	 * Add HTML classes to metabox
	 *
	 * @since 1.0
	 * @access public
	 */
	public function add_html_classes() {
		if ( $this->is_closed() ) {
			$this->args['class'] .= ' closed';
		}
	}
	
	/**
	 * Determine if metabox is closed
	 *
	 * @since 1.0
	 * @access public
	 */
	public function is_closed() {
		$settings = ( is_network_admin() ) ? get_site_option('wpmudev_metabox_states', array()) : get_option('wpmudev_metabox_states', array());
		return (( isset($settings[$this->args['id']]) ) ? (bool) $settings[$this->args['id']] : false);
	}

	/**
	 * Localize the library
	 *
	 * The function will try to load the translation file from the WP_LANG_DIR
	 *
	 * @since 1.0
	 * @access public
	 */
	public function localize() {
		$domain = 'wpmudev_metaboxes';
		// The plugin_locale filter is defined in /wp-includes/l10n.php
		$locale = apply_filters('plugin_locale', get_locale(), $domain);
		$lang_file = WP_LANG_DIR . '/' . $domain . '-' . $locale . '.mo';
		
		load_textdomain($domain, $lang_file);
	}

	/**
	 * Initializes conditional logic attributes
	 *
	 * @since 1.0
	 * @access public
	 */
	public function init_conditional_logic() {
		if ( empty($this->args['conditional']) ) {
			return;
		}
		
		$this->args['custom']['data-conditional-operator'] = 'OR';
		
		if ( isset($this->args['conditional']['operator']) ) {
			$this->args['custom']['data-conditional-operator'] = $this->args['conditional']['operator'];
			unset($this->args['conditional']['operator']);
		}
		
		if ( isset($this->args['conditional']['action']) ) {
			$this->args['custom']['data-conditional-action'] = $this->args['conditional']['action'];
			unset($this->args['conditional']['action']);
		}
		
		if ( array_key_exists('name', $this->args['conditional']) || array_key_exists('value', $this->args['conditional']) ) {
			$conditional = array(
				'name' => $this->args['conditional']['name'],
				'value' => $this->args['conditional']['value'],
			);
			$this->args['conditional'] = array($conditional);
		}
		
		foreach ( $this->args['conditional'] as $index => $value ) {
			$this->args['custom']['data-conditional-name-' . $index] = $value['name'];
			$this->args['custom']['data-conditional-value-' . $index] = ( is_array($value['value']) && count($value['value']) > 1 ) ? implode('||', $value['value']) : $value['value'];
		}
		
		$this->args['class'] .= ' wpmudev-metabox-has-conditional';
	}
	
	/**
	 * Displays the "settings updated" notice
	 *
	 * @since 1.0
	 * @access public
	 */
	public function admin_notices() {
		if ( isset($_GET['wpmudev_metabox_settings_saved']) && ! wp_cache_get('settings_saved', 'wpmudev_metaboxes') ) {
			echo '<div class="updated"><p>' . __('Settings Saved', 'wpmudev_metaboxes') . '</p></div>';
			wp_cache_set('settings_saved', true, 'wpmudev_metaboxes'); // Only show the message once per screen
		}
		
		if ( isset($_GET['wpmudev_metabox_settings_failed']) && ! wp_cache_get('settings_failed', 'wpmudev_metaboxes') ) {
			echo '<div class="error"><p>' . __('Due to prolonged inactivity on this page, one or more settings were not saved. Please try again.', 'wpmudev_metaboxes') . '</p></div>';
			wp_cache_set('settings_failed', true, 'wpmudev_metaboxes'); // Only show the message once per screen
		}
	}

	/**
	 * Determine if fields should be saved
	 *
	 * @since 1.0
	 * @access public
	 * @action save_post
	 */	
	public function maybe_save_fields( $post_id ) {
		if ( ! $this->is_active() ) {
			return;
		}
		
		$this->save_fields($post_id);
	}
	
	/**
	 * Determine if settings fields should be saved
	 *
	 * @since 1.0
	 * @access public
	 * @action init
	 */
	public function maybe_save_settings_fields() {
		if ( ! $this->is_active() || ! $this->is_settings_metabox() ) {
			return;
		}
		
		$opt_name = ( ! empty($this->args['site_option_name']) ) ? $this->args['site_option_name'] : $this->args['option_name'];
		$this->save_fields($opt_name);
	}
	
	/**
	 * Maybe enqueue styles scripts
	 *
	 * @since 1.0
	 * @access public
	 * @action admin_enqueue_scripts
	 */
	public function maybe_enqueue_styles_scripts() {
		if ( $this->is_active() ) {
			$this->admin_enqueue_styles();
			$this->admin_enqueue_scripts();
		}	
	}
	
	/**
	 * Determines if a metabox should be rendered
	 *
	 * @since 1.0
	 * @access public
	 */
	public function maybe_render( $post = null ) {
		if ( ! $this->is_active() ) {
			return false;
		}
		
		if ( $this->is_settings_metabox() ) {
			$post_id = ( ! empty($this->args['site_option_name']) ) ? $this->args['site_option_name'] : $this->args['option_name'];
		} else {
			$post_id = $post->ID;
		}
		
		$this->render($post_id);
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
		wp_enqueue_script('jquery-validate', $this->class_url('ui/js/jquery.validate.min.js'), array('jquery'), '1.12');
		wp_enqueue_script('wpmudev-metaboxes-admin', $this->class_url('ui/js/admin.js'), array('jquery', 'jquery-validate', 'jquery-effects-highlight'), WPMUDEV_METABOX_VERSION, true);
		
		$default_messages = array(
			'alphanumeric_error_msg' => __('Please enter only letters and numbers', 'wpmudev_metaboxes'),
		);
		
		wp_localize_script('wpmudev-metaboxes-admin', 'WPMUDEV_Metaboxes_Validation_Messages', array_merge($default_messages, $this->validation_messages));
		wp_localize_script('wpmudev-metaboxes-admin', 'WPMUDEV_Metaboxes', array(
			'spinner_url' => admin_url('images/spinner.gif'),
		));
	}
	
	/**
	 * Enqueues admin scripts
	 *
	 * @since 1.0
	 * @access public
	 */	
	public function admin_enqueue_styles() {
		wp_enqueue_style('wpmudev-metaboxes-admin', $this->class_url('ui/css/admin.css'), array(), WPMUDEV_METABOX_VERSION);
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
	 * Load fields
	 *
	 * @since 1.0
	 * @access public
	 */
	public static function load_fields() {
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
		add_meta_box($this->args['id'], $this->args['title'], array(&$this, 'maybe_render'), $this->args['post_type'], $this->args['context'], $this->args['priority']);
	}
	
	/**
	 * Displays before-metabox content
	 *
	 * @since 3.0
	 * @access public
	 */
	public function before_settings_metabox() {
		/**
		 * Runs right before the metabox is displayed
		 *
		 * @since 3.0
		 * @param WPMUDEV_Metabox The current metabox
		 */
		do_action('wpmudev_metabox/before_settings_metabox', $this);
		do_action('wpmudev_metabox/before_settings_metabox/' . $this->args['id'], $this);
	}

	/**
	 * Displays after-metabox content
	 *
	 * @since 3.0
	 * @access public
	 */
	public function after_settings_metabox() {
		/**
		 * Runs right after the metabox is displayed and before the submit button
		 *
		 * @since 3.0
		 * @param WPMUDEV_Metabox The current metabox
		 */
		do_action('wpmudev_metabox/after_settings_metabox', $this);
		do_action('wpmudev_metabox/after_settings_metabox/' . $this->args['id'], $this);
	}
	
	/**
	 * Renders the meta box
	 *
	 * @since 1.0
	 * @access public
	 * @param object/string post
	 */
	public function render( $post = null ) {
		if ( $post instanceof WP_Post ) {
			$post = $post->ID;
		}
		
		if ( $this->is_settings_metabox() ) :
			$atts = '';
			foreach ( (array) $this->args['custom'] as $name => $val ) {
				$atts .= ' ' . $name . '="' . esc_attr($val) . '"';	
			}
			
			if ( self::$did_metabox_count == 0 ) : ?>
			<div id="poststuff">
				<div class="meta-box-sortables">
		<?php
			endif; ?>
					<?php $this->before_settings_metabox(); ?>
					<div id="<?php echo $this->args['id']; ?>" class="<?php echo $this->args['class']; ?>"<?php echo $atts; ?>>
						<div class="handlediv" title="Click to toggle"><br /><br /></div>
						<h3 class="hndle"><span><?php echo $this->args['title']; ?></span></h3>
						<div class="inside">
		<?php
		else :
			/* For metaboxes on the post creation/edit screen, we need to inject any
			custom attributes and classes via jQuery */
			if ( ! empty($this->args['custom']) ) :
				$jquery_string = 'jQuery("#' . $this->args['id'] . '")';
				
				foreach ( (array) $this->args['custom'] as $name => $val ) {
					$jquery_string .= '.attr("' . $name . '", "' . esc_attr($val) . '")';
				}
				
				if ( ! empty($this->args['class']) ) {
					$jquery_string .= '.addClass("' . $this->args['class'] . '")';
				}
				
				$jquery_string .= ';';
		?>
							<script type="text/javascript"><?php echo $jquery_string; ?></script>
		<?php
			endif;
		endif;
		
		wp_nonce_field($this->nonce_action, $this->nonce_name);
		
		if ( ! is_null($this->args['desc']) ) : ?>
							<div class="wpmudev-metabox-desc"><?php echo $this->args['desc']; ?></div>
		<?php
		endif; ?>
							<div class="wpmudev-fields clearfix">
		<?php
		foreach ( $this->fields as $field ) : ?>
								<div class="wpmudev-field <?php echo str_replace('_', '-', strtolower(get_class($field))); ?>">
		<?php
			if ( ! empty($field->args['label']['text']) ) : ?>
									<div class="wpmudev-field-label"><?php echo $field->args['label']['text'] . (( strpos($field->args['class'], 'required') !== false ) ? '<span class="required">*</span>' : ''); ?></div>
		<?php
			endif;
			
			if ( ! empty($field->args['desc']) ) : ?>
									<div class="wpmudev-field-desc"><?php echo $field->args['desc']; ?></div>
		<?php
			endif; ?>									
									<?php $field->display($post); ?>
								</div>
		<?php
		endforeach; ?>
							</div>
		<?php
		if ( $this->is_settings_metabox() ) : ?>
					</div>
				</div>
				<?php
				$this->after_settings_metabox();
				if ( $this->args['show_submit_button'] ) : ?>
				<p class="submit">
					<input class="button-primary" type="submit" name="submit_settings" value="<?php echo $this->args['submit_button_text']; ?>" />
				</p>
				<?php
				endif; ?>
		<?php
			if ( self::$did_metabox_count == (count(self::$metaboxes) - 1) ) : ?>
			</div>
		</div>
		<?php
			endif;
		endif;
		
		self::$did_metabox_count += 1;
	}
	
	/**
	 * Pushes a value with a given array with given key
	 *
	 * @since 1.0
	 * @access public
	 * @param array $array The array to work with.
	 * @param string $key_string
	 * @param mixed $value
	 */
	public function push_to_array( &$array, $key_string, $value ) {
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
	
	/**
	 * Saves the metabox fields to the database
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id The current post ID
	 */
	public function save_fields( $post_id ) {
		if ( ! isset($_POST[$this->nonce_name]) || ! $this->is_active() || (isset($_POST[$this->nonce_name]) && ! wp_verify_nonce($_POST[$this->nonce_name], $this->nonce_action)) ) {
			// Bail - nonce is not set or could not be verified or metabox is not active for current page
			return;
		}
		
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			// Bail - this is an autosave, our form has not been submitted
			return;
		}

		// Avoid infinite loops later (e.g. when calling wp_insert_post, etc)
		remove_action('save_post', array(&$this, 'save_fields'));

		/**
		 * Runs right before save_fields is run, but after nonces have been verified.
		 *
		 * @since 3.0
		 * @param WPMUDEV_Metabox $this The current metabox
		 */
		do_action('wpmudev_metabox/before_save_fields', $this);
		do_action('wpmudev_metabox/before_save_fields/' . $this->args['id'], $this);
		
		// For settings metaboxes we don't want to call the internal save methods for each field
		if ( ! is_numeric($post_id) ) {
			$settings = ( ! empty($this->args['site_option_name']) ) ? get_site_option($post_id, array()) : get_option($post_id, array());
			
			foreach ( $this->fields as $field ) {
				$post_key = $field->get_post_key($field->args['name']);
				$value = $field->get_post_value($post_key);

				if ( $field instanceof WPMUDEV_Field_Repeater ) {
					$values = $field->sort_subfields($value);
					
					if ( count($values) == 2 ) {
						$values = array_merge($values['existing'], $values['new']);
					}
					
					foreach ( $values as $idx => $array ) {
						$index = 0;                                                                                                                                                                                                                     
						foreach ( $array as $idx2 => $val ) {
							$values[$idx][$index] = $field->subfields[$index]->sanitize_for_db($val, $post_id);
							$index ++;
						}
					}
					
					$value = $values;
				} else {
					$value = $field->sanitize_for_db($value, $post_id);
				}
				
				$this->push_to_array($settings, $post_key, $value);
			}
			
			if ( ! empty($this->args['site_option_name']) ) {
				update_site_option($post_id, $settings);
			} else {
				update_option($post_id, $settings);
			}
			
			/**
			 * Fires after the settings metabox has been saved.
			 *
			 * @since 3.0
			 * @param WPMUDEV_Metabox $this The metabox that was saved.
			 */
			do_action('wpmudev_metabox/after_settings_metabox_saved', $this);
			do_action('wpmudev_metabox/after_settings_metabox_saved/' . $this->args['id'], $this);
			
			if ( did_action('wpmudev_metabox/after_settings_metabox_saved') == count(self::$metaboxes) ) {
				/**
				 * Fires after all of the settings metaboxes have been saved
				 *
				 * @since 3.0
				 */
				do_action('wpmudev_metabox/after_all_settings_metaboxes_saved');
				do_action('wpmudev_metabox/after_all_settings_metaboxes_saved/' . $_REQUEST['page']);
				
				// Redirect to avoid accidental saves on page refresh
				wp_redirect(add_query_arg('wpmudev_metabox_settings_saved', 1), 301);
				exit;
			}
		} else {
			// Make sure $post_id isn't a revision
			if ( wp_is_post_revision($post_id) ) {
				return;
			}

			// Make sure we only run once
			if ( wp_cache_get('save_fields', 'wpmudev_metaboxes') ) {
				return;
			}
			wp_cache_set('save_fields', true, 'wpmudev_metaboxes');
		
			/**
			 * Fires after the appropriate nonce's have been verified for fields to tie into.
			 *
			 * @since 3.0
			 * @param int $post_id Post ID.
			 */
			do_action('wpmudev_metabox/save_fields', $post_id);
		}
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
		
		$this->is_settings_metabox = false;
		
		if ( ! empty($_REQUEST['page']) && ! empty($this->args['page_slugs']) ) {
			$page = $_REQUEST['page'];
			
			if ( in_array($page, $this->args['page_slugs']) ) {
				$this->is_settings_metabox = true;
			}
		}

		return $this->is_settings_metabox;
	}
	
	/**
	 * Checks if the current metabox is active for the current page
	 *
	 * @since 1.0
	 * @access public
	 * @uses $pagenow
	 * @return bool
	 */
	public function is_active( $log = false ) {
		global $pagenow;
		
		$this->is_active = false;
		
		if ( empty($pagenow) ) {
			$pagenow = basename($_SERVER['PHP_SELF']);
		}
		
		$post_type = false;
		if ( ! empty($_REQUEST['post_type']) ) {
			$post_type = $_REQUEST['post_type'];
		}
		
		$post_id = false;
		if ( ! empty($_REQUEST['post']) ) {
			$post_id = $_REQUEST['post'];
		}
		
		if ( $post_type && $this->args['post_type'] == $post_type && ($pagenow == 'post-new.php' || $pagenow == 'post.php') ) {
			$this->is_active = true;
		} elseif ( $post_id && $pagenow == 'post.php' && get_post_type($post_id) == $this->args['post_type'] ) {
			$this->is_active = true;
		} elseif ( $this->is_settings_metabox() ) {
			$this->is_active = true;
			$this->is_settings_metabox = true;
		}
		
		if ( $this->is_active ) {
			// Set this as an active metabox
			self::$metaboxes[$this->args['id']] = '';
		}
		
		if ( $log ) {
			print_r(array(
				'id' => $this->args['id'],
				'wp_post_type' => $post_type,
				'mb_post_type' => $this->args['post_type'],
				'post_id' => $post_id,
				'is_active' => (int) $this->is_active,
				'pagenow' => $pagenow,
			));
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
		$class = 'WPMUDEV_Field_' . ucfirst($type);
		
		if ( ! class_exists($class) || ! $this->is_active() ) {
			return false;	
		}
		
		if ( ! empty($args['custom_validation_message']) ) {
			$this->validation_messages = array_merge($this->validation_messages, array($args['name'] => $args['custom_validation_message']));
		}
		
		$args['echo'] = false;
		$field = new $class($args);
		$field->metabox = &$this;
		$this->fields[] = $field;
		
		return $field;
	}
}