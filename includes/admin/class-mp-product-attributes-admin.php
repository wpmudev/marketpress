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
		if ( is_null(self::$_instance) ) {
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
	require_once mp_plugin_dir('includes/admin/class-mp-product-attributes-list-table.php');
	$list_table = new MP_Product_Attributes_List_Table();
	$list_table->prepare_items();	?>
	<div class="icon32"><img src="<?php echo mp_plugin_url('ui/images/settings.png'); ?>" /></div>
	<h2 class="mp-settings-title"><?php _e('Product Attributes', 'mp'); ?> <a class="add-new-h2" href="<?php echo add_query_arg(array('action' => 'mp_add_product_attribute')); ?>"><?php _e('Add Attribute', 'mp'); ?></a></h2>
	<div class="clear"></div>
	<div class="mp-settings">
		<form method="get">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
			<?php
			$list_table->display(); ?>
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
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-store-settings-product-attributes-add',
			'title' => __('Add Product Attribute', 'mp'),
			'screen_ids' => array('store-settings-productattributes'),
		));
		$metabox->add_field('text', array(
			'name' => 'product_attribute_name',
			'label' => array('text' => __('Attribute Name', 'mp')),
			'desc' => __('The name of the attribute (e.g. color, size, etc)', 'mp'),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'product_attribute_terms_sort_by',
			'label' => array('text' => __('Sort By', 'mp')),
			'default_value' => 'ID',
			'desc' => __('Select how the options will be sorted.', 'mp'),
			'fields' => array(
				'ID' => __('ID', 'mp'),
				'ALPHA' => __('Alphabetical', 'mp'),
				'CUSTOM' => __('Custom', 'mp'),
			),
		));
		$metabox->add_field('radio_group', array(
			'name' => 'product_attribute_terms_sort_order',
			'label' => array('text' => __('Sort Order', 'mp')),
			'default_value' => 'ASC',
			'fields' => array(
				'ASC' => __('Ascending', 'mp'),
				'DESC' => __('Descending', 'mp'),
			),
		));
		$repeater = $metabox->add_field('repeater', array(
			'name' => 'product_attribute_terms',
			'layout' => 'table',
			'add_row_label' => __('Add Option', 'mp'),
			'label' => array('text' => __('Attribute Options', 'mp')),
			'desc' => __('Use the numbers on the left to sort. To delete - click the "X" to the right of each row.', 'mp'),
		));
		
		if ( $repeater instanceof WPMUDEV_Field ) {
			$repeater->add_sub_field('text', array(
				'name' => 'name',
				'label' => array('text' => __('Name', 'mp')),
				'desc' => __('Max 45 characters.', 'mp'),
				'custom' => array('maxlength' => 45),
				'validation' => array(
					'required' => true,
				),
			));		
			$repeater->add_sub_field('text', array(
				'name' => 'slug',
				'label' => array('text' => __('Slug', 'mp')),
				'desc' => __('If a slug is not entered, it will be generated automatically. Max 32 characters.', 'mp'),
				'custom' => array('maxlength' => 32),
				'validation' => array(
					'custom' => '[a-z\-]',
				),
				'custom_validation_message' => __('Only lowercase letters and dashes (-) are allowed.', 'mp'),
			));
		}
	}
	
	/**
	 * Gets the product attribute terms
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field_value
	 * @return string
	 */
	public function get_product_attribute_value( $value, $post_id, $raw, $field ) {
		global $wpdb;
		
		switch ( $field->args['name'] ) {
			case 'product_attribute_name' :
			case 'product_attribute_terms_sort_by' :
			case 'product_attribute_terms_sort_order' :
				$table_name = $wpdb->prefix . 'mp_product_attributes';
				$attribute = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE attribute_id = %d", mp_get_get_value('attribute_id')));
				$key = str_replace('product_', '', $field->args['name']);
				$value = $attribute->$key;
			break;
			
			case 'product_attribute_terms' :
				$attribute_slug = MP_Product_Attributes::get_instance()->generate_slug(mp_get_get_value('attribute_id'));
				$terms = get_terms($attribute_slug, array('hide_empty' => false));
				$value = array();
				
				foreach ( $terms as $term ) {
					$value[] = array('ID' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug);
				}
			break;
		}
		
		return $value;
	}
	
	/**
	 * Saves the product attribute
	 *
	 * @since 3.0
	 * @access private
	 * @filter wpmudev_field_save_value
	 * @uses $wpdb
	 */
	public function save_product_attribute($value, $post_id, $field) {
		global $wpdb;
		
		if ( $field->args['name'] != 'product_attribute_terms' ) {
			return $value;
		}
		
		$product_atts = MP_Product_Attributes::get_instance();
		$table_name = MP_Product_Attributes::get_instance()->get_table_name();
		$redirect_url = remove_query_arg(array('action', 'action2'));
		
		if ( mp_get_get_value('action') == 'mp_add_product_attribute' ) {
			$wpdb->insert($table_name, array(
				'attribute_name' => mp_get_post_value('product_attribute_name', ''),
				'attribute_terms_sort_by' => mp_get_post_value('product_attribute_terms_sort_by', ''),
				'attribute_terms_sort_order' => mp_get_post_value('product_attribute_terms_sort_order', ''),
			));
			$attribute_id = $wpdb->insert_id;
			$attribute_slug = $product_atts->generate_slug($attribute_id);
		
			//temporarily register the taxonomy - otherwise we won't be able to insert terms below
			register_taxonomy($attribute_slug, 'product', array(
				'show_ui' => false,
				'show_in_nav_menus' => false,
				'hierarchical' => true,
			));
		
			//insert terms
			foreach ( mp_get_post_value('product_attribute_terms->name->new', array()) as $key => $term_name ) {
				if ( $term_slug = mp_get_post_value('product_attribute_terms->slug->new->' . $key) ) {
					wp_insert_term($term_name, $attribute_slug, array('slug' => substr(sanitize_key($term_name), 0, 32)));
				} else {
					wp_insert_term($term_name, $attribute_slug);
				}
			}
			
			//redirect
			wp_redirect(add_query_arg(array('attribute_id' => $attribute_id, 'action' => 'mp_edit_product_attribute', 'mp_message' => 'mp_product_attribute_added'), $redirect_url));			
		} else {
			$term_ids = array();
			$attribute_id = mp_get_get_value('attribute_id');
			$attribute_slug = $product_atts->generate_slug($attribute_id);
			$wpdb->update($table_name, array(
				'attribute_name' => mp_get_post_value('product_attribute_name', ''),
				'attribute_terms_sort_by' => mp_get_post_value('product_attribute_terms_sort_by', ''),
				'attribute_terms_sort_order' => mp_get_post_value('product_attribute_terms_sort_order', ''),
			), array('attribute_id' => $attribute_id));

			//insert terms
			foreach ( mp_get_post_value('product_attribute_terms->name->new', array()) as $key => $term_name ) {
				if ( $term_slug = mp_get_post_value('product_attribute_terms->slug->new->' . $key) ) {
					$term = wp_insert_term($term_name, $attribute_slug, array('slug' => substr(sanitize_key($term_slug), 0, 32)));
				} else {
					$term = wp_insert_term($term_name, $attribute_slug);
				}
				
				if ( ! is_wp_error($term) ) {
					$term_ids[] = $term['term_id'];
				} else {
					// term slug already exists, get existing term slug
					$term_ids[] = $term->error_data['term_exists'];
				}
			}
			
			//update existing terms
			foreach ( mp_get_post_value('product_attribute_terms->name->existing', array()) as $term_id => $term_name ) {
				$term_ids[] = $term_id;
				wp_update_term($term_id, $attribute_slug, array(
					'name' => $term_name,
					'slug' => ( $term_slug = mp_get_post_value('product_attribute_terms->slug->existing->' . $term_id) ) ? substr($term_slug, 0, 32) : substr(sanitize_key(mp_get_post_value('product_attribute_slug->existing->' . $term_id, '')), 0, 32),
				));
			}
			
			//remove deleted terms
			$unused_terms = get_terms($attribute_slug, array('hide_empty' => false, 'exclude' => $term_ids));
			foreach ( $unused_terms as $term ) {
				wp_delete_term($term->term_id, $attribute_slug);
			}
			
			//redirect
			wp_redirect(add_query_arg(array('attribute_id' => $attribute_id, 'action' => 'mp_edit_product_attribute', 'mp_message' => 'mp_product_attribute_updated'), $redirect_url));			
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
		if ( $field->args['name'] != 'product_attribute_terms' ) {
			return;
		} ?>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('.wpmudev-subfields').on('blur', 'input[name^="product_attribute_terms[name]"]', function(){
		var $this = $(this),
				$slugField = $this.closest('.wpmudev-subfield').next('.wpmudev-subfield').find('input');
				
		if ( $.trim($slugField.val()).length > 0 ) {
			// Only continue if slug field is empty
			return;
		}
		
		var slug = $this.val().toLowerCase().replace(' ', '-').replace('[^a-z0-9]', '');
		
		$slugField.val(slug);
	});
});
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
		add_action('wpmudev_field_print_scripts', array(&$this, 'product_attribute_scripts'));
	}
}

MP_Product_Attributes_Admin::get_instance();