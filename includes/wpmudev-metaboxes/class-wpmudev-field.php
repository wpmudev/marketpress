<?php

class WPMUDEV_Field {

	/**
	 * Refers to the field's value
	 *
	 * @since 1.0
	 * @access protected
	 * @var mixed
	 */
	protected $_value = null;

	/**
	 * Refers to the name of the field
	 *
	 * @since 1.0
	 * @access protected
	 * @var string
	 */
	protected $_name = null;

	/**
	 * Refers to the order of the field (repeater field will set this)
	 *
	 * @since 1.0
	 * @access public
	 * @var int
	 */
	var $_order = null;

	/**
	 * Refers to the field's subfield id (repeater/complex field will set this)
	 *
	 * @since 1.0
	 * @access public
	 * @var int
	 */
	var $subfield_id = null;

	/**
	 * If the field is a subfield (repeater/complex field will set this)
	 *
	 * @since 1.0
	 * @access public
	 * @var bool
	 */
	var $is_subfield = false;

	/**
	 * Refers to the field's parent metabox
	 *
	 * @since 1.0
	 * @access public
	 * @var object
	 */
	var $metabox = null;

	/**
	 * Refers to the field arguments.
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	var $args = array();

	/**
	 * Refers to the default field attributes.
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	var $default_atts = array( 'name', 'id', 'class', 'style', 'disabled', 'readonly', 'value', 'placeholder' );

	/**
	 * Constructor function.
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 * 		An array of arguments. Optional.
	 *
	 * 		@type string $name The field's name attribute.
	 * 		@type string $original_name The field's original name (subfield name attributes get changed)
	 * 		@type string $name_base The field's base name (for subfields)
	 * 		@type string $id The field's id attribute.
	 * 		@type string $class The field's class attribute.
	 * 		@type string $style The field's style attribute.
	 * 		@type bool $disabled Should the field be disabled.
	 * 		@type bool $readonly Should the field be read-only.
	 * 		@type array $label {
	 * 			The field's label.
	 *
	 * 			@type string $text The text of the label.
	 * 			@type string $class Any HTML classes to apply to the label.
	 * 		}
	 * 		@type string $desc The field's description.
	 * 		@type array $custom Any custom/non-standard attributes.
	 * 		@type bool $value_only Only get the field's value - don't initialize scripts, styles, etc.
	 * 		@type string $default_value The value of the field before any user interaction.
	 * 		@type string $custom_validation_message Any custom validation message the field uses (only used for custom validation).
	 * 		@type array $validation {
	 * 			The rules to apply for validation.
	 *
	 * 			@type bool $required Makes the element required.
	 * 			@type int $minlength Makes the element require a given minimum length.
	 * 			@type int $maxlength Makes the element require a given maxmimum length.
	 * 			@type array $rangelength Makes the element require a given value range.
	 * 			@type int $min Makes the element require a given minimum value.
	 * 			@type int $max Makes the element require a given maximum value.
	 * 			@type array $range Makes the element require a value between the given range.
	 * 			@type bool $email Makes the element require a valid email.
	 * 			@type bool $url Makes the element require a valid url.
	 * 			@type bool $date Makes the element require a date.
	 * 			@type bool $number Makes the element require a decimal number.
	 * 			@type bool $digits Makes the element require digits only.
	 * 			@type bool $creditcard Makes the element require a credit card number.
	 * 			@type bool $alphanumeric Makes the element require only letters and numbers.
	 * 			@type string $custom A regular expression to test for (e.g. [0-9A-Z]).
	 * 			@type string $equalTo The id of a field that this field's value should match.
	 * 	}
	 * 	@type array $conditional {
	 * 		Conditionally hide/show this field if another field value is a certain value.
	 *
	 * 		Example 1: array('name' => 'field_name', 'value' => 'field_value', 'action' => 'show');
	 * 		Example 2: array('operator' => 'AND', 'action' => 'show', array('name' => 'field_name', 'value' => 'field_value'), array('name' => 'field_name', 'value' => 'field_value'));
	 * 		Example 3: array('operator' => 'OR', 'action' => 'show', array('name' => 'field_name', 'value' => 'field_value'), array('name' => 'field_name', 'value' => 'field_value'));
	 *
	 * 		@type string $name The name of the field to do the comparison on.
	 * 		@type string $value The value to check against. Use "-1" to check for a checkbox being unchecked.
	 * 		@type string $action The action to perform (show/hide).
	 * 	}
	 * 	@type array $save_callback Functions to call before saving to the database (e.g. strip_tags, etc)
	 * 	@type string $before_field Text/html to display before the field
	 * 	@type string $after_field Text/html to display after the field
	 */
	public function __construct( $args = array() ) {
		$this->args = array_replace_recursive( array(
			'name'						 => '',
			'original_name'				 => '',
			'name_base'					 => '',
			'id'						 => $this->get_id(),
			'class'						 => '',
			'style'						 => '',
			'disabled'					 => '',
			'readonly'					 => '',
			'desc'						 => '',
			'custom'					 => array(),
			'default_value'				 => '',
			'original_name'				 => '',
			'value_only'				 => false,
			'custom_validation_message'	 => '',
			'validation'				 => array(),
			'conditional'				 => array(),
			'save_callback'				 => array(),
			'before_field'				 => '',
			'after_field'				 => '',
			'label'						 => array(
				'text'	 => '',
				'class'	 => '',
			),
		), $args );

		$this->_name = $this->args[ 'name' ];

		if ( empty( $this->args[ 'name' ] ) ) {
			$backtrace = debug_backtrace();
			trigger_error( 'Failed to specify the "name" argument on line <strong>' . $backtrace[ 1 ][ 'line' ] . '</strong> of <strong>' . $backtrace[ 1 ][ 'file' ] . '</strong>', E_USER_ERROR );
		}

		if ( empty( $this->args[ 'original_name' ] ) ) {
			$this->args[ 'original_name' ] = $this->args[ 'name' ];
		}

		if ( $this->args[ 'value_only' ] ) {
			return false;
		}

		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );
		add_action( 'wpmudev_metabox/save_fields', array( &$this, 'save_value' ) );
		add_action( 'in_admin_footer', array( &$this, 'maybe_print_scripts' ) );

