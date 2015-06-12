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
		if ( is_null( self::$_instance ) ) {
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
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );
// Remove add-new submenu item from store admin menu
		add_action( 'admin_menu', array( &$this, 'remove_menu_items' ), 999 );
		// Hide featured image for variable products
		add_action( 'wpmudev_field/print_scripts/has_variations', array( &$this, 'maybe_hide_core_metaboxes' ) );
// Product variations save/get value

		add_action( 'init', array( &$this, 'save_init_product_variations' ) );
		add_action( 'admin_init', array( &$this, 'hide_main_content_editor_for_variations' ) );
		add_action( 'wp_ajax_save_inline_post_data', array( &$this, 'save_inline_variation_post_data' ) );
		add_action( 'wp_ajax_edit_variation_post_data', array( &$this, 'edit_variation_post_data' ) );
		//add_action( 'wp_ajax_save_init_product_variations', array( &$this, 'save_init_product_variations' ) );
		//add_filter( 'wpmudev_field/save_value/variations', array( &$this, 'save_product_variations_parent_data' ), 10, 3 );
//add_filter( 'wpmudev_field/before_get_value/variations', array( &$this, 'get_product_variations_old' ), 10, 4 );
// Custom product columns
		add_filter( 'manage_product_posts_columns', array( &$this, 'product_columns_head' ) );
		add_filter( 'manage_mp_product_posts_columns', array( &$this, 'product_columns_head' ) );
		add_action( 'manage_product_posts_custom_column', array( &$this, 'product_columns_content' ), 10, 2 );
		add_action( 'manage_mp_product_posts_custom_column', array( &$this, 'product_columns_content' ), 10, 2 );
// Add metaboxes
		add_action( 'init', array( &$this, 'init_metaboxes' ) );
