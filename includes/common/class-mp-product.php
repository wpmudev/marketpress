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
	 * @uses $post
	 * @param int/WP_Post $product Optional if in the loop
	 */
	public function __construct( $product = null ) {
		if ( is_null($product) && in_the_loop() ) {
			global $post;
			$product = $post;
		}
		
		if ( $product instanceof WP_Post ) {
			$this->ID = $product->ID;
			$this->_post = $product;
			$this->_exists = true;
			$this->_post_queried = true;
		} elseif ( is_numeric($product) ) {
			$this->ID = $product;
			$this->_get_post();
		}
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

	/*
	 * Displays the buy or add to cart button
	 *
	 * @param bool $echo Optional, whether to echo
	 * @param string $context Options are list or single
	 * @param int $post_id The post_id for the product. Optional if in the loop
	 */
 	function buy_button( $echo = true, $context = 'list' ) {
 		$post_id = $this->ID;

		$meta = (array) get_post_custom($post_id);
		//unserialize
		foreach ($meta as $key => $val) {
				$meta[$key] = maybe_unserialize($val[0]);
				if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file")
						$meta[$key] = array($meta[$key]);
		}

		//check stock
		$no_inventory = array();
		$all_out = false;
		if ($meta['mp_track_inventory']) {
				$cart = mp()->get_cart_contents();
				if (isset($cart[$post_id]) && is_array($cart[$post_id])) {
						foreach ($cart[$post_id] as $variation => $data) {
								if ($meta['mp_inventory'][$variation] <= $data['quantity'])
										$no_inventory[] = $variation;
						}
						foreach ($meta['mp_inventory'] as $key => $stock) {
								if (!in_array($key, $no_inventory) && $stock <= 0)
										$no_inventory[] = $key;
						}
				}

				//find out of stock items that aren't in the cart
				foreach ($meta['mp_inventory'] as $key => $stock) {
						if (!in_array($key, $no_inventory) && $stock <= 0)
								$no_inventory[] = $key;
				}

				if (count($no_inventory) >= count($meta["mp_price"]))
						$all_out = true;
		}

		//display an external link or form button
		if (isset($meta['mp_product_link']) && $product_link = $meta['mp_product_link']) {

				$button = '<a class="mp_link_buynow" href="' . esc_url($product_link) . '">' . __('Buy Now &raquo;', 'mp') . '</a>';
		} else if (mp_get_setting('disable_cart')) {

				$button = '';
		} else {
				$variation_select = '';
				$button = '<form class="mp_buy_form" method="post" action="' . mp_cart_link(false, true) . '">';

				if ($all_out) {
						$button .= '<span class="mp_no_stock">' . __('Out of Stock', 'mp') . '</span>';
				} else {

						$button .= '<input type="hidden" name="product_id" value="' . $post_id . '" />';

						//create select list if more than one variation
						if (is_array($meta["mp_price"]) && count($meta["mp_price"]) > 1 && empty($meta["mp_file"])) {
								$variation_select = '<select class="mp_product_variations" name="variation">';
								foreach ($meta["mp_price"] as $key => $value) {
										$disabled = (in_array($key, $no_inventory)) ? ' disabled="disabled"' : '';
										$variation_select .= '<option value="' . $key . '"' . $disabled . '>' . esc_html($meta["mp_var_name"][$key]) . ' - ';
										if ($meta["mp_is_sale"] && $meta["mp_sale_price"][$key]) {
												$variation_select .= mp_format_currency('', $meta["mp_sale_price"][$key]);
										} else {
												$variation_select .= mp_format_currency('', $value);
										}
										$variation_select .= "</option>\n";
								}
								$variation_select .= "</select>&nbsp;\n";
						} else {
								$button .= '<input type="hidden" name="variation" value="0" />';
						}

						if ($context == 'list') {
								if ($variation_select) {
										$button .= '<a class="mp_link_buynow" href="' . get_permalink($post_id) . '">' . __('Choose Option &raquo;', 'mp') . '</a>';
								} else if (mp_get_setting('list_button_type') == 'addcart') {
										$button .= '<input type="hidden" name="action" value="mp-update-cart" />';
										$button .= '<input class="mp_button_addcart" type="submit" name="addcart" value="' . __('Add To Cart &raquo;', 'mp') . '" />';
								} else if (mp_get_setting('list_button_type') == 'buynow') {
										$button .= '<input class="mp_button_buynow" type="submit" name="buynow" value="' . __('Buy Now &raquo;', 'mp') . '" />';
								}
						} else {

								$button .= $variation_select;

								//add quantity field if not downloadable
								if (mp_get_setting('show_quantity') && empty($meta["mp_file"])) {
										$button .= '<span class="mp_quantity"><label>' . __('Quantity:', 'mp') . ' <input class="mp_quantity_field" type="text" size="1" name="quantity" value="1" /></label></span>&nbsp;';
								}

								if (mp_get_setting('product_button_type') == 'addcart') {
										$button .= '<input type="hidden" name="action" value="mp-update-cart" />';
										$button .= '<input class="mp_button_addcart" type="submit" name="addcart" value="' . __('Add To Cart &raquo;', 'mp') . '" />';
								} else if (mp_get_setting('product_button_type') == 'buynow') {
										$button .= '<input class="mp_button_buynow" type="submit" name="buynow" value="' . __('Buy Now &raquo;', 'mp') . '" />';
								}
						}
				}

				$button .= '</form>';
		}

		$button = apply_filters('mp_buy_button_tag', $button, $post_id, $context);

		if ($echo)
				echo $button;
		else
				return $button;
	}

	/**
	 * Get the product's excerpt
	 *
	 * @since 3.0
	 * @param string $excerpt
	 * @param string $content
	 * @param string $excerpt_more Optional
	 * @return string
	 */	
	public function excerpt( $excerpt, $content, $excerpt_more = null ) {
		if ( is_null($excerpt_more) ) {
			$excerpt_more = ' <a class="mp_product_more_link" href="' . get_permalink($this->ID) . '">' .	 __('More Info &raquo;', 'mp') . '</a>';
		}
		
		if ( $excerpt ) {
			return apply_filters('get_the_excerpt', $excerpt) . $excerpt_more;
		} else {
			$text = strip_shortcodes($content);
			$text = str_replace(']]>', ']]&gt;', $text);
			$text = strip_tags($text);
			$excerpt_length = apply_filters('excerpt_length', 55);
			$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
			
			if ( count($words) > $excerpt_length ) {
				array_pop($words);
				$text = implode(' ', $words);
				$text = $text . $excerpt_more;
			} else {
				$text = implode(' ', $words);
			}
		}
		
		/**
		 * Filter the product excerpt
		 *
		 * @since 3.0
		 * @param string $text
		 * @param string $excerpt
		 * @param string $content
		 * @param int $product_id
		 * @param string $excerpt_more Optional
		 */
		return apply_filters('mp_product/excerpt', $text, $excerpt, $content, $this->id, $excerpt_more);
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
			$on_sale = true;
			
			if ( $start_date && $time < $start_date ) {
				$on_sale = false;
			} elseif ( $end_date && $time > $end_date ) {
				$on_sale = false;
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

	/*
	 * Get the product image
	 *
	 * @since 3.0
	 * @param bool $echo Optional, whether to echo
	 * @param string $context Options are list, single, or widget
	 * @param int $size An optional width/height for the image if contect is widget
	 * @param string $align The alignment of the image. Defaults to settings.
	 */
	public function image( $echo = true, $context = 'list', $size = null, $align = null ) {
		/**
		 * Filter the post_id used for the product image
		 *
		 * @since 3.0
		 * @param int $post_id
		 */
		$post_id = apply_filters('mp_product_image_id', $this->ID);
		$post = $this->_post;

		$post_thumbnail_id = get_post_thumbnail_id($post_id);
		$class = $title = $link = '';
		$img_classes = array('mp_product_image_' . $context, 'photo');
		
		if ( ! is_null($align) ) {
			$align = 'align' . $align;
		}

		switch ( $context ) {
			case 'list' :
				if ( ! mp_get_setting('show_thumbnail') ) {
					return '';
				}
	
				//size
				if ( intval($size) ) {
					$size = array(intval($size), intval($size));
				} else {
					if ( mp_get_setting('list_img_size') == 'custom' ) {
						$size = array(mp_get_setting('list_img_size_custom->width'), mp_get_setting('list_img_size_custom->height'));
					} else {
						$size = mp_get_setting('list_img_size');
					}
				}
	
				$link = get_permalink($post_id);
				$title = esc_attr($post->post_title);
				$class = ' class="mp_img_link"';
				$img_classes[] = is_null($align) ? mp_get_setting('image_alignment_list') : $align;
			break;
			
			case 'single' :
				//size
				if ( mp_get_setting('product_img_size') == 'custom' ) {
					$size = array(mp_get_setting('product_img_size_custom->width'), mp_get_setting('product_img_size_custom->height'));
				} else {
					$size = mp_get_setting('product_img_size');
				}

				//link
				$temp = wp_get_attachment_image_src($post_thumbnail_id, 'large');
				$link = $temp[0];

				if ( mp_get_setting('disable_large_image') ) {
					$link = '';
					$title = esc_attr($post->post_title);
				} else {
					$title = __('View Larger Image &raquo;', 'mp');
				}

				$class = ' class="mp_product_image_link mp_lightbox"';
				$img_classes[] = is_null($align) ? mp_get_setting('image_alignment_single') : $align;
				
				//in case another plugin is loadin glightbox
				if ( mp_get_setting('show_lightbox') ) {
					$class .= ' rel="lightbox"';
					wp_enqueue_script('mp-lightbox');
				}
			break;
			
			case 'widget' :
				//size
				if (intval($size))
						$size = array(intval($size), intval($size));
				else
						$size = array(50, 50);

				//link
				$link = get_permalink($post_id);

				$title = esc_attr($post->post_title);
				$class = ' class="mp_img_link"';
			break;
		}

		$image = get_the_post_thumbnail($post_id, $size, array('itemprop' => 'image', 'class' => implode(' ', $img_classes), 'title' => $title));

		if ( empty($image) && $context != 'single' ) {
			if ( ! is_array($size) ) {
					$size = array(get_option($size . '_size_w'), get_option($size . '_size_h'));
			}
			
			$img_classes[] = 'wp-post-image';
			$image = '
				<div itemscope class="hmedia">
					<div style="display:none"><span class="fn">' . get_the_title(get_post_thumbnail_id()) . '</span></div>
					<img width="' . $size[0] . '" height="' . $size[1] . '" itemprop="image" title="' . esc_attr($title) . '" class="' . implode(' ', $img_classes) . '" src="' . apply_filters('mp_default_product_img', mp_plugin_url('ui/images/default-product.png')) . '" />
				</div>';
		}
		
		//force ssl on images (if applicable) http://wp.mu/8s7
		if ( is_ssl() ) {
			$image = str_replace('http://', 'https://', $image);
		}

		//add the link
		if ( $link ) {
			$image = '
				<div itemscope class="hmedia">
					<div style="display:none"><span class="fn">' . get_the_title(get_post_thumbnail_id()) . '</span></div>
					<a rel="lightbox enclosure" id="product_image-' . $post_id . '"' . $class . ' href="' . $link . '">' . $image . '</a>
				</div>';
		}

		$image = apply_filters('mp_product_image', $image, $context, $post_id, $size);

		if ( $echo ) {
			echo $image;
		} else {
			return $image;	
		}	
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
		
		$value = null;
		
		if ( function_exists('get_field_value') ) {
			// Try to get WPMUDEV_Field value
			$value = get_field_value($name, $this->ID, $raw);
		}
		
		if ( ! is_null($value) ) {
			return $value;
		}
		
		$parent_id = $this->_post->post_parent;
		if ( ! empty($parent_id) ) {	
			// This is a variation, try to use WPMUDEV_Field value from parent product	
			if ( function_exists('get_field_value') ) {
				$value = get_field_value($name, $parent_id, $raw);
			}
		}
		
		if ( ! is_null($value) ) {
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
	 * Get Pinterest PinIt button
	 *
	 * @since 3.0
	 * @param string $context
	 * @param bool $echo
	 */
	public function pinit_button( $context = 'single_view', $echo = false ) {
		$setting = mp_get_setting('social->pinterest->show_pinit_button');
		
		if ( $setting == 'off' || $setting != $context ) {
			return '';
		}
	
		$url = urlencode(get_permalink($this->ID));
		$desc = urlencode(get_the_title($this->ID));
		$image_info =	$large_image_url = wp_get_attachment_image_src(get_post_thumbnail_id($this->ID), 'large');
		$media = ($image_info) ?	 '&media=' . urlencode($image_info[0]) : '';
		$count_pos = ( $pos = mp_get_setting('social->pinterest->show_pin_count') ) ? $pos : 'none';
		$snippet = apply_filters('mp_pinit_button_link', '<a href="//www.pinterest.com/pin/create/button/?url=' . $url . $media . '&description=' . $desc.  '" data-pin-do="buttonPin" data-pin-config="' . $count_pos . '"><img src="//assets.pinterest.com/images/pidgets/pin_it_button.png" /></a>', $this->ID, $context);
	
		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
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