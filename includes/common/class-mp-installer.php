<?php

class MP_Installer {
	/**
	 * Refers to the single instance of the class.
	 *
	 * @since 3.0
	 * @access public
	 * @var object
	 */
	public static $_instance = null;

	/**
	 * Gets the single instance of the class.
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
	 * Constructor.
	 *
	 * @since 3.0
	 * @access public
	 */
	private function __construct() {
		add_action('init', array(&$this, 'run'));
		add_action('after_switch_theme', array(&$this, 'update_admin_caps'));
	}
	
	/**
	 * Runs the installer code.
	 *
	 * @since 3.0
	 * @access public
	 */
	public function run() {
		$old_version = get_option('mp_version');
		
		if ( $old_version == MP_VERSION ) {
			return;
		}

		$old_settings = get_option('mp_settings', array());

		// Filter default settings
		$default_settings = apply_filters('mp_default_settings', mp()->default_settings);
		$settings = array_replace_recursive($default_settings, $old_settings);
		
		// Only run the follow scripts if this not a fresh install
		if ( ! empty($old_version) ) {
			//2.1.4 update
			if ( version_compare($old_version, '2.1.4', '<') ) {
				$this->update_214();
			}
			
			//2.9.2.3 update
			if ( version_compare($old_version, '2.9.2.3', '<') ) {
				$this->update_2923();
			}
			
			//3.0 update
			if ( version_compare($old_version, '3.0', '<') ) {
				$settings = $this->update_3000($settings);
			}
		}
		
		// Update settings
		update_option('mp_settings', $settings);

		// Only run these on first install
		if ( empty($old_settings) ) {
			// Define settings that don't need to autoload for efficiency
			add_option('mp_coupons', '', '', 'no');

			add_action('widgets_init', array(&$this, 'add_default_widget'), 11);
	 	}

		//add action to flush rewrite rules after we've added them for the first time
		update_option('mp_flush_rewrite', 1);

		update_option('mp_version', MP_VERSION);
	}
	
	/**
	 * Creates the product attributes table.
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb, $charset_collate
	 */
	public function create_product_attributes_table() {
		global $wpdb, $charset_collate;
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		$table_name = $wpdb->prefix . 'mp_product_attributes';
		dbDelta("CREATE TABLE $table_name (
			attribute_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			attribute_name varchar(45) DEFAULT '',
			attribute_terms_sort_by enum('ID','ALPHA','CUSTOM') DEFAULT NULL,
			attribute_terms_sort_order enum('ASC','DESC') DEFAULT NULL,
			PRIMARY KEY  (attribute_id)
		) $charset_collate");
	}
	
	/**
	 * Adds the cart widget to the default/first sidebar.
	 *
	 * @since 3.0
	 * @access public
	 * @action widgets_init
	 */
	public function add_default_widget() {
		//! TODO: copy from 2.9
	}
	
	/**
	 * Creates stores pages.
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_init
	 */
	public function create_store_pages() {
		$store_page = get_option('mp_store_page');
		//! TODO: create store pages
	}

	/**
	 * Updates presentation settings.
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 */
	public function update_presentation_settings( $settings ) {
		if ( $height = mp_get_setting('list_img_height') ) {
			mp_push_to_array($settings, 'list_img_size_custom->height', $height);
			unset($settings['list_img_height']);
		}
		
		if ( $width = mp_get_setting('list_img_width') ) {
			mp_push_to_array($settings, 'list_img_size_custom->width', $width);
			unset($settings['list_img_width']);
		}
		
		if ( $height = mp_get_setting('product_img_height') ) {
			mp_push_to_array($settings, 'product_img_size_custom->height', $height);
			unset($settings['product_img_height']);
		}
		
		if ( $width = mp_get_setting('product_img_width') ) {
			mp_push_to_array($settings, 'product_img_size_custom->width', $width);
			unset($settings['product_img_width']);
		}
		
		return $settings;
	}
	
