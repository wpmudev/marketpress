<?php

class MP_Store_Settings_Admin {
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
			self::$_instance = new MP_Store_Settings_Admin();
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
		if ( mp_get_get_value('action') == 'mp_add_product_attribute' || mp_get_get_value('action') == 'mp_edit_product_attribute' ) {
			$this->add_product_attribute_metaboxes();
			add_action('store-settings_page_store-settings-productattributes', array(&$this, 'display_settings_form'));
			
			if ( mp_get_get_value('action') == 'mp_edit_product_attribute' ) {
				add_filter('wpmudev_field_get_value', array(&$this, 'get_product_attribute_value'), 10, 4);	
			}
			
			return;
		}
		
		add_filter('wpmudev_field_save_value', array(&$this, 'save_attribute'), 10, 3);
		add_action('store-settings_page_store-settings-productattributes', array(&$this, 'display_product_attributes'));
	}
	
	/**
	 * Gets an appropriate message by it's key
	 *
	 * @since 3.0
	 * @access public
	 */
	public function get_message_by_key( $key ) {
		$messages = array(
			'mp_product_attribute_added' => __('Product attribute added successfully.', 'mp'),
			'mp_product_attribute_updated' => __('Product attribute updated successfully.', 'mp'),
		);
		
		return ( isset($messages[$key]) ) ? $messages[$key] : sprintf(__('An appropriate message for key "%s" could not be found.', 'mp'), $key);
	}
	
	/**
	 * Displays the product attributes
	 *
	 * @since 3.0
	 * @access private
	 */
	public function display_product_attributes() {
		?>
<div class="wrap mp-wrap">
	<div class="icon32"><img src="<?php echo mp_plugin_url('ui/images/settings.png'); ?>" /></div>
	<h2 class="mp-settings-title"><?php _e('Product Attributes', 'mp'); ?> <a class="add-new-h2" href="<?php echo add_query_arg(array('action' => 'mp_add_product_attribute')); ?>"><?php _e('Add Attribute', 'mp'); ?></a></h2>
	<div class="clear"></div>
	<div class="mp-settings">
		<?php
		require_once mp_plugin_dir('includes/admin/class-mp-product-attributes-list-table.php');
		$list_table = new MP_Product_Attributes_List_Table();
		$list_table->prepare_items();
		$list_table->display(); ?>
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
	public function add_product_attribute_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-store-settings-product-attributes-add',
			'title' => __('Add Product Attribute', 'mp'),
			'screen_ids' => array('store-settings_page_store-settings-productattributes'),
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
			'label' => array('text' => __('Attribute Options', 'mp')),
			'desc' => __('Use the numbers on the left to sort. To delete - click the "X" to the right of each row.', 'mp'),
		));
		$repeater->add_sub_field('text', array(
			'name' => 'name',
			'label' => array('text' => __('Name', 'mp')),
		));		
		$repeater->add_sub_field('text', array(
			'name' => 'slug',
			'label' => array('text' => __('Slug', 'mp')),
			'desc' => __('If a slug is not entered, it will be generated automatically.', 'mp'),
		));
	}
	
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
				$table_name = $wpdb->prefix . 'mp_product_attributes';
				$attribute_slug = $wpdb->get_var($wpdb->prepare("SELECT attribute_slug FROM $table_name WHERE attribute_id = %d", mp_get_get_value('attribute_id')), 0);
				$terms = get_terms($attribute_slug, array('hide_empty' => false));
				$value = array();
				
				foreach ( $terms as $term ) {
					$value[] = array('name' => $term->name, 'slug' => $term->slug);
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
		
		if ( $field->args['name'] != 'attribute_terms' ) {
			return $value;
		}
		
		$table_name = MP_Product_Attributes::get_instance()->get_table_name();
		$attribute_slug = substr(sanitize_key($_POST['attribute_name']), 0, 32);
		
		if ( mp_get_get_value('action') == 'mp_add_product_attribute' ) {
			$attribute_id = $wpdb->insert($table_name, array(
				'attribute_name' => $_POST['attribute_name'],
				'attribute_slug' => $attribute_slug,
				'attribute_terms_sort_by' => $_POST['sort_by'],
				'attribute_terms_sort_order' => $_POST['sort_order'],
			));
		
			//temporarily register the taxonomy - otherwise we won't be able to insert terms below
			register_taxonomy($attribute_slug, 'product', array(
				'show_ui' => false,
				'show_in_nav_menus' => false,
				'hierarchical' => true,
			));
		
			//insert terms
			foreach ( $_POST['attribute_term_name'] as $key => $term_name ) {
				wp_insert_term($term_name, $attribute_slug);
			}
			
			//redirect
			wp_redirect(add_query_arg(array('attribute_id' => $attribute_id, 'action' => 'mp_edit_product_attribute', 'mp_message' => 'mp_product_attribute_added')));			
		} else {
			$attribute_id = mp_get_get_value('attribute_id');
			$wpdb->update($table_name, array(
				'attribute_name' => $_POST['attribute_name'],
				'attribute_slug' => $attribute_slug,
				'attribute_terms_sort_by' => $_POST['sort_by'],
				'attribute_terms_sort_order' => $_POST['sort_order'],
			), array('attribute_id' => $attribute_id));
			
			//update terms
			//TODO: figure out how to update existing terms
			/*foreach ( $_POST['attribute_term_name'] as $key => $term_name ) {
				wp_update_term($term_name, $attribute_slug);
			}*/
			
			//redirect
			wp_redirect(add_query_arg(array('attribute_id' => $attribute_id, 'action' => 'mp_edit_product_attribute', 'mp_message' => 'mp_product_attribute_updated')));			
		}
		
		exit;
	}
		
	/**
	 * Displays the settings form/metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function display_settings_form() {
		$updated = false;
		$title = __('Store Settings', 'mp') . ': ';
		
		switch ( mp_get_current_screen()->id ) {
			case 'store-settings_page_store-settings-presentation' :
				$title .= __('Presentation', 'mp');
			break;
			
			case 'store-settings_page_store-settings-messaging' :
				$title .= __('Messaging', 'mp');
			break;
			
			case 'store-settings_page_store-settings-shipping' :
				$title .= __('Shipping', 'mp');
			break;
			
			case 'store-settings_page_store-settings-payments' :
				$title .= __('Payment', 'mp');
			break;
			
			case 'store-settings_page_store-settings-shortcodes' :
				$title .= __('Short Codes', 'mp');
			break;
			
			case 'store-settings_page_store-settings-importers' :
				$title .= __('Importers', 'mp');
			break;
			
			case 'store-settings_page_store-settings-productattributes' :
				$title = ( mp_get_get_value('action') == 'mp_add_product_attribute' ) ? __('Add Product Attribute', 'mp') : __('Edit Product Attribute', 'mp');
			break;
			
			default :
				$title .= __('General', 'mp');
			break;
		}
		?>
<div class="wrap mp-wrap">
	<div class="icon32"><img src="<?php echo mp_plugin_url('ui/images/settings.png'); ?>" /></div>
	<h2 class="mp-settings-title"><?php echo $title; ?></h2>
	<div class="clear"></div>
	<?php
 	if ( $message_key = mp_get_get_value('mp_message') ) : ?>
 	<div class="updated"><p><?php echo $this->get_message_by_key($message_key); ?></p></div>
 	<?php
 	endif; ?> 	
	<div class="mp-settings">
	 	<form id="mp-main-form" method="post" action="<?php echo add_query_arg(array()); ?>">
			<?php
			do_action('wpmudev_metaboxes_settings'); ?>
			<p class="submit">
				<input class="button-primary" type="submit" name="submit_settings" value="<?php _e('Save Changes', 'mp') ?>" />
			</p>
		</form>
	</div>
</div>
	<?php
	}
}

MP_Store_Settings_Admin::get_instance();