// Add quick/bulk edit capability for product fields
		add_action( 'quick_edit_custom_box', array( &$this, 'quick_edit_custom_box' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( &$this, 'bulk_edit_custom_box' ), 10, 2 );
		add_action( 'admin_print_scripts-edit.php', array( &$this, 'enqueue_bulk_quick_edit_js' ) );
		add_action( 'save_post', array( &$this, 'save_quick_edit' ), 10, 2 );
		add_action( 'save_post', array( &$this, 'save_post_quantity_fix' ), 10, 2 );
// Product screen scripts
		add_action( 'in_admin_footer', array( &$this, 'toggle_product_attributes_js' ) );
// Product attributes save/get value
		$mp_product_atts = MP_Product_Attributes::get_instance();
		$atts			 = $mp_product_atts->get();
		foreach ( $atts as $att ) {
			add_filter( 'wpmudev_field/save_value/' . $mp_product_atts->generate_slug( $att->attribute_id ), array( &$this, 'save_product_attribute' ), 10, 3 );
		}

		add_filter( 'enter_title_here', array( &$this, 'custom_placeholder_title' ), 10, 2 );
		add_action( 'admin_menu', array( &$this, 'remove_metaboxes' ) );
	}

	/**
	 * Enqueues admin javascript/css
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_styles_scripts() {
		wp_enqueue_style( 'colorbox', mp_plugin_url( 'includes/admin/ui/colorbox/colorbox.css' ), false, '1.5.10' );
		wp_enqueue_script( 'colorbox', mp_plugin_url( 'ui/js/jquery.colorbox-min.js' ), false, '1.5.10' );
	}

	/**
	 * Print js related to displaying/hiding of product attributes
	 *
	 * @since 3.0
	 * @access public
	 * @action in_admin_footer
	 */
	public function toggle_product_attributes_js() {
		if ( get_current_screen()->id != MP_Product::get_post_type() ) {
// not product screen - bail
			return;
		}
		?>
		<script type="text/javascript">
			( function( $ ) {
				var $inputs = $( 'input[name="tax_input[product_category][]"]' );

				var toggleProductAttributes = function() {
					var selectors = [ ];
					var $subfield = $( '.wpmudev-subfield' );

					if ( $inputs.filter( ':checked' ).length == 0 ) {
						// no categories checked - reset all product attributes to visible
						$subfield.has( '[name*="product_attr_"]' ).removeClass( 'wpmudev-field-hidden' );
						return;
					}

					// hide all product attributes
					$subfield.has( '[name*="product_attr_"]' ).addClass( 'wpmudev-field-hidden' );

					// loop through checked input and show associated attributes
					$inputs.filter( ':checked' ).each( function() {
						$subfield.has( '[data-product-category-' + $( this ).val() + ']' ).removeClass( 'wpmudev-field-hidden' );
					} );
				};

				$( document ).ready( function() {
					toggleProductAttributes();
					$inputs.on( 'change', toggleProductAttributes );
				} );
			}( jQuery ) );
		</script>
		<?php
	}

	/**
	 * Maybe hide some core metaboxes
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field/print_scripts/has_variations
	 */
	public function maybe_hide_core_metaboxes( $field ) {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( '[name="has_variations"]' ).change( function() {
					var $elms = $( '#postimagediv, #postdivrich, #postexcerpt' );

					if ( $( this ).prop( 'checked' ) ) {
						$elms.hide();
					} else {
						$elms.show();
						/* This is required to fix a bug in webkit with the WYSIWYG showing up all
						 garbled after unhiding */
						$( window ).trigger( 'scroll' );
					}
				} ).trigger( 'change' );
			} );
		</script>
		<?php
	}

	public function save_post_quantity_fix( $post_id, $post ) {
		if ( empty( $_POST ) ) {
			return $post_id;
		}

		if ( mp_doing_autosave() ) {
			return $post_id;
		}

		if ( wp_is_post_revision( $post ) ) {
			return $post_id;
		}

		if ( $post->post_type != MP_Product::get_post_type() ) {
			return $post_id;
		}

		$quantity = mp_get_post_value( 'inv->inventory', '' );

		update_post_meta( $post_id, 'inventory', $quantity );
	}

	/**
	 * Save the custom quick edit form fields
	 *
	 * @since 3.0
	 * @access public
	 * @action save_post
	 */
	public function save_quick_edit( $post_id, $post ) {
		if ( empty( $_POST ) ) {
			return $post_id;
		}

		if ( ($nonce = mp_get_post_value( 'quick_edit_product_nonce' )) && !wp_verify_nonce( $nonce, 'quick_edit_product' ) ) {
			return $post_id;
		}

		if ( mp_doing_autosave() ) {
			return $post_id;
		}

		if ( wp_is_post_revision( $post ) ) {
			return $post_id;
		}

		if ( $post->post_type != MP_Product::get_post_type() ) {
			return $post_id;
		}

		$price		 = mp_get_post_value( 'product_price', '' );
		$sale_price	 = mp_get_post_value( 'product_sale_price', '' );

		update_post_meta( $post_id, 'regular_price', $price );
		update_post_meta( $post_id, 'sale_price_amount', $sale_price );

		if ( isset( $_POST[ 'mp_product_images_indexes' ] ) ) {

			$mp_product_images_indexes	 = $_POST[ 'mp_product_images_indexes' ];
			$mp_product_images			 = explode( ',', $mp_product_images_indexes );

			if ( !empty( $mp_product_images_indexes ) ) {
				update_post_meta( $post_id, 'mp_product_images', $mp_product_images_indexes );

				if ( isset( $mp_product_images[ 0 ] ) ) {
					update_post_meta( $post_id, '_thumbnail_id', $mp_product_images[ 0 ] );
				} else {
					delete_post_meta( $post_id, '_thumbnail_id' );
				}
			} else {
				delete_post_meta( $post_id, 'mp_product_images' );
				delete_post_meta( $post_id, '_thumbnail_id' );
			}
		}
	}

	/**
	 * Enqueue quick/bulk edit script
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_print_scripts-edit.php
	 */
	public function enqueue_bulk_quick_edit_js() {
		if ( get_current_screen()->post_type != MP_Product::get_post_type() ) {
			return;
		}

		wp_enqueue_script( 'mp-bulk-quick-edit-product', mp_plugin_url( 'includes/admin/ui/js/bulk-quick-edit-product.js' ), array( 'jquery', 'inline-edit-post' ), MP_VERSION, true );
	}

	/**
	 * Display the custom quick edit box
	 *
	 * @since 3.0
	 * @access public
	 * @action quick_edit_custom_box
	 */
	public function quick_edit_custom_box( $column_name, $post_type ) {
		if ( $post_type != MP_Product::get_post_type() || $column_name != 'product_price' ) {
			return;
		}
		?>
		<fieldset id="quick-edit-col-product-price" class="inline-edit-col-left" style="clear:left">
			<div class="inline-edit-col"><!-- content inserted via js here --></div>
		</fieldset>
		<?php
	}

	/**
	 * Display the custom bulk edit box
	 *
	 * @since 3.0
	 * @access public
	 * @action bulk_edit_custom_box
	 */
	public function bulk_edit_custom_box( $column_name, $post_type ) {
		if ( $post_type != MP_Product::get_post_type() || $column_name != 'product_price' ) {
			return;
		}
		?>
		<fieldset id="bulk-edit-col-product-price" class="inline-edit-col-left" style="clear:left">
			<div class="inline-edit-col clearfix">
				<label class="alignleft"><span class="title"><?php _e( 'Price', 'mp' ); ?></span><span class="input-text-wrap"><input type="text" name="product_price" style="width:100px" /></span></label>
				<label class="alignleft" style="margin-left:15px"><span class="title"><?php _e( 'Sale Price', 'mp' ); ?></span><span class="input-text-wrap"><input type="text" name="product_sale_price" style="width:100px" /></span></label>
				<input type="hidden" name="bulk_edit_products_nonce" value="<?php echo wp_create_nonce( 'bulk_edit_products' ); ?>" />		
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Filter the product admin columns
	 *
	 * @since 3.0
	 * @access public
	 * @filter manage_product_posts_columns, manage_mp_product_posts_columns
	 * @return array
	 */
	public function product_columns_head( $columns ) {
		return array(
			'cb'						 => '<input type="checkbox" />',
			'title'						 => __( 'Product Name', 'mp' ),
			'product_variations'		 => __( 'Variations', 'mp' ),
			'product_sku'				 => __( 'SKU', 'mp' ),
			'product_price'				 => __( 'Price', 'mp' ),
			'product_stock'				 => __( 'Stock', 'mp' ),
			'product_sales'				 => __( 'Sales', 'mp' ),
			'taxonomy-product_category'	 => __( 'Categories', 'mp' ),
			'taxonomy-product_tag'		 => __( 'Tags', 'mp' ),
		);
	}

	/**
	 * Display data for each product admin column
	 *
	 * @since 3.0
	 * @access public
	 * @action manage_product_posts_custom_column, manage_mp_product_posts_custom_column
	 */
	public function product_columns_content( $column, $post_id ) {
		$product	 = new MP_Product( $post_id );
		$variations	 = $product->get_variations();

		switch ( $column ) {
			case 'product_variations' :
				if ( $product->has_variations() ) {
					$names = array();
					foreach ( $variations as $variation ) {
						$names[] = $variation->get_meta( 'name' );
					}
				} else {
					$names = array( '&mdash;' );
				}

				echo implode( '<br />', $names );
				break;

			case 'product_sku' :
				if ( $product->has_variations() ) {
					$skus = array();
					foreach ( $variations as $variation ) {
						$skus[] = $variation->get_meta( 'sku', '&mdash;' );
					}
				} else {
					$skus = array( $product->get_meta( 'sku', '&mdash;' ) );
				}

				echo implode( '<br />', $skus );
				break;

			case 'product_price' :
				if ( $product->has_variations() ) {
					$prices = array();
					foreach ( $variations as $variation ) {
						$price = $variation->get_price();
						if ( $variation->on_sale() ) {
							$prices[] = '<strike>' . mp_format_currency( '', $price[ 'regular' ] ) . '</strike> ' . mp_format_currency( '', $price[ 'sale' ][ 'amount' ] );
						} else {
							$prices[] = mp_format_currency( '', $price[ 'regular' ] );
						}
					}
				} else {
					$price = $product->get_price();
					if ( $product->on_sale() ) {
						$prices = array( '<strike>' . mp_format_currency( '', $price[ 'regular' ] ) . '</strike> ' . mp_format_currency( '', $price[ 'sale' ][ 'amount' ] ) );
					} else {
						$prices = array( mp_format_currency( '', $price[ 'regular' ] ) );
					}
				}

				echo implode( '<br />', $prices );
				echo '
					<div style="display:none">
						<div id="quick-edit-product-content-' . $post_id . '">
							<label class="alignleft"><span class="title">' . __( 'Price', 'mp' ) . '</span><span class="input-text-wrap"><input type="text" name="product_price" style="width:100px" value="' . $price[ 'regular' ] . '" /></span></label>
							<label class="alignleft" style="margin-left:15px"><span class="title">' . __( 'Sale Price', 'mp' ) . '</span><span class="input-text-wrap"><input type="text" name="product_sale_price" style="width:100px" value="' . $price[ 'sale' ][ 'amount' ] . '" /></span></label>
							<input type="hidden" name="quick_edit_product_nonce" value="' . wp_create_nonce( 'quick_edit_product' ) . '" />
						</div>
					</div>';
				break;

			case 'product_stock' :
				if ( $product->has_variations() ) {
					$stock = array();
					foreach ( $variations as $variation ) {
						$stock[] = $variation->get_meta( 'inventory', '&mdash;' );
					}
				} else {
					$stock = array( $product->get_meta( 'inventory', '&mdash;' ) );
				}

				$display_stock = implode( '<br />', $stock );

				echo $display_stock == 'Array' ? '&mdash;' : $display_stock;
				break;

			case 'product_sales' :
				if ( $product->has_variations() ) {
					$sales = array();
					foreach ( $variations as $variation ) {
						$sales[] = $variation->get_meta( 'mp_sales_count', 0 );
					}
				} else {
					$sales = array( $product->get_meta( 'mp_sales_count', 0 ) );
				}

				echo implode( '<br />', $sales );
				break;
		}
	}

	/**
	 * Initialize metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_product_type_metabox();
		$this->init_product_price_inventory_variants_metabox();
		$this->init_product_images_metabox();
		//$this->init_product_details_metabox();
//$this->init_variations_metabox();
		$this->init_related_products_metabox();
	}

	/**
	 * Remove add-new submenu item from store admin menu
	 *
	 * @since 3.0
	 * @access public
	 */
	public function remove_menu_items() {
		remove_submenu_page( 'edit.php?post_type=' . MP_Product::get_post_type(), 'post-new.php?post_type=' . MP_Product::get_post_type() );
		remove_submenu_page( 'edit.php?post_type=' . MP_Product::get_post_type(), 'edit-tags.php?taxonomy=product_category&amp;post_type=' . MP_Product::get_post_type() );
		remove_submenu_page( 'edit.php?post_type=' . MP_Product::get_post_type(), 'edit-tags.php?taxonomy=product_tag&amp;post_type=' . MP_Product::get_post_type() );
	}

	/**
	 * Saves the product attributes to the database
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field/save_value/product_attr_*
	 */
	public function save_product_attribute( $value, $post_id, $field ) {
		$slug = $field->args[ 'name' ];
		wp_set_post_terms( $post_id, $value, $slug );
		return $value;
	}

	/**
	 * Gets the product variations from the database and formats for repeater field
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field/get_value/variations
	 */
	public function get_product_variations( $value, $post_id, $raw, $field ) {
		$product	 = new MP_Product( $post_id );
		$variations	 = $product->get_variations();
		$data		 = array();

		foreach ( $variations as $variation ) {
			$meta = array();

			foreach ( $field->subfields as $subfield ) {
				switch ( $subfield->args[ 'original_name' ] ) {
					case 'description' :
						$meta[ $subfield->args[ 'original_name' ] ] = $subfield->format_value( $variation->post_content, $variation->ID );
						break;

					case 'image' :
						$meta[ $subfield->args[ 'original_name' ] ] = get_post_thumbnail_id( $variation->ID );
						break;

					default :
						if ( false !== (strpos( $subfield->args[ 'original_name' ], 'product_attr_' )) ) {
							$terms	 = get_the_terms( $variation->ID, $subfield->args[ 'original_name' ] );
							$term	 = false;

							if ( is_array( $terms ) ) {
								$term_obj	 = array_shift( $terms );
								$term		 = $term_obj->term_id;
							}

							$meta[ $subfield->args[ 'original_name' ] ] = $term;
						} else {
							$meta[ $subfield->args[ 'original_name' ] ] = $subfield->get_value( $variation->ID, $subfield->args[ 'original_name' ] );
						}
						break;
				}
			}

			$data[] = array_merge( array( 'ID' => $variation->ID ), $meta );
		}

		return $data;
	}

//calculate all the possible combinations creatable from a given choices array
	function possible_product_combinations( $groups, $prefix = '' ) {
		$result	 = array();
		$group	 = array_shift( $groups );
		foreach ( $group as $selected ) {
			if ( $groups ) {
				$result = array_merge( $result, $this->possible_product_combinations( $groups, $prefix . $selected . '|' ) );
			} else {
				$result[] = $prefix . $selected;
			}
		}
		return $result;
	}

	public static function term_id( $term, $taxonomy ) {

		if ( is_numeric( $term ) ) {
			return $term;
		} else {
			if ( $term_obj = term_exists( $term, $taxonomy ) ) {
				return $term_obj[ 'term_id' ];
			} else {
				$term_insert_response = wp_insert_term( $term, $taxonomy );
				if ( is_wp_error( $term_insert_response ) ) {
					if ( term_exists( $term, $taxonomy ) ) {
						$term = get_term( $term, $taxonomy, OBJECT );
						return $term->term_id;
					} else {
						echo $term_insert_response->get_error_message(); //shouldn't happen ever!
						exit;
					}
				} else {
					return $term_insert_response[ 'term_id' ];
				}
			}
		}
	}

	public static function maybe_create_attribute( $given_taxonomy, $new_taxonomy_name ) {
		$taxonomy = $given_taxonomy;

		if ( isset( $new_taxonomy_name ) && !empty( $new_taxonomy_name ) ) {
			global $wpdb;

			$product_atts		 = MP_Product_Attributes::get_instance();
			$table_name			 = MP_Product_Attributes::get_instance()->get_table_name();
			$table_name_terms	 = $wpdb->prefix . 'mp_product_attributes_terms';

			$result = $wpdb->get_col( $wpdb->prepare(
			"SELECT attribute_id FROM $table_name WHERE attribute_name = %s", $new_taxonomy_name
			) );

			if ( is_array( $result ) ) {
				$attribute_id = $result[ 0 ]; //get the first attribute with the given name from the array
			}

			if ( !is_numeric( $attribute_id ) ) {
				$wpdb->insert( $table_name, array(
					'attribute_name'			 => $new_taxonomy_name,
					'attribute_terms_sort_by'	 => 'ID',
					'attribute_terms_sort_order' => 'ASC',
				) );

				$attribute_id = $wpdb->insert_id;
			}

			$attribute_slug = $product_atts->generate_slug( $attribute_id );

			//temporarily register the taxonomy - otherwise we won't be able to insert terms below
			register_taxonomy( $attribute_slug, MP_Product::get_post_type(), array(
				'show_ui'			 => false,
				'show_in_nav_menus'	 => false,
				'hierarchical'		 => true,
			) );

			$taxonomy = $attribute_slug;
		}

		return $taxonomy;
	}

	public function edit_variation_post_data() {
		$post_id = mp_get_post_value( 'post_id' );
		check_ajax_referer( 'mp-ajax-nonce', 'ajax_nonce' );

		$post_meta_errors = 0;

		if ( isset( $post_id ) && is_numeric( $post_id ) ) {

			foreach ( $_POST as $key => $val ) {
				$variation_name = '';

				if ( strpos( $key, 'product_attr' ) === 0 ) {
					$insert_post_terms = wp_set_post_terms( $post_id, $this->term_id( $val, $key ), $key, false );
					if ( is_wp_error( $insert_post_terms ) ) {
						echo $insert_post_terms->get_error_message();
					} else {
						global $wpdb;

						$product_atts		 = MP_Product_Attributes::get_instance();
						$table_name			 = MP_Product_Attributes::get_instance()->get_table_name();
						$table_name_terms	 = $wpdb->prefix . 'mp_product_attributes_terms';

						$product_attributes = $wpdb->get_results(
						"SELECT attribute_id FROM $table_name"
						);

						foreach ( $product_attributes as $product_attribute ) {
							$attribute_name	 = 'product_attr_' . $product_attribute->attribute_id;
							$post_terms		 = wp_get_post_terms( $post_id, $attribute_name );

							if ( is_array( $post_terms ) && count( $post_terms ) > 0 ) {
								$variation_name = $variation_name . '' . $post_terms[ 0 ]->name . ' ';
							}
						}

						update_post_meta( $post_id, 'name', sanitize_text_field( $variation_name ) );
					}
				}
			}

			/* $response_array = array(
			  'status'		 => 'fail',
			  'status_message' => __( 'ERROR: Changed can\'t be saved.', 'mp' )
			  );
			  echo json_encode( $response_array );
			  exit; */

			$meta_array_values = array(
				'sku'						 => mp_get_post_value( 'sku' ),
				'external_url'				 => mp_get_post_value( 'external_url' ),
				'file_url'					 => mp_get_post_value( 'file_url' ),
				'inventory_tracking'		 => mp_get_post_value( 'inventory_tracking' ),
				'inventory'					 => mp_get_post_value( 'inventory->inventory' ),
				'inv_out_of_stock_purchase'	 => mp_get_post_value( 'inventory->out_of_stock_purchase' ),
				'regular_price'				 => mp_get_post_value( 'regular_price' ),
				'has_sale'					 => mp_get_post_value( 'has_sale' ),
				'sale_price_amount'			 => mp_get_post_value( 'sale_price->amount' ),
				'sale_price_start_date'		 => mp_get_post_value( 'sale_price->start_date' ),
				'sale_price_end_date'		 => mp_get_post_value( 'sale_price->end_date' ),
				'weight_pounds'				 => mp_get_post_value( 'weight->pounds' ),
				'weight_ounces'				 => mp_get_post_value( 'weight->ounces' ),
				'weight'					 => '',
				'charge_shipping'			 => mp_get_post_value( 'charge_shipping' ),
				'weight_extra_shipping_cost' => mp_get_post_value( 'weight->extra_shipping_cost' ),
				'charge_tax'				 => mp_get_post_value( 'charge_tax' ),
				'special_tax_rate'			 => mp_get_post_value( 'special_tax_rate' ),
			//'description'				 => mp_get_post_value( 'description' ),
			);

			$meta_array_values = apply_filters( 'mp_edit_variation_post_data', $meta_array_values, $post_id );

			foreach ( $meta_array_values as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}

			$my_post = array(
				'ID'			 => $post_id,
				'post_status'	 => 'publish',
			);
			wp_update_post( $my_post );
		}

		$response_array = array(
			'status'		 => 'success',
			'status_message' => __( 'Changes saved successfully', 'mp' )
		);

		echo json_encode( $response_array );
		exit;
	}

	/**
	 * Save inline changed data for variations
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb
	 */
	public function save_inline_variation_post_data() {
		$post_id = mp_get_post_value( 'post_id' );
		check_ajax_referer( 'mp-ajax-nonce', 'ajax_nonce' );

		if ( isset( $post_id ) && is_numeric( $post_id ) ) {

			$value_type		 = mp_get_post_value( 'meta_name' );
			$value_sub_type	 = mp_get_post_value( 'meta_sub_name' );
			$value			 = mp_get_post_value( 'meta_value' );

			switch ( $value_type ) {
				case 'delete':
					wp_delete_post( $post_id, true );
					break;
				case 'delete_variations':
					delete_post_meta( $post_id, 'has_variations' );
					//update_post_meta( $post_id, $value_type, $value );
					break;
				case 'sku':
					update_post_meta( $post_id, $value_type, $value );
					break;
				case 'product_attr':
					$insert_post_terms = wp_set_post_terms( $post_id, $this->term_id( $value, $value_sub_type ), $value_sub_type, false );
					if ( is_wp_error( $insert_post_terms ) ) {
						echo $insert_post_terms->get_error_message();
					} else {
						global $wpdb;

						$product_atts		 = MP_Product_Attributes::get_instance();
						$table_name			 = MP_Product_Attributes::get_instance()->get_table_name();
						$table_name_terms	 = $wpdb->prefix . 'mp_product_attributes_terms';

						$product_attributes = $wpdb->get_results(
						"SELECT attribute_id FROM $table_name"
						);

						$variation_name = '';

						foreach ( $product_attributes as $product_attribute ) {
							$attribute_name	 = 'product_attr_' . $product_attribute->attribute_id;
							$post_terms		 = wp_get_post_terms( $post_id, $attribute_name );
							if ( is_array( $post_terms ) && count( $post_terms ) > 0 ) {
								$variation_name = $variation_name . '' . $post_terms[ 0 ]->name . ' ';
							}
						}

						update_post_meta( $post_id, 'name', sanitize_text_field( $variation_name ) );
					}
					break;

				default:
					if ( $value_type == '_thumbnail_id' && $value == '' ) {
						delete_post_meta( $post_id, '_thumbnail_id' );
					} else {
						update_post_meta( $post_id, $value_type, sanitize_text_field( $value ) );
					}
			}
		}
	}

	/**
	 * Hide content editor from admin for products with variations
	 *
	 * @since 3.0
	 * @access public
	 * @action init
	 */
	public function hide_main_content_editor_for_variations() {
		// Get the Post ID.
		$post_id = isset( $_GET[ 'post' ] ) ? (int) $_GET[ 'post' ] : (isset( $_POST[ 'post_ID' ] ) ? (int) $_POST[ 'post_ID' ] : '');
		if ( !isset( $post_id ) )
			return;

		if ( get_post_type( $post_id ) == MP_Product::get_post_type() ) {
			$has_variations = get_post_meta( $post_id, 'has_variations', false );
			if ( $has_variations ) {
				remove_post_type_support( MP_Product::get_post_type(), 'editor' );
			}
		}
	}

	/**
	 * Create variation combinations and saves initial product variations to the database
	 * Add new terms if don't exist
	 * Add new taxonomies if don't exist
	 *
	 * @since 3.0
	 * @access public
	 * @action init
	 * @uses $wpdb
	 */
	public function save_init_product_variations() {
		global $wp_taxonomies;

		if ( mp_get_post_value( 'has_variation', 'no' ) == 'no' ) {
			return;
		}

		$variation_names	 = mp_get_post_value( 'product_attributes_categories', array() );
		$new_variation_names = mp_get_post_value( 'variation_names', array() );
		$variation_values	 = mp_get_post_value( 'variation_values', array() );
		$post_id			 = mp_get_post_value( 'post_ID' );

		$data = array();

		if ( isset( $variation_values ) && !empty( $variation_values ) ) {

			update_post_meta( $post_id, 'has_variations', 1 );

			$i = 0;

			foreach ( $variation_names as $variation_name ) {

				$variation_name = $this->maybe_create_attribute( 'product_attr_' . $variation_name, $new_variation_names[ $i ] ); //taxonomy name made of the prefix and attribute's ID

				$args = array(
					'orderby'		 => 'name',
					'hide_empty'	 => false,
					'fields'		 => 'all',
					'hierarchical'	 => true,
				);

				/* Get terms for the given taxonomy (variation name i.e. color, size etc) */
				$terms = get_terms( array( $variation_name ), $args );

				/* Put variation values in the array */
				$variation_values_row	 = $variation_values[ $i ];
				$variation_values_row	 = str_replace( array( '[', ']', '"' ), '', $variation_values_row );
				$variations_data		 = explode( ',', $variation_values_row );

				global $variations_single_data;
				foreach ( $variations_data as $variations_single_data ) {

					/* Check if the term ($variations_single_data ie red, blue, green etc) for the given taxonomy already exists */
					$term_object = array_filter(
					$terms, function ($e) {
						global $variations_single_data;
						return $e->slug == sanitize_key( trim( $variations_single_data ) ); //compare slug-like variation name against the existent ones in the db
					}
					);

					reset( $term_object );
					$data[ $i ][]			 = $variation_name . '=' . ((!empty( $term_object )) ? $term_object[ key( $term_object ) ]->term_id : $variations_single_data); //add taxonomy + term_id (if exists), if not leave the name of the term we'll create later
					$data_original[ $i ][]	 = $variation_name . '=' . $variations_single_data;
				}

				$i++;
			}

			$combinations			 = $this->possible_product_combinations( $data );
			$combinations_original	 = $this->possible_product_combinations( $data_original );

			$combination_num	 = 1;
			$combination_index	 = 0;

			foreach ( $combinations as $combination ) {

				$variation_id = wp_insert_post( array(
					//'ID'			 => $variation_id,
					'post_title'	 => mp_get_post_value( 'post_title' ),
					'post_content'	 => mp_get_post_value( 'content' ),
					'post_status'	 => 'publish',
					'post_type'		 => 'mp_product_variation',
					'post_parent'	 => $post_id,
				) );

				/* Make a variation name from the combination */
				$variation_title_combinations = explode( '|', $combinations_original[ $combination_index ] );

				$variation_name_title = '';

				foreach ( $variation_title_combinations as $variation_title_combination ) {
					$variation_name_title_array = explode( '=', $variation_title_combination );
					$variation_name_title .= $variation_name_title_array[ 1 ] . ' ';
				}

				$sku_post_val	 = mp_get_post_value( 'sku' );
				$sku			 = isset( $sku_post_val ) && !empty( $sku_post_val ) ? $sku_post_val . '-' . $combination_num : '';

				$variation_metas = apply_filters( 'mp_variations_meta', array(
					'name'						 => $variation_name_title, //mp_get_post_value( 'post_title' ),
					'sku'						 => $sku,
					'inventory_tracking'		 => mp_get_post_value( 'inventory_tracking' ),
					'inventory'					 => mp_get_post_value( 'inventory->inventory' ),
					'inv_out_of_stock_purchase'	 => mp_get_post_value( 'inventory->out_of_stock_purchase' ),
					'file_url'					 => '', //to do
					'external_url'				 => '', //to do
					'regular_price'				 => mp_get_post_value( 'regular_price' ),
					'sale_price_amount'			 => mp_get_post_value( 'sale_price->amount' ),
					'sale_price_start_date'		 => mp_get_post_value( 'sale_price->start_date' ),
					'sale_price_end_date'		 => mp_get_post_value( 'sale_price->end_date' ),
					'sale_price'				 => '', //array - to do
					'weight_pounds'				 => mp_get_post_value( 'weight->pounds' ),
					'weight_ounces'				 => mp_get_post_value( 'weight->ounces' ),
					'weight'					 => '', //array - to do
					'charge_shipping'			 => mp_get_post_value( 'charge_shipping' ),
					'charge_tax'				 => mp_get_post_value( 'charge_tax' ),
					'has_sale'					 => mp_get_post_value( 'has_sale' ),
					'weight_extra_shipping_cost' => mp_get_post_value( 'weight->extra_shipping_cost' ),
					'special_tax_rate'			 => mp_get_post_value( 'special_tax_rate' ),
				//'description'				 => mp_get_post_value( 'content' ),
				), mp_get_post_value( 'post_ID' ), $variation_id );


				/* Add default post metas for variation */
				foreach ( $variation_metas as $meta_key => $meta_value ) {
					update_post_meta( $variation_id, $meta_key, sanitize_text_field( $meta_value ) );
				}

				/* Add post terms for the variation */
				$variation_terms = explode( '|', $combination );

				foreach ( $variation_terms as $variation_term ) {
					$variation_term_vals = explode( '=', $variation_term );
					wp_set_post_terms( $variation_id, $this->term_id( $variation_term_vals[ 1 ], $variation_term_vals[ 0 ] ), $variation_term_vals[ 0 ], true );
				}

				$combination_num++;
				$combination_index++;
			}

			//exit;
		} else {
			//update_post_meta( $post_id, 'has_variations', 0 );
		}
	}

	public function save_product_variations_parent_data( $value, $post_id, $field ) {
		
	}

	/**
	 * Saves the product variations to the database
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field/save_value/variations
	 * @uses $wpdb
	 */
	public function save_product_variations_old( $value, $post_id, $field ) {
		global $wpdb;

		$variations		 = mp_get_post_value( 'variations', array() );
		$sorted			 = $field->sort_subfields( $variations );
		$ids			 = array();
		$delete_where	 = "{$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->posts}.post_parent = $post_id AND {$wpdb->posts}.post_type = 'mp_product_variation'";


		if ( mp_get_post_value( 'has_variations', false ) ) {
			foreach ( $sorted as $order => $array ) {
				$variation_id	 = key( $array );
				$fields			 = current( $array );

				if ( false === strpos( $variation_id, '_' ) ) {
					$variation_id	 = $ids[]			 = wp_insert_post( array(
						'post_content'	 => mp_arr_get_value( 'description', $fields, '' ),
						'post_title'	 => 'Product Variation of ' . $post_id,
						'post_status'	 => 'publish',
						'post_type'		 => 'mp_product_variation',
						'post_parent'	 => $post_id,
						'menu_order'	 => $order,
					) );
				} else {
					$ids[]			 = $variation_id	 = substr( $variation_id, 1 );
					wp_update_post( array(
						'ID'			 => $variation_id,
						'post_content'	 => mp_arr_get_value( 'description', $fields, '' ),
						'post_status'	 => 'publish',
						'menu_order'	 => $order,
					) );
				}

// Update post thumbnail
				if ( empty( $fields[ 'image' ] ) ) {
					delete_post_thumbnail( $variation_id );
				} else {
					set_post_thumbnail( $variation_id, $fields[ 'image' ] );
				}

// Unset the fields that shouldn't be saved as post meta
				$fields[ 'description' ] = $fields[ 'image' ]		 = null;

				$index = 0;
				foreach ( $fields as $name => $value ) {
					if ( is_null( $value ) ) {
						$index ++;
						continue;
					}

					$subfield = $field->subfields[ $index ];

					if ( false !== strpos( $name, 'product_attr_' ) ) {
						wp_set_post_terms( $variation_id, $subfield->sanitize_for_db( $value, $variation_id ), $name );
					} else {
						$subfield->save_value( $variation_id, $name, $value, true );
					}

					$index ++;
				}
			}

			$delete_where .= " AND {$wpdb->posts}.ID NOT IN (" . implode( ',', $ids ) . ")";
		}

// Delete variations that no longer exist
		$wpdb->query( "
			DELETE FROM $wpdb->posts
			USING $wpdb->posts
			INNER JOIN $wpdb->postmeta
			WHERE $delete_where"
		);

		return null; // Returning null will bypass internal save mechanism
	}

	/**
	 * Initializes the related products metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_related_products_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'		 => 'mp-related-products-metabox',
			'title'		 => __( 'Related Products', 'mp' ),
			'post_type'	 => MP_Product::get_post_type(),
			'context'	 => 'side',
			'desc'		 => __( 'If you would like, you can choose specific related products instead of using the ones generated by MarketPress', 'mp' ),
		) );

		$metabox->add_field( 'post_select', array(
			'name'			 => 'related_products',
			'multiple'		 => true,
			'placeholder'	 => __( 'Choose Products', 'mp' ),
			'query'			 => array(
				'post__not_in'	 => array( get_the_ID() ),
				'post_type'		 => MP_Product::get_post_type(),
				'posts_per_page' => -1,
			),
		) );
	}

	/**
	 * Initializes the product type metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_product_type_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'		 => 'mp-product-type-metabox',
			'title'		 => sprintf( __( 'Product Kind %1$s(Physical Product, Digital, etc)%2$s', 'mp' ), '<span class="mp_meta_small_desc">', '</span>' ),
			'post_type'	 => MP_Product::get_post_type(),
			'context'	 => 'normal',
		) );

		$product_kinds = array(
			'physical'	 => __( 'Physical / Tangible Product', 'mp' ),
			'digital'	 => __( 'Digital Download', 'mp' ),
		//'external'	 => __( 'External / Affiliate Link', 'mp' ),
		);

		$post_id = isset($_GET[ 'post' ]) ? $_GET[ 'post' ] : 0;

		$has_variations = get_post_meta( (int) $post_id, 'has_variations', false );
		if ( !$has_variations ) {
			$product_kinds[ 'external' ] = __( 'External / Affiliate Link', 'mp' );
		}

		$metabox->add_field( 'select', array(
			'name'			 => 'product_type',
			'id'			 => 'mp-product-type-select',
			'default_value'	 => 'physical',
			'options'		 => $product_kinds
		) );
	}

	public function init_product_price_inventory_variants_metabox() {

		if ( isset( $_GET[ 'post' ] ) ) {
			$post_id = $_GET[ 'post' ];
		} else {
			$post_id = -1;
		}

		$has_variations = get_post_meta( $post_id, 'has_variations', false );

		$metabox = new WPMUDEV_Metabox( array(
			'id'		 => 'mp-product-price-inventory-variants-metabox',
			'title'		 => $has_variations ? __( 'Product Variations' ) : sprintf( __( '%1$sPRICE, INVENTORY & VARIANTS%2$s %3$sSet price, manage inventory and create Product Variants (if appropriate for your product).%2$s', 'mp' ), '<span class="mp_meta_section_title">', '</span>', '<span class="mp_meta_bellow_desc">' ),
			'post_type'	 => MP_Product::get_post_type(),
			'context'	 => 'normal',
		) );

		if ( !$has_variations ) {

			$metabox->add_field( 'text', array(
				'name'			 => 'sku',
				'placeholder'	 => __( 'Enter SKU', 'mp' ),
				'label'			 => array( 'text' => sprintf( __( 'SKU %1$s(Stock Keeping Unit)%2$s', 'mp' ), '<span class="mp_meta_small_desc">', '</span>' ) ),
				'class'			 => 'mp-product-field-40 mp-blank-bg'
			) );

			$metabox->add_field( 'text', array(
				'name'			 => 'regular_price',
				'label'			 => array( 'text' => __( 'Price', 'mp' ) ),
				'placeholder'	 => __( 'Enter Price', 'mp' ),
				'validation'	 => array(
					'required'	 => true,
					'number'	 => true,
					'min'		 => 0,
				),
				'class'			 => 'mp-product-field-40 mp-blank-bg'
			) );

			$metabox->add_field( 'checkbox', array(
				'name'		 => 'has_sale',
				'message'	 => __( 'Set up a Sale for this Product', 'mp' ),
			) );

			$sale_price = $metabox->add_field( 'complex', array(
				'name'			 => 'sale_price',
				'label'			 => array( 'text' => __( 'Sale Price', 'mp' ) ),
				'conditional'	 => array(
					'name'	 => 'has_sale',
					'value'	 => 1,
					'action' => 'show',
				),
				'custom'		 => array(
					'label_position' => 'up',
					'label_type'	 => 'standard'
				),
				'class'			 => 'mp-product-sale-price-holder mp-special-box'
			) );

			if ( $sale_price instanceof WPMUDEV_Field ) {
				$sale_price->add_field( 'text', array(
					'name'			 => 'amount',
					'placeholder'	 => __( 'Enter Sale Price', 'mp' ),
					'label'			 => array( 'text' => __( 'Price', 'mp' ) ),
					'custom'		 => array(
//'data-msg-lessthan' => __( 'Value must be less than regular price', 'mp' ),
					),
					'validation'	 => array(
						'number' => true,
						'min'	 => 0,
					//'lessthan'	 => '[name*="regular_price"]'
					),
				) );
				$sale_price->add_field( 'datepicker', array(
					'name'	 => 'start_date',
					'label'	 => array( 'text' => __( 'Start Date (if applicable)', 'mp' ) ),
				) );
				$sale_price->add_field( 'datepicker', array(
					'name'	 => 'end_date',
					'label'	 => array( 'text' => __( 'End Date (if applicable)', 'mp' ) ),
				) );
			}

			$metabox->add_field( 'checkbox', array(
				'name'			 => 'charge_tax',
				'message'		 => __( 'Charge Taxes (Special Rate)', 'mp' ),
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => array( 'physical', 'digital' ),
					'action' => 'show',
				),
			) );

			/* $metabox->add_field( 'text', array(
			  'name'			 => 'special_tax_rate',
			  'label'			 => array( 'text' => __( 'Special Tax Rate', 'mp' ) ),
			  'placeholder'	 => __( 'Tax Rate', 'mp' ),
			  'validation'	 => array(
			  'required'	 => true,
			  'number'	 => true,
			  'min'		 => 0,
			  ),
			  'class'			 => 'mp-product-field-20 mp-blank-bg'
			  ) ); */

			$metabox->add_field( 'text', array(
				'name'						 => 'special_tax_rate',
				'label'						 => array( 'text' => __( 'Special Tax Rate', 'mp' ) ),
				'placeholder'				 => __( 'Tax Rate', 'mp' ),
				'default_value'				 => '',
				'desc'						 => __( 'If you would like this product to use a special tax rate, enter it here. If you omit the "%" symbol the rate will be calculated as a fixed amount for each of this product in the user\'s cart.', 'mp' ),
				/* 'conditional'				 => array(
				  'name'	 => 'product_type',
				  'value'	 => array( 'physical', 'digital' ),
				  'action' => 'show',
				  ), */
				'conditional'				 => array(
					'name'	 => 'charge_tax',
					'value'	 => 1,
					'action' => 'show',
				),
				'custom_validation_message'	 => __( 'Please enter a valid tax rate', 'mp' ),
				'validation'				 => array(
					'custom' => '[^0-9.%]',
				),
				'custom'					 => array(
					'label_position' => 'up',
					'label_type'	 => 'standard'
				),
				'class'						 => 'mp-product-special-tax-holder mp-special-box'
			) );

			$metabox->add_field( 'checkbox', array(
				'name'			 => 'charge_shipping',
				'message'		 => __( 'Charge Shipping', 'mp' ),
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => 'physical',
					'action' => 'show',
				),
			) );

			$weight = $metabox->add_field( 'complex', array(
				'name'			 => 'weight',
				'label'			 => array( 'text' => __( 'Weight', 'mp' ) ),
				'conditional'	 => array(
					'name'	 => 'charge_shipping',
					'value'	 => 1,
					'action' => 'show',
				),
				'custom'		 => array(
					'label_position' => 'up',
					'label_type'	 => 'standard'
				),
				'class'			 => ( 'metric' == mp_get_setting( 'shipping->system' ) ) ? 'mp-product-shipping-holder mp-special-box mp-system-metric' : 'mp-product-shipping-holder mp-special-box'
			) );

			if ( $weight instanceof WPMUDEV_Field ) {
				if ( 'metric' == mp_get_setting( 'shipping->system' ) ) {
					$weight->add_field( 'text', array(
						'name'		 => 'pounds',
						'label'		 => array( 'text' => __( 'Kilograms', 'mp' ) ),
						'validation' => array(
							'number' => true,
						),
					) );
				} else {
					$weight->add_field( 'text', array(
						'name'		 => 'pounds',
						'label'		 => array( 'text' => __( 'Pounds', 'mp' ) ),
						'validation' => array(
							'digits' => true,
						),
					) );
					$weight->add_field( 'text', array(
						'name'		 => 'ounces',
						'label'		 => array( 'text' => __( 'Ounces', 'mp' ) ),
						'validation' => array(
							'digits' => true,
						),
					) );
				}

				$weight->add_field( 'text', array(
					'name'			 => 'extra_shipping_cost',
					'label'			 => array( 'text' => sprintf( __( 'Extra Shipping Cost %1$s(if applicable)%2$s', 'mp' ), '<span class="mp_meta_small_desc">', '</span>' ) ),
					'default_value'	 => '0.00',
					'validation'	 => array(
						'number' => true,
						'min'	 => 0,
					),
				) );
			}

			$metabox->add_field( 'checkbox', array(
				'name'			 => 'inventory_tracking',
				'message'		 => __( 'Track Product Inventory', 'mp' ),
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => array( 'physical', 'digital' ),
					'action' => 'show',
				),
			) );

			$inventory = $metabox->add_field( 'complex', array(
				'name'			 => 'inv',
				'label'			 => array( 'text' => __( '', 'mp' ) ),
				'conditional'	 => array(
					'name'	 => 'inventory_tracking',
					'value'	 => 1,
					'action' => 'show',
				),
				'custom'		 => array(
					'label_position' => 'up',
					'label_type'	 => 'standard'
				),
				'class'			 => 'mp-product-inventory-holder mp-special-box',
			) );

			if ( $inventory instanceof WPMUDEV_Field ) {
				$inventory->add_field( 'text', array(
					'name'			 => 'inventory',
					'label'			 => array( 'text' => __( 'Quantity', 'mp' ) ),
					/* 'conditional'	 => array(
					  'action'	 => 'show',
					  'operator'	 => 'AND',
					  array(
					  'name'	 => 'product_type',
					  'value'	 => 'physical',
					  ),
					  array(
					  'name'	 => 'variations[inventory_tracking]',
					  'value'	 => 1,
					  ),
					  ), */
					'conditional'	 => array(
						'name'	 => 'inventory_tracking',
						'value'	 => 1,
						'action' => 'show',
					),
					'validation'	 => array(
						'integer' => true,
					),
				) );

				$inventory->add_field( 'checkbox', array(
					'name'		 => 'out_of_stock_purchase',
					'message'	 => __( 'Allow this product to be purchased even if it\'s out of stock', 'mp' ),
				/* 'conditional'	 => array(
				  'name'	 => 'product_type',
				  'value'	 => 'physical',
				  'action' => 'show',
				  ), */
				) );
			}

			$metabox->add_field( 'radio_group', array(
				'name'			 => 'has_variation',
				'label'			 => array( 'text' => '' ),
				'options'		 => array(
					'no'	 => __( 'This is a unique product without variations', 'mp' ),
					'yes'	 => sprintf( __( 'This product has a multiple variations %1$s(e.g. Multiple colors, sizes)%2$s', 'mp' ), '<span class="mp_meta_small_desc">', '</span>' ),
				),
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => array( 'physical', 'digital' ),
					'action' => 'show',
				),
				'default_value'	 => 'no',
				'class'			 => 'mp_variations_select'
			) );
		}

		if ( $has_variations ) {
			$metabox->add_field( 'variations', array(
				'name'		 => 'variations_module',
				'label'		 => '', //array( 'text' => sprintf( __( '%3$sProduct Variations%2$s', 'mp' ), '<span class="mp_variations_product_name">', '</span>', '<span class="mp_variations_title">' ) ),
				'message'	 => __( 'Variations', 'mp' ),
				/* 'conditional'	 => array(
				  'name'	 => 'has_variation',
				  'value'	 => 'yes',
				  'action' => 'show',
				  ), */
				'class'		 => 'mp_variations_table_box'
			) );
		} else {
			$metabox->add_field( 'variations', array(
				'name'			 => 'variations_module',
				'label'			 => array( 'text' => sprintf( __( '%3$sAdd variations for%2$s %1$sProduct%2$s', 'mp' ), '<span class="mp_variations_product_name">', '</span>', '<span class="mp_variations_title">' ) ),
				'message'		 => __( 'Variations', 'mp' ),
				'desc'			 => __( 'Add variations for this product. e.g. If you are selling t-shirts, you can create Color & Size variations', 'mp' ),
				'conditional'	 => array(
					'name'	 => 'has_variation',
					'value'	 => 'yes',
					'action' => 'show',
				),
				'class'			 => 'mp_variations_box'
			) );
		}

		$metabox->add_field( 'file', array(
			'name'			 => 'file_url',
			'label'			 => array( 'text' => __( 'File URL', 'mp' ) ),
			//'placeholder'	 => __( 'Choose a file', 'mp' ),
			'button_label'	 => 'Select a file',
			'conditional'	 => array(
				'action'	 => 'show',
				'operator'	 => 'AND',
				array(
					'name'	 => 'product_type',
					'value'	 => 'digital',
				),
				array(
					'name'	 => 'has_variation',
					'value'	 => 'no',
				),
			),
			'class'			 => 'mp-product-field-50 mp-blank-bg'
		) );

		$metabox->add_field( 'text', array(
			'name'			 => 'external_url',
			'label'			 => array( 'text' => __( 'External Link', 'mp' ) ),
			//'placeholder'	 => __( 'Choose a file', 'mp' ),
			'button_label'	 => 'Insert a Link',
			'conditional'	 => array(
				'action'	 => 'show',
				'operator'	 => 'AND',
				array(
					'name'	 => 'product_type',
					'value'	 => 'external',
				),
				array(
					'name'	 => 'has_variation',
					'value'	 => 'no',
				),
			),
			'class'			 => 'mp-product-field-50 mp-blank-bg'
		) );
	}

	/**
	 * Initializes the product type metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_product_images_metabox() {

		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-product-images-metabox',
			'title'			 => sprintf( __( '%1$sPRODUCT IMAGES%2$s %3$sAdd images of the product. The first image on the list is the featured image for this product (you can reorder images on the list)%2$s', 'mp' ), '<span class="mp_meta_section_title">', '</span>', '<span class="mp_meta_bellow_desc">' ),
			'post_type'		 => MP_Product::get_post_type(),
			'context'		 => 'normal',
			'conditional'	 => array(
				'action'	 => 'show',
				'operator'	 => 'OR',
				array(
					'name'	 => 'has_variation',
					'value'	 => 'no',
				),
				array(
					'name'	 => 'product_type',
					'value'	 => 'external',
				),
			),
		) );

		$metabox->add_field( 'images', array(
			'name'			 => 'product_images',
			'label'			 => '', //array( 'text' => sprintf( __( '%3$sProduct Variations%2$s', 'mp' ), '<span class="mp_variations_product_name">', '</span>', '<span class="mp_variations_title">' ) ),
			//'message'	 => __( 'Images', 'mp' ),
			'conditional'	 => array(
				'action'	 => 'hide',
				'operator'	 => 'OR',
				array(
					'name'	 => 'product_type',
					'value'	 => 'external',
				),
				array(
					'name'	 => 'has_variation',
					'value'	 => 'yes',
				),
			),
			'class'			 => 'mp_product_images'
		) );

		/* $repeater = $metabox->add_field( 'repeater', array(
		  'name'			 => 'product_images',
		  'layout'		 => 'rows',
		  'add_row_label'	 => __( 'Add Image', 'mp' ),
		  'class' => 'mp_product_images'
		  ) );

		  if ( $repeater instanceof WPMUDEV_Field ) {
		  $repeater->add_sub_field( 'image', array(
		  'name' => 'product_image',
		  ) );
		  } */
	}

	/**
	 * Initializes the product details metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_product_details_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-product-details-metabox',
			'title'			 => __( 'Product Details', 'mp' ),
			'post_type'		 => MP_Product::get_post_type(),
			'context'		 => 'normal',
			'conditional'	 => array(
				'name'	 => 'has_variations',
				'value'	 => 1,
				'action' => 'hide',
			),
		) );

		$metabox->add_field( 'tab_labels', array(
			'name'	 => 'product_tabs',
			'tabs'	 => array(
				array(
					'label'	 => __( 'General', 'mp' ),
					'slug'	 => 'general',
					'active' => true,
				),
				array(
					'label'	 => __( 'Price', 'mp' ),
					'slug'	 => 'price',
				),
				array(
					'label'	 => __( 'Taxes', 'mp' ),
					'slug'	 => 'taxes',
				),
				array(
					'label'	 => __( 'Shipping', 'mp' ),
					'slug'	 => 'shipping',
				),
			),
		) );

// General Tab
		$metabox->add_field( 'tab', array(
			'name'	 => 'product_tab_general',
			'slug'	 => 'general'
		) );
		$metabox->add_field( 'text', array(
			'name'	 => 'sku',
			'label'	 => array( 'text' => __( 'SKU', 'mp' ) ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'			 => 'inventory_tracking',
			'label'			 => array( 'text' => __( 'Track Inventory?', 'mp' ) ),
			'conditional'	 => array(
				'name'	 => 'product_type',
				'value'	 => 'physical',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'text', array(
			'name'			 => 'inventory',
			'label'			 => array( 'text' => __( 'Inventory Count', 'mp' ) ),
			'desc'			 => __( 'Enter the quantity that you have available to sell.', 'mp' ),
			'conditional'	 => array(
				'action'	 => 'show',
				'operator'	 => 'AND',
				array(
					'name'	 => 'product_type',
					'value'	 => 'physical',
				),
				array(
					'name'	 => 'inventory_tracking',
					'value'	 => 1,
				),
			),
			'validation'	 => array(
				'required'	 => true,
				'digits'	 => true,
				'min'		 => 0,
			),
		) );
		$metabox->add_field( 'file', array(
			'name'			 => 'file_url',
			'label'			 => array( 'text' => __( 'File URL', 'mp' ) ),
			'conditional'	 => array(
				'name'	 => 'product_type',
				'value'	 => 'digital',
				'action' => 'show',
			),
			'validation'	 => array(
				'url' => true,
			),
		) );
		$metabox->add_field( 'text', array(
			'name'			 => 'external_url',
			'label'			 => array( 'text' => __( 'External URL', 'mp' ) ),
			'default_value'	 => 'http://',
			'conditional'	 => array(
				'name'	 => 'product_type',
				'value'	 => 'external',
				'action' => 'show',
			),
			'validation'	 => array(
				'url' => true,
			),
		) );

// Price Tab
		$metabox->add_field( 'tab', array(
			'name'	 => 'product_tab_price',
			'slug'	 => 'price'
		) );

		$metabox->add_field( 'text', array(
			'name'		 => 'regular_price',
			'label'		 => array( 'text' => __( 'Regular Price', 'mp' ) ),
			'validation' => array(
				'required'	 => true,
				'number'	 => true,
				'min'		 => 0,
			),
		) );

		$sale_price = $metabox->add_field( 'complex', array(
			'name'	 => 'sale_price',
			'label'	 => array( 'text' => __( 'Sale Price', 'mp' ) ),
		) );

		if ( $sale_price instanceof WPMUDEV_Field ) {
			$sale_price->add_field( 'text', array(
				'name'		 => 'amount',
				'label'		 => array( 'text' => __( 'Price', 'mp' ) ),
				'custom'	 => array(
					'data-msg-lessthan' => __( 'Value must be less than regular price', 'mp' ),
				),
				'validation' => array(
					'number' => true,
					'min'	 => 0,
				//'lessthan'	 => '[name*="regular_price"]'
				),
			) );
			$sale_price->add_field( 'datepicker', array(
				'name'	 => 'start_date',
				'label'	 => array( 'text' => __( 'Start Date (if applicable)', 'mp' ) ),
			) );
			$sale_price->add_field( 'datepicker', array(
				'name'	 => 'end_date',
				'label'	 => array( 'text' => __( 'End Date (if applicable)', 'mp' ) ),
			) );
		}


// Tax Tab
		$metabox->add_field( 'tab', array(
			'name'	 => 'product_tab_taxes',
			'slug'	 => 'taxes'
		) );
		$metabox->add_field( 'text', array(
			'name'						 => 'special_tax_rate',
			'label'						 => array( 'text' => __( 'Special Tax Rate', 'mp' ) ),
			'default_value'				 => '',
			'desc'						 => __( 'If you would like this product to use a special tax rate, enter it here. If you omit the "%" symbol the rate will be calculated as a fixed amount for each of this product in the user\'s cart.', 'mp' ),
			'conditional'				 => array(
				'name'	 => 'product_type',
				'value'	 => array( 'physical', 'digital' ),
				'action' => 'show',
			),
			'custom_validation_message'	 => __( 'Please enter a valid tax rate', 'mp' ),
			'validation'				 => array(
				'custom' => '[^0-9.%]',
			),
		) );

// Shipping Tab
		$metabox->add_field( 'tab', array(
			'name'	 => 'product_tab_shipping',
			'slug'	 => 'shipping'
		) );

		$weight = $metabox->add_field( 'complex', array(
			'name'			 => 'weight',
			'label'			 => array( 'text' => __( 'Weight', 'mp' ) ),
			'conditional'	 => array(
				'name'	 => 'product_type',
				'value'	 => 'physical',
				'action' => 'show',
			),
		) );

		if ( $weight instanceof WPMUDEV_Field ) {
			$weight->add_field( 'text', array(
				'name'		 => 'pounds',
				'label'		 => array( 'text' => __( 'Pounds', 'mp' ) ),
				'validation' => array(
					'digits' => true,
				),
			) );
			$weight->add_field( 'text', array(
				'name'		 => 'ounces',
				'label'		 => array( 'text' => __( 'Ounces', 'mp' ) ),
				'validation' => array(
					'digits' => true,
				),
			) );
		}

		$metabox->add_field( 'text', array(
			'name'			 => 'extra_shipping_cost',
			'label'			 => array( 'text' => __( 'Extra Shipping Cost', 'mp' ) ),
			'default_value'	 => '0.00',
			'conditional'	 => array(
				'name'	 => 'product_type',
				'value'	 => array( 'physical', 'digital' ),
				'action' => 'show',
			),
			'validation'	 => array(
				'number' => true,
				'min'	 => 0,
			),
		) );
	}

	/**
	 * Initializes the product variation metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_variations_metabox() {
		$metabox	 = new WPMUDEV_Metabox( array(
			'id'			 => 'mp-product-variations-metabox',
			'title'			 => __( 'Variations', 'mp' ),
			'post_type'		 => MP_Product::get_post_type(),
			'context'		 => 'normal',
			'desc'			 => __( 'Create your product variations here. You can reorder variations by using the number to left of each variation, or delete one by clicking the "x" to the right of each variation. <strong>NOTE: The variation that shows up first in this list will be considered the "main variation". The details from this variation will be used in product listings.</strong>', 'mp' ),
			'conditional'	 => array(
				'name'	 => 'has_variations',
				'value'	 => 1,
				'action' => 'show',
			),
		) );
		$repeater	 = $metabox->add_field( 'repeater', array(
			'name'			 => 'variations',
			'layout'		 => 'rows',
			'add_row_label'	 => __( 'Add Variation', 'mp' ),
		) );

		if ( $repeater instanceof WPMUDEV_Field ) {
			$repeater->add_sub_field( 'tab_labels', array(
				'name'	 => 'tabs',
				'tabs'	 => array(
					array(
						'label'	 => __( 'General', 'mp' ),
						'slug'	 => 'general',
						'active' => true,
					),
					array(
						'label'	 => __( 'Price', 'mp' ),
						'slug'	 => 'price',
					),
					array(
						'label'	 => __( 'Taxes', 'mp' ),
						'slug'	 => 'taxes',
					),
					array(
						'label'	 => __( 'Shipping', 'mp' ),
						'slug'	 => 'shipping',
					),
					array(
						'label'	 => __( 'Attributes', 'mp' ),
						'slug'	 => 'attributes',
					),
				),
			) );

// General Tab
			$repeater->add_sub_field( 'tab', array(
				'name'	 => 'tab_general',
				'slug'	 => 'general'
			) );
			$repeater->add_sub_field( 'text', array(
				'name'		 => 'name',
				'label'		 => array( 'text' => __( 'Name', 'mp' ) ),
				'validation' => array(
					'required' => true,
				),
			) );
			$repeater->add_sub_field( 'text', array(
				'name'	 => 'sku',
				'label'	 => array( 'text' => __( 'SKU', 'mp' ) ),
			) );
			$repeater->add_sub_field( 'image', array(
				'name'	 => 'image',
				'label'	 => array( 'text' => __( 'Image', 'mp' ) ),
			) );
			$repeater->add_sub_field( 'checkbox', array(
				'name'			 => 'inventory_tracking',
				'label'			 => array( 'text' => __( 'Track Inventory?', 'mp' ) ),
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => 'physical',
					'action' => 'show',
				),
			) );
			$repeater->add_sub_field( 'text', array(
				'name'			 => 'inventory',
				'label'			 => array( 'text' => __( 'Inventory Count', 'mp' ) ),
				'desc'			 => __( 'Enter the quantity that you have available to sell.', 'mp' ),
				'conditional'	 => array(
					'action'	 => 'show',
					'operator'	 => 'AND',
					array(
						'name'	 => 'product_type',
						'value'	 => 'physical',
					),
					array(
						'name'	 => 'variations[inventory_tracking]',
						'value'	 => 1,
					),
				),
				'validation'	 => array(
					'required'	 => true,
					'digits'	 => true,
					'min'		 => 0,
				),
			) );
			$repeater->add_sub_field( 'file', array(
				'name'			 => 'file_url',
				'label'			 => array( 'text' => __( 'File URL', 'mp' ) ),
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => 'digital',
					'action' => 'show',
				),
				'validation'	 => array(
					'url' => true,
				),
			) );
			$repeater->add_sub_field( 'wysiwyg', array(
				'name'	 => 'description',
				'label'	 => array( 'text' => __( 'Description', 'mp' ) ),
				'desc'	 => __( 'If you would like the description to be different than the main product enter it here.', 'mp' ),
			) );
			$repeater->add_sub_field( 'text', array(
				'name'			 => 'external_url',
				'label'			 => array( 'text' => __( 'External URL', 'mp' ) ),
				'default_value'	 => 'http://',
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => 'external',
					'action' => 'show',
				),
				'validation'	 => array(
					'url' => true,
				),
			) );

// Price Tab
			$repeater->add_sub_field( 'tab', array(
				'name'	 => 'tab_price',
				'slug'	 => 'price',
			) );
			$repeater->add_sub_field( 'text', array(
				'name'			 => 'regular_price',
				'label'			 => array( 'text' => __( 'Regular Price', 'mp' ) ),
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => array( 'physical', 'digital' ),
					'action' => 'show',
				),
				'validation'	 => array(
					'required'	 => true,
					'number'	 => true,
				),
			) );
			$sale_price = $repeater->add_sub_field( 'complex', array(
				'name'			 => 'sale_price',
				'label'			 => array( 'text' => __( 'Sale Price (if applicable)', 'mp' ) ),
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => array( 'physical', 'digital' ),
					'action' => 'show',
				),
			) );
			$sale_price->add_field( 'text', array(
				'name'		 => 'amount',
				'label'		 => array( 'text' => __( 'Price', 'mp' ) ),
				'custom'	 => array(
					'data-msg-lessthan' => __( 'Value must be less than regular price', 'mp' ),
				),
				'validation' => array(
					'number'	 => true,
					'min'		 => 0,
					'lessthan'	 => '[name*="regular_price"]'
				),
			) );
			$sale_price->add_field( 'datepicker', array(
				'name'	 => 'start_date',
				'label'	 => array( 'text' => __( 'Start Date', 'mp' ) ),
			) );
			$sale_price->add_field( 'datepicker', array(
				'name'	 => 'end_date',
				'label'	 => array( 'text' => __( 'End Date (if applicable)', 'mp' ) ),
			) );

// Shipping Tab
			$repeater->add_sub_field( 'tab', array(
				'name'	 => 'tab_shipping',
				'slug'	 => 'shipping'
			) );
			$weight = $repeater->add_sub_field( 'complex', array(
				'name'			 => 'weight',
				'label'			 => array( 'text' => __( 'Weight', 'mp' ) ),
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => 'physical',
					'action' => 'show',
				),
			) );
			$weight->add_field( 'text', array(
				'name'		 => 'pounds',
				'label'		 => array( 'text' => __( 'Pounds', 'mp' ) ),
				'validation' => array(
					'digits' => true,
					'min'	 => 0,
				),
			) );
			$weight->add_field( 'text', array(
				'name'		 => 'ounces',
				'label'		 => array( 'text' => __( 'Ounces', 'mp' ) ),
				'validation' => array(
					'digits' => true,
					'min'	 => 0,
				),
			) );
			$repeater->add_sub_field( 'text', array(
				'name'			 => 'extra_shipping_cost',
				'label'			 => array( 'text' => __( 'Extra Shipping Cost', 'mp' ) ),
				'default_value'	 => '0.00',
				'conditional'	 => array(
					'name'	 => 'product_type',
					'value'	 => array( 'physical', 'digital' ),
					'action' => 'show',
				),
				'validation'	 => array(
					'number' => true,
					'min'	 => 0,
				),
			) );

// Taxes Tab
			$repeater->add_sub_field( 'tab', array(
				'name'	 => 'tab_taxes',
				'slug'	 => 'taxes'
			) );
			$repeater->add_sub_field( 'text', array(
				'name'						 => 'special_tax_rate',
				'label'						 => array( 'text' => __( 'Special Tax Rate', 'mp' ) ),
				'desc'						 => __( 'If you would like this variation to use a special tax rate, enter it here. If you omit the "%" symbol the rate will be calculated as a fixed amount for each of this product in the user\'s cart.', 'mp' ),
				'default_value'				 => '',
				'conditional'				 => array(
					'name'	 => 'product_type',
					'value'	 => array( 'physical', 'digital' ),
					'action' => 'show',
				),
				'custom_validation_message'	 => __( 'Please enter a valid tax rate', 'mp' ),
				'validation'				 => array(
					'custom' => '[^0-9.%]',
				),
			) );

// Attributes Tab
			$repeater->add_sub_field( 'tab', array(
				'name'	 => 'tab_attributes',
				'slug'	 => 'attributes',
				'desc'	 => __( 'Each product variation needs to have product attributes assigned to it so the system knows how to differentiate one product variation from the other. It is <strong>important</strong> that you assign a category to this product before choosing any attributes.', 'mp' ),
			) );
			$mp_product_atts = MP_Product_Attributes::get_instance();
			$atts			 = $mp_product_atts->get();
			foreach ( $atts as $att ) {
				$slug	 = $mp_product_atts->generate_slug( $att->attribute_id );
				$terms	 = get_terms( $slug, 'hide_empty=0' );
				$terms	 = $mp_product_atts->sort( $terms, false );
				$args	 = array(
					'name'			 => $slug,
					'label'			 => array( 'text' => $att->attribute_name ),
					'multiple'		 => false,
					'placeholder'	 => sprintf( __( 'Select a %s', 'mp' ), $att->attribute_name ),
					'conditional'	 => array(
						'name'	 => 'product_type',
						'value'	 => array( 'physical', 'digital' ),
						'action' => 'show',
					),
					'validation'	 => array(
						'required' => true,
					),
				);

// Set options
				$options = array( '' );
				foreach ( $terms as $term ) {
					$args[ 'options' ][ $term->term_id ] = $term->name;
				}

// Set associated product categories
				$cats	 = $mp_product_atts->get_associated_categories( $att->attribute_id );
				$custom	 = array();
				foreach ( $cats as $cat_id ) {
					$key						 = 'data-product-category-' . $cat_id;
					$args[ 'custom' ][ $key ]	 = 'true';
				}

				$repeater->add_sub_field( 'advanced_select', $args );
			}
		}
	}

	/**
	 * Add custom title placeholder to product edit screen
	 *
	 * @since 3.0
	 * @access public
	 * @filter enter_title_here
	 */
	function custom_placeholder_title( $placeholder, $post ) {
		if ( $post->post_type == MP_Product::get_post_type() ) {
			$placeholder = __( 'Enter your product name here', 'mp' );
		}

		return $placeholder;
	}

	/**
	 * Remove metaboxes from the single product admin page
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_menu
	 */
	function remove_metaboxes() {
		if ( apply_filters( 'mp_remove_excerpt_meta_box', true ) ) {
			remove_meta_box( 'postexcerpt', 'product', 'normal' );
		}

		if ( apply_filters( 'mp_remove_author_meta_box', true ) ) {
			remove_meta_box( 'authordiv', 'product', 'normal' );
		}
	}

}

MP_Products_Screen::get_instance();
