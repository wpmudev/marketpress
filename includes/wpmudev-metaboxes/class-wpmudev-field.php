<?php

class WPMUDEV_Field {
	/**
	 * Refers to the field arguments
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	var $args = array();
	
	/**
	 * Refers to the default field attributes
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	var $default_atts = array('name', 'id', 'class', 'style', 'disabled', 'readonly', 'value');
		
	/**
	 * Constructor function
	 *
	 * @since 1.0
	 * @access public
	 * @param array $args (optional)
	 */
	public function __construct( $args = array() ) {
		$this->args = wp_parse_args($args, array(
			'name' => '',							//the field's name attribute
			'id' => '',								//the field's id attribute
			'class' => '',						//the field's class attribute
			'style' => '',						//the field's style attribute
			'disabled' => '',					//whether the field should be disabled or not
			'readonly' => '',					//whether the field should be read-only or not
			'desc' => '',							//the field's description
			'custom' => array(),			//any custom/non-standard attributes,
			'default_value' => '',		//the default value of the field before any user interaction,
			'value_only' => false,		//only get the field's value - don't initialize scripts, styles, etc
			'validation' => array(		//the rules to apply for validation
			'custom_validation_message' => '',	//any custom validation message the field uses (only used for custom validation)
			/*'required' => false,
				'minlength' => 0,
				'maxlength' => 0,
				'rangelength' => array(),
				'min' => 0,
				'max' => 0,
				'range' => 0,
				'email' => true,
				'url' => true,
				'date' => true,
				'number' => true,
				'digits' => true,
				'creditcard' => true,
				'alphanumeric' => true,
				'custom' => '', //a regular expression to test for (e.g. [0-9A-Z])
				'equalTo' => '#field-id',*/
			),	
			'conditional' => array(		//conditionally hide/show this field if another field value is a certain value
				/*'name' =>	'',
				'value' => '',
				'action' => 'show',*/
			),
		));
		
		if ( $this->args['value_only'] ) {
			return false;
		}
		
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		add_action('wpmudev_metaboxes_save_fields', array(&$this, 'save_value'));
		
		if ( ! $this->scripts_printed() ) {
			add_action('in_admin_footer', array(&$this, 'print_scripts'));
		}
		
		if ( ! empty($this->args['conditional']) ) {
			$this->args['custom']['data-conditional-name'] = $this->args['conditional']['name'];
			$this->args['custom']['data-conditional-value'] = $this->args['conditional']['value'];
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
	 * Saves the field to the database
	 *
	 * @since 1.0
	 * @access public
	 * @action save_post
	 * @param int $post
	 * @param $value (optional)
	 */
	public function save_value( $post_id, $value = null ) {
		if ( is_null($value) ) {
			$value = isset($_POST[$this->args['name']]) ? $_POST[$this->args['name']] : '';
		}
		
		$value = apply_filters('wpmudev_field_save_value', $this->sanitize_for_db($value), $post_id, $this);
		
		update_post_meta($post_id, $this->args['name'], $value);
		update_post_meta($post_id, '_' . $this->args['name'], get_class($this));
	}
	
	/**
	 * Gets the field value from the database
	 *
	 * @since 1.0
	 * @access public
	 * @param int $post_id
	 * @param bool $raw Whether or not to get the raw/unformatted value as saved in the db
	 */
	public function get_value( $post_id, $raw = false ) {		
		$value = get_post_meta($post_id, $this->args['name'], true);
		
		if ( $value === '' ) {
			return false;
		}
		
		$value = ( $raw ) ? $value : $this->format_value($value, $args);
		
		return apply_filters('wpmudev_field_get_value', $value, $this, $post_id, $raw);
	}

	/**
	 * Formats the field value for display
	 *
	 * @since 1.0
	 * @access public
	 * @param $value
	 */
	public function format_value( $value ) {
		return apply_filters('wpmudev_field_format_value', $value, $post_id, $this);
	}
	
	/**
	 * Sanitizes the field value before saving to database
	 *
	 * @since 1.0
	 * @access public
	 * @param $value
	 */	
	public function sanitize_for_db( $value ) {
		return apply_filters('wpmudev_field_sanitize_for_db', $value, $post_id, $this);
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
	 * Determines if a field's scripts have already been printed
	 *
	 * @since 3.0
	 * @access public
	 * @uses $printed_field_scripts
	 */
	public function scripts_printed() {
		global $printed_field_scripts;
		
		$class = get_class($this);
		
		if ( in_array($class, $printed_field_scripts) ) {
			return true;
		}
		
		$printed_field_scripts[] = $class;
		
		return false;
	}
	
	/**
	 * Displays the form field
	 *
	 * @since 1.0
	 * @access public
	 */
	public function display() {
		wp_die(__('You must the MP_Field::display() method in your form field class', 'mp'), E_USER_ERROR);
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
		
		foreach ( $this->default_atts as $key ) {
			if ( empty($this->args[$key]) ) { continue; }
			$atts .= $key . '="' . esc_attr($this->args[$key]) . '" ';
		}
		
		foreach ( $this->args['custom'] as $key => $val ) {
			$atts .= $key . '="' . esc_attr($val) . '" ';
		}
		
		$atts = trim($atts);
		
		return apply_filters('wpmudev_field_parse_atts', $atts, $this);
	}
	
	/**
	 * Gets the field's ID - if not yet an ID will be generated
	 *
	 * @since 1.0
	 * @access public
	 */
	public function get_id() {
		if ( empty($this->args['id']) ) {
			$this->args['id'] = 'mp-field-' . uniqid(true);
		}
		
		return apply_filters('wpmudev_field_get_id', $this->args['id'], $this);
	}
}