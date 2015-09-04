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
		if ( is_null( self::$_instance ) ) {
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
		add_action( 'init', array( &$this, 'run' ) );
		add_action( 'after_switch_theme', array( &$this, 'add_admin_store_caps' ) );
		add_action( 'admin_notices', array( &$this, 'db_update_notice' ) );
		add_action( 'admin_menu', array( &$this, 'add_menu_items' ), 99 );
		add_action( 'wp_ajax_mp_update_product_postmeta', array( &$this, 'update_product_postmeta' ) );
		add_action( 'admin_print_scripts-store-settings_page_mp-db-update', array( &$this, 'enqueue_db_update_scripts' ) );
	}

	/**
	 * Enqueue db update scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_db_update_scripts() {
		wp_enqueue_style( 'jquery-smoothness', mp_plugin_url( 'includes/admin/ui/smoothness/jquery-ui-1.10.4.custom.css' ), '', MP_VERSION );
		wp_enqueue_script( 'mp-db-update', mp_plugin_url( 'includes/admin/ui/js/db-update.js' ), array( 'jquery-ui-progressbar' ), MP_VERSION );
		wp_localize_script( 'mp-db-update', 'mp_db_update', array(
			'error_text'	 => __( 'An error occurred while updating. Please refresh this page and try again.', 'mp' ),
			'progressbar'	 => array(
				'label_text'	 => __( 'Upgrading Database...Please Wait...', 'mp' ),
				'complete_text'	 => __( 'Complete!', 'mp' ),
			),
		) );
	}

	public function possible_product_combinations( $groups, $prefix = '' ) {
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

	public function get_product_variation_value_by_index( $post_id, $post_meta_name, $index, $single = false,
													   $default_value = '' ) {


		if ( 'mp_shipping->extra_cost' == $post_meta_name ) {
			$value = get_post_meta( $post_id, 'mp_shipping', false );
			if ( isset( $value[ 'extra_cost' ] ) ) {
				return $value[ 'extra_cost' ];
			} else {
				return '';
			}
		}

		$value = get_post_meta( $post_id, $post_meta_name, $single );

		if ( 'mp_special_tax' == $post_meta_name ) {
			$value = $value * 100;
		}

		if ( $value && !empty( $value ) ) {
			if ( $single ) {
				return $value;
			} else {
				$value = isset( $value[ 0 ][ $index ] ) ? $value[ 0 ][ $index ] : $default_value;
				return $value;
			}
		} else {
			return $default_value;
		}
	}

	public function product_variations_transition( $post_id, $product_type ) {
		global $wp_taxonomies, $wpdb;

		$variation_values		 = null;
		$variation_values		 = get_post_meta( $post_id, 'mp_var_name', true );
		//$variation_name		 = 'Variation'; //default variation category / attribute name
		$variation_names[ 0 ]	 = 'Variation';
		$data					 = array();

		if ( isset( $variation_values ) && !empty( $variation_values ) ) {

			update_post_meta( $post_id, 'has_variations', 1 );

			$i = 0;

			foreach ( $variation_names as $variation_name ) {

				$variation_name = MP_Products_Screen::maybe_create_attribute( 'product_attr_' . $variation_name, $variation_name ); //taxonomy name made of the prefix and attribute's ID

				$args = array(
					'orderby'		 => 'name',
					'hide_empty'	 => false,
					'fields'		 => 'all',
					'hierarchical'	 => true,
				);

				/* Get terms for the given taxonomy (variation name i.e. color, size etc) */
				$terms = get_terms( array( $variation_name ), $args );

				/* Put variation values in the array */
				$variation_values_row	 = $variation_values;
				//$variation_values_row	 = str_replace( array( '[', ']', '"' ), '', $variation_values_row );
				//$variations_data		 = explode( ',', $variation_values_row );
				$variations_data		 = $variation_values_row;

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

			wp_suspend_cache_addition( true );
			wp_defer_term_counting( true );
			wp_defer_comment_counting( true );

			$wpdb->query( 'SET autocomit = 0;' );

			foreach ( $combinations as $combination ) {

				$post_title		 = get_the_title( $post_id );
				$post_content	 = get_the_content( $post_id );
				$variation_id	 = wp_insert_post( array(
					'post_title'	 => $post_title,
					'post_content'	 => $post_content,
					'post_status'	 => 'publish',
					'post_type'		 => MP_Product::get_variations_post_type(),
					'post_parent'	 => $post_id,
				) );

				/* Make a variation name from the combination */
				$variation_title_combinations = explode( '|', $combinations_original[ $combination_index ] );

				$variation_name_title = '';

				foreach ( $variation_title_combinations as $variation_title_combination ) {
					$variation_name_title_array = explode( '=', $variation_title_combination );
					$variation_name_title .= $variation_name_title_array[ 1 ] . ' ';
				}

				$sku						 = $this->get_product_variation_value_by_index( $post_id, 'mp_sku', $combination_index );
				$inventory_tracking			 = $this->get_product_variation_value_by_index( $post_id, 'mp_track_inventory', $combination_index, true );
				$inventory					 = $this->get_product_variation_value_by_index( $post_id, 'mp_inventory', $combination_index );
				$file_url					 = $this->get_product_variation_value_by_index( $post_id, 'mp_file', $combination_index, true );
				$external_url				 = $this->get_product_variation_value_by_index( $post_id, 'mp_product_link', $combination_index, true );
				$regular_price				 = $this->get_product_variation_value_by_index( $post_id, 'mp_price', $combination_index );
				$sale_price					 = $this->get_product_variation_value_by_index( $post_id, 'mp_sale_price', $combination_index );
				$has_sale					 = $this->get_product_variation_value_by_index( $post_id, 'mp_is_sale', $combination_index, true );
				$special_tax_rate			 = $this->get_product_variation_value_by_index( $post_id, 'mp_special_tax', $combination_index, true );
				$description				 = $this->get_product_variation_value_by_index( $post_id, 'mp_custom_field_label', $combination_index );
				$weight_extra_shipping_cost	 = $this->get_product_variation_value_by_index( $post_id, 'mp_shipping->extra_cost', $combination_index, true );

				$this->post_meta_transition( $post_id, 'mp_shipping', 'weight_extra_shipping_cost' );

				if ( is_numeric( $special_tax_rate ) ) {
					$charge_tax = 1;
				} else {
					$charge_tax = 0;
				}

				if ( is_numeric( $weight_extra_shipping_cost ) ) {
					$charge_shipping = 1;
				} else {
					$charge_shipping = 0;
				}

				$variation_metas = apply_filters( 'mp_variations_meta', array(
					'name'						 => $variation_name_title, //mp_get_post_value( 'post_title' ),
					'sku'						 => $sku,
					'inventory_tracking'		 => $inventory_tracking,
					'inventory'					 => $inventory,
					'inv_out_of_stock_purchase'	 => 0,
					'file_url'					 => $file_url,
					'external_url'				 => $external_url,
					'regular_price'				 => $regular_price,
					'sale_price_amount'			 => $sale_price,
					'has_sale'					 => $has_sale,
					'special_tax_rate'			 => $special_tax_rate,
					'description'				 => $description,
					'sale_price_start_date'		 => '',
					'sale_price_end_date'		 => '',
					'sale_price'				 => '', //array - to do
					'weight'					 => '', //array - to do
					'weight_pounds'				 => '',
					'weight_ounces'				 => '',
					'charge_shipping'			 => $charge_shipping,
					'charge_tax'				 => $charge_tax,
					'weight_extra_shipping_cost' => $weight_extra_shipping_cost,
				), mp_get_post_value( 'post_ID' ), $variation_id );

				/* Add default post metas for variation */
				foreach ( $variation_metas as $meta_key => $meta_value ) {
					update_post_meta( $variation_id, $meta_key, sanitize_text_field( $meta_value ) );
				}

				/* Add post terms for the variation */
				$variation_terms = explode( '|', $combination );

				foreach ( $variation_terms as $variation_term ) {
					$variation_term_vals = explode( '=', $variation_term );
					//has_term( $term, $taxonomy, $post )
					//wp_set_object_terms
					if ( !has_term( MP_Products_Screen::term_id( $variation_term_vals[ 1 ], $variation_term_vals[ 0 ] ), $variation_term_vals[ 0 ], $variation_id ) ) {
						wp_set_post_terms( $variation_id, MP_Products_Screen::term_id( $variation_term_vals[ 1 ], $variation_term_vals[ 0 ] ), $variation_term_vals[ 0 ], true );
					}
				}

				$combination_num++;
				$combination_index++;

				do_action( 'mp_update/variation', $variation_id );
			}

			$wpdb->query( 'COMMIT;' );
			$wpdb->query( 'SET autocommit = 1;' );

			wp_suspend_cache_addition( false );
			wp_defer_term_counting( false );
			wp_defer_comment_counting( false );
			//}
			//exit;
		} else {
			//do nothing
		}
	}

	public function post_meta_transition( $post_id, $old_post_meta_name, $new_post_meta_name ) {
		$old_value = get_post_meta( $post_id, $old_post_meta_name, true );

		if ( is_array( $old_value ) ) {
			$old_value = $old_value[ 0 ];
		}

		if ( $new_post_meta_name == 'special_tax_rate' ) {
			if ( $old_value > 0 ) {
				update_post_meta( $post_id, 'charge_tax', '1' );
			} else {
				update_post_meta( $post_id, 'charge_tax', '0' );
			}
			$old_value = $old_value * 100; //20% was marked as 0.2 in the previous version
		}

		if ( $old_post_meta_name == 'mp_shipping' ) {
			$old_value = get_post_meta( $post_id, $old_post_meta_name, true );
			if ( isset( $old_value ) && is_array( $old_value ) ) {
				$old_value = $old_value[ 'extra_cost' ];
			} else {
				$old_value = 0;
			}

			if ( (int) $old_value > 0 ) {
				update_post_meta( $post_id, 'charge_shipping', '1' );
			}
		}

		/* if($old_post_meta_name == 'mp_shipping->extra_cost'){

		  } */

		update_post_meta( $post_id, $new_post_meta_name, $old_value );
	}

	/**
	 * Update product postmeta
	 *
	 * @since 3.0
	 * @access public
	 * @action wp_ajax_mp_update_product_postmeta
	 */
	public function update_product_postmeta() {
		if ( !wp_verify_nonce( mp_get_post_value( '_wpnonce' ), 'mp_update_product_postmeta' ) ) {
			wp_send_json_error();
		}

		$old_version = get_option( 'mp_previous_version' );
		if ( version_compare( $old_version, '3.0.0.2', '<' ) ) {
			$update_fix_needed = true;
		} else {
			$update_fix_needed = false;
		}

		ini_set( 'max_execution_time', 0 );
		set_time_limit( 0 );

		$per_page	 = 20;
		$query		 = new WP_Query( array(
			'cache_results'			 => false,
			'update_post_term_cache' => false,
			'post_type'				 => 'product',
			'posts_per_page'		 => $per_page,
			'paged'					 => max( 1, mp_get_post_value( 'page' ) ),
		) );

		$page	 = mp_get_post_value( 'page', 1 );
		$updated = ($page * $per_page);

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();

			$variations = get_post_meta( $post_id, 'mp_var_name', true );

			if ( $variations && is_array( $variations ) && $update_fix_needed == false ) {//need update since it used mp_var_name post meta which is not used in the 3.0 version
				if ( count( $variations ) > 1 ) {
					//It's a variation product

					$mp_file		 = get_post_meta( $post_id, 'mp_file', true );
					$mp_product_link = get_post_meta( $post_id, 'mp_product_link', true );

					if ( !empty( $mp_file ) ) {
						$product_type = 'digital';
					} else if ( !empty( $mp_product_link ) ) {
						$product_type = 'external';
					} else {
						$product_type = 'physical';
					}

					update_post_meta( $post_id, 'product_type', $product_type );

					$response = array(
						'updated'	 => ceil( $updated / $query->found_posts ) * 100,
						'is_done'	 => false,
					);

					$this->product_variations_transition( $post_id, $product_type );
				} else {
					//It's single/regular/non-variant product

					$post_thumbnail = get_post_thumbnail_id( $post_id );
					if ( is_numeric( $post_thumbnail ) ) {
						update_post_meta( $post_id, 'mp_product_images', $post_thumbnail );
					}

					$mp_file		 = get_post_meta( $post_id, 'mp_file', true );
					$mp_product_link = get_post_meta( $post_id, 'mp_product_link', true );

					if ( !empty( $mp_file ) ) {
						$product_type = 'digital';
					} else if ( !empty( $mp_product_link ) ) {
						$product_type = 'external';
					} else {
						$product_type = 'physical';

						$weight_pounds	 = get_post_meta( $post_id, 'weight_pounds', true );
						$weight_ounces	 = get_post_meta( $post_id, 'weight_ounces', true );

						if ( empty( $weight_ounces ) ) {
							update_post_meta( $post_id, 'weight_ounces', 0 );
						}

						if ( empty( $weight_pounds ) ) {
							update_post_meta( $post_id, 'weight_pounds', 0 );
						}
					}

					update_post_meta( $post_id, 'product_type', $product_type );

					$this->post_meta_transition( $post_id, 'mp_sku', 'sku' );
					$this->post_meta_transition( $post_id, 'mp_price', 'regular_price' );
					$this->post_meta_transition( $post_id, 'mp_sale_price', 'sale_price_amount' );
					$this->post_meta_transition( $post_id, 'mp_track_inventory', 'track_inventory' );
					$this->post_meta_transition( $post_id, 'mp_inventory', 'inventory' );
					$this->post_meta_transition( $post_id, 'mp_special_tax', 'special_tax_rate' );
					$this->post_meta_transition( $post_id, 'mp_is_sale', 'has_sale' );

					$this->post_meta_transition( $post_id, 'mp_shipping', 'extra_shipping_cost' );
					$this->post_meta_transition( $post_id, 'mp_shipping', 'weight_extra_shipping_cost' );

					$this->post_meta_transition( $post_id, 'mp_file', 'file_url' ); //If not empty then mark it as digital product
					$this->post_meta_transition( $post_id, 'mp_product_link', 'external_url' ); //If not empty then mark it as external product
				//
				}
			} else {//update for 3.0 and 3.0.0.1
				$post_thumbnail = get_post_thumbnail_id( $post_id );
				if ( is_numeric( $post_thumbnail ) ) {
					update_post_meta( $post_id, 'mp_product_images', $post_thumbnail );
				}
			}

			do_action( 'mp_update/product', $post_id );
		}

		$response = array(
			'updated'	 => ceil( $updated / $query->found_posts ) * 100,
			'is_done'	 => false,
		);

		if ( $updated >= $query->found_posts ) {
			$response[ 'is_done' ] = true;
		}

		delete_option( 'mp_db_update_required' );

		wp_send_json_success( $response );
	}

	/**
	 * Update tax settings
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 * @return array
	 */
	public function update_tax_settings( $settings ) {
		if ( $rates = mp_arr_get_value( 'tax->rate->canada_rate', $settings ) ) {
			foreach ( $rates as $key => $value ) {
				mp_push_to_array( $settings, "tax->canada_rate->{$key}", $value );
			}

			unset( $settings[ 'tax' ][ 'rate' ][ 'canada_rate' ] );
		}

		return $settings;
	}

	/**
	 * Add admin menu items and enqueue scripts
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_menu
	 */
	public function add_menu_items() {
		if ( get_option( 'mp_db_update_required' ) ) {
			add_submenu_page( 'store-settings', __( 'Update Data', 'mp' ), __( 'Update Data', 'mp' ), 'activate_plugins', 'mp-db-update', array( &$this, 'db_update_page' ) );
		}
	}

	/**
	 * Add term_order column to $wpdb->terms table
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb
	 */
	public function add_term_order_column() {
		global $wpdb;

		$result = $wpdb->query( "SHOW COLUMNS FROM $wpdb->terms LIKE 'term_order'" );

		if ( $result == 0 ) {
			$result = $wpdb->query( "ALTER TABLE $wpdb->terms ADD `term_order` SMALLINT UNSIGNED NULL DEFAULT '0' AFTER `term_group`" );
		}
	}

	/**
	 * Display the db update page
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb
	 */
	public function db_update_page() {
		global $wpdb;
		?>
		<div class="wrap">
			<h2><?php _e( 'Update MarketPress Data', 'mp' ); ?></h2>
			<h4><?php _e( 'MarketPress requires a database update to continue working correctly.<br />Below you will find a list of items that require your attention.', 'mp' ); ?></h4>

			<br />

			<?php
			$old_version = get_option( 'mp_previous_version' );
			if ( version_compare( $old_version, '3.0.0.2', '<' ) ) {
				$update_fix_needed = true;
			} else {
				$update_fix_needed = false;
			}

			if ( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'mp_var_name'" ) || $update_fix_needed ) {
				$postcount = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product'" );
				?>
				<style type="text/css">
					.ui-progressbar {
						position: relative;
						width: 400px;
					}
					.progress-label {
						position: absolute;
						left: 0;
						top: 4px;
						font-weight: bold;
						text-align: center;
						text-shadow: 1px 1px 0 #fff;
						width: 100%;
					}
				</style>
				<h2><?php _e( 'Product Metadata', 'mp' ); ?></h2>
				<form id="mp-update-product-postmeta-form" action="<?php echo admin_url( 'admin-ajax.php' ); ?>">
					<?php wp_nonce_field( 'mp_update_product_postmeta' ); ?>
					<input type="hidden" name="action" value="mp_update_product_postmeta" />
					<input type="hidden" name="page" value="1" />
					<p class="mp-important"><strong><?php _e( 'Depending on the amount of products you have, this update could take quite some time. Please keep this window open while the update completes. If you have products with multiple variations, the progress bar may move slower, please don\'t exit the window.', 'mp' ); ?></strong></p>
					<?php
					if ( is_multisite() ) {
						?>
						<p class="mp-important"><strong><?php _e( 'Please update each subsite in your WordPress network where you have older version of the MarketPress plugin.', 'mp' ); ?></strong></p>
						<?php
					}
					?>
					<p class="submit"><input class="button-primary" type="submit" value="<?php _e( 'Perform Update', 'mp' ); ?>"></p>
				</form>
				<?php
			} else {
				_e( 'MarketPress performed a quick automatic update successfully!', 'mp' );
				delete_option( 'mp_db_update_required' );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Display data update notice
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_notices
	 */
	public function db_update_notice() {
		if ( !get_option( 'mp_db_update_required' ) || !current_user_can( 'activate_plugins' ) || mp_get_get_value( 'page' ) == 'mp-db-update' ) {
			return;
		}

		echo '<div class="error"><p>' . sprintf( __( 'MarketPress requires a database update to continue working correctly. <a class="button-primary" href="%s">Go to update page</a>', 'mp' ), admin_url( 'admin.php?page=mp-db-update' ) ) . '</p></div>';
	}

	/**
	 * Runs the installer code.
	 *
	 * @since 3.0
	 * @access public
	 */
	public function run() {
		$old_version = get_option( 'mp_version' );

		if ( $old_version == MP_VERSION ) {
			return;
		}

		$old_settings = get_option( 'mp_settings', array() );

		// Filter default settings
		$default_settings	 = apply_filters( 'mp_default_settings', mp()->default_settings );
		$settings			 = array_replace_recursive( $default_settings, $old_settings );

		// Only run the follow scripts if this not a fresh install
		if ( !empty( $old_version ) ) {
			//2.1.4 update
			if ( version_compare( $old_version, '2.1.4', '<' ) ) {
				$this->update_214();
			}

			//2.9.2.3 update
			if ( version_compare( $old_version, '2.9.2.3', '<' ) ) {
				$this->update_2923();
			}

			//3.0 update
			if ( version_compare( $old_version, '3.0.0.2', '<' ) ) {
				$settings = $this->update_3000( $settings );
			}
		}

		// Update settings
		update_option( 'mp_settings', $settings );

		// Give admin role all store capabilities
		$this->add_admin_store_caps();
		// Add "term_order" to $wpdb->terms table
		$this->add_term_order_column();
		// Create/update product attributes table
		$this->create_product_attributes_table();

		// Only run these on first install
		if ( empty( $old_settings ) ) {
			add_action( 'widgets_init', array( &$this, 'add_default_widget' ), 11 );
		}

		//add action to flush rewrite rules after we've added them for the first time
		update_option( 'mp_flush_rewrites', 1 );

		update_option( 'mp_previous_version', $old_version );
		update_option( 'mp_version', MP_VERSION );
	}

	/**
	 * Creates the product attributes table.
	 *
	 * @since 3.0
	 * @access public
	 * @uses $wpdb
	 */
	public function create_product_attributes_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// Create mp_product_attributes table
		$table_name	 = $wpdb->prefix . 'mp_product_attributes';
		$sql		 = "CREATE TABLE $table_name (
			attribute_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			attribute_name varchar(45) DEFAULT '',
			attribute_terms_sort_by enum('ID','ALPHA','CUSTOM') DEFAULT NULL,
			attribute_terms_sort_order enum('ASC','DESC') DEFAULT NULL,
			PRIMARY KEY  (attribute_id)
		) $charset_collate;";
		dbDelta( $sql );

		// Create mp_product_attributes_terms table
		$table_name	 = $wpdb->prefix . 'mp_product_attributes_terms';
		$sql		 = "CREATE TABLE $table_name (
			attribute_id int(11) unsigned NOT NULL,
			term_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (attribute_id, term_id)
		) $charset_collate;";
		dbDelta( $sql );
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
	 * Updates presentation settings.
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 */
	public function update_presentation_settings( $settings ) {
		if ( $height = mp_get_setting( 'list_img_height' ) ) {
			mp_push_to_array( $settings, 'list_img_size_custom->height', $height );
			unset( $settings[ 'list_img_height' ] );
		}

		if ( $width = mp_get_setting( 'list_img_width' ) ) {
			mp_push_to_array( $settings, 'list_img_size_custom->width', $width );
			unset( $settings[ 'list_img_width' ] );
		}

		if ( $height = mp_get_setting( 'product_img_height' ) ) {
			mp_push_to_array( $settings, 'product_img_size_custom->height', $height );
			unset( $settings[ 'product_img_height' ] );
		}

		if ( $width = mp_get_setting( 'product_img_width' ) ) {
			mp_push_to_array( $settings, 'product_img_size_custom->width', $width );
			unset( $settings[ 'product_img_width' ] );
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
		if ( $subject = mp_get_setting( 'email->new_order_subject' ) ) {
			mp_push_to_array( $settings, 'email->new_order->subject', $subject );
			unset( $settings[ 'new_order_subject' ] );
		}

		if ( $text = mp_get_setting( 'email->new_order_txt' ) ) {
			mp_push_to_array( $settings, 'email->new_order->text', $text );
			unset( $settings[ 'email' ][ 'new_order_txt' ] );
		}

		if ( $subject = mp_get_setting( 'email->shipped_order_subject' ) ) {
			mp_push_to_array( $settings, 'email->order_shipped->subject', $subject );
			unset( $settings[ 'email' ][ 'shipped_order_subject' ] );
		}

		if ( $text = mp_get_setting( 'email->shipped_order_txt' ) ) {
			mp_push_to_array( $settings, 'email->order_shipped->text', $text );
			unset( $settings[ 'email' ][ 'shipped_order_txt' ] );
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
		if ( !get_option( 'mp_settings_legacy' ) ) {
			add_option( 'mp_settings_legacy', $settings, '', 'no' );
		}

		if ( !get_option( 'mp_coupons_legacy' ) ) {
			add_option( 'mp_coupons_legacy', get_option( 'mp_coupons' ), '', 'no' );
		}
	}

	/**
	 * Add store custom capabilities to admin users
	 *
	 * @since 3.0
	 * @access public
	 * @action after_switch_theme
	 */
	public function add_admin_store_caps() {
		$role		 = get_role( 'administrator' );
		$store_caps	 = mp_get_store_caps();

		foreach ( $store_caps as $cap ) {
			$role->add_cap( $cap );
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
		$this->_db_update_required();
		$this->backup_legacy_settings( $settings );
		$settings	 = $this->update_notification_settings( $settings );
		$settings	 = $this->update_presentation_settings( $settings );
		$settings	 = $this->update_tax_settings( $settings );

		//currency changes
		if ( 'TRL' == mp_get_setting( 'currency' ) ) {
			$settings[ 'currency' ] = 'TRY';
		}

		//set theme to new default 3.0 theme
		$settings[ 'store_theme' ] = 'default';

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
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => 'mp_stock_email_sent' ), array( '%s' ) );
	}

	/**
	 * Runs on 2.1.4 update to fix price sorts.
	 *
	 * @since 3.0
	 * @access public
	 */
	public function update_214() {
		global $wpdb;

		$posts = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product'" );

		foreach ( $posts as $post_id ) {
			$meta = get_post_custom( $post_id );
			//unserialize
			foreach ( $meta as $key => $val ) {
				$meta[ $key ]	 = maybe_unserialize( $val[ 0 ] );
				if ( !is_array( $meta[ $key ] ) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file" && $key != "mp_price_sort" )
					$meta[ $key ]	 = array( $meta[ $key ] );
			}

			//fix price sort field if missing
			if ( empty( $meta[ "mp_price_sort" ] ) && is_array( $meta[ "mp_price" ] ) ) {
				if ( $meta[ "mp_is_sale" ] && $meta[ "mp_sale_price" ][ 0 ] )
					$sort_price	 = $meta[ "mp_sale_price" ][ 0 ];
				else
					$sort_price	 = $meta[ "mp_price" ][ 0 ];
				update_post_meta( $post_id, 'mp_price_sort', $sort_price );
			}
		}
	}

	/**
	 * Set flag that db update is required
	 *
	 * @since 3.0
	 * @access public
	 */
	protected function _db_update_required() {
		add_option( 'mp_db_update_required', 1 );
	}

}

MP_Installer::get_instance();
