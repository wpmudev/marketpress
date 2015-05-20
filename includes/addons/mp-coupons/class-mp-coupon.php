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
	 * Constructor
	 *
	 * @since 3.0
	 * @access public
	 * @param string/int $coupon A coupon code or coupon ID.
	 */
	public function __construct( $coupon ) {
		if ( is_numeric( $coupon ) ) {
			$this->ID = $coupon;
		} else {
			$this->_code = strtolower( preg_replace( '/[^A-Z0-9_-]/', '', strtoupper( $coupon ) ) );
		}

		$this->_get_post();
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
		if ( !$this->exists() ) {
			return false;
		}

		if ( property_exists( $this->_post, $name ) ) {
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
		if ( is_null( $this->ID ) ) {
			if ( $_post = wp_cache_get( $this->_code, 'mp_coupon' ) ) {
				$this->_post = $_post;
			} else {
				$posts = get_posts( array(
					'name'			 => $this->_code,
					'post_type'		 => 'mp_coupon',
					'posts_per_page' => 1,
					'post_status'	 => 'publish',
				) );

				if ( !empty( $posts ) ) {
					$this->_post = current( $posts );
				}

				wp_cache_set( $this->_code, $this->_post, 'mp_coupon' );
			}
		} else {
			$this->_post = get_post( $this->ID );
		}

		if ( is_null( $this->_post ) ) {
			$this->_exists = false;
		} elseif ( $this->_post->post_type != 'mp_coupon' ) {
			$this->_exists = false;
		} else {
			$this->_exists	 = true;
			$this->ID		 = $this->_post->ID;
			$this->_code	 = $this->post_title;
		}
	}

	/**
	 * Get coupon discount amount
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 * @param bool $format Optional, whether to format the discount amount or not. Defaults to true.
	 */
	public function discount_amt( $echo = true, $format = true ) {
		$discount	 = $this->get_meta( 'discount' );
		$product_ids = $this->get_products( true );

		$discount_amt = 0;

		$product_ids = array_keys( $product_ids );

		foreach ( $product_ids as $product_id ) {
			$product		 = new MP_Product( $product_id );
			$product_price	 = $product->get_price( 'lowest' );

			if ( 'subtotal' == $this->get_meta( 'discount_type' ) ) {
				$discount_amt += ($this->get_price( $product_price ) - $product_price);
			} else {
				$discount_amt += (($this->get_price( $product_price ) - $product_price) * mp_cart()->get_item_qty( $product_id ));
			}
		}

		if ( $format ) {
			$discount_amt = mp_format_currency( '', $discount_amt );
		}

		if ( $echo ) {
			echo $discount_amt;
		} else {
			return $discount_amt;
		}
	}

	/**
	 * Get discount meta formatted
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 */
	public function discount_formatted( $echo = true ) {
		$discount			 = $discount_display	 = $this->get_meta( 'discount' );

		if ( false === strpos( $discount, '%' ) ) {
			$discount_display = mp_format_currency( '', $discount );
		}

		if ( $echo ) {
			echo $discount_display;
		} else {
			return $discount_display;
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
	 * Get coupon code
	 *
	 * @since 3.0
	 * @access public
	 */
	public function get_code() {
		return $this->_code;
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
		if ( !$this->exists() ) {
			return $default;
		}

		$value = false;
		if ( function_exists( 'get_field_value' ) ) {
			// Try to get WPMUDEV_Field value
			$value = get_field_value( $name, $this->ID, $raw );
		}

		if ( $value !== false && $value !== '' ) {
			return $value;
		}

		// Try to use regular post meta
		$meta_val = get_post_meta( $this->ID, $name, true );
		if ( $meta_val !== '' ) {
			return $meta_val;
		}

		return $default;
	}

	/**
	 * Get the coupon price
	 *
	 * @since 3.0
	 * @access public
	 * @param float $price The price to calculate.
	 * @return float
	 */
	public function get_price( $price ) {
		$discount = $this->get_meta( 'discount' );

		if ( false !== strpos( $discount, '%' ) ) {
			// Percentage discount
			$discount_amt	 = abs( str_replace( '%', '', $discount ) ) / 100;
			$new_price		 = ($price - ($price * $discount_amt));
		} else {
			// Fix amount discount
			$new_price = ($price - abs( $discount ));
		}

		return (float) $new_price;
	}

	/**
	 * Get the products that the coupon can be applied to
	 *
	 * @param int The product to check.
	 * @param bool $ids_only If true, only return product IDs.
	 * @return array An array of products that the coupon can be applied to.
	 */
	function get_products( $ids_only = false ) {
		$products = mp_cart()->get_items();

		$applies_to = $this->get_meta( 'applies_to' );

		switch ( $applies_to ) {
			case 'product':
				$products		 = array();
				$cart_products	 = mp_cart()->get_items_as_objects();
				$coupon_products = $this->get_meta( 'product' );

				foreach ( $cart_products as $product ) {
					$product_id = $product->ID;
					
					if ( $product->is_variation() ) {
						$product_id = $product->post_parent;
					}

					if ( in_array( $product_id, $coupon_products ) ) {
						if ( $ids_only ) {
							$products[] = $product->ID;
						} else {
							$key				 = ( mp_cart()->is_global ) ? $product->global_id() : $product->ID;
							$products[ $key ]	 = mp_cart()->get_line_item( $product );
						}
					}
				}
				break;

			case 'category':
				$products		 = array();
				$cart_products	 = mp_cart()->get_items_as_objects();

				$coupon_terms = $this->get_meta( 'category' );

				foreach ( $cart_products as $product ) {
					$terms = get_the_terms( $product->ID, 'product_category' );

					$product_id = $product->ID;

					if ( $product->is_variation() ) {
						$product_id = $product->post_parent;
					}

					if ( is_array( $terms ) ) {
						foreach ( $terms as $term ) {
							if ( in_array( (string) $term->term_id, $coupon_terms ) ) {
								if ( $ids_only ) {
									$products[] = $product->ID;
								} else {
									$key				 = ( mp_cart()->is_global ) ? $product->global_id() : $product->ID;
									$products[ $key ]	 = mp_cart()->get_line_item( $product );
								}
							}
						}
					}
				}
				break;

			//! TODO - code other coupon cases
		}

		return $products;
	}

	/**
	 * Check if coupon is valid
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function is_valid() {
		$now		 = time();
		$is_valid	 = true;

		if ( !$this->exists() ) {
			$is_valid = false;
		} elseif ( $this->remaining_uses( false, true ) == 0 ) {
			$is_valid = false;
		} elseif ( $now < strtotime( $this->get_meta( 'start_date', 0, false ) ) ) {
			$is_valid = false;
		} elseif ( $this->get_meta( 'has_end_date' ) && ($now > strtotime( $this->get_meta( 'end_date', 0, false ) )) ) {
			$is_valid = false;
		} elseif ( array() == $this->get_products( true ) ) {
			$is_valid = false;
		} else {
			if ( !$this->get_meta( 'can_be_combined' ) ) {
				$applied = mp_coupons_addon()->get_applied();

				if ( count( $applied ) == 1 ) {
					$is_valid = false;
				}
			} else {
				//! TODO: coupon can be applied with other coupons
			}
		}

		/**
		 * Filter is coupon is valid
		 *
		 * @since 3.0
		 * @param bool Is valid?
		 * @param MP_Coupon The current coupon object.
		 */
		return apply_filters( 'mp_coupon/is_valid', $is_valid, $this );
	}

	/**
	 * Display coupon meta value
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The name of the meta to get.
	 * @param mixed $default The default value to return if meta doesn't exist or is an empty string. Optional.
	 * @param bool $raw Whether to return the raw meta or the formatted value. Optional.
	 * @return mixed
	 */
	public function meta( $name, $default = false, $raw = false ) {
		echo $this->get_meta( $name, $default, $raw );
	}

	/**
	 * Get remaining uses
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo Optional, whether to echo or return. Defaults to echo.
	 * @param bool $numeric Optional, whether to return numeric value or string (e.g. "Unlimited" uses). Defaults to false.
	 */
	public function remaining_uses( $echo = true, $numeric = false ) {
		$max_uses	 = $this->get_meta( 'max_uses' );
		$remaining	 = ( $numeric ) ? 9999999 : __( 'Unlimited', 'mp' );

		if ( $max_uses ) {
			$max_uses	 = (float) $max_uses;
			$times_used	 = (float) $this->get_meta( 'times_used', 0 );
			$remaining	 = ($max_uses - $times_used);
		}

		if ( $echo ) {
			echo $remaining;
		} else {
			return $remaining;
		}
	}

	/**
	 * Update meta value
	 *
	 * @since 3.0
	 * @access public
	 * @param string $name The name of the meta to update.
	 * @param mixed $value The new value of the meta.
	 */
	public function update_meta( $name, $value ) {
		update_field_value( $name, $value, $this->ID );
	}

	/**
	 * Use a coupon
	 *
	 * @since 3.0
	 * @access public
	 */
	public function use_coupon() {
		$uses = (float) $this->get_meta( 'times_used', 0 ) + 1;

		update_post_meta( $this->ID, 'times_used', $uses );
		//$this->update_meta( 'times_used', $uses );
	}

}
