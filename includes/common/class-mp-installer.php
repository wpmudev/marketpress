<?php

class MP_Installer {
	/**
	 * Refers to the single instance of the class
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
			self::$_instance = new MP_Installer();
		}
		return self::$_instance;
	}
	
	/**
	 * Constructor
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action('plugins_loaded', array(&$this, 'run'));
	}
	
	/**
	 * Runs the installer code
	 *
	 * @since 3.0
	 * @access public
	 */
	public function run() {
		$old_version = get_option('mp_version');
		
		if ( ! get_option('mp_do_install') ) {
			if ( $old_version == MP_VERSION ) {
				return;
			}
		}

		$old_settings = get_option('mp_settings');

		//filter default settings
		$default_settings = apply_filters('mp_default_settings', $this->default_settings);
		$settings = wp_parse_args((array) $old_settings, $default_settings);
		
		//2.1.4 update
		if ( version_compare($old_version, '2.1.4', '<') ) {
			$this->_update_214();
		}
		
		//2.9.2.3 update
		if ( version_compare($old_version, '2.9.2.3', '<') ) {
			$this->_update_2923();
		}
		
		//3.0 update
		if ( version_compare($old_version, '3.1', '<') ) {
			$settings = $this->_update_3000($settings);
		}

		//update settings
		update_option('mp_settings', $settings);

		//only run these on first install
		if ( empty($old_settings) ) {
			//define settings that don't need to autoload for efficiency
			add_option('mp_coupons', '', '', 'no');
			add_option('mp_store_page', '', '', 'no');

			//create store page
			add_action('admin_init', array(&$this, 'create_store_page'));

			//add cart widget to first sidebar
			add_action('widgets_init', array(&$this, 'add_default_widget'), 11);
	 	}

		//add action to flush rewrite rules after we've added them for the first time
		update_option('mp_flush_rewrite', 1);

		update_option('mp_version', MP_VERSION);
		delete_option('mp_do_install');		
	}
	
	/**
	 * Creates the product attributes table
	 *
	 * @since 3.0
	 * @access private
	 * @uses $wpdb, $charset_collate
	 */
	private function _create_product_attributes_table() {
		global $wpdb, $charset_collate;
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		$table_name = $wpdb->prefix . 'mp_product_attributes';
		dbDelta("CREATE TABLE $table_name (
			attribute_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			attribute_name varchar(45) DEFAULT '',
			attribute_slug varchar(32) DEFAULT '',
			attribute_terms_sort_by enum('ID','ALPHA','CUSTOM') DEFAULT NULL,
			attribute_terms_sort_order enum('ASC','DESC') DEFAULT NULL,
			PRIMARY KEY  (attribute_id)
		) $charset_collate");
	}

	/**
	 * Runs on 3.0 update
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _update_3000( $settings ) {
		$this->_update_coupon_schema();
		$this->_create_product_attributes_table();
		
		//currency changes
		if ( 'TRL' == mp_get_setting('currency') ) {
			$settings['currency'] = 'TRY';
		}

		return $settings;
	}	
	
	/**
	 * Runs on 2.9.2.3 update to fix low inventory emails not being sent
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _update_2923() {
		global $wpdb;
		$wpdb->delete($wpdb->postmeta, array('meta_key' => 'mp_stock_email_sent'), array('%s'));
	}

	/**
	 * Runs on 2.1.4 update to fix price sorts
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _update_214() {
		global $wpdb;

		$posts = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product'");

		foreach ($posts as $post_id) {
			$meta = get_post_custom($post_id);
			//unserialize
			foreach ($meta as $key => $val) {
				$meta[$key] = maybe_unserialize($val[0]);
				if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file" && $key != "mp_price_sort")
					$meta[$key] = array($meta[$key]);
			}

			//fix price sort field if missing
			if ( empty($meta["mp_price_sort"]) && is_array($meta["mp_price"]) ) {
				if ( $meta["mp_is_sale"] && $meta["mp_sale_price"][0] )
					$sort_price = $meta["mp_sale_price"][0];
				else
					$sort_price = $meta["mp_price"][0];
				update_post_meta($post_id, 'mp_price_sort', $sort_price);
			}
		}
	}
	
	/**
	 * Updates the coupon schema
	 *
	 * @since 3.0
	 * @access private
	 */
	private function _update_coupon_schema() {
		$coupons = get_option('mp_coupons', false);
		
		if ( $coupons === false ) {
			//no coupons to update
			return false;
		}
		
		//include WPMUDEV Metaboxes/Fields
		include_once mp_plugin_dir('includes/wpmudev-metaboxes/class-wpmudev-field.php');
		mp_include_dir(mp_plugin_dir('includes/wpmudev-metaboxes/fields'));
		
		foreach ( $coupons as $code => $coupon ) {
			$type = isset($coupon['applies_to']['type']) ? $coupon['applies_to']['type'] : 'all';
			$id = isset($coupon['applies_to']['id']) ? $coupon['applies_to']['id'] : '';
			
			$metadata = array(
				'discount' => array(
					'type' => 'WPMUDEV_Field_Text',
					'value' => ( $coupon['discount_type'] == 'pct' ) ? $coupon['discount'] . '%' : $coupon['discount'],
				),
				'max_uses' => array(
					'type' => 'WPMUDEV_Field_Text',
					'value' => $coupon['uses'],
				),
				'applies_to' => array(
					'type' => 'WPMUDEV_Field_Radio_Group',
					'value' => $type,
				),
				'category' => array(
					'type' => 'WPMUDEV_Field_Taxonomy_Select',
					'value' => ( $type == 'category' ) ? $id : '',
				),
				'product' => array(
					'type' => 'WPMUDEV_Field_Post_Select',
					'value' => ( $type == 'product' ) ? $id : '',
				),
				'start_date' => array(
					'type' => 'WPMUDEV_Field_Datepicker',
					'value' => date('Ymd', $coupon['start']),
				),
				'indefinite' => array(
					'type' => 'WPMUDEV_Field_Checkbox',
					'value' => ( empty($coupon['end']) ) ? '1' : '0', 
				),
				'end_date' => array(
					'type' => 'WPMUDEV_Field_Datepicker',
					'value' =>  ( empty($coupon['end']) ) ? '' : date('Ymd', $coupon['end']),
				),
			);
			
			$post_id = wp_insert_post(array(
				'post_title' => strtoupper($code),
				'post_content' => '',
				'post_status' => 'publish',
				'post_type' => 'product_coupon',
			));
			
			foreach ( $metadata as $name => $data ) {
				$type = $data['type'];
				$field = new $type(array('name' => $name, 'value_only' => true));
				$field->save_value($post_id, $data['value']);
			}
		}
	}
}

MP_Installer::get_instance();