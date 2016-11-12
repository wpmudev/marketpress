<?php
/**
* Get Products columns
*/
function mp_get_products_csv_columns() {
	
	return array(
		'ID'                         => array( 'name' => __( 'ID', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_author'                => array( 'name' => __( 'Post Author', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_date'                  => array( 'name' => __( 'Post Date', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_date_gmt'              => array( 'name' => __( 'Post Date GMT', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_content'               => array( 'name' => __( 'Post Content', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_title'                 => array( 'name' => __( 'Post Title', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_excerpt'               => array( 'name' => __( 'Post Excerpt', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_status'                => array( 'name' => __( 'Post Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'comment_status'             => array( 'name' => __( 'Comment Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'ping_status'                => array( 'name' => __( 'Ping Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_password'              => array( 'name' => __( 'Post Password', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_name'                  => array( 'name' => __( 'Post Name', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'to_ping'                    => array( 'name' => __( 'To Ping', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'pinged'                     => array( 'name' => __( 'Pinged', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_modified'              => array( 'name' => __( 'Post Modified', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_modified_gmt'          => array( 'name' => __( 'Post Modified GMT', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_content_filtered'      => array( 'name' => __( 'Post Content Filtered', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_parent'                => array( 'name' => __( 'Post Parent', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'guid'                       => array( 'name' => __( 'GUID', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'menu_order'                 => array( 'name' => __( 'Menu Order', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_type'                  => array( 'name' => __( 'Post Type', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_mime_type'             => array( 'name' => __( 'Post Mime Type', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'comment_count'              => array( 'name' => __( 'Comment Count', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'tags'                       => array( 'name' => __( 'Tags', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'categories'                 => array( 'name' => __( 'Categories', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'_edit_last'                 => array( 'name' => __( 'Edit Last', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'_edit_lock'                 => array( 'name' => __( 'Edit Lock', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'_thumbnail_id'              => array( 'name' => __( 'Thumbnail ID', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'charge_shipping'            => array( 'name' => __( 'Charge Shipping', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_charge_shipping' ),
		'charge_tax'                 => array( 'name' => __( 'Charge Tax', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_charge_tax' ),
		'external_url'               => array( 'name' => __( 'External URL', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_external_url' ),
		'file_url'                   => array( 'name' => __( 'File URL', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_file_url' ),
		'has_sale'                   => array( 'name' => __( 'Has Sale', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_has_sale' ),
		'has_variation'              => array( 'name' => __( 'Has Variation', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_has_variation' ),
		'inv'                        => array( 'name' => __( 'Inv', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_inv' ),
		'inv_inventory'              => array( 'name' => __( 'Inv Inventory', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_inv_inventory' ),
		'inv_out_of_stock_purchase'  => array( 'name' => __( 'Inv Out Of Stock Purchase', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_inv_out_of_stock_purchase' ),
		'inventory'                  => array( 'name' => __( 'Inventory', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'inventory_tracking'         => array( 'name' => __( 'Inventory Tracking', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_inventory_tracking' ),
		'mp_product_images'          => array( 'name' => __( 'MP Product Images', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_sales_count'             => array( 'name' => __( 'MP Sales Count', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'per_order_limit'            => array( 'name' => __( 'Per Order Limit', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_per_order_limit' ),
		'product_images'             => array( 'name' => __( 'Product Images', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_product_images' ),
		'product_type'               => array( 'name' => __( 'Product Type', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_product_type' ),
		'regular_price'              => array( 'name' => __( 'Regular Price', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_regular_price' ),
		'related_products'           => array( 'name' => __( 'Related Products', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_related_products' ),
		'sale_price'                 => array( 'name' => __( 'Sale Price', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_sale_price' ),
		'sale_price_amount'          => array( 'name' => __( 'Sale Price Amount', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_sale_price_amount' ),
		'sale_price_end_date'        => array( 'name' => __( 'Sale Price End Date', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_sale_price_end_date' ),
		'sale_price_start_date'      => array( 'name' => __( 'Sale Price Start Date', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_sale_price_start_date' ),
		'sku'                        => array( 'name' => __( 'SKU', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_sku' ),
		'special_tax_rate'           => array( 'name' => __( 'Special Tax Rate', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_special_tax_rate' ),
		'variations_module'          => array( 'name' => __( 'Variation Module', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_variations_module' ),
		'name'						 => array( 'name' => __( 'Variation Title', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_name' ),
		'mp_variable_attribute'		 => array( 'name' => __( 'Variation Attribute', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_mp_variable_attribute' ),
		'weight'                     => array( 'name' => __( 'Weight', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_weight' ),
		'weight_extra_shipping_cost' => array( 'name' => __( 'Weight Extra Shipping Cost', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_weight_extra_shipping_cost' ),
		'weight_pounds'              => array( 'name' => __( 'Weight Pounds', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_weight_pounds' ),
		'weight_ounces'              => array( 'name' => __( 'Weight Ounces', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_weight_ounces' ),
		'featured'              	 => array( 'name' => __( 'Featured Product', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '_featured' ),
	); 
	
} // END function mp_get_products_csv_columns

/**
* Get Products columns from 2.9
*/
function mp_get_29_products_csv_columns() {
	
	return array(
		'title'          => array( '3.0_name' => 'post_title', 'required' => true, 'WPMU_DEV_API_NAME' => '', 'WPMU_DEV_API_VAL' => '' ),
		'description'    => array( '3.0_name' => 'post_content', 'required' => true, 'WPMU_DEV_API_NAME' => '', 'WPMU_DEV_API_VAL' => '' ),
		'sku'            => array( '3.0_name' => 'sku', 'required' => false, 'WPMU_DEV_API_NAME' => '_sku', 'WPMU_DEV_API_VAL' => 'WPMUDEV_Field_Text' ),
		'price'          => array( '3.0_name' => 'regular_price', 'required' => false, 'WPMU_DEV_API_NAME' => '_regular_price', 'WPMU_DEV_API_VAL' => 'WPMUDEV_Field_Text' ),
		'sale_price'     => array( '3.0_name' => 'sale_price_amount', 'required' => false, 'WPMU_DEV_API_NAME' => '_sale_price_amount', 'WPMU_DEV_API_VAL' => 'WPMUDEV_Field_Text' ), // /!\ Complex field
		'tags'           => array( '3.0_name' => 'tags', 'required' => false, 'WPMU_DEV_API_NAME' => '', 'WPMU_DEV_API_VAL' => '' ), // /!\ new Field
		'categories'     => array( '3.0_name' => 'categories', 'required' => false, 'WPMU_DEV_API_NAME' => '', 'WPMU_DEV_API_VAL' => '' ), // /!\ new Field
		'stock'          => array( '3.0_name' => array( 'inventory', 'inv_inventory' ), 'required' => false, 'WPMU_DEV_API_NAME' => array( '', '_inv_inventory' ), 'WPMU_DEV_API_VAL' => array( '', 'WPMUDEV_Field_Text' ) ), // /!\ Complex field
		'external_link'  => array( '3.0_name' => 'external_url', 'required' => false, 'WPMU_DEV_API_NAME' => '_external_url', 'WPMU_DEV_API_VAL' => 'WPMUDEV_Field_Text' ),
		'download_url'   => array( '3.0_name' => 'file_url', 'required' => false, 'WPMU_DEV_API_NAME' => '_file_url', 'WPMU_DEV_API_VAL' => 'WPMUDEV_Field_File' ), // /!\ File field
		'sales_count'    => array( '3.0_name' => 'mp_sales_count', 'required' => false, 'WPMU_DEV_API_NAME' => '', 'WPMU_DEV_API_VAL' => '' ),
		'extra_shipping' => array( '3.0_name' => 'weight_extra_shipping_cost', 'required' => false, 'WPMU_DEV_API_NAME' => '_weight_extra_shipping_cost', 'WPMU_DEV_API_VAL' => 'WPMUDEV_Field_Text' ),
		'weight'         => array( '3.0_name' => array( 'weight_pounds', 'weight_ounces' ), 'required' => false, 'WPMU_DEV_API_NAME' => array( '_weight_pounds', '_weight_ounces' ), 'WPMU_DEV_API_VAL' => array( 'WPMUDEV_Field_Text', 'WPMUDEV_Field_Text' ) ),
		'image'          => array( '3.0_name' => 'mp_product_images', 'required' => false, 'WPMU_DEV_API_NAME' => '', 'WPMU_DEV_API_VAL' => '' ), // /!\ File url to download 
	);
	
} // END function mp_get_products_csv_columns


/**
* Get Orders columns
*/
function mp_get_orders_csv_columns() {
	
	return array(
		'ID'                    => array( 'name' => __( 'ID', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_author'           => array( 'name' => __( 'Post Author', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_date'             => array( 'name' => __( 'Post Date', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_date_gmt'         => array( 'name' => __( 'Post Date GMT', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_content'          => array( 'name' => __( 'Post Content', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_title'            => array( 'name' => __( 'Post Title', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_excerpt'          => array( 'name' => __( 'Post Excerpt', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_status'           => array( 'name' => __( 'Post Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'comment_status'        => array( 'name' => __( 'Comment Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'ping_status'           => array( 'name' => __( 'Ping Status', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_password'         => array( 'name' => __( 'Post Password', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_name'             => array( 'name' => __( 'Post Name', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'to_ping'               => array( 'name' => __( 'To Ping', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'pinged'                => array( 'name' => __( 'Pinged', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_modified'         => array( 'name' => __( 'Post Modified', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_modified_gmt'     => array( 'name' => __( 'Post Modified GMT', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_content_filtered' => array( 'name' => __( 'Post Content Filtered', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_parent'           => array( 'name' => __( 'Post Parent', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'guid'                  => array( 'name' => __( 'GUID', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'menu_order'            => array( 'name' => __( 'Menu Order', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_type'             => array( 'name' => __( 'Post Type', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'post_mime_type'        => array( 'name' => __( 'Post Mime Type', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'comment_count'         => array( 'name' => __( 'Comment Count', 'mp' ), 'required' => true, 'WPMU_DEV_API_NAME' => '' ),
		'_edit_last'            => array( 'name' => __( 'Edit Last', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'_edit_lock'            => array( 'name' => __( 'Edit Lock', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_paid_time'          => array( 'name' => __( 'Paid Time', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_cart_info'          => array( 'name' => __( 'Cart Info', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_cart_items'         => array( 'name' => __( 'Cart Items', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_discount_info'      => array( 'name' => __( 'Discount Info', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_shipping_info'      => array( 'name' => __( 'Shipping Info', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_billing_info'       => array( 'name' => __( 'Billing Info', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_payment_info'       => array( 'name' => __( 'Payment Info', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_order_total'        => array( 'name' => __( 'Order Total', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_shipping_total'     => array( 'name' => __( 'Shipping Total', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_shipping_tax'       => array( 'name' => __( 'Shipping Tax', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_tax_total'          => array( 'name' => __( 'Tax Total', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_tax_inclusive'      => array( 'name' => __( 'Tax Inclusive', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_tax_shipping'       => array( 'name' => __( 'Tax Shipping', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_order_items'        => array( 'name' => __( 'Order Items', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
		'mp_received_time'      => array( 'name' => __( 'Received Time', 'mp' ), 'required' => false, 'WPMU_DEV_API_NAME' => '' ),
	); 

} // END function mp_get_orders_csv_columns

/**
* Add a post
*/
function mp_ie_add_post( $required, $metas, $cats, $tags, $thumbnail_id ) {

	if( isset( $required['post_parent'] ) && $required['post_parent'] != 0 ) {
		// We have variation
		return mp_ie_add_variation( $required, $metas, $cats, $tags, $thumbnail_id );
	} else {
		// We have normal product, proceed...
		return mp_ie_add_normal_product( $required, $metas, $cats, $tags, $thumbnail_id );
	}
}

/**
* Add a variation 3.0
*/

function mp_ie_add_variation( $required, $metas, $cats, $tags, $thumbnail_id ) {
	$post = get_post( $required['ID'] );
	
	update_post_meta( $required['post_parent'], 'has_variations', 1 );
	
	MP_Products_Screen::maybe_create_attribute( 'product_attr_' . $metas['mp_variable_attribute'], $metas['mp_variable_attribute'] );	
	
	if( empty( $post ) ) {
		// Define import ID
		$required['import_id'] = $required['ID'];
		unset( $required['ID'] );
		// To ensure the author ID exists, use the administrator as author
		$required['post_author'] = mp_ie_get_admin_id();

		$post_id = wp_insert_post( $required, true );

		if( ! is_wp_error( $post_id ) ) {
			foreach( $metas as $key => $meta ) {
				update_post_meta( $post_id, $key, maybe_unserialize( $meta ) );

				if( $key == "_thumbnail_id" ) {
					set_post_thumbnail( $post_id, $thumbnail_id );
				}
				
				update_post_meta( $required['post_parent'], 'has_variation', 1 );
			}
			$message = sprintf( __( 'Variation (%s) created.', 'mp' ), $post_id );
		} else {
			$message = sprintf( '<span class="error">%s</span>', sprintf( __( 'Variation ID "%s" not created: %s', 'mp' ), $required['ID'], $post_id->get_error_message() ) );
		}
	}
	else {
		$message = sprintf( '<span class="error">%s</span>', sprintf( __( 'A variation with ID="%s" already exists in your database and cannot be inserted.', 'mp' ), $required['ID'] ) );
	}
	
	return $message;
}

/**
* Add a normal 3.0 product
*/

function mp_ie_add_normal_product( $required, $metas, $cats, $tags, $thumbnail_id ) {
	$post = get_post( $required['ID'] );
	
	if( empty( $post ) ) {
		// Define import ID
		$required['import_id'] = $required['ID'];
		unset( $required['ID'] );
		// To ensure the author ID exists, use the administrator as author
		$required['post_author'] = mp_ie_get_admin_id();

		$post_id = wp_insert_post( $required, true );

		if( ! is_wp_error( $post_id ) ) {			
			foreach( $metas as $key => $meta ) {
				update_post_meta( $post_id, $key, maybe_unserialize( $meta ) );

				if( $key == "_thumbnail_id" ) {
					set_post_thumbnail( $post_id, $thumbnail_id );
				}
			}

			$post_cats = array();
			foreach ( $cats as $cat ) {
				$cat = trim( $cat );

				if( ! empty( $cat ) ) {			
					$term = term_exists( $cat, 'product_category' );
					
					if( empty( $term ) ) {
						$term = wp_insert_term( $cat, 'product_category' );

						if( is_wp_error( $term ) ) {
							return sprintf( '<span class="error">%s</span>', sprintf( __( 'Product Category "%s" not created: %s', 'mp' ), $cat, $term->get_error_message() ) );
						}
					}

					$post_cats[] = (int) $term['term_id'];
				}
			}
			wp_set_object_terms( $post_id, $post_cats, 'product_category' );

			$post_tags = array();
			foreach ( $tags as $tag ) {
				$tag = trim( $tag );

				if( ! empty( $tag ) ) {			
					$term = term_exists( $tag, 'product_tag' );
					
					if( empty( $term ) ) {
						$term = wp_insert_term( $tag, 'product_tag' );

						if( is_wp_error( $term ) ) {
							return sprintf( '<span class="error">%s</span>', sprintf( __( 'Product Tag "%s" not created: %s', 'mp' ), $tag, $term->get_error_message() ) );
						}
					}

					$post_tags[] = (int) $term['term_id'];
				}
			}
			wp_set_object_terms( $post_id, $post_tags, 'product_tag' );

			$post_link = sprintf( '<a title="%s" href="%s">%s</a>', sprintf( __( 'Edit %s', 'mp' ), esc_attr( $required[ 'post_title' ] ) ), get_edit_post_link( $post_id ), $required[ 'post_title' ] );
			
			$message = sprintf( __( 'Post "%s" (%s) created.', 'mp' ), $post_link, $post_id );
		}
		else {
			$message = sprintf( '<span class="error">%s</span>', sprintf( __( 'Post ID "%s" not created: %s', 'mp' ), $required['ID'], $post_id->get_error_message() ) );
		}
	}
	else {
		$message = sprintf( '<span class="error">%s</span>', sprintf( __( 'A post with ID="%s" already exists in your database and cannot be inserted.', 'mp' ), $required['ID'] ) );
	}
	
	return $message;
}

/**
* Add a 2.9 post
*/
function mp_ie_add_29_post( $required, $metas, $cats, $tags ) {
	
	// To ensure the author ID exists, use the administrator as author
	$required['post_author'] = mp_ie_get_admin_id();
	$required['post_type']   = MP_Product::get_post_type();
	$required['post_status'] = 'publish';

	$product_id = wp_insert_post( $required, true );

	if( ! is_wp_error( $product_id ) ) {			
		foreach( $metas as $key => $meta ) {
			update_post_meta( $product_id, $key, maybe_unserialize( $meta ) );
		}

		$product_cats = array();
		foreach ( $cats as $cat ) {
			$cat = trim( $cat );

			if( ! empty( $cat ) ) {			
				$term = term_exists( $cat, 'product_category' );
				
				if( empty( $term ) ) {
					$term = wp_insert_term( $cat, 'product_category' );

					if( is_wp_error( $term ) ) {
						return sprintf( '<span class="error">%s</span>', sprintf( __( 'Product Category "%s" not created: %s', 'mp' ), $cat, $term->get_error_message() ) );
					}
				}

				$product_cats[] = (int) $term['term_id'];
			}
		}
		wp_set_object_terms( $product_id, $product_cats, 'product_category' );

		$product_tags = array();
		foreach ( $tags as $tag ) {
			$tag = trim( $tag );

			if( ! empty( $tag ) ) {			
				$term = term_exists( $tag, 'product_tag' );
				
				if( empty( $term ) ) {
					$term = wp_insert_term( $tag, 'product_tag' );

					if( is_wp_error( $term ) ) {
						return sprintf( '<span class="error">%s</span>', sprintf( __( 'Product Tag "%s" not created: %s', 'mp' ), $tag, $term->get_error_message() ) );
					}
				}

				$product_tags[] = (int) $term['term_id'];
			}
		}
		wp_set_object_terms( $product_id, $product_tags, 'product_tag' );

		$product_link = sprintf( '<a title="%s" href="%s">%s</a>', sprintf( __( 'Edit %s', 'mp' ), 
			esc_attr( $required[ 'post_title' ] ) ), 
		get_edit_post_link( $product_id ), 
		$required[ 'post_title' ] );
		
		$message = sprintf( __( 'Post "%s" (%s) created.', 'mp' ), $product_link, $product_id );
	}
	else {
		$message = sprintf( '<span class="error">%s</span>', sprintf( __( 'Post ID "%s" not created: %s', 'mp' ), $required['ID'], $product_id->get_error_message() ) );
	}
	
	return $message;
	
}

/**
* Get Super Admin ID
* 
* @return int 
*/
function mp_ie_get_admin_id() {
	
	$users_query = new WP_User_Query( array( 
		'role' => 'administrator', 
	) );
	$users = $users_query->get_results();

	if( isset( $users[0] ) ) {
		return $users[0]->ID;
	}
	else {
		return 0;
	}
	
}
