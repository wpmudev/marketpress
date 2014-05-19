<?php

class MarketPress_Admin_Page_Shortcodes {
	function __construct() {
		add_action('marketpress_admin_page_shortcodes', array(&$this, 'settings'));
		add_filter('marketpress_settings_tabs', array(&$this, 'tab'));	
	}
	
	function tab( $tabs ) {
		$tabs['shortcodes'] = array(
			'name' => __('Shortcodes', 'mp'),
			'cart_only' => false,
			'order' => 8,
		);
		
		return $tabs;
	}
	
	function settings( $settings ) {
		?>
		<!--<h2><?php _e('MarketPress Shortcodes', 'mp'); ?></h2>-->
			<div class="mp-postbox">
				<h3 class='hndle'><span><?php _e('Shortcodes', 'mp') ?></span></h3>
				<div class="inside">
					<p><?php _e('Shortcodes allow you to include dynamic store content in posts and pages on your site. Simply type or paste them into your post or page content where you would like them to appear. Optional attributes can be added in a format like <em>[shortcode attr1="value" attr2="value"]</em>.', 'mp') ?></p>
					<table class="form-table">
						<tr>
						<th scope="row"><?php _e('Product Tag Cloud', 'mp') ?></th>
						<td>
							<strong>[mp_tag_cloud]</strong> -
							<span class="description"><?php _e('Displays a cloud or list of your product tags.', 'mp') ?></span>
							<a href="http://codex.wordpress.org/Template_Tags/wp_tag_cloud"><?php _e('Optional Attributes &raquo;', 'mp') ?></a>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Product Categories List', 'mp') ?></th>
						<td>
							<strong>[mp_list_categories]</strong> -
							<span class="description"><?php _e('Displays an HTML list of your product categories.', 'mp') ?></span>
							<a href="http://codex.wordpress.org/Template_Tags/wp_list_categories"><?php _e('Optional Attributes &raquo;', 'mp') ?></a>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Product Categories Dropdown', 'mp') ?></th>
						<td>
							<strong>[mp_dropdown_categories]</strong> -
							<span class="description"><?php _e('Displays an HTML dropdown of your product categories.', 'mp') ?></span>
							<a href="http://codex.wordpress.org/Template_Tags/wp_dropdown_categories"><?php _e('Optional Attributes &raquo;', 'mp') ?></a>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Popular Products List', 'mp') ?></th>
						<td>
							<strong>[mp_popular_products]</strong> -
							<span class="description"><?php _e('Displays a list of popular products ordered by sales.', 'mp') ?></span>
							<p>
							<strong><?php _e('Optional Attributes:', 'mp') ?></strong>
							<ul class="mp-shortcode-options">
								<li><?php _e('"number" - max number of products to display. Defaults to 5.', 'mp') ?></li>
								<li><?php _e('Example:', 'mp') ?> <em>[mp_popular_products number="5"]</em></li>
							</ul></p>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Related Products', 'mp') ?></th>
						<td>
							<strong>[mp_related_products]</strong> -
							<span class="description"><?php _e('Displays a products related to the one being viewed.', 'mp') ?></span>
							<p>
							<strong><?php _e('Optional Attributes:', 'mp') ?></strong>
							<ul class="mp-shortcode-options">
							<li><?php _e('"product_id" - The product to show related items for.', 'mp') ?></li>
							<li><?php _e('"limit" - How many related items to show. Defaults to the value set in presentation settings.', 'mp') ?></li>
							<li><?php _e('"simple_list" - Whether to display the items as a simple list or based on the list/grid view setting. Defaults to the value set in presentation settings.', 'mp') ?></li>
							<li><?php _e('"relate_by" - How to relate the products. Can be "category", "tags" or "both". Defaults to the value set in presentation settings.', 'mp') ?></li>							
							<li><?php _e('Example:', 'mp') ?> <em>[mp_related_products product_id="12345" in_same_category="1" in_same_tags="1" limit="3" simple_list="0"]</em></li>
							</ul></p>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Products List', 'mp') ?></th>
						<td>
							<strong>[mp_list_products]</strong> -
							<span class="description"><?php _e('Displays a list of products according to preference. Optional attributes default to the values in Presentation Settings -> Product List.', 'mp') ?></span>
							<p>
							<strong><?php _e('Optional Attributes:', 'mp') ?></strong>
							<ul class="mp-shortcode-options">
								<li><?php _e('"paginate" - Whether to paginate the product list. This is useful to only show a subset.', 'mp') ?></li>
								<li><?php _e('"page" - The page number to display in the product list if "paginate" is set to true.', 'mp') ?></li>
								<li><?php _e('"per_page" - How many products to display in the product list if "paginate" is set to true.', 'mp') ?></li>
								<li><?php _e('"order_by" - What field to order products by. Can be: title, date, ID, author, price, sales, rand (random).', 'mp') ?></li>
								<li><?php _e('"order" - Direction to order products by. Can be: DESC, ASC', 'mp') ?></li>
								<li><?php _e('"category" - Limits list to a specific product category. Use the category Slug', 'mp') ?></li>
								<li><?php _e('"tag" - Limits list to a specific product tag. Use the tag Slug', 'mp') ?></li>
								<li><?php _e('"list_view" - 1 for list view, 0 (default) for grid view', 'mp') ?></li>
								<li><?php _e('"filters" - 1 to show product filters, 0 to not show filters', 'mp') ?></li>
								<li><?php _e('Example:', 'mp') ?> <em>[mp_list_products paginate="true" page="1" per_page="10" order_by="price" order="DESC" category="downloads" filters="1"]</em></li>
							</ul></p>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Single Product', 'mp') ?></th>
						<td>
							<strong>[mp_product]</strong> -
							<span class="description"><?php _e('Displays a single product according to preference.', 'mp') ?></span>
							<p>
							<strong><?php _e('Optional Attributes:', 'mp') ?></strong>
							<ul class="mp-shortcode-options">
								<li><?php _e('"product_id" - The ID of the product to display. This is the Post ID, you can find it in the url of a product edit page.', 'mp') ?></li>
								<li><?php _e('"title" - Whether to display the product title.', 'mp') ?></li>
								<li><?php _e('"content" - Whether and what type of content to display. Options are false/0, "full", or "excerpt". Default "full"', 'mp') ?></li>
								<li><?php _e('"image" - Whether and what context of image size to display. Options are false/0, "single", or "list". Default "single"', 'mp') ?></li>
								<li><?php _e('"meta" - Whether to display the product meta (price, buy button).', 'mp') ?></li>
								<li><?php _e('Example:', 'mp') ?> <em>[mp_product product_id="1" title="1" content="excerpt" image="single" meta="1"]</em></li>
							</ul></p>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Product Image', 'mp') ?></th>
						<td>
							<strong>[mp_product_image]</strong> -
							<span class="description"><?php _e('Displays the featured image of a given product.', 'mp') ?></span>
							<p>
							<strong><?php _e('Optional Attributes:', 'mp') ?></strong>
							<ul class="mp-shortcode-options">
								<li><?php _e('"product_id" - The ID for the product.	This is the Post ID, you can find it in the url of a product edit page. Optional if shortcode is in the loop.', 'mp') ?></li>
								<li><?php _e('"context" - What context for preset size options. Options are list, single, or widget, default single.', 'mp') ?></li>
								<li><?php _e('"align" - Set the alignment of the image. If omitted defaults to the alignment set in presentation settings.', 'mp') ?></li>
								<li><?php _e('Example:', 'mp') ?> <em>[mp_product_image product_id="1" size="150" align="left"]</em></li>
							</ul></p>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Product Buy Button', 'mp') ?></th>
						<td>
							<strong>[mp_buy_button]</strong> -
							<span class="description"><?php _e('Displays the buy or add to cart button.', 'mp') ?></span>
							<p>
							<strong><?php _e('Optional Attributes:', 'mp') ?></strong>
							<ul class="mp-shortcode-options">
								<li><?php _e('"product_id" - The ID for the product.	This is the Post ID, you can find it in the url of a product edit page. Optional if shortcode is in the loop.', 'mp') ?></li>
								<li><?php _e('"context" - What context for display. Options are list or single, default single which shows all variations.', 'mp') ?></li>
								<li><?php _e('Example:', 'mp') ?> <em>[mp_buy_button product_id="1" context="single"]</em></li>
							</ul></p>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Product Price', 'mp') ?></th>
						<td>
							<strong>[mp_product_price]</strong> -
							<span class="description"><?php _e('Displays the product price (and sale price).', 'mp') ?></span>
							<p>
							<strong><?php _e('Optional Attributes:', 'mp') ?></strong>
							<ul class="mp-shortcode-options">
								<li><?php _e('"product_id" - The ID for the product.	This is the Post ID, you can find it in the url of a product edit page. Optional if shortcode is in the loop.', 'mp') ?></li>
								<li><?php _e('"label" - A label to prepend to the price. Defaults to "Price: ".', 'mp') ?></li>
								<li><?php _e('Example:', 'mp') ?> <em>[mp_product_price product_id="1" label="Buy this thing now!"]</em></li>
							</ul></p>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Product SKU', 'mp') ?></th>
						<td>
							<strong>[mp_product_sku]</strong> -
							<span class="description"><?php _e('Displays the product SKU number(s).', 'mp') ?></span>
							<p>
							<strong><?php _e('Optional Attributes:', 'mp') ?></strong>
							<ul class="mp-shortcode-options">
								<li><?php _e('"product_id" - The ID for the product.	This is the Post ID, you can find it in the url of a product edit page. Optional if shortcode is in the loop.', 'mp') ?></li>
								<li><?php _e('"seperator" - If there are variation, what to seperate the list of SKUs with. Defaults to a comma ", ".', 'mp') ?></li>
								<li><?php _e('Example:', 'mp') ?> <em>[mp_product_sku product_id="1" seperator=", "]</em></li>
							</ul></p>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Product Meta', 'mp') ?></th>
						<td>
							<strong>[mp_product_meta]</strong> -
							<span class="description"><?php _e('Displays the full product meta box with price and buy now/add to cart button.', 'mp') ?></span>
							<p>
							<strong><?php _e('Optional Attributes:', 'mp') ?></strong>
							<ul class="mp-shortcode-options">
								<li><?php _e('"product_id" - The ID for the product.	This is the Post ID, you can find it in the url of a product edit page. Optional if shortcode is in the loop.', 'mp') ?></li>
								<li><?php _e('"label" - A label to prepend to the price. Defaults to "Price: ".', 'mp') ?></li>
								<li><?php _e('"context" - What context for display. Options are list or single, default single which shows all variations.', 'mp') ?></li>
								<li><?php _e('Example:', 'mp') ?> <em>[mp_product_meta product_id="1" label="Buy this thing now!"]</em></li>
							</ul></p>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Store Links', 'mp') ?></th>
						<td>
							<strong>[mp_cart_link]</strong> -
							<span class="description"><?php _e('Displays a link or url to the current shopping cart page.', 'mp') ?></span><br />
							<strong>[mp_store_link]</strong> -
							<span class="description"><?php _e('Displays a link or url to the current store page.', 'mp') ?></span><br />
							<strong>[mp_products_link]</strong> -
							<span class="description"><?php _e('Displays a link or url to the current products list page.', 'mp') ?></span><br />
							<strong>[mp_orderstatus_link]</strong> -
							<span class="description"><?php _e('Displays a link or url to the order status page.', 'mp') ?></span><br />
							<p>
							<strong><?php _e('Optional Attributes:', 'mp') ?></strong>
							<ul class="mp-shortcode-options">
								<li><?php _e('"url" - Whether to return a clickable link or url. Can be: true, false. Defaults to showing link.', 'mp') ?></li>
								<li><?php _e('"link_text" - The text to show in the link.', 'mp') ?></li>
								<li><?php _e('Example:', 'mp') ?> <em>[mp_cart_link link_text="Go here!"]</em></li>
							</ul></p>
						</td>
						</tr>
						<tr>
						<th scope="row"><?php _e('Store Navigation List', 'mp') ?></th>
						<td>
							<strong>[mp_store_navigation]</strong> -
							<span class="description"><?php _e('Displays a list of links to your store pages.', 'mp') ?></span>
						</td>
						</tr>
					</table>
				</div>
			</div>

			<?php
			//for adding additional help content boxes
			do_action('mp_help_page', $settings);
			?>
		<?php
	}
}

mp_register_admin_page('MarketPress_Admin_Page_Shortcodes');
