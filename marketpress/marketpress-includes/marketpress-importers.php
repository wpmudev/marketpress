<?php
/*
MarketPress Importers
*/

/*
 * Parent class to extend
 */
class MarketPress_Importer {
  var $importer_name = '';
	var $results;
  
	function MarketPress_Importer() {
		$this->__construct();
	}
	
  function __construct() {
    global $mp;
		
		$priority = isset($_POST['mp_import-'.sanitize_title($this->importer_name)]) ? 1 : 10;
		
    add_action( 'marketpress_add_importer', array(&$this, '_html'), $priority );
		
		$this->on_creation();
	}

	function _html() {
	  global $mp;
		
		if (isset($_POST['mp_import-'.sanitize_title($this->importer_name)])) {
			$this->process();
			remove_all_actions('marketpress_add_importer', 10);
		}
		?>
		<div class="postbox">
			<h3 class='hndle'><span><?php printf(__('Import From %s', 'mp'), $this->importer_name); ?></span></h3>
			<div class="inside">
			<?php $this->display(); ?>
			</div>
		</div>
		<?php
	}
	
	/* Public methods */
	
	function import_button($label = '') {
		$label = !empty($label) ? $label : __('Import Now &raquo;', 'mp');
		?>
		<p class="submit">
			<input type="submit" name="mp_import-<?php echo sanitize_title($this->importer_name); ?>" value="<?php echo $label; ?>" />
		</p>
		<?php
	}
	
	function on_creation() {

	}
	
	function display() {

	}
	
	function process() {
		
	}
	
}

/* ------------------------------------------------------------------ */
/* ----------------------- Begin Importers -------------------------- */
/* ------------------------------------------------------------------ */

/*
 * WP e-Commerce Plugin Importer
 */
class WP_eCommerceImporter extends MarketPress_Importer {

	var $importer_name = 'WP e-Commerce';

	function display() {
	  global $wpdb;

		if ($this->results) {
			?>
			<p><?php printf( __('Successfully imported %s products from WP e-Commerce. The old products were not deleted, so running the importer again will just create copies of the products in MarketPress.', 'mp'), number_format_i18n($this->results) ); ?></p>
      
			<?php if ( class_exists('WP_eCommerce') ) { ?>
			<p><?php printf( __('You should <a href="%s">deactivate the WP e-Commerce plugin now</a>. Have fun!', 'mp'), wp_nonce_url(admin_url('plugins.php?action=deactivate&plugin=wp-e-commerce%2Fwp-shopping-cart.php'), 'deactivate-plugin_wp-e-commerce/wp-shopping-cart.php') ); ?></p>
			<?php
			}
		} else {
			$num_products = $wpdb->get_var("SELECT count(*) FROM $wpdb->posts WHERE post_type = 'wpsc-product'");
			if ($num_products) {
				?>
				<span class="description"><?php _e('This will allow you to import your products and most of their attributes from the WP e-Commerce plugin.', 'mp'); ?></span>
	
				<p><?php printf( __('It appears that you have %s products from WP e-Commerce. Click below to begin your import!', 'mp'), number_format_i18n($num_products) ); ?></p>
				<?php
				$this->import_button(); 
	    } else { //no products
        ?>
				<p><?php printf( __('It appears you have no products from WP e-Commerce to import. Check that the plugin is updated to the latest version (the importer only works with >3.8).', 'mp'), wp_nonce_url(admin_url('plugins.php?action=deactivate&plugin=wp-e-commerce%2Fwp-shopping-cart.php'), 'deactivate-plugin_wp-e-commerce/wp-shopping-cart.php') ); ?></p>
				<?php
			}
		}
	}
	
