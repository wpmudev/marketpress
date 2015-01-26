<?php

class MP_Multisite {
	/**
	 * Refers to the current multisite build
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	var $build = 2;
	
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
			self::$_instance = new MP_Multisite();
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
		if ( is_network_admin() ) {
			// Network admin stuff
			$this->maybe_install();
		} elseif ( is_admin() ) {
			if ( mp_doing_ajax() ) {
				// Ajax stuff
			} else {
				// Non-ajax stuff
			}
		} else {
			// Front end stuff
		}
	}

	/**
	 * Fixes bad mp_term_relationships records (e.g. term_id = 0)
	 *
	 * @since 2.9.4
	 * @access public
	 * @uses $wpdb
	 */
	public function fix_bad_term_relationships() {
		global $wpdb;
		
		$current_blog_id = get_current_blog_id();
		$results = $wpdb->get_results("
			SELECT t1.post_id AS global_id, t1.term_id, t2.blog_id, t2.post_id
			FROM {$wpdb->base_prefix}mp_term_relationships t1
			INNER JOIN {$wpdb->base_prefix}mp_products t2 ON t2.id = t1.post_id 
			WHERE t1.term_id = 0
			ORDER BY t2.blog_id ASC
		");
		
		if ( is_array($results) ) {
			foreach ( $results as $row ) {
				if ( get_current_blog_id() != $row->blog_id ) {
					switch_to_blog( $row->blog_id );
				}
				
				$this->index_product( $row->post_id );
			}
			
			switch_to_blog( $current_blog_id );
		}
	}

	/**
	 * Check to see if install sequence needs to be run
	 *
	 * @since 3.0
	 * @access public
	 */
	public function maybe_install() {
		$build = (int) get_site_option( 'mp_network_build', 1 );
		
		//check if installed
		if ( $this->build === $build ) {
			return;
		}
		
		switch ( $build ) {
			case 1 :
				if ( ! get_site_option( 'mp_network_settings' ) ) {
					$this->initial_install();
				}
			break;
			
			case 2 :
				$this->fix_bad_term_relationships();
			break;
		}
		
		$this->ms_tables();
		
		update_site_option( 'mp_network_build', $this->build );
		update_option( 'mp_flush_rewrites', 1 );
	}
	
	/**
	 * Create/update multisite tables
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb
	 */
	public function ms_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		$charset_collate = $wpdb->get_charset_collate();
		
		// mp_products table
		dbDelta( "CREATE TABLE {$wpdb->base_prefix}mp_products (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			site_id bigint(20),
			blog_id bigint(20),
			blog_public int(2),
			post_id bigint(20),
			post_author bigint(20) UNSIGNED NOT NULL DEFAULT '0',
			post_title text NOT NULL,
			post_content longtext NOT NULL,
			post_permalink text NOT NULL,
			post_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			post_date_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			post_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			post_modified_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			price decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
			sales_count bigint(20) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY	 (id)
		) $charset_collate;" );
		
		// mp_terms table
		dbDelta( "CREATE TABLE {$wpdb->base_prefix}mp_terms (
			term_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(200) NOT NULL DEFAULT '',
			slug varchar(200) NOT NULL DEFAULT '',
			type varchar(20) NOT NULL DEFAULT 'product_category',
			count bigint(10) NOT NULL DEFAULT '0',
			PRIMARY KEY  (term_id),
			UNIQUE KEY slug (slug),
			KEY name (name)
		) $charset_collate;" );

		// mp_terms_relationships
		dbDelta( "CREATE TABLE {$wpdb->base_prefix}mp_term_relationships (
			post_id bigint(20) UNSIGNED NOT NULL,
			term_id bigint(20) UNSIGNED NOT NULL,
			PRIMARY KEY  (post_id, term_id),
			KEY (term_id)
		) $charset_collate;" );
	}
}

MP_Multisite::get_instance();