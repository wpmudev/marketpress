<?php
	
class MP_Coupon {
	/**
	 * Refers to the coupon's ID.
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	var $ID = null;

	/**
	 * Refers to the coupon code
	 *
	 * @since 3.0
	 * @access protected
	 * @var string
	 */
	protected $_code = null;

	/**
	 * Refers to the coupon's internal WP_Post object.
	 *
	 * @since 3.0
	 * @access protected
	 * @type WP_Post
	 */
	protected $_post = null;
	
	/**
	 * Refers to the whether the coupon exists or not.
	 *
	 * @since 3.0
	 * @access protected
	 * @type bool
	 */
	protected $_exists = null;
	
	/**
	 * Refers to whether or not the class has attempted to fetch the internal WP_Post object or not.
	 *
	 * @since 3.0
	 * @access protected
	 * @type bool
	 */
	protected $_post_queried = false;
	
	/**
	 * Constructor
	 *
	 * @since 3.0
	 * @access public
	 * @param string $code A coupon code.
	 */
	public function __construct( $code ) {
		$this->_code = preg_replace('/[^A-Z0-9_-]/', '', strtolower($code));
		$this->_get_post($code);
	}
	
	/**
	 * Attempt to get an internal WP_Post object property (e.g post_name, post_status, etc)
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The property name.
	 * @return string The property value or false if the property or post doesn't exist.
	 */
	public function __get( $name ) {
		if ( ! $this->exists() ) {
			return false;
		}
		
		if ( property_exists($this->_post, $name) ) {
			return $this->_post->$name;
		}
		
		return false;
	}
	
	/**
	 * Attempt to set the internal WP_Post object
	 *
	 * @since 3.0
	 * @access protected
	 * @param string $code
	 */
	protected function _get_post( $code ) {
		$posts = get_posts(array(
			'name' => $code,
			'post_type' => 'mp_coupon',
			'posts_per_page' => 1,
			'post_status' => 'publish',
		));
		
		if ( ! empty($posts) ) {
			$this->_post = current($posts);
		}
		
		if ( is_null($this->_post) ) {
			$this->_exists = false;
		} elseif ( $this->_post->post_type != 'mp_coupon' ) {
			$this->_exists = false;
		} else {
			$this->_exists = true;
			$this->ID = $this->_post->ID;
		}
	}

	/**
	 * Check if a product exists
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function exists() {
		return $this->_exists;
	}
	
	/**
	 * Get coupon meta value
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The name of the meta to get.
	 * @param mixed $default The default value to return if meta doesn't exist or is an empty string. Optional.
	 * @param bool $raw Whether to return the raw meta or the formatted value. Optional.
	 * @return mixed
	 */
	public function get_meta( $name, $default = false, $raw = false ) {
		if ( ! $this->exists() ) {
			return $default;
		}
		
		$value = false;
		if ( function_exists('get_field_value') ) {
			// Try to get WPMUDEV_Field value
			$value = get_field_value($name, $this->ID, $raw);
		}

		if ( $value !== false && $value !== '' ) {
			return $value;
		}
				
		// Try to use regular post meta
		$meta_val = get_post_meta($this->ID, $name, true);
		if ( $meta_val !== '' ) {
			return $meta_val;
		}
		
		return $default;
	}

	/**
	 * Checks a coupon to see if it can be applied to a product
	 *
	 * @param int The product to check.
	 * @return bool
	 */
	function is_applicable( $product_id ) {
		$can_apply = true;
		$coupons = mp_coupons()->get();
		$applies_to = $this->get_meta('applies_to');
		
		if ( isset($applies_to['type']) && isset($applies_to['id']) ) {
			$what = $applies_to['type']; // the type will be 'product', 'category'
			$item_id	= $applies_to['id']; // the is is either id post ID or the term ID depending on the above
			 
			switch( $what ) {
				case 'product':
				 	$can_apply = ( $product_id == $item_id ) ? true : false;
					break;
				 
				case 'category':
				 	$terms = get_the_terms($product_id, 'product_category');
				 	$can_apply = false;
				 	
				 	if ( is_array($terms) ) {
						foreach ( $terms as $term) {
							if ( $term->term_id == $item_id ) {
								$can_apply = true;
								break;
							}
						}
					}
				break;
			}
		}
		
		return $can_apply;
	}
}