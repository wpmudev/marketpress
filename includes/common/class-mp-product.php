<?php

class MP_Product {
	/**
	 * Refers to the product's ID
	 *
	 * @since 3.0
	 * @access public
	 * @type int
	 */
	var $ID = null;
	
	/**
	 * Referrs to the product's variations
	 *
	 * @since 3.0
	 * @access public
	 * @type array
	 */
	var $variations = null;
	
	/**
	 * Refers to if the product is on sale
	 *
	 * @since 3.0
	 * @access protected
	 * @type bool
	 */
	protected $_on_sale = null;
	
	/**
	 * Refers to the product's internal WP_Post object
	 *
	 * @since 3.0
	 * @access protected
	 * @type WP_Post
	 */
	protected $_post = null;
	
	/**
	 * Refers to the whether the product exists or not
	 *
	 * @since 3.0
	 * @access protected
	 * @type bool
	 */
	protected $_exists = null;
	
	/**
	 * Refers to whether or not the class has attempted to fetch the internal WP_Post object or not
	 *
	 * @since 3.0
	 * @access protected
	 * @type bool
	 */
	protected $_post_queried = false;
	
	/**
	 * Get the internal post type for products
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public static function get_post_type() {
		return mp_get_setting('product_post_type') == 'mp_product' ? 'mp_product' : 'product';
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access public
	 * @param int $product_id
	 */
	public function __construct( $product_id ) {
		$this->ID = $product_id;
		$this->_get_post();
	}
	
	/**
	 * Get product variations
	 *
	 * @since 3.0
	 * @access public
	 * @return array An array of MP_Product objects.
	 */
	public function get_variations() {
		if ( ! is_null($this->variations) ) {
			return $this->variations;
		}

		$this->variations = array();
		$query = new WP_Query(array(
			'post_type' => 'mp_product_variation',
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'post_parent' => $this->ID,
		));		
		
		while ( $query->have_posts() ) : $query->the_post();
			$this->variations[] = new MP_Product(get_the_ID());
		endwhile;
		
		wp_reset_postdata();
		
		return $this->variations;
	}
	
	/**
	 * Determine if product has variations
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function has_variations() {
		$variations = $this->get_variations();
		return ( ! empty($variations) );
	}
	
	/**
	 * Determine if product is on sale
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function on_sale() {
		if ( ! is_null($this->_on_sale) ) {
			return $this->_on_sale;
		}
		
		$sale_price = $this->get_meta('sale_price_amount');
		$on_sale = false;
		
		if ( $sale_price ) {
			$start_date = $this->get_meta('sale_price_start_date', false, true);
			$end_date = $this->get_meta('sale_price_end_date', false, true);
			$time = current_time('Y-m-d');
			
			if ( $start_date && $end_date && $time >= $start_date && $time <= $end_date ) {
				$on_sale = true;
			} elseif ( $start_date && $time >= $start_date ) {
				$on_sale = true;
			} elseif ( $end_date && $time <= $end_date ) {
				$on_sale = true;
			}
		}
		
		$this->_on_sale = $on_sale;
		return $on_sale;
	}
	
	/**
	 * Get a product's price
	 *
	 * Will return the product's regular and sale price - if applicable.
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_price() {
		$price = array(
			'regular' => $this->get_meta('regular_price', 0),
			'sale' => array(
				'amount' => '',
				'start_date' => '',
				'end_date' => '',
			),
		);
		
		if ( $this->on_sale() && ($sale_price = $this->get_meta('sale_price_amount')) ) {
			$price['sale'] = array(
				'amount' => $sale_price,
				'start_date' => $this->get_meta('start_date', false, true),
				'end_date' => $this->get_meta('end_date', false, true),
			);
		}
		
		return $price;
	}
	
	/**
	 * Get product meta value
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The name of the meta to get.
	 * @param mixed $default The default value to return if meta doesn't exist. Optional.
	 * @param bool $raw Whether to return the raw meta or the formatted value. Optional.
	 * @return mixed
	 */
	public function get_meta( $name, $default = false, $raw = false ) {
		if ( ! $this->exists() ) {
			return $default;
		}
		
		if ( function_exists('get_field_value') ) {
			// Try to get WPMUDEV_Field value
			$value = get_field_value($name, $this->ID, $raw);
		}
		
		if ( $value ) {
			return $value;
		}
		
		$parent_id = $this->_post->post_parent;
		if ( ! empty($parent_id) ) {	
			// This is a variation, try to use WPMUDEV_Field value from parent product	
			if ( function_exists('get_field_value') ) {
				$value = get_field_value($name, $parent_id, $raw);
			}
		}
		
		if ( $value ) {
			return $value;
		}
		
		// Try to use regular post meta
		$meta_val = get_post_meta($this->ID, $name, true);
		if ( $meta_val !== '' ) {
			return $meta_val;
		}
		
		// This is a variation - try to use regular post meta from parent product
		if ( ! empty($parent_id) ) {
			$meta_val = get_post_meta($parent_id, $name, true);
			if ( $meta_val !== '' ) {
				return $meta_val;
			}
		}
		
		return $default;
	}
	
	/**
	 * Display product meta value
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The name of the meta to get.
	 * @param mixed $default The default value to return if meta doesn't exist. Optional.
	 * @param bool $raw Whether to return the raw meta or the formatted value. Optional.
	 * @return mixed
	 */
	public function meta( $name, $default = false, $raw = false ) {
		echo $this->get_meta($name, $default, $raw);
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
	 */
	protected function _get_post() {
		$this->_post_queried = true;
		$this->_post = get_post($this->ID);
		$this->_exists = true;
		
		if ( is_null($this->_post) ) {
			$this->_exists = false;
		}
	}
}