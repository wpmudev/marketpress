<?php

class MP_Product {
	/**
	 * Refers to the product's ID.
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	var $ID = null;
	
	/**
	 * Refers to the product's variations.
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_variations = null;
	
	/**
	 * Refers to the product's variation IDs
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_variation_ids = null;
	
	/**
	 * Refers to the product's attributes.
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $_attributes = null;
		
	/**
	 * Refers to if the product is on sale.
	 *
	 * @since 3.0
	 * @access protected
	 * @var bool
	 */
	protected $_on_sale = null;
	
	/**
	 * Refers to the product's internal WP_Post object.
	 *
	 * @since 3.0
	 * @access protected
	 * @type WP_Post
	 */
	protected $_post = null;
	
	/**
	 * Refers to the whether the product exists or not.
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
	 * Get the internal post type for products.
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public static function get_post_type() {
		return mp_get_setting('product_post_type') == 'mp_product' ? 'mp_product' : 'product';
	}
	
	/**
	 * Display the lightbox for product variations
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_product_get_variations_lightbox, wp_ajax_nopriv_mp_product_get_variations_lightbox
	 */
	public function ajax_display_variations_lightbox() {
		$product_id = mp_get_get_value('product_id');
		$product = new MP_Product($product_id);
		
		if ( ! $product->exists() ) {
			die(__('The product specified could not be found', 'mp'));
		}
		?>
<form class="mp_product_options_cb" method="post" action="<?php echo get_permalink(mp_get_setting('pages->cart')); ?>">
	<input type="hidden" name="product_id" value="<?php echo $product->ID; ?>" />
	<div class="mp_product_options_image">
		<img class="mp_product_options_thumb" src="<?php $product->image_url(true, 'medium'); ?>" />
	</div>
	<div class="mp_product_options_content">
		<h3 class="mp_product_name"><?php echo $product->post_title; ?></h3>
		<div class="mp_product_options_excerpt"><?php echo $product->excerpt(); ?></div>
		<div class="mp_product_options_atts"><?php $product->attribute_fields(); ?></div>
		<?php
			if ( mp_get_setting('product_button_type') == 'addcart') : ?>
		<button class="mp_button_addcart" type="submit" name="addcart"><?php _e('Add To Cart', 'mp'); ?></button>
		<?php
			elseif ( mp_get_setting('product_button_type') == 'buynow' ) : ?>
		<button class="mp_button_buynow" type="submit" name="buynow"><?php _e('Buy Now', 'mp'); ?></button>
		<?php
			endif; ?>
	</div>
</form>
		<?php
		die;
	}
	
