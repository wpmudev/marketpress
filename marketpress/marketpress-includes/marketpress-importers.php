<?php
/*
MarketPress Importers
*/

/*
 * Parent class to extend
 */
class MarketPress_Importer {
  var $importer_name = '';
  
	function MarketPress_Importer() {
		$this->__construct();
	}
	
  function __construct() {
    global $mp;

    add_filter( 'marketpress_tabs', array(&$this, '_add_tab') );
    add_action( 'marketpress_add_screen', array(&$this, '_html') );

		$this->on_creation();
	}

	function _html($tab) {
	  global $mp;
	
		if ( $tab != sanitize_title($this->importer_name) )
		  return;
		?>
    <div class="icon32"><img src="<?php echo $mp->plugin_url . 'images/import.png'; ?>" /></div>
      <form id="mp-import-form" method="post" action="">
			<h2><?php printf(__('Import From %s', 'mp'), $this->importer_name); ?></h2>
	    <div id="poststuff" class="metabox-holder mp-importer">
	      <div class="postbox">
	        <h3 class='hndle'><span><?php echo $this->importer_name; ?></span></h3>
	        <div class="inside">
					<?php $this->display_process(); ?>
	        </div>
	      </div>
	    </div>
	    </form>
    </div>
    <?php
	}

  function _add_tab($tabs) {
    $importer = sanitize_title($this->importer_name);
		$tabs[$importer] = sprintf(__('%s Importer', 'mp'), $this->importer_name);
		return $tabs;
	}
	
	/* Public methods */
	
	function on_creation() {

	}
	
	function display_process() {

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

	function display_process() {
	  global $wpdb;

		if (isset($_POST['import'])) {
      set_time_limit(90); //this can take a while
      $num_products = 0;
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
        
				//add sale price
				if (isset($meta['_wpsc_special_price'][0]) && $meta['_wpsc_special_price'][0]) {
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
				if (isset($meta['_thumbnail_id'][0]))
    			update_post_meta($new_id, '_thumbnail_id', $meta['_thumbnail_id'][0]);
    			
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
				$num_products++;
			}
			?>
			<p><?php printf( __('Successfully imported %s products from WP e-Commerce. The old products were not deleted, so running the importer again will just create copies of the products in MarketPress.', 'mp'), number_format_i18n($num_products) ); ?></p>
      <p><?php printf( __('You should <a href="%s">deactivate the WP e-Commerce plugin now</a>. Have fun!', 'mp'), wp_nonce_url(admin_url('plugins.php?action=deactivate&plugin=wp-e-commerce%2Fwp-shopping-cart.php'), 'deactivate-plugin_wp-e-commerce/wp-shopping-cart.php') ); ?></p>
			<?php
		} else {
			$num_products = $wpdb->get_var("SELECT count(*) FROM $wpdb->posts WHERE post_type = 'wpsc-product'");
			if ($num_products) {
			?>
			<span class="description"><?php _e('This will allow you to import your products and most of their attributes from the WP e-Commerce plugin.', 'mp'); ?></span>

			<p><?php printf( __('It appears that you have %s products from WP e-Commerce. Click below to begin your import!', 'mp'), number_format_i18n($num_products) ); ?></p>
	    <p class="submit">
	      <input type="submit" name="import" value="<?php _e('Import Now &raquo;', 'mp') ?>" />
	    </p>
	    <?php
	    } else { //no products
        ?>
				<p><?php printf( __('It appears you have no products in WP e-Commerce to import. You should <a href="%s">deactivate the WP e-Commerce plugin</a>, or check that the plugin is updated to the latest version (the importer only works with >3.8).', 'mp'), wp_nonce_url(admin_url('plugins.php?action=deactivate&plugin=wp-e-commerce%2Fwp-shopping-cart.php'), 'deactivate-plugin_wp-e-commerce/wp-shopping-cart.php') ); ?></p>
				<?php
			}
		}
	}

}
//only load if the plugin is active and installed
if ( class_exists('WP_eCommerce') ) {
	$mp_wpecommerce = new WP_eCommerceImporter();
}
?>