<?php

class WPMUDEV_Field {
	/**
	 * Refers to the field's value
	 *
	 * @since 1.0
	 * @access private
	 * @var mixed
	 */
	private $_value = null;
	
	/**
	 * Refers to the field's subfield id (repeater field will set this)
	 *
	 * @since 1.0
	 * @access private
	 * @var int
	 */
	private $_subfield_id = null;
	
	/**
	 * If the field is a subfield (repeater field will set this)
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
	var $default_atts = array('name', 'id', 'class', 'style', 'disabled', 'readonly', 'value');
		
	/**
	 * Constructor function.
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args {
	 *		An array of arguments. Optional.
	 *
	 *		@type string $name The field's name attribute.
	 *		@type string $original_name The field's original name (subfield name attributes get changed)
	 *		@type string $id The field's id attribute.
	 *		@type string $class The field's class attribute.
	 *		@type string $style The field's style attribute.
	 *		@type bool $disabled Should the field be disabled.
	 *		@type bool $readonly Should the field be read-only.
	 *		@type string $desc The field's description.
	 *		@type array $custom Any custom/non-standard attributes.
	 *		@type bool $value_only Only get the field's value - don't initialize scripts, styles, etc.
	 *		@type string $default_value The value of the field before any user interaction.
	 *		@type string $custom_validation_message Any custom validation message the field uses (only used for custom validation).
	 *		@type array $validation {
	 *			The rules to apply for validation.
	 *
	 *			@type bool $required Makes the element required.
	 *			@type int $minlength Makes the element require a given minimum length.
	 *			@type int $maxlength Makes the element require a given maxmimum length.
	 *			@type array $rangelength Makes the element require a given value range.
	 *			@type int $min Makes the element require a given minimum value.
	 *			@type int $max Makes the element require a given maximum value.
	 *			@type array $range Makes the element require a value between the given range.
	 *			@type bool $email Makes the element require a valid email.
	 *			@type bool $url Makes the element require a valid url.
	 *			@type bool $date Makes the element require a date.
	 *			@type bool $number Makes the element require a decimal number.
	 *			@type bool $digits Makes the element require digits only.
	 *			@type bool $creditcard Makes the element require a credit card number.
	 *			@type bool $alphanumeric Makes the element require only letters and numbers.
	 *			@type string $custom A regular expression to test for (e.g. [0-9A-Z]).
	 *			@type string $equalTo The id of a field that this field's value should match.
	 *	}
	 *	@type array $conditional {
	 *		Conditionally hide/show this field if another field value is a certain value.
	 *
	 *		@type string $name The name of the field to do the comparison on.
	 *		@type string $value The value to check against.
	 *		@type string $action The action to perform (show/hide).
	 *	}
	 */
	public function __construct( $args = array() ) {
		$this->args = wp_parse_args($args, array(
			'name' => '',
			'id' => '',
			'class' => '',
			'style' => '',
			'disabled' => '',
			'readonly' => '',
			'desc' => '',
			'custom' => array(),
			'default_value' => '',
			'original_name' => '',
			'value_only' => false,
			'custom_validation_message' => '',
			'validation' => array(),	
			'conditional' => array(),
		));
		
		if ( $this->args['value_only'] ) {
			return false;
		}
		
		if ( empty($this->args['original_name']) ) {
			$this->args['original_name'] = $this->args['name'];
		}
		
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		add_action('wpmudev_metaboxes_save_fields', array(&$this, 'save_value'));
		
		if ( ! $this->scripts_printed() ) {
			add_action('in_admin_footer', array(&$this, 'print_scripts'));
		}
		
		if ( ! empty($this->args['conditional']) ) {
			$this->args['custom']['data-conditional-name'] = $this->args['conditional']['name'];
			$this->args['custom']['data-conditional-value'] = ( is_array($this->args['conditional']['value']) && count($this->args['conditional']['value']) > 1 ) ? implode('||', $this->args['conditional']['value']) : $this->args['conditional']['value'];
			$this->args['custom']['data-conditional-action'] = $this->args['conditional']['action'];
		}
		
		if ( ! empty($this->args['validation']) ) {
			foreach ( $this->args['validation'] as $key => $val ) {
				if ( $key == 'custom' ) {
					$this->args['custom']['data-custom-validation'] = $val;
				}
				elseif ( is_bool($val) === true ) {
					$this->args['class'] .= " $key";
				} else {
					$this->args['custom'][$key] = $val;
				}
			}
		}
		
		$this->on_creation($this->args);
	}
	
