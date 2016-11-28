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
	 * Determine if an addon has settings
	 *
	 * @since 3.0
	 * @access public
	 * @param string $addon The class name of the addon to check.
	 * @return bool
	 */
	public function addon_has_settings( $addon ) {
		$addon_obj = $this->get_addon( $addon );
		return $addon_obj->has_settings;
	}

	/**
	 * Disable add-on
	 *
	 * @since 3.0
	 * @access public
	 * @param array/string $addons
	 */
	public function disable( $addons ) {
		foreach ( (array) $addons as $addon ) {
			if ( false !== ($key = array_search( $addon, $this->_addons_enabled )) ) {
				unset( $this->_addons_enabled[ $key ] );
				$addon_obj = $this->get_addon( $addon );
				
				/**
				 * Fires after an addon is disabled
				 *
				 * @since 3.0
				 */
				do_action( 'mp_addons/disable' . $addon_obj->class );
			}
		}
		
		mp_update_setting( 'addons', $this->_addons_enabled );
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
			$addon_obj = $this->get_addon( $addon );
			
			if( file_exists( $addon_obj->path ) )
                        {
                                require_once $addon_obj->path;
                                
                                /**
                                  * Fires after an addon is enabled
                                  *
                                  * @since 3.0
                                  */
                                do_action( 'mp_addons/enable/' . $addon_obj->class );

                                array_unique( $this->_addons_enabled );

                                mp_update_setting( 'addons', $this->_addons_enabled );
                        }
		}
		
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
		return mp_arr_get_value( $class, $this->_addons );
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
	 * Check if an addon is enabled
	 *
	 * @since 3.0
	 * @access public
	 */
	public function is_addon_enabled( $class ) {
		return ( in_array( $class, $this->_addons_enabled ) );
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
	 *		@type bool $has_settings Whether the addon has settings or not.
	 * }
	 */
	public function register( $args ) {
		$args = array_replace_recursive( array(
			'label' => '',
			'desc' => '',
			'class' => '',
			'path' => '',
			'has_settings' => false,
		), $args );
		
		$this->_addons[ $args['class'] ] = (object) $args;
	}

	/**
	 * Set enabled addons
	 *
	 * @since 3.0
	 * @access protected
	 * @action init, switch_blog
	 */
	public function set_enabled_addons() {
		$this->_addons_enabled = mp_get_setting( 'addons', array() );
		foreach ( $this->_addons_enabled as $addon ) {
			if ( $addon_obj = mp_arr_get_value( $addon, $this->_addons ) ) {
				if( file_exists( $addon_obj->path ) ) require_once $addon_obj->path;
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
		add_action( 'switch_blog', array( &$this, 'set_enabled_addons' ) );
		add_action( 'init', array( &$this, 'set_enabled_addons' ), 5 );
	}
}

MP_Addons::get_instance();

if ( ! function_exists( 'mp_addons' ) ) :
	/**
	 * Get the instance of the MP_Addons class
	 *
	 * @since 3.0
	 * @return MP_Addons
	 */
	function mp_addons() {
		return MP_Addons::get_instance();
	}
endif;