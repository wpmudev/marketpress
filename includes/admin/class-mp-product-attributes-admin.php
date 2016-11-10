<?php

class MP_Product_Attributes_Admin {

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
			self::$_instance = new MP_Product_Attributes_Admin();
		}
		return self::$_instance;
	}

	/**
	 * Displays the product attributes
	 *
	 * @since 3.0
	 * @access private
	 */
	public static function display_product_attributes() {
		?>
		<style type="text/css">
			th.column-ID {
				width: 50px;
			}
		</style>
		<div class="wrap mp-wrap">
			<?php
			require_once mp_plugin_dir( 'includes/admin/class-mp-product-attributes-list-table.php' );
			$list_table = new MP_Product_Attributes_List_Table();
			$list_table->prepare_items();
			?>
			<div class="icon32"><img src="<?php echo mp_plugin_url( 'ui/images/settings.png' ); ?>" /></div>
			<h2 class="mp-settings-title"><?php _e( 'Product Attributes', 'mp' ); ?> <a class="add-new-h2" href="<?php echo add_query_arg( array( 'action' => 'mp_add_product_attribute' ) ); ?>"><?php _e( 'Add Attribute', 'mp' ); ?></a></h2>
			<div class="clear"></div>
			<div class="mp-settings">
				<form method="get">
					<input type="hidden" name="page" value="<?php echo $_REQUEST[ 'page' ]; ?>" />
					<?php $list_table->display(); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Add product attribute metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public static function add_product_attribute_metaboxes() {
		$metabox	 = new WPMUDEV_Metabox( array(
			'id'		 => 'mp-store-settings-product-attributes-add',
			'title'		 => __( 'Add Product Attribute', 'mp' ),
			'page_slugs' => array( 'store-settings-productattributes' ),
		) );
		$metabox->add_field( 'text', array(
			'name'		 => 'product_attribute_name',
			'label'		 => array( 'text' => __( 'Attribute Name', 'mp' ) ),
			'desc'		 => __( 'The name of the attribute (e.g. color, size, etc)', 'mp' ),
			'validation' => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'product_attribute_terms_sort_by',
			'label'			 => array( 'text' => __( 'Sort By', 'mp' ) ),
			'default_value'	 => 'ID',
			'desc'			 => __( 'Select how the options will be sorted.', 'mp' ),
			'options'		 => array(
				'ID'	 => __( 'ID', 'mp' ),
				'ALPHA'	 => __( 'Alphabetical', 'mp' ),
				'CUSTOM' => __( 'Custom', 'mp' ),
			),
		) );
		mp()->register_custom_types();
		$cats		 = get_terms( 'product_category', array(
			'hide_empty' => false,
			'fields'	 => 'id=>name'
		) );
		/* $metabox->add_field('advanced_select', array(
		  'name' => 'product_attribute_categories',
		  'label' => array('text' => __('Product Categories', 'mp')),
		  'placeholder' => __( 'Select Product Categories', 'mp' ),
		  'desc' => __( 'Select the product category/categories that this attribute should be available to. If you don\'t select any categories then this attribute will apply to all product categories.', 'mp' ),
		  'options' => $cats,
		  )); */
		$metabox->add_field( 'radio_group', array(
			'name'			 => 'product_attribute_terms_sort_order',
			'label'			 => array( 'text' => __( 'Sort Order', 'mp' ) ),
			'default_value'	 => 'ASC',
			'options'		 => array(
				'ASC'	 => __( 'Ascending', 'mp' ),
				'DESC'	 => __( 'Descending', 'mp' ),
			),
			'conditional'	 => array(
				'name'	 => 'product_attribute_terms_sort_by',
				'value'	 => 'CUSTOM',
				'action' => 'hide',
			),
			'width'			 => '33%',
		) );
		$repeater	 = $metabox->add_field( 'repeater', array(
			'name'			 => 'product_attribute_terms',
			'layout'		 => 'table',
			'add_row_label'	 => __( 'Add Option', 'mp' ),
			'label'			 => array( 'text' => __( 'Attribute Options', 'mp' ) ),
			'desc'			 => __( 'Use the numbers on the left to sort. To delete - click the "X" to the right of each row.', 'mp' ),
		) );

		if ( $repeater instanceof WPMUDEV_Field ) {
			$repeater->add_sub_field( 'text', array(
				'name'		 => 'name',
				'label'		 => array( 'text' => __( 'Name', 'mp' ) ),
				'desc'		 => __( 'Max 45 characters.', 'mp' ),
				'custom'	 => array( 'maxlength' => 45 ),
				'validation' => array(
					'required' => true,
				),
			) );
			$repeater->add_sub_field( 'text', array(
				'name'						 => 'slug',
				'label'						 => array( 'text' => __( 'Slug', 'mp' ) ),
				'desc'						 => __( 'If a slug is not entered, it will be generated automatically. Max 32 characters.', 'mp' ),
				'custom'					 => array( 'maxlength' => 32 ),
				'validation'				 => array(
					'custom' => '[a-z\-]',
				),
				'custom_validation_message'	 => __( 'Only lowercase letters and dashes (-) are allowed.', 'mp' ),
			) );
		}
	}

	public static function get_product_attributes() {
		global $wpdb;
		$table_name	 = $wpdb->prefix . 'mp_product_attributes';
		$results	 = $wpdb->get_results( 'SELECT * FROM ' . $table_name, OBJECT );

		return $results;
	}

	public static function get_product_attributes_select( $name = '', $value_type = 'id', $id = '',
													   $class = 'mp_product_attributes_select' ) {
		$product_attributes = MP_Product_Attributes_Admin::get_product_attributes();
		?>
		<select name="<?php echo $name; ?>" <?php if ( !empty( $id ) ) { ?>id="<?php echo $id; ?>"<?php } ?> class="<?php echo $class; ?>">
			<option value="-1"><?php _e( '- Create New Variation -', 'mp' ); ?></option>
			<?php foreach ( $product_attributes as $product_attribute ) {
				$tags = '';
				if( $attribute_terms = MP_Product_Attributes_Admin::get_product_attribute_terms( $product_attribute->attribute_id ) ){
					$tags = mp_array_column( $attribute_terms, 'name' );
					$tags = implode( ',', $tags );
				}

				?>
				<option data-tags='<?php echo $tags ?>' value="<?php
				if ( $value_type == 'id' ) {
					echo $product_attribute->attribute_id;
				} else {
					echo sanitize_key( $product_attribute->attribute_name );
				};
				?>"><?php echo $product_attribute->attribute_name; ?></option>
						<?php
					}
					?>
		</select>
		<?php
	}

	public static function get_product_attribute_color( $attribute_id, $order = false ) {
		if ( $order ) {
			$attribute_id = $order;
		} else {
			if ( $attribute_id > 10 ) {
				$attribute_id = substr( $attribute_id, -1 );
			}
		}
		return 'variation_color_' . $attribute_id;
	}


/**
	 * Gets the product attribute terms
	 *
	 * @since 3.0
	 * @access public
	 * @return string
	 */
	public static function get_product_attribute_terms( $attribute_id ) {

		if( !isset( $attribute_id ) || empty( $attribute_id ) || !is_numeric( $attribute_id ) ){
			return false; 
		}

		global $wpdb;

		$product_atts	 = MP_Product_Attributes::get_instance();
		$attribute_slug	 = $product_atts->generate_slug( $attribute_id );
		$terms			 = get_terms( $attribute_slug, array( 'hide_empty' => false ) );
		$value			 = array();

		if( empty( $terms ) || !is_array( $terms ) ){
			return false;
		}

		// Sort terms by term order
		$product_atts->sort_terms_by_custom_order( $terms );

		foreach ( $terms as $term ) {
			$value[] = array(
				'ID'	 => $term->term_id,
				'name'	 => $term->name,
				'slug'	 => $term->slug,
			);
		}

		return $value;
	}	

	/**
	 * Gets the product attribute terms
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field_value
	 * @return string
	 */
	public static function get_product_attribute_value( $value, $post_id, $raw, $field ) {
		global $wpdb;

		switch ( $field->args[ 'name' ] ) {
			case 'product_attribute_name' :
			case 'product_attribute_terms_sort_by' :
			case 'product_attribute_terms_sort_order' :
				$table_name	 = $wpdb->prefix . 'mp_product_attributes';
				$cache_key	 = 'attribute_row_' . mp_get_get_value( 'attribute_id' );
				$attribute	 = wp_cache_get( $cache_key, 'mp_product_attributes' );

				if ( false === $attribute ) {
					$attribute = $wpdb->get_row( $wpdb->prepare( "
						SELECT * FROM $table_name
						WHERE attribute_id = %d", mp_get_get_value( 'attribute_id' )
					) );
					wp_cache_set( $cache_key, $attribute, 'mp_product_attributes' );
				}

				$key	 = str_replace( 'product_', '', $field->args[ 'name' ] );
				$value	 = $attribute->$key;
				break;

			case 'product_attribute_categories' :
				$table_name	 = $wpdb->prefix . 'mp_product_attributes_terms';
				$cache_key	 = 'attribute_categories_' . mp_get_get_value( 'attribute_id' );
				$results	 = wp_cache_get( $cache_key, 'mp_product_attributes' );

				if ( false === $results ) {
					$results = $wpdb->get_results( $wpdb->prepare( "
						SELECT term_id
						FROM $table_name
						WHERE attribute_id = %s", mp_get_get_value( 'attribute_id' )
					) );
					wp_cache_set( $cache_key, $results, 'mp_product_attributes' );
				}

				$value = wp_list_pluck( $results, 'term_id' );
				break;

			case 'product_attribute_terms' :
				$product_atts	 = MP_Product_Attributes::get_instance();
				$attribute_slug	 = $product_atts->generate_slug( mp_get_get_value( 'attribute_id' ) );
				$terms			 = get_terms( $attribute_slug, array( 'hide_empty' => false ) );
				$value			 = array();

				// Sort terms by term order
				$product_atts->sort_terms_by_custom_order( $terms );

				foreach ( $terms as $term ) {
					$value[] = array(
						'ID'	 => $term->term_id,
						'name'	 => $term->name,
						'slug'	 => $term->slug,
					);
				}
				break;
		}

		return $value;
	}

	/**
	 * Saves the product attribute
	 *
	 * @since 3.0
	 * @action wpmudev_metabox_before_save_fields
	 * @uses $wpdb
	 */
	public static function save_product_attribute( $metabox ) {
		global $wpdb;

		$product_atts		 = MP_Product_Attributes::get_instance();
		$table_name			 = MP_Product_Attributes::get_instance()->get_table_name();
		$table_name_terms	 = $wpdb->prefix . 'mp_product_attributes_terms';
		$redirect_url		 = remove_query_arg( array( 'action', 'action2' ) );

		foreach ( $metabox->fields as $k => $field ) {
			if ( 'product_attribute_terms' == $field->args[ 'name' ] ) {
				$terms = $metabox->fields[ $k ]->sort_subfields( mp_get_post_value( 'product_attribute_terms' ) );
				break;
			}
		}

		if ( mp_get_get_value( 'action' ) == 'mp_add_product_attribute' ) {
			$wpdb->insert( $table_name, array(
				'attribute_name'			 => mp_get_post_value( 'product_attribute_name', '' ),
				'attribute_terms_sort_by'	 => mp_get_post_value( 'product_attribute_terms_sort_by', '' ),
				'attribute_terms_sort_order' => mp_get_post_value( 'product_attribute_terms_sort_order', '' ),
			) );
			$attribute_id	 = $wpdb->insert_id;
			$attribute_slug	 = $product_atts->generate_slug( $attribute_id );

			//temporarily register the taxonomy - otherwise we won't be able to insert terms below
			register_taxonomy( $attribute_slug, MP_Product::get_post_type(), array(
				'show_ui'			 => false,
				'show_in_nav_menus'	 => false,
				'hierarchical'		 => true,
			) );

			//insert terms
			foreach ( $terms as $order => $term ) {
				$id		 = key( $term );
				$term	 = current( $term );

				if ( !empty( $term[ 'slug' ] ) ) {
					wp_insert_term( $term[ 'name' ], $attribute_slug, array( 'slug' => substr( sanitize_key( $term[ 'slug' ] ), 0, 32 ) ) );
				} else {
					wp_insert_term( $term[ 'name' ], $attribute_slug );
				}
			}

			//insert product categories
			if ( $cats = mp_get_post_value( 'product_attribute_categories' ) ) {
				$cats	 = explode( ',', $cats );
				$sql	 = "INSERT INTO $table_name_terms (attribute_id, term_id) VALUES ";

				foreach ( $cats as $term_id ) {
					$sql .= $wpdb->prepare( "(%s, %s),", $attribute_id, $term_id );
				}

				$sql = rtrim( $sql, ',' ); //remove trailing comma
				$wpdb->query( $sql );
			}

			//redirect
			wp_redirect( add_query_arg( array(
				'attribute_id'	 => $attribute_id,
				'action'		 => 'mp_edit_product_attribute',
				'mp_message'	 => 'mp_product_attribute_added'
			), $redirect_url ) );
		} else {
			$term_ids		 = array();
			$attribute_id	 = mp_get_get_value( 'attribute_id' );
			$attribute_slug	 = $product_atts->generate_slug( $attribute_id );
			$wpdb->update( $table_name, array(
				'attribute_name'			 => mp_get_post_value( 'product_attribute_name', '' ),
				'attribute_terms_sort_by'	 => mp_get_post_value( 'product_attribute_terms_sort_by', '' ),
				'attribute_terms_sort_order' => mp_get_post_value( 'product_attribute_terms_sort_order', '' ),
			), array(
				'attribute_id' => $attribute_id
			) );

			//insert terms
			foreach ( $terms as $order => $term ) {
				$id		 = key( $term );
				$term	 = current( $term );

				$term_args	 = array();
				$term_slug	 = $term[ 'slug' ];
				$term_name	 = $term[ 'name' ];

				if ( false === strpos( $id, '_' ) ) {
					if ( !empty( $term_slug ) ) {
						$term_args[ 'slug' ] = substr( sanitize_key( $term_slug ), 0, 32 );
					}

					$term = wp_insert_term( $term_name, $attribute_slug, $term_args );

					if ( !is_wp_error( $term ) ) {
						$term_ids[]	 = $term_id	 = $term[ 'term_id' ];
					} else {
						// term slug already exists, get existing term slug
						$term_ids[]	 = $term_id	 = $term->error_data[ 'term_exists' ];
					}
				} else {
					$term_id			 = $term_ids[]			 = substr( $id, 1 );
					$term_args[ 'slug' ] = $term_slug;
					$term_args[ 'name' ] = $term_name;

					if ( !empty( $term_args[ 'slug' ] ) ) {
						$term_args[ 'slug' ] = substr( sanitize_key( $term_args[ 'slug' ] ), 0, 32 );
					}

					wp_update_term( $term_id, $attribute_slug, $term_args );
				}

				// Update term order
				$wpdb->update( $wpdb->terms, array(
					'term_order' => ($order + 1)
				), array(
					'term_id' => $term_id
				) );
			}

			// Remove deleted terms
			$unused_terms = get_terms( $attribute_slug, array(
				'hide_empty' => false,
				'exclude'	 => $term_ids
			) );
			foreach ( $unused_terms as $term ) {
				wp_delete_term( $term->term_id, $attribute_slug );
			}

			//update product categories
			$wpdb->delete( $table_name_terms, array( 'attribute_id' => $attribute_id ) );
			if ( $cats = mp_get_post_value( 'product_attribute_categories' ) ) {
				$cats	 = explode( ',', $cats );
				$sql	 = "INSERT INTO $table_name_terms (attribute_id, term_id) VALUES ";

				foreach ( $cats as $term_id ) {
					$sql .= $wpdb->prepare( "(%s, %s),", $attribute_id, $term_id );
				}

				$sql = rtrim( $sql, ',' ); //remove trailing comma
				$wpdb->query( $sql );
			}

			//redirect
			wp_redirect( add_query_arg( array(
				'attribute_id'	 => $attribute_id,
				'action'		 => 'mp_edit_product_attribute',
				'mp_message'	 => 'mp_product_attribute_updated'
			), $redirect_url ) );
		}

		exit;
	}

	/**
	 * Print custom scripts for the product attribute repeater field
	 *
	 * @since 3.0
	 * @access public
	 * @param WPMUDEV_Field $field
	 */
	function product_attribute_scripts( $field ) {
		if ( $field->args[ 'name' ] != 'product_attribute_terms' ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$( '.wpmudev-subfields' ).on( 'blur', 'input[name^="product_attribute_terms"][name*="[name]"]', function() {
					var $this = $( this ),
						$slugField = $this.closest( '.wpmudev-subfield' ).next( '.wpmudev-subfield' ).find( 'input' );

					if ( $.trim( $slugField.val() ).length > 0 ) {
						// Only continue if slug field is empty
						return;
					}

					var slug = $this.val().toLowerCase().replace( / /ig, '-' ).replace( /[^a-z0-9-]/ig, '' );

					$slugField.val( slug );
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Constructor
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action( 'wpmudev_field/print_scripts', array( &$this, 'product_attribute_scripts' ) );
	}

}

MP_Product_Attributes_Admin::get_instance();