	/**
	 * Saves the field to the database.
	 *
	 * @since 1.0
	 * @access public
	 * @action save_post
	 * @param int $post_id
	 * @param $value (optional)
	 */
	public function save_value( $post_id, $value = null ) {
		if ( is_null($value) ) {
			$value = isset($_POST[$this->args['name']]) ? $_POST[$this->args['name']] : '';
		}
		
		/**
		 * Modify the value before it's saved to the database. Return null to bypass internal saving mechanisms.
		 *
		 * @since 1.0
		 * @param mixed $value The field's value
		 * @param mixed $post_id The current post id or option name
		 * @param object $this Refers to the current field object
		 */
		$value = apply_filters('wpmudev_field_save_value', $this->sanitize_for_db($value), $post_id, $this);
		$value = apply_filters('wpmudev_field_save_value_' . $this->args['name'], $value, $post_id, $this);
		
		if ( is_null($value) ) {
			return;
		}
				
		update_post_meta($post_id, $this->args['name'], $value);
		update_post_meta($post_id, '_' . $this->args['name'], get_class($this));		
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
	 * Sets the subfield's id (for repeater fields)
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $id
	 */
	public function set_subfield_id( $id ) {
		$this->_subfield_id = $id;
	}
	
	/**
	 * Gets the field value from the database.
	 *
	 * @since 1.0
	 * @access public
	 * @param mixed $post_id
	 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
	 * @return mixed
	 */
	public function get_value( $post_id, $raw = false ) {
		$value = null;
		
		if ( ! is_null($this->_value) ) {
			return $this->_value;
		}
		
		if ( ! empty($post_id) ) {
			if ( metadata_exists(get_post_type($post_id), $post_id, $this->args['name']) ) {
				$value = get_post_meta($post_id, $this->args['name'], true);
				
				if ( $value === '' ) {
					$value = $this->args['default_value'];
				}
			}
		}
		
		/**
		 * Modify the returned value.
		 *
		 * @since 1.0
		 * @param mixed $value The return value
		 * @param mixed $post_id The current post id or option name
		 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
		 * @param object $this Refers to the current field object		 
		 */
		$value = apply_filters('wpmudev_field_get_value', $value, $post_id, $raw, $this);
		$value = apply_filters('wpmudev_field_get_value_' . $this->args['name'], $value, $post_id, $raw, $this);
		
		if ( is_null($value) || $value === false ) {
			$value = $this->args['default_value'];
		}
		
		$value = ( $raw ) ? $value : $this->format_value($value);
		
		// Set the field's value for future
		$this->_value = $value;
		
		return $value;
	}

	/**
	 * Formats the field value for display.
	 *
	 * @since 1.0
	 * @access public
	 * @param $value
	 */
	public function format_value( $value ) {
		/**
		 * Modify the formatted value.
		 *
		 * @since 1.0
		 * @param mixed $value The return value
		 * @param object $this Refers to the current field object
		 */	
		return apply_filters('wpmudev_field_format_value', $value, $this);
	}
	
	/**
	 * Sanitizes the field value before saving to database.
	 *
	 * @since 1.0
	 * @access public
	 * @param $value
	 */	
	public function sanitize_for_db( $value ) {
		/**
		 * Modify the value right before it's saved to the database.
		 *
		 * @since 1.0
		 * @param mixed $value The current value
		 * @param mixed $post_id The current post id or option name
		 * @param object $this Refers to the current field object
		 */	
		return apply_filters('wpmudev_field_sanitize_for_db', $value, $post_id, $this);
	}
	
	/**
	 * Prints inline javascript
	 *
	 * @since 1.0
	 * @access public
	 */
	public function print_scripts() {
		/**
		 * Triggered when the field's scripts are printed.
		 *
		 * @since 1.0
		 * @param object $this The current field object.
		 */
		do_action('wpmudev_field_print_scripts', $this);
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
		
		$class = get_class($this);
		
		if ( in_array($class, $wpmudev_metaboxes_printed_field_scripts) ) {
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
	 */
	public function display() {
		wp_die(__('You must the WPMUDEV_Field::display() method in your form field class', 'wpmudev_metaboxes'), E_USER_ERROR);
	}
	
	/**
	 * Use this to setup your child form field instead of __construct()
	 *
	 * @since 1.0
	 * @access public
	 */
	public function on_creation() {
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
	 * Gets a form field's attribute markup (e.g. style, class, id, etc)
	 *
	 * @since 1.0
	 * @access public
	 * @return string
	 */
	public function parse_atts() {
		$atts = '';
		$args = $this->args; //make a copy of field args so as to not overwrite
		
		if ( ! is_null($this->_subfield_id) && ! empty($args['name']) ) {
			// repeater field - add the subfield id
			$args['name'] = str_replace('[new][]', '[existing][' . $this->_subfield_id . ']', $args['name']);
		}
		
		foreach ( $this->default_atts as $key ) {
			if ( empty($args[$key]) ) {
				continue;
			}
			
			$atts .= $key . '="' . esc_attr($args[$key]) . '" ';
		}
		
		foreach ( $args['custom'] as $key => $val ) {
			$atts .= $key . '="' . esc_attr($val) . '" ';
		}
				
		$atts = trim($atts);
		
		/**
		 * Modify the field's attributes before display
		 *
		 * @since 1.0
		 * @param string $atts The field's attributes
		 * @param object $this Refers to the current field object
		 */
		return apply_filters('wpmudev_field_parse_atts', $atts, $this);
	}
	
	/**
	 * Gets the field's ID - if not set an ID will be generated
	 *
	 * @since 1.0
	 * @access public
	 * @return string
	 */
	public function get_id() {
		if ( empty($this->args['id']) ) {
			$this->args['id'] = 'mp-field-' . uniqid(true);
		}

		/**
		 * Modify the field's id before return
		 *
		 * @since 1.0
		 * @param string $id
		 * @param object $this Refers to the current field object
		 */		
		return apply_filters('wpmudev_field_get_id', $this->args['id'], $this);
	}
}