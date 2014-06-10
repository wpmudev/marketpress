<?php

global $mp_settings_metaboxes;
$mp_settings_metaboxes = array();

class MP_Settings_Metabox {
	var $ID = null;						// the ID of the of metabox. if not set will be auto-generated
	var $title = null;				// the title of the metabox
	var $description = null;	// the description of the metabox (optional)
	var $tab = null;					// the tab the metabox should display on
	var $order = 99;					// the order in which the metabox will display
	
	/** 
	 * Constructor function
	 *
	 * @since 3.0
	 * @access public
	 */
	public final function __construct() {
		$this->init_vars();
		
		if ( is_null($this->ID) ) {
			$this->ID = $this->tab . '-' . sanitize_title($this->title);
		}
		
		$this->on_creation();

		add_filter('mp_settings_' . $this->tab . '_save', array(&$this, 'save_settings'));
		add_action('mp_settings_' . $this->tab . '_metaboxes', array(&$this, 'display'), $this->order);
		add_action('admin_head', array(&$this, 'maybe_print_scripts'));
		add_action('admin_enqueue_scripts', array(&$this, 'maybe_enqueue_scripts'));
	}
	
	/**
	 * Determines if the metabox is currently active
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public final function is_active() {
		$screen = get_current_screen();
		return ( $screen->id == 'product_page_store-settings' && mp_arr_get_value('tab', $_GET, 'general') == $this->tab );
	}
	
	/**
	 * Determines if the enqueue_scripts() function should be called for this metabox
	 *
	 * since 3.0
	 */
	public final function maybe_enqueue_scripts() {
		if ( $this->is_active() )
			$this->enqueue_scripts();
	}
	
	/**
	 * Determines if the print_scripts() function should be called for this metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public final function maybe_print_scripts() {
		if ( $this->is_active() )
			$this->print_scripts();
	}

	/**
	 * Display the metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public final function display() {
		?>
		<div id="mp-<?php echo $this->ID; ?>" class="mp-postbox <?php $this->accordion_class($this->ID); ?>">
			<h3 class="hndle">
				<?php
				echo $this->title;
				echo ( is_null($this->description) ) ? '' : ' - <span class="description">' . $this->description . '</span>';
				?>
			</h3>
			<div class="inside">
				<?php $this->display_inside(); ?>
			</div>
		</div>
		<?php
	}
	
	/**
	 * Gets an accordion's open/closed state for a given user and echos the appropriate CSS class
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $id The ID of the accordion/postbox
	 * @param int $user_id The user ID (optional) defaults to current user ID
	 */
	public final function accordion_class( $id, $user_id = null ) {
		echo mp()->get_user_preference($user_id, 'accordion_state_' . $id, 'open');
	}	
	
	/**
	 * Display override error
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $function The function name to display
	 */
	public final function override_error( $function ) {
		$callee = mp_arr_get_value('2->file', debug_backtrace());
		wp_die(sprintf(__('function %s must be over-ridden in a sub-class in <strong>%s</strong>.', 'mp'), $function, $callee));
	}
	
	/**
	 * Initialize class variables - should be overwritten by a child class
	 *
	 * @since 3.0
	 * @access public
	 * @access public
	 */
	public function init_vars() {
		$this->override_error('MP_Metabox::init_vars()');
	}
	
	/**
	 * Use instead of __construct()
	 *
	 * @since 3.0
	 * @access public
	 */
	public function on_creation() {
		//nothing to do here
	}
	
	/**
	 * Saves the metabox settings - runs right before settings are saved to db
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 * @return array
	 */
	public function save_settings( $settings ) {
		return $settings;
	}
	
	/**
	 * Displays the content within the metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function display_inside() {
		$this->override_error('MP_Metabox::display_inside()');
	}
	
	/**
	 * Prints any necessary javascript
	 *
	 * @since 3.0
	 * @access public
	 */
	public function print_scripts() {
		//nothing to do here
	}
	
	/**
	 * Enqueues any necessary javascript
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_scripts() {
		//nothing to do here
	}	
}