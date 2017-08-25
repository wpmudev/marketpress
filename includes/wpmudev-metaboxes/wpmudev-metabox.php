<?php
if ( ! defined( 'WPMUDEV_METABOX_DIR' ) ) {
	define( 'WPMUDEV_METABOX_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WPMUDEV_METABOX_URL' ) ) {
	define( 'WPMUDEV_METABOX_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'WPMUDEV_METABOX_VERSION' ) ) {
	define( 'WPMUDEV_METABOX_VERSION', '1.0' );
}

WPMUDEV_Metabox::load_fields();
require_once WPMUDEV_Metabox::class_dir( 'api.php' );

/**
 * Stores the field scripts that have been printed (so we don't print them more than once)
 *
 * @global array
 * @name $printed_field_scripts
 */
$GLOBALS['wpmudev_metaboxes_printed_field_scripts'] = array();

// Save the state of a given metabox
add_action( 'wp_ajax_wpmudev_metabox_save_state', array( 'WPMUDEV_Metabox', 'ajax_save_state' ) );
// Add metaboxes below title
add_action( 'edit_form_after_title', array( 'WPMUDEV_Metabox', 'add_metaboxes_below_title' ) );

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
	 * If the run once actions/methods have been run
	 *
	 * @since 3.0
	 * @access public
	 */
	public static $did_run_once = false;

	/**
	 * Save the state of the metabox (open or closed)
	 *
	 * @since 1.0
	 * @access public
	 * @action wp_ajax_wpmudev_metabox_save_state
	 */
	public static function ajax_save_state() {
		$option    = 'wpmudev_metabox_states';
		$data      = get_option( $option, array() );
		$id        = isset( $_POST['id'] ) ? $_POST['id'] : null;
		$is_closed = isset( $_POST['closed'] ) ? $_POST['closed'] : null;

		if ( is_null( $id ) || is_null( $is_closed ) ) {
			return;
		}

		if ( $is_closed == 'true' ) {
			$is_closed = true;
		} else {
			$is_closed = false;
		}

		self::push_to_array( $data, $id, $is_closed );

		// is_network_admin() doesn't work for ajax calls - see https://core.trac.wordpress.org/ticket/22589
		if ( is_multisite() && preg_match( '#^' . network_admin_url() . '#i', $_SERVER['HTTP_REFERER'] ) ) {
			update_site_option( $option, $data );
		} else {
			update_option( $option, $data );
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
	 *
	 * @return array or false if no files exist
	 */
	public static function get_dir_files( $dir, $ext = 'php' ) {
		$myfiles = array();

		if ( ! is_null( $ext ) ) {
			$ext = '.' . $ext;
		}

		if ( false === file_exists( $dir ) ) {
			return false;
		}

		$dir   = trailingslashit( $dir );
		$files = glob( $dir . '*' . $ext );

		return ( empty( $files ) ) ? false : $files;
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
		if ( false === ( $files = self::get_dir_files( $dir, $ext ) ) ) {
			return false;
		}

		foreach ( $files as $file ) {
			include_once $file;
		}
	}

	/**
	 * Constructor function
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param array $args {
	 *        An array of arguments. Optional.
	 *
	 * @type string $class The class of the metabox.
	 * @type string $id The id of the metabox (required).
	 * @type string $title The title of the metabox (required).
	 * @type string $desc The description of the metabox.
	 * @type string $post_type The post type to display the metabox on.
	 * @type array $page_slugs The page slugs to display the metabox on - for screens other than post-edit.php and post-new.php.
	 * @type string $context The context of the metabox (advanced, normal, side or below_title).
	 * @type string $priority The priority of the metabox (default, high, normal).
	 * @type string $option_name If not a post metabox, enter the option name that will be used to retrieve/save the field's value (e.g. plugin_settings).
	 * @type string $site_option_name If not a post metabox, enter the site option name that will be used to retrieve/save the field's value (e.g. plugin_settings).
	 * @type int $order Display order for settings metaboxes. If this is not entered metaboxes will be rendered in the order they logically show up in the code. Defaults to 10.
	 * @type bool $show_submit_button Display a submit button below the metabox or not.
	 * @type string submit_button_text The text for the submit button.
	 * @type array $conditional {
	 *            Conditionally hide/show this field if another field value is a certain value.
	 *
	 *            Example 1: array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show');
	 *            Example 2: array('operator' => 'AND', array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show'), array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show'));
	 *            Example 3: array('operator' => 'OR', array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show'), array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show'));
	 *
	 * @type string $name The name of the field to do the comparison on.
	 * @type string $value The value to check against. Use "-1" to check for a checkbox being unchecked.
	 * @type string $action The action to perform (show/hide).
	 *        }
	 * }
	 */
	public function __construct( $args = array() ) {

		$this->args = array_replace_recursive( array(
			'class'              => 'postbox wpmudev-postbox',
			'id'                 => '',
			'title'              => '',
			'desc'               => null,
			'post_type'          => '',
			'page_slugs'         => array(),
			'context'            => 'advanced',
			'priority'           => 'default',
			'option_name'        => '',
			'site_option_name'   => '',
			'order'              => 10,
			'conditional'        => array(),
			'custom'             => array(),
			'hook'               => 'wpmudev_metabox/render_settings_metaboxes',
			'show_submit_button' => true,
			'ajax_save'          => false,
			'submit_button_text' => __( 'Save Changes', 'wpmudev_metaboxes' ),
		), $args );

		$this->args = apply_filters( 'wpmudev_metabox/init_args', $this->args );

		$this->nonce_action = 'wpmudev_metabox_' . str_replace( '-', '_', $this->args['id'] ) . '_save_fields';
		$this->nonce_name   = md5( $this->nonce_action . '_nonce' );

		// These only need to be run once
		if ( ! self::$did_run_once ) {
			$this->localize();
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
			add_action( 'admin_notices', array( &$this, 'admin_notices' ) );
			add_action( 'network_admin_notices', array( &$this, 'admin_notices' ) );
			self::$did_run_once = true;
		}

		$this->init_conditional_logic();
		$this->add_html_classes();

		add_action( 'add_meta_boxes_' . $this->args['post_type'], array(
			&$this,
			'add_meta_boxes'
		), $this->args['order'] );

		add_action( $this->args['hook'], array(
			&$this,
			'maybe_render'
		), $this->args['order'] );
		add_filter( 'postbox_classes_' . $this->args['post_type'] . '_' . $this->args['id'], array(
			&$this,
			'add_meta_box_classes'
		) );
		add_action( 'save_post', array( &$this, 'maybe_save_fields' ) );
		add_action( 'init', array( &$this, 'maybe_save_settings_fields' ), 99 );
		if ( $this->args['ajax_save'] ) {
			add_action( 'wp_ajax_wpmudev_fields_save', array( &$this, 'ajax_save_process' ) );
			add_action( 'admin_footer', array( &$this, 'print_ajax_save_script' ) );
		}
	}

	/**
	 *
	 */
	public function print_ajax_save_script() {
		global $post;
		//if this is auto draft, we don't output any script
		if ( $post instanceof WP_Post && $post->post_status == 'auto-draft' ) {
			return;
		}

		$post_id = 0;
		if ( $post instanceof WP_Post ) {
			//this is inside post edit page
			$url     = admin_url( 'admin-ajax.php' );
			$post_id = $post->ID;
		} elseif ( isset( $_GET['page'] ) ) {
			$url = admin_url( 'admin-ajax.php?page=' . $_GET['page'] );
		}

		//no case here
		if ( ! isset( $url ) ) {
			return;
		}

		foreach ( $this->fields as $field ) {
			$id = $field->get_id();
			?>
			<script type="text/javascript">
				jQuery(function ($) {
					$('body').on('change', '#<?php echo $id ?>', function () {
						var that = $(this);
						var name = '<?php echo $field->get_name() ?>';
						$.ajax({
							type: 'POST',
							url: '<?php echo $url ?>',
							data: {
								action: 'wpmudev_fields_save',
								value: that.val(),
								name: '<?php echo $field->get_name() ?>',
								post_id: '<?php echo $post_id ?>'
							},
							success: function (data) {
								$(document).trigger('wpmudev_fields_saved_field_' + name, data);
							}
						})
					});
				})
			</script>
			<?php
		}
	}

	/**
	 * Ajax saving field when enabled
	 */
	public function ajax_save_process() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$name            = isset( $_POST['name'] ) ? $_POST['name'] : 0;
		$value           = isset( $_POST['value'] ) ? $_POST['value'] : 0;
		$post_id         = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;
		$is_setting_page = true;
		//determine seting page or post meta
		$post = get_post( $post_id );
		if ( $post_id && $post instanceof WP_Post ) {
			if ( $post->post_status == 'auto-draft' ) {
				//this case just auto draft, quit
				die;
			}
			$is_setting_page = false;
		}

		foreach ( $this->fields as $field ) {
			if ( $field->get_name() == $name ) {
				//enter the logic
				if ( $is_setting_page ) {
					//we still need to fire some action for ajax
					do_action( 'wpmudev_metabox/before_save_fields', $this );
					do_action( 'wpmudev_metabox/before_save_fields/' . $this->args['id'], $this );
					// For settings metaboxes we don't want to call the internal save methods for each field
					$opt_name = $opt_name = ( ! empty( $this->args['site_option_name'] ) ) ? $this->args['site_option_name'] : $this->args['option_name'];
					$settings = ( ! empty( $this->args['site_option_name'] ) ) ? get_site_option( $opt_name, array() ) : get_option( $opt_name, array() );
					$value    = $field->sanitize_for_db( $value, $post_id );
					$post_key = $field->get_post_key( $field->args['name'] );
					$this->push_to_array( $settings, $post_key, $value );

					//do the update
					if ( ! empty( $this->args['site_option_name'] ) ) {
						update_site_option( $opt_name, $settings );
					} else {
						update_option( $opt_name, $settings );
					}
				} else {
					//todo saving for post
				}
			}
		}

		echo $value;
		die;
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

		if ( is_array( $wp_meta_boxes ) && mp_arr_get_value( $post->post_type . '->below_title', $wp_meta_boxes ) ) {
			do_meta_boxes( get_current_screen(), 'below_title', $post );
			unset( $wp_meta_boxes[ $post->post_type ]['below_title'] );
		}
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
		$settings = ( is_network_admin() ) ? get_site_option( 'wpmudev_metabox_states', array() ) : get_option( 'wpmudev_metabox_states', array() );

		return ( ( isset( $settings[ $this->args['id'] ] ) ) ? (bool) $settings[ $this->args['id'] ] : false );
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
		$locale    = apply_filters( 'plugin_locale', get_locale(), $domain );
		$lang_file = WP_LANG_DIR . '/' . $domain . '-' . $locale . '.mo';

		load_textdomain( $domain, $lang_file );
	}

	/**
	 * Initializes conditional logic attributes
	 *
	 * @since 1.0
	 * @access public
	 */
	public function init_conditional_logic() {
		if ( empty( $this->args['conditional'] ) ) {
			return;
		}

		$this->args['custom']['data-conditional-operator'] = 'OR';

		if ( isset( $this->args['conditional']['operator'] ) ) {
			$this->args['custom']['data-conditional-operator'] = $this->args['conditional']['operator'];
			unset( $this->args['conditional']['operator'] );
		}

		if ( isset( $this->args['conditional']['action'] ) ) {
			$this->args['custom']['data-conditional-action'] = $this->args['conditional']['action'];
			unset( $this->args['conditional']['action'] );
		}

		if ( array_key_exists( 'name', $this->args['conditional'] ) || array_key_exists( 'value', $this->args['conditional'] ) ) {
			$conditional               = array(
				'name'  => $this->args['conditional']['name'],
				'value' => $this->args['conditional']['value'],
			);
			$this->args['conditional'] = array( $conditional );
		}

		foreach ( $this->args['conditional'] as $index => $value ) {
			$this->args['custom'][ 'data-conditional-name-' . $index ]  = $value['name'];
			$this->args['custom'][ 'data-conditional-value-' . $index ] = ( is_array( $value['value'] ) && count( $value['value'] ) > 1 ) ? implode( '||', $value['value'] ) : $value['value'];
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
		if ( isset( $_GET['wpmudev_metabox_settings_saved'] ) ) {
			echo '<div class="updated"><p>' . __( 'Settings Saved', 'wpmudev_metaboxes' ) . '</p></div>';
		}

		if ( isset( $_GET['wpmudev_metabox_settings_failed'] ) ) {
			echo '<div class="error"><p>' . __( 'Due to prolonged inactivity on this page, one or more settings were not saved. Please try again.', 'wpmudev_metaboxes' ) . '</p></div>';
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

		$this->save_fields( $post_id );
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

		$opt_name = ( ! empty( $this->args['site_option_name'] ) ) ? $this->args['site_option_name'] : $this->args['option_name'];
		$this->save_fields( $opt_name );
	}

	/**
	 * Determines if a metabox should be rendered
	 *
	 * @since 1.0
	 * @access public
	 */
	public function maybe_render( $post = null ) {
		if ( ! $this->is_active( true ) ) {
			return false;
		}

		if ( $this->is_settings_metabox() ) {
			$post_id = ( ! empty( $this->args['site_option_name'] ) ) ? $this->args['site_option_name'] : $this->args['option_name'];
		} else {
			$post_id = $post->ID;
		}

		$this->render( $post_id );
	}

	/**
	 * Adds classes to the metabox for easier styling
	 *
	 * @since 1.0
	 * @access public
	 * @filter postbox_classes_{$post_type}_{$id}
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public function add_meta_box_classes( $classes ) {
		return array_merge( $classes, array( 'wpmudev-postbox' ) );
	}

	/**
	 * Enqueues admin scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function admin_enqueue_scripts() {
		if ( isset( $_GET['page'] ) ) {
			$page = $_GET['page'];
		} else {
			$page = '';
		}

		//Load MP validation only on MP pages
		$screenpage = get_current_screen();

		if ( $page == 'store-settings'
			|| $page == 'store-setup-wizard'
			|| $page == 'store-settings-presentation' 
			|| $page == 'store-settings-notifications' 
			|| $page == 'store-settings-shipping' 
			|| $page == 'store-settings-payments' 
			|| $page == 'store-settings-productattributes' 
			|| $page == 'store-settings-capabilities'
			|| $page == 'store-settings-import'
			|| $page == 'store-settings-addons'
			|| ( isset( $_GET['taxonomy'] ) && ($_GET['taxonomy'] == 'product_category' || $_GET['taxonomy'] == 'product_tag') )
			|| ( isset( $_GET['post_type'] ) && ($_GET['post_type'] == 'mp_coupon' || $_GET['post_type'] == 'mp_order' || $_GET['post_type'] == 'product' ) ) 
			|| ( isset( $screenpage->post_type ) && ( $screenpage->post_type == MP_Product::get_post_type() || $screenpage->post_type == "mp_order" || $screenpage->post_type == "mp_coupon") ) )  {
				wp_register_script( 'jquery-validate', $this->class_url( 'ui/js/jquery.validate.min.js' ), array( 'jquery' ), '1.12' );
				wp_register_script( 'jquery-validate-methods', $this->class_url( 'ui/js/jquery.validate.methods.min.js' ), array( 'jquery-validate' ), '1.12' );
				wp_enqueue_script( 'wpmudev-metaboxes-admin', $this->class_url( 'ui/js/admin.js' ), array(
					'jquery-validate-methods',
					'jquery-ui-position',
					'jquery-effects-highlight'
				), WPMUDEV_METABOX_VERSION, true );
		}

		$messages = array(
			'alphanumeric_error_msg' => __( 'Please enter only letters and numbers', 'wpmudev_metaboxes' ),
			'lessthan_error_msg'     => __( 'Value must be less than {0}', 'wpmudev_metaboxes' ),
		);

		wp_localize_script( 'wpmudev-metaboxes-admin', 'WPMUDEV_Metaboxes_Validation_Messages', $messages );
		wp_localize_script( 'wpmudev-metaboxes-admin', 'WPMUDEV_Metaboxes', array(
			'spinner_url'    => admin_url( 'images/spinner.gif' ),
			'error'          => __( 'error', 'mp' ),
			'errors'         => __( 'errors', 'mp' ),
			'has'            => __( 'has', 'mp' ),
			'have'           => __( 'have', 'mp' ),
			'form_error_msg' => __( 'Oops! The form contains %s1 which %s2 been highlighted below.', 'mp' ) . "\n" . __( 'Please fix the %s1 and then try submitting the form again.', 'mp' ),
		) );
	}

	/**
	 * Enqueues admin scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function admin_enqueue_styles() {
		wp_enqueue_style( 'wpmudev-metaboxes-admin', $this->class_url( 'ui/css/admin.css' ), array(), WPMUDEV_METABOX_VERSION );
	}

	/**
	 * Gets the base class directory with optional path
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public static function class_dir( $path = '' ) {
		return WPMUDEV_METABOX_DIR . ltrim( $path, '/' );
	}

	/**
	 * Gets the base class url with optional path
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public static function class_url( $path = '' ) {
		return WPMUDEV_METABOX_URL . ltrim( $path, '/' );
	}

	/**
	 * Load fields
	 *
	 * @since 1.0
	 * @access public
	 */
	public static function load_fields() {
		require_once self::class_dir( 'class-wpmudev-field.php' );
		self::include_dir( self::class_dir( 'fields' ) );
	}

	/**
	 * Adds the meta box to the appropriate screen
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param object $post
	 */
	public function add_meta_boxes( $post ) {
		add_meta_box( $this->args['id'], $this->args['title'], array(
			&$this,
			'maybe_render'
		), $this->args['post_type'], $this->args['context'], $this->args['priority'] );
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
		 *
		 * @param WPMUDEV_Metabox The current metabox
		 */
		do_action( 'wpmudev_metabox/before_settings_metabox', $this );
		do_action( 'wpmudev_metabox/before_settings_metabox/' . $this->args['id'], $this );
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
		 *
		 * @param WPMUDEV_Metabox The current metabox
		 */
		do_action( 'wpmudev_metabox/after_settings_metabox', $this );
		do_action( 'wpmudev_metabox/after_settings_metabox/' . $this->args['id'], $this );
	}

	/**
	 * Renders the meta box
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param object /string post
	 */
	public function render( $post = null ) {
		if ( $post instanceof WP_Post ) {
			$post = $post->ID;
		}

		if ( $this->is_settings_metabox() ) :
			$atts                                   = '';
			foreach ( (array) $this->args['custom'] as $name => $val ) {
				$atts .= ' ' . $name . '="' . esc_attr( $val ) . '"';
			}

		if ( self::$did_metabox_count == 0 ) :
        
            $current_screen = get_current_screen();
			$current_screen_id_part = explode( '_page_', $current_screen->id );
			$current_screen_id = $current_screen->parent_base . '_page_' . end( $current_screen_id_part );
			?>
			<div id="poststuff">
			<div class="meta-box-sortables <?php echo esc_attr( $current_screen_id ); ?>">
		<?php endif;
			?>
			<?php $this->before_settings_metabox(); ?>
			<div id="<?php echo $this->args['id']; ?>" class="<?php echo $this->args['class']; ?>"<?php echo $atts; ?>>
			<div class="handlediv" title="Click to toggle"><br/><br/></div>
			<h3 class="hndle">
				<span><?php echo $this->args['title']; ?></span>
			</h3>
			<div class="inside">
		<?php
		else :
		/* For metaboxes on the post creation/edit screen, we need to inject any
		  custom attributes and classes via jQuery */
		if ( ! empty( $this->args['custom'] ) ) :
		$jquery_string = 'jQuery("#' . $this->args['id'] . '")';

		foreach ( (array) $this->args['custom'] as $name => $val ) {
			$jquery_string .= '.attr("' . $name . '", "' . esc_attr( $val ) . '")';
		}

		if ( ! empty( $this->args['class'] ) ) {
			$jquery_string .= '.addClass("' . $this->args['class'] . '")';
		}

		$jquery_string .= ';';
		?>
			<script type="text/javascript"><?php echo $jquery_string; ?></script>
			<?php
		endif;
		endif;

		wp_nonce_field( $this->nonce_action, $this->nonce_name );

		if ( ! is_null( $this->args['desc'] ) ) :
			?>
			<div class="wpmudev-metabox-desc"><?php echo $this->args['desc']; ?></div>
		<?php endif;
		?>
		<div class="wpmudev-fields clearfix">
			<?php
			foreach ( $this->fields as $field ) :
				$classes = array(
					$field->args['class'] . ' wpmudev-field',
					str_replace( '_', '-', strtolower( get_class( $field ) ) )
				);
				if ( empty( $field->args['desc'] ) ) {
					$classes[] = 'no-field-desc';
					$classes[] = $field->args['class'];
				}
				?>
				<div class="<?php echo implode( ' ', $classes ); ?>">
					<?php
					if ( ! empty( $field->args['label']['text'] ) ) :
						?>
						<div
							class="wpmudev-field-label"><?php echo $field->args['label']['text'] . ( ( ! empty( $field->args['custom']['data-rule-required'] ) ) ? '<span class="required">*</span>' : '' ); ?></div>
						<?php
					endif;

					if ( ! empty( $field->args['desc'] ) ) :
						?>
						<div class="wpmudev-field-desc"><?php echo $field->args['desc']; ?></div>
					<?php endif;
					?>
					<?php $field->display( $post ); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( $this->is_settings_metabox() ) : ?>
			</div>
			</div>
			<?php
			$this->after_settings_metabox();
			if ( $this->args['show_submit_button'] ) :
				?>
				<p class="submit">
					<input class="button-primary" type="submit" name="submit_settings"
					       value="<?php echo $this->args['submit_button_text']; ?>"/>
				</p>
			<?php endif;
			?>
			<?php if ( self::$did_metabox_count == ( count( self::$metaboxes ) - 1 ) ) : ?>
				</div>
				</div>
				<?php
			endif;
		endif;

		self::$did_metabox_count += 1;
	}

	/**
	 * Pushes a value to a given array with given key
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param array $array The array to work with.
	 * @param string $key_string
	 * @param mixed $value
	 */
	public function push_to_array( &$array, $key_string, $value ) {
		$keys   = explode( '->', $key_string );
		$branch = &$array;

		while ( count( $keys ) ) {
			$key = array_shift( $keys );

			if ( ! is_array( $branch ) ) {
				$branch = array();
			}

			$branch = &$branch[ $key ];
		}

		$branch = $value;
	}

	/**
	 * Saves the metabox fields to the database
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param int $post_id The current post ID
	 */
	public function save_fields( $post_id ) {
		if ( ! isset( $_POST[ $this->nonce_name ] ) || ! $this->is_active() || ( isset( $_POST[ $this->nonce_name ] ) && ! wp_verify_nonce( $_POST[ $this->nonce_name ], $this->nonce_action ) ) ) {
			// Bail - nonce is not set or could not be verified or metabox is not active for current page
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			// Bail - this is an autosave, our form has not been submitted
			return;
		}

		// Avoid infinite loops later (e.g. when calling wp_insert_post, etc)
		remove_action( 'save_post', array( &$this, 'save_fields' ) );

		/**
		 * Runs right before save_fields is run, but after nonces have been verified.
		 *
		 * @since 3.0
		 *
		 * @param WPMUDEV_Metabox $this The current metabox
		 */
		do_action( 'wpmudev_metabox/before_save_fields', $this );
		do_action( 'wpmudev_metabox/before_save_fields/' . $this->args['id'], $this );

		// For settings metaboxes we don't want to call the internal save methods for each field
		if ( ! is_numeric( $post_id ) ) {
			$settings = ( ! empty( $this->args['site_option_name'] ) ) ? get_site_option( $post_id, array() ) : get_option( $post_id, array() );

			foreach ( $this->fields as $field ) {
				$post_key = $field->get_post_key( $field->args['name'] );
				$value    = $field->get_post_value( $post_key );

				if ( $field instanceof WPMUDEV_Field_Repeater ) {
					$values = $field->sort_subfields( $value );
					$data   = array();

					foreach ( $values as $order => $array ) {
						foreach ( $array as $id => $array2 ) {
							$index = 0;
							foreach ( $array2 as $name => $val ) {
								$data[ $order ][ $name ] = $field->subfields[ $index ]->sanitize_for_db( $val, $post_id );
								$index ++;
							}
						}
					}

					$value = $data;
				} else {
					$value = $field->sanitize_for_db( $value, $post_id );
				}

				$this->push_to_array( $settings, $post_key, $value );
			}

			if ( ! empty( $this->args['site_option_name'] ) ) {
				update_site_option( $post_id, $settings );
			} else {
				update_option( $post_id, $settings );
			}

			/**
			 * Fires after the settings metabox has been saved.
			 *
			 * @since 3.0
			 *
			 * @param WPMUDEV_Metabox $this The metabox that was saved.
			 */
			do_action( 'wpmudev_metabox/after_settings_metabox_saved', $this );
			do_action( 'wpmudev_metabox/after_settings_metabox_saved/' . $this->args['id'], $this );

			if ( did_action( 'wpmudev_metabox/after_settings_metabox_saved' ) == count( self::$metaboxes ) ) {
				/**
				 * Fires after all of the settings metaboxes have been saved
				 *
				 * @since 3.0
				 */
				do_action( 'wpmudev_metabox/after_all_settings_metaboxes_saved' );
				do_action( 'wpmudev_metabox/after_all_settings_metaboxes_saved/' . $_REQUEST['page'] );

				// Redirect to avoid accidental saves on page refresh
				$quick_setup_step = mp_get_get_value( 'quick_setup_step' );
				if ( isset( $quick_setup_step ) && $quick_setup_step == '2' ) {
					wp_redirect( add_query_arg( 'quick_setup_step', 3 ), 301 );
				} else {
					wp_redirect( add_query_arg( 'wpmudev_metabox_settings_saved', 1 ), 301 );
				}
				exit;
			}
		} else {
			// Make sure $post_id isn't a revision
			if ( wp_is_post_revision( $post_id ) ) {
				return;
			}

			// Make sure we only run once
			if ( wp_cache_get( 'save_fields', 'wpmudev_metaboxes' ) ) {
				return;
			}
			wp_cache_set( 'save_fields', true, 'wpmudev_metaboxes' );

			/**
			 * Fires after the appropriate nonce's have been verified for fields to tie into.
			 *
			 * @since 3.0
			 *
			 * @param int $post_id Post ID.
			 */
			do_action( 'wpmudev_metabox/save_fields', $post_id );
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
		$is_settings_metabox = false;
		if ( ! empty( $this->args['page_slugs'] ) ) {
			$is_settings_metabox = true;
		}

		return $is_settings_metabox;
	}

	/**
	 * Check if the metabox is an active settings metabox
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function is_active_settings_metabox() {
		if ( ! $this->is_settings_metabox() ) {
			return false;
		}

		$is_active = false;
		if ( ! empty( $_REQUEST['page'] ) && ! empty( $this->args['page_slugs'] ) ) {
			$page = $_REQUEST['page'];

			if ( in_array( $page, $this->args['page_slugs'] ) ) {
				$is_active = true;
			}
		}

		return $is_active;
	}

	/**
	 * Checks if the current metabox is active for the current page
	 *
	 * @since 1.0
	 * @access public
	 * @uses $pagenow, $post
	 * @return bool
	 */
	public function is_active( $log = false ) {
		global $pagenow, $post;

		if ( ! is_null( $this->is_active ) ) {
			return $this->is_active;
		}

		$this->is_active = false;
		if ( $this->is_settings_metabox() ) {
			if ( $this->is_active_settings_metabox() ) {
				$this->is_active           = true;
				$this->is_settings_metabox = true;
			}
		} else {
			$post_id = $post_type = false;
			if ( is_null( $post ) && ! empty( $_REQUEST['post'] ) ) {
				$post_id = $_REQUEST['post'];
				$post    = get_post( $post_id );
				setup_postdata( $post );
			}

			if ( $post instanceof WP_Post ) {
				$post_id   = $post->ID;
				$post_type = $post->post_type;
			}

			if ( empty( $pagenow ) ) {
				$pagenow = basename( $_SERVER['PHP_SELF'] );
			}

			if ( empty( $post_type ) && ! empty( $_REQUEST['post_type'] ) ) {
				$post_type = $_REQUEST['post_type'];
			}

			if ( $post_type && $this->args['post_type'] == $post_type && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' ) ) {
				$this->is_active = true;
			} elseif ( $post_id && $pagenow == 'post.php' && $post_type == $this->args['post_type'] ) {
				$this->is_active = true;
			}

			//some case, in edit mode the $post different fom the current editing post, we will have to validate
			if ( $this->is_active == false && is_object( $post ) && isset( $_GET['post'] ) && $pagenow == 'post.php' ) {
				$get_post_id = $_GET['post'];
				if ( $get_post_id > 0 && $get_post_id != $post->ID ) {
					//we will need to use the $get_ppost_id
					$actual_post = get_post( $get_post_id );
					if ( is_object( $actual_post ) && $actual_post->post_type == $this->args['post_type'] ) {
						$this->is_active = true;
					}
				}
			}

			if ( $this->is_active == false && $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['post_ID'] ) && $_POST['post_ID'] != 0 && $_POST['action'] == 'editpost' ) {
				$post_post_id = $_POST['post_ID'];
				if (!is_object($post) || ( isset( $post->ID ) && $post_post_id != $post->ID ) )  {
					$actual_post = get_post( $post_post_id );
					if ( is_object( $actual_post ) && $actual_post->post_type == $this->args['post_type'] ) {
						$this->is_active = true;
					}
				}
			}
		}

		if ( $this->is_active ) {
			// Set this as an active metabox
			self::$metaboxes[ $this->args['id'] ] = '';
		}

		if ( $log ) {
			print_r( array(
				'id'           => $this->args['id'],
				'wp_post_type' => $post_type,
				'mb_post_type' => $this->args['post_type'],
				'post_id'      => $post_id,
				'is_active'    => (int) $this->is_active,
				'pagenow'      => $pagenow,
			) );
		}

		return $this->is_active;
	}

	/**
	 * Adds a form field to the meta box
	 *
	 * @since 1.0
	 * @access public
	 *
	 * @param string $type
	 * @param array $args
	 *
	 * @return WPMUDEV_Field
	 */
	public function add_field( $type, $args = array() ) {
		$class = 'WPMUDEV_Field_' . ucfirst( $type );

		if ( ! class_exists( $class ) || ! $this->is_active() ) {
			return false;
		}

		if ( ! empty( $args['custom_validation_message'] ) ) {
			$this->validation_messages = array_merge( $this->validation_messages, array( $args['name'] => $args['custom_validation_message'] ) );
		}

		$args['echo']   = false;
		$field          = new $class( $args );
		$field->metabox = &$this;
		$this->fields[] = $field;

		return $field;
	}

}
