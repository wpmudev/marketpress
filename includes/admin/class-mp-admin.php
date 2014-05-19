<?php

class MP_Admin {
	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;
	
	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Admin();
		}
		return self::$_instance;
	}
	
	/**
	 * Constructor
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		require_once mp_plugin_dir('includes/wpmudev-metaboxes/wpmudev-metabox.php');
		require_once mp_plugin_dir('includes/admin/class-mp-manage-orders-screen.php');
		require_once mp_plugin_dir('includes/admin/class-mp-product-coupons-screen.php');	
		//display the current screen id
		//add_action('current_screen', create_function('', 'echo get_current_screen()->id;'));
		//add menu items
		add_action('admin_menu', array(&$this, 'add_menu_items'));
		//admin scripts/styles
		add_action('admin_enqueue_scripts', array(&$this, 'admin_styles_scripts'));
		//save orders screen options
		add_filter('set-screen-option', array(&$this, 'save_orders_screen_options'), 10, 3);
		//load settings metaboxes
		add_action('plugins_loaded', array(&$this, 'load_settings_metaboxes'));
		//set custom post-updated messages
		add_filter('post_updated_messages', array(&$this, 'post_updated_messages'));
	}
	
	/**
	 * Modifies the post-updated messages for the mp_order, product and mp_coupon post types
	 *
	 * @since 3.0
	 * @access public
	 * @filter post_updated_messages
	 * @param array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;
		
		$post_type = get_post_type($post_ID);
		
		if ( $post_type != 'mp_order' && $post_type != 'product' && $post_type != 'mp_coupon' ) { return $messages; }
		
		$obj = get_post_type_object($post_type);
		$singular = $obj->labels->singular_name;
		
		$messages[$post_type] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf(__($singular.' updated. <a href="%s">View ' . strtolower($singular) . '</a>'), esc_url(get_permalink($post_ID))),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __($singular.' updated.'),
			5 => isset($_GET['revision']) ? sprintf(__($singular . ' restored to revision from %s'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
			6 => sprintf(__($singular.' published. <a href="%s">View ' . strtolower($singular).'</a>'), esc_url(get_permalink($post_ID))),
			7 => __('Page saved.'),
			8 => sprintf(__($singular . ' submitted. <a target="_blank" href="%s">Preview ' . strtolower($singular).'</a>'), esc_url(add_query_arg('preview', 'true', get_permalink($post_ID)))),
			9 => sprintf(__($singular . ' scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview ' . strtolower($singular).'</a>'), date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(get_permalink($post_ID))),
			10 => sprintf(__($singular . ' draft updated. <a target="_blank" href="%s">Preview ' . strtolower($singular).'</a>'), esc_url(add_query_arg( 'preview', 'true', get_permalink($post_ID)))),
		);
		
		return $messages;
	}
	
	/**
	 * Get all files from a given directory
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $dir The full path of the directory
	 * @param string $ext Get only files with a given extension. Set to NULL to get all files.
	 * @return array or false if no files exist
	 */
	public function get_dir_files( $dir, $ext = 'php' ) {
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
	 * @since 3.0
	 * @access public
	 *
	 * @param string $dir The directory to work with
	 * @param string $ext Only include files with this extension
	 */
	public function include_dir( $dir, $ext = 'php' ) {
		if ( false === ($files = $this->get_dir_files($dir, $ext)) )
			return false;
		
		foreach ( $files as $file ) {
			include_once $file;
		}
	}
				
	/**
	 * Load settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function load_settings_metaboxes() {
		require_once mp_plugin_dir('includes/admin/class-mp-settings-metabox.php');
		$this->include_dir(mp_plugin_dir('includes/admin/metaboxes-settings'));
	}

	/**
	 * Add items to the admin menu
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_menu_items() {
		//store settings
		add_submenu_page('edit.php?post_type=product', __('Store Settings', 'mp'), __('Store Settings', 'mp'), 'manage_options', 'store-settings', array(&$this, 'admin_page'));
		
		if ( ! defined('WPMUDEV_REMOVE_BRANDING') )
	 		define('WPMUDEV_REMOVE_BRANDING', false);
	
	 	if ( ! WPMUDEV_REMOVE_BRANDING )
			add_action("load-product_page_store-settings", array(&$this, 'add_help_tab'));
	}
		
	/**
	 * Adds the MarketPress help tab
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_help_tab() {
		get_current_screen()->add_help_tab(array(
			'id' => 'marketpress-help',
			'title' => __('MarketPress Instructions', 'mp'),
			'content' => '<iframe src="//premium.wpmudev.org/wdp-un.php?action=help&id=144" width="100%" height="600px"></iframe>'
		));
	}
	
	/**
	 * Displays an admin page
	 *
	 * @since 3.0
	 * @access public
	 */
	public function admin_page() {
		$slug = mp_get_get_value('page', $_GET, 'store-settings');
		$page = sanitize_file_name($slug . '.php');
		$path = mp_plugin_dir('includes/admin/views/' . $page);
		
		if ( ! file_exists($path) ) {
			return false;
		}
		
		include_once $path;
	}
	
	/**
	 * Determine which scripts/styles to load
	 *
	 * @since 3.0
	 * @access public
	 */
	public function admin_styles_scripts() {
		global $current_screen;
		
		$screens = array('product');
		if ( in_array($current_screen->id, $screens) ) {
			wp_enqueue_script('mp-post', mp_plugin_url('ui/js/post-screen.js'), array('jquery'), MP_VERSION);
		}
		
		$screens = array('product', 'product_page_store-settings', 'product_page_orders', 'product_coupon');
		if ( in_array($current_screen->id, $screens) ) {
			wp_enqueue_style('mp3-admin-css', mp_plugin_url('ui/css/marketpress3.css'), false, MP_VERSION);
		}
		
		//store settings screens
		$screens = array('product_page_store-settings');
		if ( in_array($current_screen->id, $screens) ) {
			$this->admin_script_settings();
			$this->admin_css_settings();
		}
	}
	
	/**
	 * Enqueue styles for store settings screen
	 *
	 * @since 3.0
	 * @access public
	 */
	public function admin_css_settings() {
		global $wp_version;

		// load local dashicons if wp version is < 3.8
		if ( version_compare($wp_version, '3.8', '<') ) {
			wp_enqueue_style('dashicons', mp_plugin_url('ui/css/dashicons.css'), false, MP_VERSION);
		}
		
		wp_enqueue_style('jquery-colorpicker-css', mp_plugin_url('ui/colorpicker/css/colorpicker.css'), false, MP_VERSION);
	}

	/**
	 * Enqueue scripts for store settings screen
	 *
	 * @since 3.0
	 * @access public
	 */
	public function admin_script_settings() {
		global $blog_id,  $current_user;
		
		wp_enqueue_script('jquery-ui-spinner');
		wp_enqueue_script('jquery-colorpicker', mp_plugin_url('ui/colorpicker/js/colorpicker.js'), array('jquery'), MP_VERSION);
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('mp-plugins', mp_plugin_url('ui/js/plugins.js'), array('jquery'), MP_VERSION);
		wp_enqueue_script('mp-settings', mp_plugin_url('ui/js/settings.js'), array('jquery', 'mp-plugins', 'jquery-colorpicker', 'jquery-ui-datepicker', 'jquery-ui-spinner'), MP_VERSION);

		//only load languages for datepicker if not english (or it will show Chinese!)
		if ( mp()->language != 'en' )
			wp_enqueue_script( 'jquery-118n', mp_plugin_url('ui/js/i18n/jquery-ui-i18n.min.js'), array('jquery', 'jquery-ui-datepicker'), MP_VERSION);

		if (!defined('WPMUDEV_REMOVE_BRANDING') && intval(mp_get_setting('hide_popup')) < 3) {
			wp_enqueue_script( 'mp-need-help', mp_plugin_url('ui/js/need-help.js'), array('jquery'), MP_VERSION);
			$new_count = intval(mp_get_setting('hide_popup')) + 1;
			mp_update_setting('hide_popup', $new_count);
		}
		
		//localize settings
		wp_localize_script('mp-settings', 'MarketPress', array(
			'updateUserPreferenceNonce' => wp_create_nonce('mp-update-user-preference'),
			'accordionText' => __('Did you know these section are collapsible? Go ahead... click here and try it out for your self.', 'mp'),
			'userPrefs' => get_user_meta(get_current_user_id(), 'mp_user_preferences', true),
			'ajaxLoader' => '<img class="mp-ajax-loader" src="' . mp_plugin_url('ui/images/ajax-loader.gif') . '" alt="" />',
		));
	}
	
	/**
	 * Displays the export orders form
	 *
	 * @since 3.0
	 * @access public
	 */
	public function export_orders_form() {
		global $wpdb;
		
		if (!isset($_GET['post_status']) || $_GET['post_status'] != 'trash') { ?>
		<div class="icon32"><img src="<?php echo mp_plugin_url('ui/images/download.png'); ?>" /></div>
		<h2><?php _e('Export Orders', 'mp'); ?></h2>
		<?php if ( defined( 'MP_LITE' ) ) { ?>
		<a class="mp-pro-update" href="http://premium.wpmudev.org/project/e-commerce/" title="<?php _e('Upgrade Now', 'mp'); ?> &raquo;"><?php _e('Upgrade to enable CSV order exports &raquo;', 'mp'); ?></a><br />
		<?php } ?>
		<form action="<?php echo admin_url('admin-ajax.php?action=mp-orders-export'); ?>" method="post">
			<?php
			$months = $wpdb->get_results( $wpdb->prepare( "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM $wpdb->posts
			WHERE post_type = %s
			ORDER BY post_date DESC
		", 'mp_order' ) );
		
		$month_count = count( $months );
		
		if ( !$month_count || ( 1 == $month_count && 0 == $months[0]->month ) )
			return;
		
		$m = isset( $_GET['m'] ) ? (int) $_GET['m'] : 0;
		?>
		<select name='m'>
			<option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'Show all dates' ); ?></option>
		<?php
		foreach ( $months as $arc_row ) {
			if ( 0 == $arc_row->year )
				continue;
		
			$month = zeroise( $arc_row->month, 2 );
			$year = $arc_row->year;
		
			printf( "<option %s value='%s'>%s</option>\n",
				selected( $m, $year . $month, false ),
				esc_attr( $arc_row->year . $month ),
				$wp_locale->get_month( $month ) . " $year"
			);
		}
		
		$status = isset( $_GET['post_status'] ) ? $_GET['post_status'] : 'all';
		?>
		</select>
		<select name="order_status">
		<option<?php selected( $status, 'all' ); ?> value="all" selected="selected"><?php _e('All Statuses', 'mp'); ?></option>
		<option<?php selected( $status, 'order_received' ); ?> value="order_received"><?php _e('Received', 'mp'); ?></option>
		<option<?php selected( $status, 'order_paid' ); ?> value="order_paid"><?php _e('Paid', 'mp'); ?></option>
		<option<?php selected( $status, 'order_shipped' ); ?> value="order_shipped"><?php _e('Shipped', 'mp'); ?></option>
		<option<?php selected( $status, 'order_closed' ); ?> value="order_closed"><?php _e('Closed', 'mp'); ?></option>
		</select>
		<input type="submit" value="<?php _e('Download &raquo;', 'mp'); ?>" name="export_orders" class="button-secondary"<?php echo defined( 'MP_LITE' ) ? ' disabled="disabled"' : ''; ?> />
		</form>
		
		
		<br class="clear">
		<?php } ?>
		</div>
		<?php	
	}
}

MP_Admin::get_instance();