	/**
	 * Update the product attributes based upon selection
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_product_update_attributes, wp_ajax_nopriv_mp_product_update_attributes
	 */
	public function ajax_update_attributes() {
		$product_atts = MP_Product_Attributes::get_instance();
		$all_atts = $product_atts->get();
		$tax_query = $filtered_atts = $taxonomies = $filtered_terms = $json = array();
		$product_id = mp_get_post_value('product_id');
		
		if ( empty($product_id) ) {
			wp_send_json_error();
		}
		
		foreach ( $_POST as $key => $val ) {
			if ( false !== strpos($key, $product_atts::SLUGBASE) ) {
				$taxonomies[] = $key;
				$tax_query[] = array(
					'taxonomy' => $key,
					'terms' => $val,
				);
			}
		}
		
		// Get variations that have all selected attributes
		$posts = get_posts(array(
			'post_type' => 'mp_product_variation',
			'posts_per_page' => -1,
			'post_parent' => mp_get_post_value('product_id'),
			'tax_query' => array('relation' => 'AND') + $tax_query,
		));
		
		// Filter out taxonomies that already have values and are still valid
		foreach ( $all_atts as $att ) {
			$slug = $product_atts->generate_slug($att->attribute_id);
			if ( ! in_array($slug, $taxonomies) ) {
				$filtered_atts[] = $slug;
			}
		}
		
		//! TODO: take into account out-of-stock variations
		
		// Make sure all attribute terms are unique
		foreach ( $posts as $post ) {
			foreach ( $filtered_atts as $tax_slug ) {
				$terms = get_the_terms($post->ID, $tax_slug);
				foreach ( $terms as $term ) {
					$filtered_terms[$tax_slug][$term->term_id] = $term;
				}
			}
		}
		
		// Format attribute terms for display
		foreach ( $filtered_terms as $tax_slug => $terms ) {
			$json[$tax_slug] = '';
			$index = 0;
			$terms = $product_atts->sort($terms, false);
			foreach ( $terms as $term ) {
				$checked = ( mp_get_post_value($tax_slug) == $term->term_id ) ? true : false;
				$required = ( $index == 0 ) ? true : false;
				$json[$tax_slug] .= self::attribute_field($term->term_id, $term->name, $tax_slug, $required, $checked);
				$index ++;
			}
		}
		
		wp_send_json_success($json);
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
	 * Display a single attribute field
	 *
	 * @since 3.0
	 * @access public
	 */
	public function attribute_field( $term_id, $term_name, $tax_slug, $required = false, $checked = false ) {
		$input_id = 'mp_product_options_att_' . $term_id;
		$class = ( $required ) ? ' class="required"' : '';
		
		$html = '
				<label class="mp_product_options_att_input_label" for="' . $input_id . '">
					<input id="' . $input_id . '"' . $class . ' type="radio" name="' . $tax_slug . '" value="' . $term_id . '"' . (( $checked ) ? ' checked' : '') . ' />
					<span>' . $term_name . '</span>
				</label>';
		
		/**
		 * Filter the attribute field
		 *
		 * @since 3.0
		 * @param string $html
		 * @param int $term_id
		 * @param string $term_name
		 * @param string $tax_slug
		 * @param bool $required		 
		 */
		$html = apply_filters('mp_product/attribute_field', $html, $term_id, $term_name, $tax_slug, $required);
		
		return $html;	
	}
	
	/**
	 * Display the attribute fields
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo
	 */
	public function attribute_fields( $echo = true ) {
		$html = '';
		$atts = $this->get_attributes();
		$selected_att = null;
		
		foreach ( $atts as $slug => $att ) {
			$html .= '
				<div class="mp_product_options_att">
					<strong class="mp_product_options_att_label">' . $att['name'] . '</strong>
					<div class="clearfix" id="mp_' . $slug . '">';
			
			$index = 0;
			foreach ( $att['terms'] as $term_id => $term_name ) {
				$html .= $this->attribute_field($term_id, $term_name, $slug, ( $index == 0 ) ? true : false);
				$index ++;
			}
			
			$html .= '
					</div>
				</div>';
		}
		
		$input_id = 'mp_product_options_att_quantity';
		$html .= '
				<div class="mp_product_options_att">
					<strong class="mp_product_options_att_label">' . __('Quantity', 'mp') . '</strong>
					<div class="clearfix">
						<label class="mp_product_options_att_input_label" for="' . $input_id . '">
							<input id="' . $input_id . '" class="required digits" type="text" name="product_quantity" value="1" />
						</label>
					</div>
				</div>';					

		
		/**
		 * Filter the attribute fields
		 *
		 * @since 3.0
		 * @param string The current html.
		 * @param MP_Product The current MP_Product object.
		 */
		$html = apply_filters('mp_product/attribute_fields', $html, $this);
		$html = apply_filters('mp_product/attribute_fields/' . $this->ID, $html, $this);
		
		if ( $echo ) {
			echo $html;
		} else {
			return $html;
		}
	}
	
	/**
	 * Get a specific variation by it's index
	 *
	 * @since 3.0
	 * @access public
	 * @param int $index Optional.
	 */
	public function get_variation( $index = 0 ) {
		$variations = $this->get_variations();
		return mp_arr_get_value($index, $variations);
	}
	
	/**
	 * Get product variation ids
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_variation_ids() {
		if ( ! is_null($this->_variation_ids) ) {
			return $this->_variation_ids;
		}
		
		$this->get_variations();
		return $this->_variation_ids;
	}
	
	/**
	 * Get product variations
	 *
	 * @since 3.0
	 * @access public
	 * @return array An array of MP_Product objects.
	 */
	public function get_variations() {
		if ( ! is_null($this->_variations) ) {
			return $this->_variations;
		}
		
		$this->_variations = array();
		if ( ! $this->get_meta('has_variations') ) {
			return $this->_variations;
		}

		$query = new WP_Query(array(
			'post_type' => 'mp_product_variation',
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'post_parent' => $this->ID,
		));		
		
		$this->_variation_ids = array();
		while ( $query->have_posts() ) : $query->the_post();
			$this->_variations[] = $variation = new MP_Product(get_the_ID());
			$this->_variation_ids[] = $variation->ID;
		endwhile;
		
		wp_reset_postdata();
		
		return $this->_variations;
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
			$button = '<form class="mp_buy_form" method="post" data-ajax-url="' . admin_url('admin-ajax.php?action=mp_update_cart') . '" action="' . mp_cart_link(false, true) . '">';

			if ( ! $this->in_stock() ) {
				$button .= '<span class="mp_no_stock">' . __('Out of Stock', 'mp') . '</span>';
			} else {
				$button .= '<input type="hidden" name="product_id" value="' . $this->ID . '" />';
				
				if ( $context == 'list' ) {
					if ( $this->has_variations() ) {
						$button .= '<a class="mp_link_buynow has_variations" data-href="' . admin_url('admin-ajax.php?action=mp_product_get_variations_lightbox&amp;product_id=' . $this->ID) . '" href="' . $this->url(false) . '">' . __('Choose Options', 'mp') . '</a>';
					} else if ( mp_get_setting('list_button_type') == 'addcart' ) {
						$button .= '<button class="mp_button_addcart" type="submit" name="addcart">' . __('Add To Cart', 'mp') . '</button>';
					} else if ( mp_get_setting('list_button_type') == 'buynow' ) {
						$button .= '<button class="mp_button_buynow" type="submit" name="buynow">' . __('Buy Now', 'mp') . '</button>';
					}
				} else {
					$button .= $variation_select;

					//add quantity field if not downloadable
					if ( mp_get_setting('show_quantity') && ! $this->is_download() ) {
						$button .= '<span class="mp_quantity"><label>' . __('Quantity:', 'mp') . ' <input class="mp_quantity_field" type="text" size="1" name="quantity" value="1" /></label></span>&nbsp;';
					}

					if ( mp_get_setting('product_button_type') == 'addcart') {
						$button .= '<button class="mp_button_addcart" type="submit" name="addcart">' . __('Add To Cart', 'mp') . '</button>';
					} else if (mp_get_setting('product_button_type') == 'buynow') {
						$button .= '<button class="mp_button_buynow" type="submit" name="buynow">' . __('Buy Now', 'mp') . '</button>';
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
		
		if ( $this->has_variations() ) {
			// Get price range
			if ( $price['lowest'] != $price['highest'] ) {
				$snippet .= '<strong class="mp_normal_price">' . mp_format_currency('', $price['lowest']) . ' - ' .  mp_format_currency('', $price['highest']) . '</strong>';
			} else {
				$snippet .= '<strong class="mp_normal_price">' . mp_format_currency('', $price['lowest'])  . '</strong>';
			}
		} elseif ( $this->on_sale() ) {
			$percent_off = round((($price['regular'] - $price['sale']['amount']) * 100) / $price['regular']) . '%';
			$snippet .= '<strike class="mp_normal_price">' . mp_format_currency('', $price['regular']) . '</strike>';
			
			if ( ($end_date = $price['sale']['end_date']) && ($days_left = $price['sale']['days_left']) ) {
				$snippet .= '<span class="mp_savings_amt">' . sprintf(__('Save: %s', 'mp'), $percent_off) . sprintf(_n(' - only 1 day left!', ' - only %s days left!', $days_left, 'mp'), $days_left) . '</span>';
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
	 * @param string $excerpt_more Optional
	 * @param string $excerpt Optional
	 * @param string $content Optional
	 * @return string
	 */	
	public function excerpt( $excerpt = null, $content = null, $excerpt_more = null ) {
		if ( is_null($excerpt_more) ) {
			$excerpt_more = ' <a class="mp_product_more_link" href="' . get_permalink($this->ID) . '">' .	 __('More Info &raquo;', 'mp') . '</a>';
		}
		
		if ( is_null($excerpt) ) {
			$excerpt = $this->has_variations() ? $this->get_variation()->post_excerpt : $this->_post->post_excerpt;
		}
		
		if ( is_null($content) ) {
			$content = $this->has_variations() ? $this->get_variation()->post_content : $this->_post->post_content;
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
			
			$text = wpautop($text);
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
			'lowest' => '',
			'highest' => '',
			'sale' => array(
				'amount' => false,
				'start_date' => false,
				'end_date' => false,
				'days_left' => false,
			),
		);
		
		if ( $this->has_variations() ) {
			$variations = $this->get_variations();
			$prices = array();
			
			foreach ( $variations as $variation ) {
				$price = $variation->get_price();
				
				if ( $variation->on_sale() ) {
					$prices[] = $price['sale']['amount'];
				} else {
					$prices[] = $price['regular'];
				}
			}
			
			$price['lowest'] = min($prices);
			$price['highest'] = max($prices);
		} elseif ( $this->on_sale() && ($sale_price = $this->get_meta('sale_price_amount')) ) {
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
		if ( $post_id != $this->ID ) {
			$this->ID = $post_id;
			$this->_post = $post = get_post($post_id);
		}
		
		$image_post_id = $post_id;
		$post_thumbnail_id = get_post_thumbnail_id($post_id);
		$class = $title = $link = $img_align = '';
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
				$link_class = ' class="mp_img_link"';
				$img_align = is_null($align) ? mp_get_setting('image_alignment_list') : $align;
				
				if ( $this->has_variations() ) {
					$image_post_id = $this->get_variation()->ID;
				}
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
				// size
				if ( mp_get_setting('product_img_size') == 'custom' ) {
					$size = array(mp_get_setting('product_img_size_custom->width'), mp_get_setting('product_img_size_custom->height'));
				} else {
					$size = mp_get_setting('product_img_size');
				}

				if ( mp_get_setting('disable_large_image') ) {
					$link = '';
					$title = esc_attr($post->post_title);
				} else {
					$temp = wp_get_attachment_image_src($post_thumbnail_id, 'large');
					$link = $temp[0];
					$title = __('View Larger Image &raquo;', 'mp');
				}

				$link_class = ' class="mp_product_image_link mp_lightbox"';
				$img_align = is_null($align) ? mp_get_setting('image_alignment_single') : $align;
				
				// Get variant images
				if ( $this->has_variations() ) {
					$variant_images = array();
					$variations = $this->get_variations();
					$variant_images[] = '
						<a class="mp_variant_image_link selected" href="' . $this->image_url(false) . '">
							<img width="40" height="40" src="' . $this->image_url(false, 40) . '" alt="" />
							<span class="mp_variant_alt_image"><img src="' . $this->image_url(false, $size) . '" alt="" /></span>
							<div class="mp_variant_alt_content">' . apply_filters('the_content', $this->post_content) . '</div>
						</a>';
					
					foreach ( $variations as $variation ) {
						$variant_images[] = '
							<a class="mp_variant_image_link" href="' . $variation->image_url(false) . '">
								<img width="40" height="40" src="' . $variation->image_url(false, 40) . '" alt="" />
								<span class="mp_variant_alt_image"><img src="' . $variation->image_url(false, $size) . '" alt="" /></span>
								<div class="mp_variant_alt_content">' . (( strlen($variation->post_content) > 0 ) ? apply_filters('the_content', $variation->post_content) : apply_filters('the_content', $this->post_content)) . '</div>
							</a>';
					}
				}
				
				// in case another plugin is loadin glightbox
				if ( mp_get_setting('show_lightbox') ) {
					$link_class .= ' rel="lightbox"';
					wp_enqueue_script('mp-lightbox');
				}
			break;
			
			case 'widget' :
				//size
				if ( $size = intval($size) ) {
				 $size = array($size, $size);
				} else {
					$size = array(50, 50);
				}

				//link
				$link = get_permalink($post_id);
				$link_class = ' class="mp_img_link"';
				
				$title = esc_attr($post->post_title);
			break;
		}
		
		$image = get_the_post_thumbnail($image_post_id, $size, array('itemprop' => 'image', 'class' => implode(' ', $img_classes), 'title' => $title));

		if ( empty($image) ) {
			if ( $context == 'floating-cart' ) {
				$image = '<img width="' . $size[0] . '" height="' . $size[1] . '" class="' . implode(' ', $img_classes) . '" src="' . apply_filters('mp_default_product_img', mp_plugin_url('ui/images/default-product.png')) . '" />';
			} else {
				if ( ! is_array($size) ) {
					$size = array(get_option($size . '_size_w'), get_option($size . '_size_h'));
				}
				
				$img_classes[] = 'wp-post-image';
				$image = '<img width="' . $size[0] . '" height="' . $size[1] . '" itemprop="image" title="' . esc_attr($title) . '" class="' . implode(' ', $img_classes) . '" src="' . apply_filters('mp_default_product_img', mp_plugin_url('ui/images/default-product.png')) . '" />';
			}
		}
		
		//force ssl on images (if applicable) http://wp.mu/8s7
		if ( is_ssl() ) {
			$image = str_replace('http://', 'https://', $image);
		}

		$snippet = '
			<div itemscope class="hmedia' . ( empty($img_align) ? '' : " $img_align") . '">
				<div style="display:none"><span class="fn">' . get_the_title(get_post_thumbnail_id()) . '</span></div>';
				
		if ( $link ) {
			$snippet .= '<a rel="lightbox enclosure" id="product_image-' . $post_id . '"' . $link_class . ' href="' . $link . '">' . $image . '</a>';
		} else {
			$snippet .= $image;
		}
		
		if ( ! empty($variant_images) ) {
			$snippet .= apply_filters('mp_product_variant_images', '<div class="mp_product_variant_images clearfix">' . implode('', $variant_images) . '</div>', $variations, $post_id);
		}

		$snippet .= '
			</div>';
		
		$snippet = apply_filters('mp_product_image', $snippet, $context, $post_id, $size);

		if ( $echo ) {
			echo $snippet;
		} else {
			return $snippet;	
		}	
	}
	
	/**
	 * Get the product image url
	 *
	 * @since 3.0
	 * @access public
	 * @param bool $echo
	 * @param string/int $size
	 */
	public function image_url( $echo = true, $size = 'large' ) {
		if ( $thesize = intval($size) ) {
			$size = array($thesize, $thesize);
		}
		
		$post_id = $this->ID;
		if ( $this->has_variations() ) {
			$post_id = $this->get_variation()->ID;
		}
		
		if ( has_post_thumbnail($post_id) ) {
			$img_id = get_post_thumbnail_id($post_id);
			$img_src = array_shift(wp_get_attachment_image_src($img_id, $size));
		}
		
		if ( empty($img_src) ) {
			$img_src = mp_plugin_url('ui/images/default-product.png');
		}
		
		if ( $echo ) {
			echo $img_src;
		} else {
			return $img_src;
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
	 * Get the product attributes
	 *
	 * @since 3.0
	 * @access public
	 * @return array
	 */
	public function get_attributes() {
		if ( ! is_null($this->_attributes) ) {
			return $this->_attributes;
		}
		
		$mp_product_atts = MP_Product_Attributes::get_instance();
		$all_atts = $mp_product_atts->get();
		$this->_attributes = array();
		
		$ids = array($this->ID);
		if ( $this->has_variations() ) {
			$ids = $this->get_variation_ids();
		}
		
		$taxonomies = array();
		foreach ( $all_atts as $att ) {
			$taxonomies[] = $mp_product_atts->generate_slug($att->attribute_id);
		}
		
		$terms = wp_get_object_terms($ids, $taxonomies);
		$terms_sorted = $mp_product_atts->sort($terms);
		$names = array();
		foreach ( $terms_sorted as $tax_slug => $terms ) {
			$tax_id = $mp_product_atts->get_id_from_slug($tax_slug);
			
			foreach ( $terms as $term ) {
				if ( $att = $mp_product_atts->get_one($tax_id) ) {
					if ( ! array_key_exists($term->taxonomy, $names) ) {
						mp_push_to_array($this->_attributes, "{$term->taxonomy}->name", $att->attribute_name);
					}
					
					mp_push_to_array($this->_attributes, "{$term->taxonomy}->terms->{$term->term_id}", $term->name);
				}
			}
		}
		
		return $this->_attributes;
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
				
		// Try to use regular post meta
		$meta_val = get_post_meta($this->ID, $name, true);
		if ( $meta_val !== '' ) {
			return $meta_val;
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