	/**
	 * Updates notification settings.
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 */
	public function update_notification_settings( $settings ) {		
		if ( $subject = mp_get_setting('email->new_order_subject') ) {
			mp_push_to_array($settings, 'email->new_order->subject', $subject);
			unset($settings['new_order_subject']);
		}
		
		if ( $text = mp_get_setting('email->new_order_txt') ) {
			mp_push_to_array($settings, 'email->new_order->text', $text);
			unset($settings['email']['new_order_txt']);
		}

		if ( $subject = mp_get_setting('email->shipped_order_subject') ) {
			mp_push_to_array($settings, 'email->order_shipped->subject', $subject);
			unset($settings['email']['shipped_order_subject']);
		}
		
		if ( $text = mp_get_setting('email->shipped_order_txt') ) {
			mp_push_to_array($settings, 'email->order_shipped->text', $text);
			unset($settings['email']['shipped_order_txt']);
		}
	
		return $settings;
	}
	
	/**
	 * Creates a backup of the mp_settings and mp_coupons options.
	 *
	 * In the event that a user needs to rollback to a plugin version < 3.0 this data can be used to restore legacy settings.
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 */
	public function backup_legacy_settings( $settings ) {
		if ( ! get_option('mp_settings_legacy') ) {
			add_option('mp_settings_legacy', $settings, '', false);
		}
		
		if ( ! get_option('mp_coupons_legacy') ) {
			add_option('mp_coupons_legacy', get_option('mp_coupons'), '', false);
		}
	}
	
	/**
	 * Updates the product post type
	 *
	 * Updates the product post type to avoid conflicts with WooCommerce
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb
	 */
	public function update_product_post_type() {
		global $wpdb;
		$wpdb->update($wpdb->posts, array('post_type' => 'mp_product'), array('post_type' => 'product'));
	}
	
	/**
	 * Updates the admin capabilities
	 *
	 * @since 3.0
	 * @access public
	 * @action after_switch_theme
	 */
	
	public function update_admin_caps() {
		$role = get_role('administrator');
		$taxonomies = array('product_category', 'product_tag');
		$pts = array('mp_product', 'product_coupon', 'mp_order');
		
		foreach ( $taxonomies as $tax_slug ) {
			$tax = get_taxonomy($tax_slug);
			foreach ( $tax->cap as $cap ) {
				$role->add_cap($cap);
			}
		}
		
		foreach ( $pts as $pt_slug ) {
			$pt = get_post_type_object($pt_slug);
			foreach ( $pt->cap as $cap ) {
				$role->add_cap($cap);
			}
		}
	}
	
	/**
	 * Runs on 3.0 update.
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 */
	public function update_3000( $settings ) {
		$this->backup_legacy_settings($settings);
		$this->update_coupon_schema();
		$this->create_product_attributes_table();
		$this->update_product_post_type();
		$this->update_admin_caps();
		$settings = $this->update_notification_settings($settings);
		$settings = $this->update_presentation_settings($settings);
		
		// Create store pages
		add_action('admin_init', array(&$this, 'create_store_pages'));
		
		//currency changes
		if ( 'TRL' == mp_get_setting('currency') ) {
			$settings['currency'] = 'TRY';
		}

		return $settings;
	}	
	
	/**
	 * Runs on 2.9.2.3 update to fix low inventory emails not being sent.
	 *
	 * @since 3.0
	 * @access public
	 */
	public function update_2923() {
		global $wpdb;
		$wpdb->delete($wpdb->postmeta, array('meta_key' => 'mp_stock_email_sent'), array('%s'));
	}

	/**
	 * Runs on 2.1.4 update to fix price sorts.
	 *
	 * @since 3.0
	 * @access public
	 */
	public function update_214() {
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
	 * Updates the coupon schema.
	 *
	 * @since 3.0
	 * @access public
	 */
	public function update_coupon_schema() {
		$coupons = get_option('mp_coupons');
		
		if ( empty($coupons) ) {
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
					'value' => date('Y-m-d', $coupon['start']),
				),
				'indefinite' => array(
					'type' => 'WPMUDEV_Field_Checkbox',
					'value' => ( empty($coupon['end']) ) ? '0' : '1', 
				),
				'end_date' => array(
					'type' => 'WPMUDEV_Field_Datepicker',
					'value' =>  ( empty($coupon['end']) ) ? '' : date('Y-m-d', $coupon['end']),
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
				$field->save_value($post_id, $name, $data['value'], true);
			}
		}
		
		delete_option('mp_coupons');
	}
}

MP_Installer::get_instance();