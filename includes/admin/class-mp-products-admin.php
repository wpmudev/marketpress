<?php

class MP_Products_Screen {
	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;
	
	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Products_Screen();
		}
		return self::$_instance;
	}

	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		// Remove add-new submenu item from store admin menu
		add_action('admin_menu', array(&$this, 'remove_menu_items'), 999);
		// Product variations save/get value
		add_filter('wpmudev_field_save_value_variations', array(&$this, 'save_product_variations'), 10, 3);
		add_filter('wpmudev_field_get_value_variations', array(&$this, 'get_product_variations'), 10, 4);
				
		// Init metaboxes
		$this->init_product_details_metabox();
		$this->init_variations_metabox();
		$this->init_attributes_metabox();	
	}
		
	/**
	 * Remove add-new submenu item from store admin menu
	 *
	 * @since 3.0
	 * @access public
	 */
	public function remove_menu_items() {
		remove_submenu_page('edit.php?post_type=product', 'post-new.php?post_type=product');
	}
	
	/**
	 * Gets the product variations from the database and formats for repeater field
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field_get_value_variations
	 */
	public function get_product_variations( $value, $post_id, $raw, $field ) {
		$variations = new WP_Query(array(
			'post_type' => 'product_variation',
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'post_parent' => $post_id
		));
		$data = array();
		
		while ( $variations->have_posts() ) : $variations->the_post();
			$meta = array();
			$variation_id = get_the_ID();
			
			foreach ( $field->subfields as $subfield ) {
				$meta[$subfield->args['original_name']] = $subfield->get_value($variation_id, $subfield->args['original_name']);
			}
			
			$data[] = array_merge(array('ID' => $variation_id), $meta);
		endwhile;
		
		wp_reset_postdata();
		
		return $data;
	}
	
	/**
	 * Saves the product variations to the database
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field_save_value_variations
	 * @uses $wpdb
	 */
	public function save_product_variations( $value, $post_id, $field ) {
		global $wpdb;
		
		$variations = mp_get_post_value('variations', array());
		$sorted = $field->sort_subfields($variations);
		$outer_index = 0;
		$ids = array();
		$where = "{$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->posts}.post_parent = $post_id";
		
		if ( mp_get_post_value('has_variations', false) ) {
			foreach ( $sorted as $type => $array ) {
				foreach ( $array as $variation_id => $fields ) {
					switch ( $type ) {
						case 'new' :
							$variation_id = $ids[] = wp_insert_post(array(
								'post_content' => mp_arr_get_value('description', $fields, ''),
								'post_title' => 'Product Variation of ' . $post_id,
								'post_status' => 'publish',
								'post_type' => 'product_variation',
								'post_parent' => $post_id,
								'menu_order' => $outer_index,
							));
						break;
						
						case 'existing' :
							$ids[] = $variation_id;
							wp_update_post(array(
								'ID' => $variation_id,
								'post_content' => mp_arr_get_value('description', $fields, ''),
								'post_status' => 'publish',
								'menu_order' => $outer_index,
							));
						break;
					}
					
					// Unset the fields that shouldn't be saved as post meta
					unset($fields['description']);
							
					$index = 0;
					foreach ( $fields as $name => $value ) {
						$subfield = $field->subfields[$index];
						$subfield->save_value($variation_id, $name, $value, true);
						
						if ( strpos($name, 'product_attr_') !== false ) {
							wp_set_post_terms($variation_id, $subfield->sanitize_for_db($value), $name);	
						}
						
						$index ++;
					}
					
					$outer_index ++;
				}
			}
			
			$where .= " AND {$wpdb->posts}.ID NOT IN (" . implode(',', $ids) . ")";
		}
		
		// Delete variations that no longer exists
		$wpdb->query("
			DELETE FROM $wpdb->posts
			USING $wpdb->posts
			INNER JOIN $wpdb->postmeta
			WHERE $where"
		);
		
		return null; // Returning null will bypass internal save mechanism
	}

	/**
	 * Initializes the product details metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_product_details_metabox() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-product-details-metabox',
			'title' => __('Product Details', 'mp'),
			'post_type' => 'product',
			'context' => 'normal',
		));
		$metabox->add_field('radio_group', array(
			'name' => 'product_type',
			'label' => array('text' => __('Product Type', 'mp')),
			'default_value' => 'physical',
			'options' => array(
				'physical' => __('Physical/Tangible Product', 'mp'),
				'digital' => __('Digital Download', 'mp'),				
				'external' => __('External/Affiliate Link', 'mp'),
			),
		));
		$metabox->add_field('text', array(
				'name' => 'sku',
				'label' => array('text' => __('SKU', 'mp')),
			));
		$metabox->add_field('text', array(
			'name' => 'inventory',
			'label' => array('text' => __('Inventory Count', 'mp')),
			'desc' => __('Enter the quantity that you have available to sell. Leave blank if you do not want to track inventory.', 'mp'),
			'conditional' => array(
				'name' => 'product_type',
				'value' => 'physical',
				'action' => 'show',
			),
		));
		$metabox->add_field('text', array(
			'name' => 'regular_price',
			'label' => array('text' => __('Regular Price', 'mp')),
			'conditional' => array(
				'name' => 'variation_type',
				'value' => array('physical', 'digital'),
				'action' => 'show',
			),
		));
		$sale_price = $metabox->add_field('complex', array(
			'name' => 'sale_price',
			'label' => array('text' => __('Sale Price', 'mp')),
			'conditional' => array(
				'name' => 'product_type',
				'value' => array('physical', 'digital'),
				'action' => 'show',
			),												
		));
		
		if ( $sale_price instanceof WPMUDEV_Field ) {
			$sale_price->add_field('text', array(
				'name' => 'price',
				'label' => array('text' => __('Price', 'mp')),
			));
			$sale_price->add_field('datepicker', array(
				'name' => 'start_date',
				'label' => array('text' => __('Start Date', 'mp')),
			));
			$sale_price->add_field('datepicker', array(
				'name' => 'end_date',
				'label' => array('text' => __('End Date (if applicable)', 'mp')),
			));
		}
		
		$weight = $metabox->add_field('complex', array(
			'name' => 'weight',
			'label' => array('text' => __('Weight', 'mp')),
			'conditional' => array(
				'name' => 'product_type',
				'value' => 'physical',
				'action' => 'show',
			),				
		));
		
		if ( $weight instanceof WPMUDEV_Field ) {
			$weight->add_field('text', array(
				'name' => 'pounds',
				'label' => array('text' => __('Pounds', 'mp')),
			));
			$weight->add_field('text', array(
				'name' => 'ounces',
				'label' => array('text' => __('Ounces', 'mp')),
			));
		}
		
		$metabox->add_field('text', array(
			'name' => 'extra_shipping_cost',
			'label' => array('text' => __('Extra Shipping Cost', 'mp')),
			'default_value' => '0.00',
			'conditional' => array(
				'name' => 'product_type',
				'value' => array('physical', 'digital'),
				'action' => 'show',
			),	
		));
		$metabox->add_field('text', array(
			'name' => 'special_tax_rate',
			'label' => array('text' => __('Special Tax Rate', 'mp')),
			'default_value' => '0.00',
			'conditional' => array(
				'name' => 'product_type',
				'value' => array('physical', 'digital'),
				'action' => 'show',
			),	
		));			
		$metabox->add_field('file', array(
			'name' => 'file_url',
			'label' => array('text' => __('File URL', 'mp')),
			'conditional' => array(
				'name' => 'product_type',
				'value' => 'digital',
				'action' => 'show',
			),	
		));
		$metabox->add_field('text', array(
			'name' => 'external_url',
			'label' => array('text' => __('External URL', 'mp')),
			'conditional' => array(
				'name' => 'product_type',
				'value' => 'external',
				'action' => 'show',
			),	
		));
	}
		
	/**
	 * Initializes the product variation metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_variations_metabox() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-product-variations-metabox',
			'title' => __('Variations', 'mp'),
			'post_type' => 'product',
			'context' => 'normal',
		));
		$metabox->add_field('checkbox', array(
			'name' => 'has_variations',
			'message' => __('Does this product have variations such as color, size, etc?', 'mp'),
		));
		$repeater = $metabox->add_field('repeater', array(
			'name' => 'variations',
			'layout' => 'rows',
			'add_row_label' => __('Add Variation', 'mp'),
			'conditional' => array(
				'name' => 'has_variations',
				'value' => 1,
				'action' => 'show',
			),
		));
		
		if ( $repeater instanceof WPMUDEV_Field ) {
			$repeater->add_sub_field('image', array(
				'name' => 'image',
				'label' => array('text' => __('Image', 'mp')),
			));
			$repeater->add_sub_field('text', array(
				'name' => 'sku',
				'label' => array('text' => __('SKU', 'mp')),
			));
			$repeater->add_sub_field('text', array(
				'name' => 'inventory',
				'label' => array('text' => __('Inventory Count', 'mp')),
				'desc' => __('Enter the quantity that you have available to sell. Leave blank if you do not want to track inventory.', 'mp'),
				'conditional' => array(
					'name' => 'product_type',
					'value' => 'physical',
					'action' => 'show',
				),
			));
			$repeater->add_sub_field('text', array(
				'name' => 'regular_price',
				'label' => array('text' => __('Regular Price', 'mp')),
				'conditional' => array(
					'name' => 'variation_type',
					'value' => array('physical', 'digital'),
					'action' => 'show',
				),
			));
			$sale_price = $repeater->add_sub_field('complex', array(
				'name' => 'sale_price',
				'label' => array('text' => __('Sale Price', 'mp')),
				'conditional' => array(
					'name' => 'product_type',
					'value' => array('physical', 'digital'),
					'action' => 'show',
				),												
			));
			$sale_price->add_field('text', array(
				'name' => 'price',
				'label' => array('text' => __('Price', 'mp')),
			));
			$sale_price->add_field('datepicker', array(
				'name' => 'start_date',
				'label' => array('text' => __('Start Date', 'mp')),
			));
			$sale_price->add_field('datepicker', array(
				'name' => 'end_date',
				'label' => array('text' => __('End Date (if applicable)', 'mp')),
			));
			$weight = $repeater->add_sub_field('complex', array(
				'name' => 'weight',
				'label' => array('text' => __('Weight', 'mp')),
				'conditional' => array(
					'name' => 'product_type',
					'value' => 'physical',
					'action' => 'show',
				),				
			));
			$weight->add_field('text', array(
				'name' => 'pounds',
				'label' => array('text' => __('Pounds', 'mp')),
			));
			$weight->add_field('text', array(
				'name' => 'ounces',
				'label' => array('text' => __('Ounces', 'mp')),
			));
			$repeater->add_sub_field('text', array(
				'name' => 'extra_shipping_cost',
				'label' => array('text' => __('Extra Shipping Cost', 'mp')),
				'default_value' => '0.00',
				'conditional' => array(
					'name' => 'product_type',
					'value' => array('physical', 'digital'),
					'action' => 'show',
				),	
			));
			$repeater->add_sub_field('text', array(
				'name' => 'special_tax_rate',
				'label' => array('text' => __('Special Tax Rate', 'mp')),
				'default_value' => '0.00',
				'conditional' => array(
					'name' => 'product_type',
					'value' => array('physical', 'digital'),
					'action' => 'show',
				),	
			));			
			$repeater->add_sub_field('file', array(
				'name' => 'file_url',
				'label' => array('text' => __('File URL', 'mp')),
				'conditional' => array(
					'name' => 'product_type',
					'value' => 'digital',
					'action' => 'show',
				),	
			));
			$repeater->add_sub_field('wysiwyg', array(
				'name' => 'description',
				'label' => array('text' => __('Description', 'mp')),
				'desc' => __('If you would like the description to be different than the main product enter it here.', 'mp'),
			));
			$repeater->add_sub_field('text', array(
				'name' => 'external_url',
				'label' => array('text' => __('External URL', 'mp')),
				'conditional' => array(
					'name' => 'product_type',
					'value' => 'external',
					'action' => 'show',
				),	
			));
			$repeater->add_sub_field('text', array(
				'name' => 'external_url',
				'label' => array('text' => __('External URL', 'mp')),
				'conditional' => array(
					'name' => 'product_type',
					'value' => 'external',
					'action' => 'show',
				),	
			));
			$repeater->add_sub_field('section', array(
				'title' => __('Variation Attributes', 'mp'),
				'desc' => __('Choose the attribute(s) that this variation should be associated with.', 'mp'),
			));
			
			$mp_product_atts = MP_Product_Attributes::get_instance();
			$atts = $mp_product_atts->get();
			foreach ( $atts as $att ) {
				$slug = $mp_product_atts->generate_slug($att->attribute_id);
				$terms = get_terms($slug, 'hide_empty=0');
				$options = array();
				
				foreach ( $terms as $term ) {
					$options[$term->term_id] = $term->name;
				}
				
				$repeater->add_sub_field('advanced_select', array(
					'name' => $slug,
					'label' => array('text' => $att->attribute_name),
					'options' => $options,
					'conditional' => array(
						'name' => 'product_type',
						'value' => array('physical', 'digital'),
						'action' => 'show',
					),	
				));
			}
		}
	}
	
	/**
	 * Initializes the product attributes metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_attributes_metabox() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-product-attributes-metabox',
			'title' => __('Attributes', 'mp'),
			'post_type' => 'product',
			'context' => 'normal',
		));		
	}
}

MP_Products_Screen::get_instance();