	function process() {
	  global $wpdb;

		set_time_limit(90); //this can take a while
		$this->results = 0;
		$products = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_type = 'wpsc-product'", ARRAY_A);
		foreach ($products as $product) {
			//import product
			$old_id = $product['ID'];
			unset($product['ID']); //clear id so it inserts as new
			$product['post_type'] = 'product';
			$product['comment_status'] = 'closed';
			$product['comment_count'] = 0;
			
			//add tags
			$tags = wp_get_object_terms($old_id, 'product_tag');
			if (is_array($tags) && count($tags)) {
				$product_tags = array();
				foreach ($tags as $tag) {
					$product_tags[] = $tag->name;
				}
				$product_tags = join(", ", $product_tags);
				$product['tax_input']['product_tag'] = $product_tags;
			}

			$new_id = wp_insert_post($product); //create the post
			
			//insert categories
			$cats = wp_get_object_terms($old_id, 'wpsc_product_category');
			if (is_array($cats) && count($cats)) {
				$product_cats = array();
				foreach ($cats as $cat) {
					$product_cats[] = $cat->name;
				}
				wp_set_object_terms($new_id, $product_cats, 'product_category');
			}
			
			//add product meta
			$meta = get_post_custom($old_id);
			$meta_data = unserialize($meta['_wpsc_product_metadata'][0]);

			update_post_meta($new_id, 'mp_sku', $meta['_wpsc_sku']); //add sku
			update_post_meta($new_id, 'mp_price', $meta['_wpsc_price']); //add price
			
			//add sale price only if set and different than reg price
			if (isset($meta['_wpsc_special_price'][0]) && $meta['_wpsc_special_price'][0] && $meta['_wpsc_special_price'][0] != $meta['_wpsc_price'][0]) {
				update_post_meta($new_id, 'mp_is_sale', 1);
				update_post_meta($new_id, 'mp_sale_price', $meta['_wpsc_special_price']);
			}

			//add stock count
			if (isset($meta['_wpsc_stock'][0]) && $meta['_wpsc_stock'][0]) {
				update_post_meta($new_id, 'mp_track_inventory', 1);
				update_post_meta($new_id, 'mp_inventory', $meta['_wpsc_stock']);
			}

			//add external link
			if (!empty($meta_data['external_link']))
				update_post_meta($new_id, 'mp_product_link', esc_url_raw($meta_data['external_link']));

			//add extra shipping
			if (isset($meta_data['shipping']['local']) && $meta_data['shipping']['local'])
				update_post_meta($new_id, 'mp_shipping', array( 'extra_cost' => $meta_data['shipping']['local'] ) );

			//add thumbnail
			if (isset($meta['_thumbnail_id'][0])) { //if featured image is set
				update_post_meta($new_id, '_thumbnail_id', $meta['_thumbnail_id'][0]);
			} else { //grab first attachment as there is no featured image
				$images =& get_children( "post_type=attachment&post_mime_type=image&post_parent=$old_id" );
				$thumbnail_id = false;
				foreach ( (array) $images as $attachment_id => $attachment ) {
					$thumbnail_id = $attachment_id;
					break; //only grab the first attachment
				}
				if ($thumbnail_id)
					update_post_meta($new_id, '_thumbnail_id', $thumbnail_id);
			}
				
			//get first downloadable product url
			$args = array(
				'post_type' => 'wpsc-product-file',
				'post_parent' => $old_id,
				'numberposts' => 1,
				'post_status' => 'any'
			);
			$attached_files = (array)get_posts($args);
			if (count($attached_files))
				update_post_meta($new_id, 'mp_file', esc_url_raw($attached_files[0]->guid));
			
			//inc count
			$this->results++;
		}	
	}
}
//only load if the plugin is active and installed
$mp_wpecommerce = new WP_eCommerceImporter();


/*
 * CSV Importer
 */
class CsvImporter extends MarketPress_Importer {

	var $importer_name = 'CSV';

	function display() {
	  ?>
		<span class="description"><?php _e('This will allow you to import products and most of their attributes from a CSV file.', 'mp'); ?></span>

		<p><?php _e('Coming soon...', 'mp'); ?></p>
		<?php
	}
}
//only load if the plugin is active and installed
$mp_csv = new CsvImporter();
?>