		$this->init_conditional_logic();
		$this->init_validation();
		$this->on_creation( $this->args );
	}

	/**
	 * Initializes validation attributes
	 *
	 * @since 1.0
	 * @access public
	 */
	public function init_validation() {
		if ( empty( $this->args[ 'validation' ] ) ) {
			return;
		}

		foreach ( $this->args[ 'validation' ] as $key => $val ) {
			if ( is_bool( $val ) ) {
				if ( $val ) {
					$val = 'true';
				} else {
					$val = 'false';
				}
			}

			if ( $key == 'custom' ) {
				$index													 = max( (int) wp_cache_get( 'custom_validation_index', 'wpmudev_field' ), 0 );
				$this->args[ 'custom' ][ "data-rule-custom-{$index}" ]	 = $val;
				wp_cache_set( 'custom_validation_index', ($index + 1 ), 'wpmudev_field' );
			} else {
				$this->args[ 'custom' ][ "data-rule-{$key}" ] = $val;
			}
		}
	}

	/**
	 * Initializes conditional logic attributes
	 *
	 * @since 1.0
	 * @access public
	 */
	public function init_conditional_logic() {
		if ( empty( $this->args[ 'conditional' ] ) ) {
			return;
		}

		$this->args[ 'custom' ][ 'data-conditional-operator' ] = 'OR';

		if ( isset( $this->args[ 'conditional' ][ 'operator' ] ) ) {
			$this->args[ 'custom' ][ 'data-conditional-operator' ] = $this->args[ 'conditional' ][ 'operator' ];
			unset( $this->args[ 'conditional' ][ 'operator' ] );
		}

		if ( isset( $this->args[ 'conditional' ][ 'action' ] ) ) {
			$this->args[ 'custom' ][ 'data-conditional-action' ] = $this->args[ 'conditional' ][ 'action' ];
			unset( $this->args[ 'conditional' ][ 'action' ] );
		}

		if ( array_key_exists( 'name', $this->args[ 'conditional' ] ) || array_key_exists( 'value', $this->args[ 'conditional' ] ) ) {
			$conditional				 = array(
				'name'	 => $this->args[ 'conditional' ][ 'name' ],
				'value'	 => $this->args[ 'conditional' ][ 'value' ],
			);
			$this->args[ 'conditional' ] = array( $conditional );
		}

		foreach ( $this->args[ 'conditional' ] as $index => $value ) {
			$this->args[ 'custom' ][ 'data-conditional-name-' . $index ]	 = $value[ 'name' ];
			$this->args[ 'custom' ][ 'data-conditional-value-' . $index ]	 = ( is_array( $value[ 'value' ] ) && count( $value[ 'value' ] ) > 1 ) ? implode( '||', $value[ 'value' ] ) : $value[ 'value' ];
		}

		$this->args[ 'class' ] .= ' wpmudev-field-has-conditional';
	}

	/**
	 * Gets the field name attribute
	 *
	 * @since 1.0
	 * @access public
	 * @return string
	 */
	public function get_name() {
		$name = $this->args[ 'name' ];

		if ( !is_null( $this->subfield_id ) && !empty( $name ) ) {
			// Repeater field - add the subfield id
			$name = str_replace( '[new][]', '[existing][' . $this->subfield_id . ']', $name );
		}

		return $name;
	}

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
	public function get_post_value( $key, $default = false ) {
		$keys	 = explode( '->', $key );
		$keys	 = array_map( 'trim', $keys );

		if ( count( $keys ) > 0 ) {
			$value = $this->array_search( $_POST, $key );
		} else {
			$value = isset( $_POST[ $keys[ 0 ] ] ) ? $_POST[ $keys[ 0 ] ] : null;
		}

		$value = ( is_null( $value ) ) ? $default : $value;
		return apply_filters( 'wpmudev_fields/get_post_value_' . implode( '_', $keys ), $value );
	}

	/**
	 * Searches an array multidimensional array for a specific path (if it exists)
	 *
	 * @since 3.0.0
	 *
	 * @param array $array The array we want to search
	 * @param string $path The path we want to check for (e.g. key1->key2->key3 = $array[key1][key2][key3])
	 * @return mixed
	 */
	public function array_search( $array, $path ) {
		$keys	 = explode( '->', $path );
		$keys	 = array_map( 'trim', $keys );

		for ( $i = $array; ($key = array_shift( $keys )) !== null; $i = $i[ $key ] ) {
			if ( !isset( $i[ $key ] ) ) {
				return null;
			}
		}

		return $i;
	}

	/**
	 * Saves the field to the database.
	 *
	 * @since 1.0
	 * @access public
	 * @action save_post
	 * @param int $post_id
	 * @param string $meta_key The meta key to use when storing the field value. Defaults to null.
	 * @param mixed $value The value of the field. Defaults to null.
	 * @param bool $force Whether to bypass the is_subfield check. Subfields normally don't run their own save routine. Defaults to false.
	 */
	public function save_value( $post_id, $meta_key = null, $value = null, $force = false ) {
		if ( $this->is_subfield && !$force ) {
			return;
		}

		if ( is_null( $value ) ) {
			$post_key	 = $this->get_post_key();
			$value		 = $this->get_post_value( $post_key, null );
		}

		if ( is_null( $meta_key ) ) {
			$meta_key = $this->get_meta_key();
		}

		/**
		 * Modify the value before it's saved to the database. Return null to bypass internal saving mechanisms.
		 *
		 * @since 1.0
		 * @param mixed $value The field's value
		 * @param mixed $post_id The current post id or option name
		 * @param object $this Refers to the current field object
		 */
		$value	 = apply_filters( 'wpmudev_field/save_value', $this->sanitize_for_db( $value, $post_id ), $post_id, $this );
		$value	 = apply_filters( 'wpmudev_field/save_value/' . $this->args[ 'name' ], $value, $post_id, $this );

		if ( is_null( $value ) ) {
			return;
		}

		update_post_meta( $post_id, $meta_key, $value );
		update_post_meta( $post_id, '_' . $meta_key, get_class( $this ) );
	}

	/**
	 * Gets the correct meta key from a field's name
	 *
	 * @since 1.0
	 * @access public
	 * @return string
	 */
	public function get_meta_key() {
		if ( strpos( $this->args[ 'name' ], '[' ) === false ) {
			return $this->args[ 'name' ];
		}

		$name_parts = explode( '[', $this->args[ 'name' ] );

		foreach ( $name_parts as &$part ) {
			$part = rtrim( $part, ']' );
		}

		$name_parts = array_filter( $name_parts, create_function( '$x', 'return ! empty($x);' ) );

		return array_shift( $name_parts );
	}

	/**
	 * Gets the correct post key from a field's name
	 *
	 * @since 1.0
	 * @access public
	 * @return string
	 */
	public function get_post_key() {
		if ( strpos( $this->args[ 'name' ], '[' ) === false ) {
			// Name isn't an array so just return the name
			return $this->args[ 'name' ];
		}

		$name_parts = explode( '[', $this->args[ 'name' ] );

		foreach ( $name_parts as &$part ) {
			$part = rtrim( $part, ']' );
		}

		$name_parts = array_filter( $name_parts, create_function( '$x', 'return ! empty($x);' ) );

		return implode( '->', $name_parts );
	}

	/**
	 * Sets the value essentially overriding the internal get_value() function
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $value
	 */
	public function set_value( $value ) {
		$this->_value = $value;
	}

	/**
	 * Set the internal meta key for retrieving/saving the field's value
	 *
	 * @since 1.0
	 * @access public
	 * @param string @meta_key
	 */
	public function set_meta_key( $meta_key ) {
		$this->_meta_key = $meta_key;
	}

	/**
	 * Set the order of the field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $order
	 */
	public function set_order( $order ) {
		// Set field order
		$order			 = (float) $order;
		$this->_order	 = $order;

		// Set name according to order
		$name_parts				 = explode( '[', $this->args[ 'name_base' ] );
		$find					 = array_pop( $name_parts );
		$name_start				 = implode( '[', $name_parts );
		$this->args[ 'name' ]	 = str_replace( $find, "$order][$find", $this->_name );
	}

	/**
	 * Sets the subfield's id (for repeater fields)
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $id
	 */
	public function set_subfield_id( $id ) {
		$this->subfield_id	 = $id;
		$this->args[ 'id' ]	 = 'wpmudev-field-' . uniqid( true );
	}

	/**
	 * Gets the field value from the database.
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $post_id
	 * @param string $meta_key
	 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
	 * @return mixed
	 */
	public function get_value( $post_id, $meta_key = null, $raw = false ) {
		$value = null;

		/**
		 * Filter the returned value before any internal code is run
		 *
		 * @since 1.0
		 * @param mixed $value The return value
		 * @param mixed $post_id The current post id or option name
		 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
		 * @param object $this Refers to the current field object
		 */
		$value	 = apply_filters( 'wpmudev_field/before_get_value', $value, $post_id, $raw, $this );
		$value	 = apply_filters( 'wpmudev_field/before_get_value/' . $this->args[ 'name' ], $value, $post_id, $raw, $this );

		if ( !is_null( $value ) ) {
			$this->_value = $value;
		}

		if ( !is_null( $this->_value ) ) {
			return ($raw) ? $this->_value : $this->format_value( $this->_value, $post_id );
		}

		if ( is_numeric( $post_id ) ) {
			// This is a post
			if ( is_null( $meta_key ) ) {
				$meta_key = $this->get_meta_key();
			}


			if ( !empty( $post_id ) ) {
				if ( metadata_exists( 'post', $post_id, $meta_key ) ) {
					$value = get_post_meta( $post_id, $meta_key, true );

					if ( $value === '' ) {
						$value = $this->args[ 'default_value' ];
					}
				}
			}
		} else {
			// This is a settings key
			$settings	 = (!empty( $this->metabox->args[ 'site_option_name' ] ) ) ? get_site_option( $post_id, array() ) : get_option( $post_id, array() );
			$key		 = $this->get_post_key();
			$value		 = $this->array_search( $settings, $key );
		}

		/**
		 * Filter the returned value
		 *
		 * @since 1.0
		 * @param mixed $value The return value
		 * @param mixed $post_id The current post id or option name
		 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
		 * @param object $this Refers to the current field object
		 */
		$value	 = apply_filters( 'wpmudev_field/get_value', $value, $post_id, $raw, $this );
		$value	 = apply_filters( 'wpmudev_field/get_value/' . $this->args[ 'name' ], $value, $post_id, $raw, $this );

		if ( is_null( $value ) || $value === false ) {
			$value = $this->args[ 'default_value' ];
		}

		$value = ( $raw ) ? $value : $this->format_value( $value, $post_id );

		return $value;
	}

	/**
	 * Get the field value for API
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $post_id A post ID or option name.
	 * @param string $meta_key A meta or settings key to override the default. Optional.
	 * @param bool $raw True to return the unformatted value. Optional.
	 * @return mixed
	 */
	public function get_api_value( $post_id, $meta_key = null, $raw = false ) {
		return $this->get_value( $post_id, $meta_key, $raw );
	}

	/**
	 * Formats the field value for display.
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $value
	 * @param mixed $post_id
	 */
	public function format_value( $value, $post_id ) {
		/**
		 * Modify the formatted value.
		 *
		 * @since 1.0
		 * @param mixed $value The return value
		 * @param object $this Refers to the current field object
		 */
		$value = apply_filters( 'wpmudev_field/format_value', $value, $this );
		return apply_filters( 'wpmudev_field/format_value/' . $this->args[ 'name' ], $value, $this );
	}

	/**
	 * Recursively call a function over an array
	 *
	 * @since 1.0
	 * @access public
	 * @param array $array
	 * @param string $callback
	 * @return array
	 */
	public function array_map_deep( $array, $callback ) {
		$new = array();

		if ( is_array( $array ) ) {
			foreach ( $array as $key => $val ) {
				if ( is_array( $val ) ) {
					$new[ $key ] = $this->array_map_deep( $val, $callback );
				} else {
					$new[ $key ] = call_user_func( $callback, $val );
				}
			}
		} else {
			$new = call_user_func( $callback, $array );
		}

		return $new;
	}

	/**
	 * Sanitizes the field value before saving to database.
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $value
	 * @param mixed $post_id
	 */
	public function sanitize_for_db( $value, $post_id ) {
		$value = is_array( $value ) ? $this->array_map_deep( $value, 'trim' ) : trim( $value );

		if ( !empty( $this->args[ 'save_callback' ] ) ) {
			foreach ( (array) $this->args[ 'save_callback' ] as $func ) {
				$value = $func( $value );
			}
		}

		/**
		 * Modify the value right before it's saved to the database.
		 *
		 * @since 1.0
		 * @param mixed $value The current value
		 * @param mixed $post_id The current post id or option name
		 * @param object $this Refers to the current field object
		 */
		$value = apply_filters( 'wpmudev_field/sanitize_for_db', $value, $post_id, $this );
		return apply_filters( 'wpmudev_field/sanitize_for_db/' . $this->args[ 'name' ], $value, $post_id, $this );
	}

	/**
	 * Prints inline javascript
	 *
	 * @since 1.0
	 * @access public
	 */
	public function print_scripts() {

	}

	/**
	 * Determines if the field's print_scripts function should be called
	 *
	 * @since 1.0
	 * @access public
	 */
	public function maybe_print_scripts() {
		/**
		 * Runs when the field's scripts are printed.
		 *
		 * @since 1.0
		 * @param object $this The current field object.
		 */
		do_action( 'wpmudev_field/print_scripts', $this );
		do_action( 'wpmudev_field/print_scripts/' . $this->args[ 'name' ], $this );

		if ( !$this->scripts_printed() ) {
			$this->print_scripts();
		}
	}

	/**
	 * Determines if a field's scripts have already been printed
	 *
	 * @since 1.0
	 * @access public
	 * @uses $printed_field_scripts
	 */
	public function scripts_printed() {
		global $wpmudev_metaboxes_printed_field_scripts;

		$class = get_class( $this );

		if ( in_array( $class, $wpmudev_metaboxes_printed_field_scripts ) ) {
			return true;
		}

		$wpmudev_metaboxes_printed_field_scripts[] = $class;

		return false;
	}

	/**
	 * Displays the form field
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 */
	public function display( $post_id ) {
		wp_die( __( 'You must the WPMUDEV_Field::display() method in your form field class', 'wpmudev_metaboxes' ), E_USER_ERROR );
	}

	/**
	 * Use this to setup your child form field instead of __construct()
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args An array of arguments.
	 */
	public function on_creation( $args ) {

	}

	/**
	 * Enqueues the field's scripts
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_scripts() {

	}

	/**
	 * Enqueues the field's styles
	 *
	 * @since 1.0
	 * @access public
	 */
	public function enqueue_styles() {

	}

	/**
	 * Enqueue styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_styles_scripts() {
		$this->enqueue_styles();
		$this->enqueue_scripts();
	}

	/**
	 * Gets a form field's attribute markup (e.g. style, class, id, etc)
	 *
	 * @since 1.0
	 * @access public
	 * @return string
	 */
	public function parse_atts() {
		$atts			 = '';
		$args			 = $this->args; //make a copy of field args so as to not overwrite
		$args[ 'name' ]	 = $this->get_name();

		if ( !empty( $args[ 'default_value' ] ) ) {
			$args[ 'custom' ][ 'data-default-value' ] = $args[ 'default_value' ];
		}

		foreach ( $this->default_atts as $key ) {
			if ( empty( $args[ $key ] ) ) {
				continue;
			}

			$atts .= $key . '="' . esc_attr( $args[ $key ] ) . '" ';
		}

		foreach ( $args[ 'custom' ] as $key => $val ) {
			if ( is_array( $val ) ) {

			} else {
				$atts .= $key . '="' . esc_attr( $val ) . '" ';
			}
		}

		$atts = trim( $atts );

		/**
		 * Modify the field's attributes before display
		 *
		 * @since 1.0
		 * @param string $atts The field's attributes
		 * @param object $this Refers to the current field object
		 */
		return apply_filters( 'wpmudev_field/parse_atts', $atts, $this );
	}

	/**
	 * Gets the field's ID - if not set an ID will be generated
	 *
	 * @since 1.0
	 * @access public
	 * @return string
	 */
	public function get_id() {
		if ( empty( $this->args[ 'id' ] ) ) {
			$this->args[ 'id' ] = 'wpmudev-field-' . uniqid(true );
		}

		/**
		 * Modify the field's id before return
		 *
		 * @since 1.0
		 * @param string $id
		 * @param object $this Refers to the current field object
		 */
		return apply_filters( 'wpmudev_field/get_id', $this->args[ 'id' ], $this );
	}

	/**
	 * Displays content before the field output
	 *
	 * @since 1.0
	 * @access public
	 */
	public function before_field() {
		/**
		 * Allows modification of the content that displays before a given field.
		 *
		 * @since 3.0
		 * @access public
		 * @param string $text The current text.
		 * @param WPMUDEV_Field The current field object.
		 */
		$text	 = apply_filters( 'wpmudev_field/before_field', $this->args[ 'before_field' ], $this );
		$text	 = apply_filters( 'wpmudev_field/before_field/' . $this->args[ 'name' ], $text, $this );

		echo $text;
	}

	/**
	 * Displays content after the field output
	 *
	 * @since 1.0
	 * @access public
	 */
	public function after_field() {
		/**
		 * Allows modification of the content that displays after a given field.
		 *
		 * @since 3.0
		 * @access public
		 * @param string $text The current text.
		 * @param WPMUDEV_Field The current field object.
		 */
		$text	 = apply_filters( 'wpmudev_field/after_field', $this->args[ 'after_field' ], $this );
		$text	 = apply_filters( 'wpmudev_field/after_field/' . $this->args[ 'name' ], $text, $this );

		echo $text;
	}

}
