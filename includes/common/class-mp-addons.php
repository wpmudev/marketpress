<?php

class MP_Addons {
	/**
	 * Refers to the registered addons
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_addons = array();

	/**
	 * Refers to the enabled addons
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_addons_enabled = array();

	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;


	/**
	 * Disable add-on
	 *
	 * @since 3.0
	 * @access public
	 * @param array/string $addons
	 */
	public function disable( $addons ) {
		foreach ( (array) $addons as $addon ) {
			if ( false !== ($key = array_search($addon, $this->_addons_enabled)) ) {
				unset($this->_addons_enabled[$key]);
			}
		}
		
		mp_update_setting('addons', $this->_addons_enabled);
	}

	/**
	 * Enable add-on
	 *
	 * @since 3.0
	 * @access public
	 * @param array/string $addons
	 */
	public function enable( $addons ) {
		foreach ( (array) $addons as $addon ) {
			$this->_addons_enabled[] = $addon;
		}
		
		array_unique($this->_addons_enabled);
		
		mp_update_setting('addons', $this->_addons_enabled);
	}
	
	/**
	 * Get all registered add-ons
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_registered() {
		return $this->_addons;
	}

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Addons();
		}
		return self::$_instance;
	}

	/**
	 * Get an add-on object from it's class name
	 *
	 * @since 3.0
	 * @access public
	 * @param string $class The class name of the add-on.
	 * @return object The add-on. False, if add-on is not found.
	 */
	public function get_addon( $class ) {
		return mp_arr_get_value($class, $this->_addons);
	}
	
	/**
	 * Check if an addon is enabled
	 *
	 * @since 3.0
	 * @access public
	 */
	public function is_addon_enabled( $class ) {
		return ( in_array($class, $this->_addons_enabled) );
	}
	
	/**
	 * Register an add-on
	 *
	 * @since 3.0
	 * @access public
	 * @param array $args {
	 *		An array of arguments.
	 *
	 *		@type string $label The add-on label (displays in the add-ons admin list).
	 *		@type string $desc The add-on description (displays in the add-ons admin list).
	 *		@type string $class The add-on class.
	 *		@type string $path The absolute path to the add-on file.
	 * }
	 */
	public function register( $args ) {
		$this->_addons[$args['class']] = (object) $args;
	}

	/**
	 * Set enabled addons
	 *
	 * @since 3.0
	 * @access protected
	 */
	public function set_enabled_addons() {
		$this->_addons_enabled = mp_get_setting('addons', array());
		foreach ( $this->_addons_enabled as $addon ) {
			if ( $addon_obj = mp_arr_get_value($addon, $this->_addons) ) {
				require_once $addon_obj->path;
			}
		}
	}
			
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action('init', array(&$this, 'set_enabled_addons'), 5);
	}
}

MP_Addons::get_instance();