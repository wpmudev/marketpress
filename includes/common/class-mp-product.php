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
	 */
 	function buy_button( $echo = true, $context = 'list' ) {
		// Display an external link
		$button = '';
		if ( $this->get_meta('product_type') == 'external' && ($url = $this->get_meta('external_url')) ) {
			$button = '<a class="mp_link_buynow" href="' . esc_url($url) . '">' . __('Buy Now &raquo;', 'mp') . '</a>';
		} elseif ( ! mp_get_setting('disable_cart') ) {
			$variation_select = $variation_opts = false;
			$button = '<form class="mp_buy_form" method="post" data-ajax-url="' . admin_url('admin-ajax.php?action=mp_update_cart') . '" action="' . mp_cart_link(false, true) . '">';

			if ( ! $this->in_stock() ) {
				$button .= '<span class="mp_no_stock">' . __('Out of Stock', 'mp') . '</span>';
			} else {
				$button .= '<input type="hidden" name="product_id" value="' . $this->ID . '" />';
				
				if ( $this->has_variations() ) {
					$variation_select = '<select class="mp_product_variations" name="variation">';
					$variations = $this->get_variations();
					$variation_opts = array();
					
					foreach ( $variations as $variation ) {
						$price_obj = $variation->get_price();
						
						if ( $variation->on_sale() ) {
							$price = mp_format_currency('', $price_obj['sale']['amount']);
						} else {
							$price = mp_format_currency('', $price_obj['regular']);
						}
						
						$variation_opts[$variation->ID] = (object) array(
							'post' => $variation,
							'price' => $price,
						);
						$variation_select .= '<option value="' . $variation->ID . '"' . (( $variation->in_stock() ) ? '' : ' disabled="disabled"') . '">' . esc_attr($variation->get_meta('name')) . ' - ' . $price . '</option>';
					}
					
					$variation_select .= '</select>';
				}
				
				if ( $context == 'list' ) {
					if ( $variation_select ) {
						if ( mp_get_setting('store_theme') == 'default3' ) {
							$button .= '<div class="mp_link_buynow has_variations">' . __('Choose Option', 'mp');
							$button .= '<ul class="mp_variations_flyout">';
							
							foreach ( $variation_opts as $id => $variation ) {
								$button .= '<li class="mp_variation_flyout_item"><a class="clearfix" data-product-id="' . $id . '" href="' . $variation->post->url(false) . '"><strong class="mp_variation_flyout_name">' . $variation->post->get_meta('name') . '</strong><span class="mp_variation_flyout_price">' . $variation->price . '</span></a></li>';
							}
							
							$button .= '</ul>';
							$button .= '</div>';
						} else {
							$button .= '<a class="mp_link_buynow" href="' . $this->url(false) . '">' . __('Choose Option', 'mp') . '</a>';
						}
					} else if ( mp_get_setting('list_button_type') == 'addcart' ) {
						$button .= '<input class="mp_button_addcart" type="submit" name="addcart" value="' . __('Add To Cart', 'mp') . '" />';
					} else if ( mp_get_setting('list_button_type') == 'buynow' ) {
						$button .= '<input class="mp_button_buynow" type="submit" name="buynow" value="' . __('Buy Now', 'mp') . '" />';
					}
				} else {
					$button .= $variation_select;

					//add quantity field if not downloadable
					if ( mp_get_setting('show_quantity') && ! $this->is_download() ) {
						$button .= '<span class="mp_quantity"><label>' . __('Quantity:', 'mp') . ' <input class="mp_quantity_field" type="text" size="1" name="quantity" value="1" /></label></span>&nbsp;';
					}

					if ( mp_get_setting('product_button_type') == 'addcart') {
						$button .= '<input class="mp_button_addcart" type="submit" name="addcart" value="' . __('Add To Cart &raquo;', 'mp') . '" />';
					} else if (mp_get_setting('product_button_type') == 'buynow') {
						$button .= '<input class="mp_button_buynow" type="submit" name="buynow" value="' . __('Buy Now &raquo;', 'mp') . '" />';
					}
				}
			}

			$button .= '</form>';
		}

		$button = apply_filters('mp_buy_button_tag', $button, $this->ID, $context);

		if ( $echo ) {
			echo $button;
		} else {
			return $button;
		}
	}
	
	/**
	 * Get the display product price
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo
	 */
	public function display_price( $echo = true ) {
		$price = $this->get_price();
		$snippet = '<div class="mp_product_price" itemtype="http://schema.org/Offer" itemscope="" itemprop="offers">';
		
		if ( $this->on_sale() ) {
			$percent_off = round((($price['regular'] - $price['sale']['amount']) * 100) / $price['regular']) . '%';
			$snippet .= '<strike class="mp_normal_price">' . mp_format_currency('', $price['regular']) . '</strike>';
			
			if ( ($end_date = $price['sale']['end_date']) && ($days_left = $price['sale']['days_left']) ) {
				$snippet .= '<span class="mp_savings_amt">' . sprintf(__('Save: %s - only %s days left!', 'mp'), $percent_off, $days_left) . '</span>';
			} else {
				$snippet .= '<span class="mp_savings_amt">' . sprintf(__('Save: %s', 'mp'), $percent_off) . '</span>';
			}
			
			$snippet .= '<strong class="mp_sale_price" itemprop="price">' . mp_format_currency('', $price['sale']['amount']) . '</strong>';
		} else {
			$snippet .= '
			<strong class="mp_normal_price" itemprop="price">' . mp_format_currency('', $price['regular']) . '</strong>';
		}
		
		$snippet .= '</div>';
		
		/**
		 * Filter the display price of the product
		 *
		 * @since 3.0
		 * @param string The current display price text
		 * @param array The current price object
		 * @param int The product ID
		 */
		$snippet = apply_filters('mp_product/display_price', $snippet, $price, $this->ID);
		
		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
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
			'regular' => (float) $this->get_meta('regular_price'),
			'sale' => array(
				'amount' => false,
				'start_date' => false,
				'end_date' => false,
				'days_left' => false,
			),
		);
		
		if ( $this->on_sale() && ($sale_price = $this->get_meta('sale_price_amount')) ) {
			$start_date_obj = new DateTime($this->get_meta('sale_price_start_date', date('Y-m-d'), true));
			$days_left = false;
			
			if ( method_exists($start_date_obj, 'diff') ) {
				// The diff method is only available PHP version >= 5.3
				$end_date_obj = new DateTime($this->get_meta('sale_price_end_date', date('Y-m-d'), true));
				$diff = $start_date_obj->diff($end_date_obj);
				$days_left = $diff->d;
				
				/**
				 * Filter the maximum number of days before the "only x days left" nag shows
				 *
				 * @since 3.0
				 * @param int The default number of days
				 */
				$days_limit = apply_filters('mp_product/get_price/days_left_limit', 7);
				
				if ( $days_left > $days_limit ) {
					$days_left = false;
				}
			}
			
			$price['sale'] = array(
				'amount' => (float) $sale_price,
				'start_date' => $this->get_meta('sale_price_start_date', false),
				'end_date' => $this->get_meta('sale_price_end_date', false),
				'days_left' => $days_left,
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
			
			case 'floating-cart' :
				$img_classes = array('mp-floating-cart-item-image');
				
				if ( $size = intval($size) ) {
					$size = array($size, $size);
				} else {
					$size = array(50, 50);
				}
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

		if ( empty($image) ) {
			if ( $context == 'floating-cart' ) {
				$image = '<img width="' . $size[0] . '" height="' . $size[1] . '" class="' . implode(' ', $img_classes) . '" src="' . apply_filters('mp_default_product_img', mp_plugin_url('ui/images/default-product.png')) . '" />';
			} elseif ( $context != 'single' ) {
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
	 * Check if product is in stock
	 *
	 * @since 3.0
	 * @access public
	 */
	public function in_stock() {
		$track_inventory = $this->get_meta('track_inventory');
		if ( ($track_inventory && $this->get_meta('inventory')) || ! $track_inventory ) {
			return true;
		}
		
		$has_stock = false;
		if ( $this->has_variations() ) {
			$variations = $this->get_variations();		
			foreach ( $variations as $variation ) {
				if ( $variation->get_meta('track_inventory') && ! $variation->get_meta('inventory') ) {
					$has_stock = false;	
				} else {
					$has_stock = true;
					break;
				}
			}
		}
		
		return $has_stock;
	}
	
	/**
	 * Check if the product is a digital download
	 *
	 * @since 3.0
	 * @access public
	 * @return bool
	 */
	public function is_download() {
		return ( $this->get_meta('product_type') == 'digital' && $this->get_meta('file_url') );
	}

	/**
	 * Check if the product is a variation of another product
	 *
	 * @since 3.0
	 * @access public
	 */
	public function is_variation() {
		return ( ! empty($this->_post->post_parent) );
	}
		
	/**
	 * Get product meta value
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
				
		$parent_id = $this->_post->post_parent;
		if ( ! empty($parent_id) ) {
			// This is a variation, try to use WPMUDEV_Field value from parent product	
			if ( function_exists('get_field_value') ) {
				$value = get_field_value($name, $parent_id, $raw);
			}
		}
		
		if ( $value !== false && $value !== '' ) {
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
	 * @param mixed $default The default value to return if meta doesn't exist or is an empty string. Optional.
	 * @param bool $raw Whether to return the raw meta or the formatted value. Optional.
	 * @return mixed
	 */
	public function meta( $name, $default = false, $raw = false ) {
		echo $this->get_meta($name, $default, $raw);
	}
	
	/**
	 * Get the product's url
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo
	 */
	public function url( $echo = true ) {
		if ( $this->is_variation() ) {
			$url = get_permalink($this->_post->post_parent) . '#!variation=' . $this->ID;
		} else {
			$url = get_permalink($this->ID);
		}
		
		if ( $echo ) {
			echo $url;
		} else {
			return $url;
		}
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
	
		$image_info =	wp_get_attachment_image_src(get_post_thumbnail_id($this->ID), 'large');
		$count_pos = ( $pos = mp_get_setting('social->pinterest->show_pin_count') ) ? $pos : 'none';
		$url = add_query_arg(array(
			'url' => get_permalink($this->ID),
			'description' => get_the_title($this->ID),
		), '//www.pinterest.com/pin/create/button/');
		
		if ( $media = mp_arr_get_value('0', $image_info) ) {
			$url = add_query_arg('media',  $media, $url);	
		}
		
		$snippet = apply_filters('mp_pinit_button_link', '<a target="_blank" href="' . $url . '" data-pin-do="buttonPin" data-pin-config="' . $count_pos . '"><img src="//assets.pinterest.com/images/pidgets/pin_it_button.png" /></a>', $this->ID, $context);
	
		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;
		}
	}

	/**
	 * Get the product title
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo
	 */
	public function title( $echo = true ) {
		if ( $this->is_variation() ) {
			$title = get_the_title($this->_post->post_parent) . ' - ' . $this->get_meta('name');
		} else {
			$title = $this->_post->post_title;
		}
		
		if ( $echo ) {
			echo $title;
		} else {
			return $title;
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