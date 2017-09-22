<?php

class MP_Store_Settings_Presentation {

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
			self::$_instance = new MP_Store_Settings_Presentation();
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
		add_filter( 'wpmudev_field/after_field', array( &$this, 'display_create_page_button' ), 10, 2 );
		add_action( 'wpmudev_field/print_scripts', array( &$this, 'create_store_page_js' ) );

		if ( mp_get_get_value( 'page' ) == 'store-settings-presentation' ) {
			add_action( 'init', array( &$this, 'init_metaboxes' ) );
			add_action( 'wpmudev_metabox/after_settings_metabox_saved', array( &$this, 'link_store_pages' ) );
		}
	}

	/**
	 *
	 * @param $wpmudev_metabox
	 */
	public function link_store_pages( $wpmudev_metabox ) {
		if ( $wpmudev_metabox->args['id'] == 'mp-settings-presentation-pages-slugs' ) {
			$pages = mp_get_post_value( 'pages' );
			foreach ( $pages as $type => $page ) {
				MP_Pages_Admin::get_instance()->save_store_page_value( $type, $page, false );
			}
		}
	}

	/**
	 * Print scripts for creating store page
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field/print_scripts
	 */
	public function create_store_page_js( $field ) {
		if ( $field->args['original_name'] !== 'pages[store]' ) {
			return;
		}
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('.mp-create-page-button').click(function (e) {
					e.preventDefault();

					var $this = $(this),
						$select = $this.siblings('[name^="pages"]');

					$this.isWorking(true);

					$.getJSON($this.attr('href'), function (resp) {
						if (resp.success) {
							$select.attr('data-select2-value', resp.data.select2_value).mp_select2('val', resp.data.post_id).trigger('change');
							$this.isWorking(false).replaceWith(resp.data.button_html);
						} else {
							alert('<?php _e( 'An error occurred while creating the store page. Please try again.', 'mp' ); ?>');
							$this.isWorking(false);
						}
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Initialize metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_general_settings();
		$this->init_product_page_settings();
		$this->init_related_product_settings();
		$this->init_product_list_settings();
		$this->init_social_settings();
		$this->init_store_pages_slugs_settings();
		$this->init_miscellaneous_settings();
	}

	/**
	 * Gets the appropriate image size label for a given size.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $size The image size.
	 *
	 * @return string
	 */
	public function get_image_size_label( $size ) {
		$width  = get_option( "{$size}_size_w" );
		$height = get_option( "{$size}_size_h" );
		$crop   = get_option( "{$size}_crop" );

		return "{$width} x {$height} (" . ( ( $crop ) ? __( 'cropped', 'mp' ) : __( 'uncropped', 'mp' ) ) . ')';
	}

	/**
	 * Display "create page" button next to a given field
	 *
	 * @since 3.0
	 * @access public
	 * filter wpmudev_field/after_field
	 */
	public function display_create_page_button( $html, $field ) {
		switch ( $field->args['original_name'] ) {
			case 'pages[store]' :
				$type = 'store';
				break;

			case 'pages[products]' :
				$type = 'products';
				break;

			case 'pages[cart]' :
				$type = 'cart';
				break;

			case 'pages[checkout]' :
				$type = 'checkout';
				break;

			case 'pages[order_status]' :
				$type = 'order_status';
				break;
		}

		if ( isset( $type ) ) {
			if ( ( $post_id = mp_get_setting( "pages->$type" ) ) && get_post_status( $post_id ) !== false ) {
				return '<a target="_blank" class="button mp-edit-page-button" href="' . add_query_arg( array(
					'post'   => $post_id,
					'action' => 'edit',
				), get_admin_url( null, 'post.php' ) ) . '">' . __( 'Edit Page', 'mp' ) . '</a>';
			} else {
				return '<a class="button mp-create-page-button" href="' . wp_nonce_url( get_admin_url( null, 'admin-ajax.php?action=mp_create_store_page&type=' . $type ), 'mp_create_store_page' ) . '">' . __( 'Create Page', 'mp' ) . '</a>';
			}
		}

		return $html;
	}

	/**
	 * Init the store page/slugs settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_store_pages_slugs_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-presentation-pages-slugs',
			'page_slugs'  => array( 'store-settings-presentation', 'store-settings_page_store-settings-presentation' ),
			'title'       => __( 'Store Pages', 'mp' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[store]',
			'label'       => array( 'text' => __( 'Store Base', 'mp' ) ),
			'desc'        => __( 'This page will be used as the root for your store.', 'mp' ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Choose a Page', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[products]',
			'label'       => array( 'text' => __( 'Products List', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Choose a Page', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[cart]',
			'label'       => array( 'text' => __( 'Shopping Cart', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Choose a Page', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[checkout]',
			'label'       => array( 'text' => __( 'Checkout', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Choose a Page', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[order_status]',
			'label'       => array( 'text' => __( 'Order Status', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Choose a Page', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
	}

	/**
	 * Init the product list settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_social_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-presentation-social',
			'page_slugs'  => array( 'store-settings-presentation', 'store-settings_page_store-settings-presentation' ),
			'title'       => __( 'Social Settings', 'mp' ),
			'option_name' => 'mp_settings',
		) );

		$metabox->add_field( 'section', array(
			'name'  => 'section_pinterest',
			'title' => __( 'Pinterest', 'mp' ),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'          => 'social[pinterest][show_pinit_button]',
			'label'         => array( 'text' => __( 'Show "Pin It" Button', 'mp' ) ),
			'options'       => array(
				'off'         => __( 'Off', 'mp' ),
				'single_view' => __( 'Single View', 'mp' ),
				'all_view'    => __( 'All View', 'mp' ),
			),
			'default_value' => 'off',
		) );

		$metabox->add_field( 'radio_group', array(
			'name'    => 'social[pinterest][show_pin_count]',
			'label'   => array( 'text' => __( 'Pin Count', 'mp' ) ),
			'options' => array(
				'none'   => __( 'None', 'mp' ),
				'above'  => __( 'Above', 'mp' ),
				'beside' => __( 'Beside', 'mp' ),
			),
		) );

		$metabox->add_field( 'section', array(
			'name'  => 'section_facebook',
			'title' => __( 'Facebook', 'mp' ),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'          => 'social[facebook][show_facebook_like_button]',
			'label'         => array( 'text' => __( 'Show Facebook Like Button', 'mp' ) ),
			'options'       => array(
				'off'         => __( 'Off', 'mp' ),
				'single_view' => __( 'Single View', 'mp' ),
				'all_view'    => __( 'All View', 'mp' ),
			),
			'default_value' => 'off',
		) );

		$metabox->add_field( 'radio_group', array(
			'name'    => 'social[facebook][action]',
			'label'   => array( 'text' => __( 'Action', 'mp' ) ),
			'options' => array(
				'like'      => __( 'Like', 'mp' ),
				'recommend' => __( 'Recommend', 'mp' ),
			),
		) );

		$metabox->add_field( 'checkbox', array(
			'name'    => 'social[facebook][show_share]',
			'label'   => array( 'text' => __( 'Show Share Button', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
		) );

		$metabox->add_field( 'section', array(
			'name'  => 'section_twitter',
			'title' => __( 'Twitter', 'mp' ),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'    => 'social[twitter][show_twitter_button]',
			'label'   => array( 'text' => __( 'Show Twitter Button', 'mp' ) ),
			'options' => array(
				'off'         => __( 'Off', 'mp' ),
				'single_view' => __( 'Single View', 'mp' ),
				'all_view'    => __( 'All View', 'mp' ),
			),
		) );
	}

	/**
	 * Init the product list settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_product_list_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-presentation-product-list',
			'page_slugs'  => array( 'store-settings-presentation', 'store-settings_page_store-settings-presentation' ),
			'title'       => __( 'Product List/Grid Settings', 'mp' ),
			'desc'        => __( 'Settings related to the display of product lists/grids.', 'mp' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'radio_group', array(
			'name'    => 'list_view',
			'label'   => array( 'text' => __( 'Product Layout', 'mp' ) ),
			'options' => array(
				'list' => __( 'Display as list', 'mp' ),
				'grid' => __( 'Display as grid', 'mp' ),
			),
			'default_value' => 'list',
		) );
		$metabox->add_field( 'radio_group', array(
			'name'          => 'per_row',
			'label'         => array( 'text' => __( 'How many products per row?', 'mp' ) ),
			'desc'          => __( 'Set the number of products that show up in a grid row to best fit your theme', 'mp' ),
			'default_value' => 3,
			'options'       => array(
				1 => __( 'One', 'mp' ),
				2 => __( 'Two', 'mp' ),
				3 => __( 'Three', 'mp' ),
				4 => __( 'Four', 'mp' ),
			),
			'conditional'   => array(
				'name'   => 'list_view',
				'value'  => 'grid',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'    => 'list_button_type',
			'label'   => array( 'text' => __( 'Add To Cart Action', 'mp' ) ),
			'desc'    => __( 'MarketPress supports two "flows" for adding products to the shopping cart. After adding a product to their cart, two things can happen:', 'mp' ),
			'options' => array(
				'addcart' => __( 'Stay on current product page', 'mp' ),
				'buynow'  => __( 'Redirect to cart page for immediate checkout', 'mp' ),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_thumbnail',
			'label'   => array( 'text' => __( 'Show Product Thumbnail?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
		) );

		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_thumbnail_placeholder',
			'label'   => array( 'text' => __( 'Show default product placeholder thumbnail when product image is not available?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
		) );

		$metabox->add_field( 'file', array(
			'name'        => 'thumbnail_placeholder',
			'label'       => array( 'text' => __( 'Select default placeholder image thumbnail when product image is not available (if empty, plugin\'s built-in image will be used)', 'mp' ) ),
			'message'     => __( 'Yes', 'mp' ),
			'conditional' => array(
				'name'   => 'show_thumbnail_placeholder',
				'value'  => '1',
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'select', array(
			'name'        => 'list_img_size',
			'label'       => array( 'text' => __( 'Image Size', 'mp' ) ),
			'options'     => array(
				'thumbnail' => sprintf( __( 'Thumbnail - %s', 'mp' ), $this->get_image_size_label( 'thumbnail' ) ),
				'medium'    => sprintf( __( 'Medium - %s', 'mp' ), $this->get_image_size_label( 'medium' ) ),
				'large'     => sprintf( __( 'Large - %s', 'mp' ), $this->get_image_size_label( 'large' ) ),
				'custom'    => __( 'Custom', 'mp' ),
			),
			'conditional' => array(
				'name'   => 'show_thumbnail',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$custom_size = $metabox->add_field( 'complex', array(
			'name'        => 'list_img_size_custom',
			'label'       => array( 'text' => __( 'Custom Image Size', 'mp' ) ),
			'conditional' => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'show_thumbnail',
					'value' => '1',
				),
				array(
					'name'  => 'list_img_size',
					'value' => 'custom',
				)
			),
		) );

		if ( $custom_size instanceof WPMUDEV_Field ) {
			$custom_size->add_field( 'text', array(
				'name'       => 'width',
				'label'      => array( 'text' => __( 'Width', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'digits'   => true,
					'min'      => 0,
				),
			) );
			$custom_size->add_field( 'text', array(
				'name'       => 'height',
				'label'      => array( 'text' => __( 'Height', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'digits'   => true,
					'min'      => 0,
				),
			) );
		}

		$metabox->add_field( 'radio_group', array(
			'name'        => 'image_alignment_list',
			'label'       => array( 'text' => __( 'Image Alignment', 'mp' ) ),
			'options'     => array(
				//'alignnone'		 => __( 'None', 'mp' ),
				//'aligncenter'	 => __( 'Center', 'mp' ),
				'alignleft'  => __( 'Left', 'mp' ),
				'alignright' => __( 'Right', 'mp' ),
			),
			'default_value' => 'alignleft',
			'conditional' => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'show_thumbnail',
					'value' => '1',
				),
				array(
					'name'  => 'list_view',
					'value' => 'list',
				),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_excerpts',
			'label'   => array( 'text' => __( 'Show Excerpts?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'text', array(
			'name'          => 'excerpts_length',
			'label'         => array( 'text' => __( 'Excerpts Length', 'mp' ) ),
			'conditional'   => array(
				'name'   => 'show_excerpts',
				'value'  => '1',
				'action' => 'show',
			),
			'validation'    => array(
				'required' => true,
				'digits'   => 1,
			),
			'default_value' => 55
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'paginate',
			'label'   => array( 'text' => __( 'Paginate Products?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'text', array(
			'name'        => 'per_page',
			'label'       => array( 'text' => __( 'Products Per Page', 'mp' ) ),
			'conditional' => array(
				'name'   => 'paginate',
				'value'  => '1',
				'action' => 'show',
			),
			'validation'  => array(
				'required' => true,
				'digits'   => 1,
			),
		) );
		$metabox->add_field( 'select', array(
			'name'    => 'order_by',
			'label'   => array( 'text' => __( 'Sort Products By', 'mp' ) ),
			'options' => array(
				'title'  => __( 'Product Name', 'mp' ),
				'date'   => __( 'Publish Date', 'mp' ),
				'ID'     => __( 'Product ID', 'mp' ),
				'author' => __( 'Product Author', 'mp' ),
				'sales'  => __( 'Number of Sales', 'mp' ),
				'price'  => __( 'Product Price', 'mp' ),
				'rand'   => __( 'Random', 'mp' ),
			),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'    => 'order',
			'label'   => array( 'text' => __( 'Sort Order', 'mp' ) ),
			'options' => array(
				'DESC' => __( 'Descending', 'mp' ),
				'ASC'  => __( 'Ascending', 'mp' ),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'hide_products_filter',
			'label'   => array( 'text' => __( 'Hide Products Filter?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
			'desc'    => __( 'If enabled, users won\'t be able to filter products per category and/or to order by release date/name/price.', 'mp' ),
			'default_value' => 0
		) );
	}

	public function init_miscellaneous_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-miscellaneous-product-list',
			'page_slugs'  => array( 'store-settings-presentation', 'store-settings_page_store-settings-presentation' ),
			'title'       => __( 'Miscellaneous Settings', 'mp' ),
			'desc'        => __( '', 'mp' ),
			'option_name' => 'mp_settings',
		) );

		$metabox->add_field( 'text', array(
			'name'          => 'per_page_order_history',
			'label'         => array( 'text' => __( 'Order Status Entries Per Page', 'mp' ) ),
			'default_value' => get_option( 'posts_per_page' ),
			'validation'    => array(
				'required' => true,
				'digits'   => 1,
			),
		) );
	}

	/**
	 * Init the related product settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_related_product_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-presentation-product-related',
			'page_slugs'  => array( 'store-settings-presentation', 'store-settings_page_store-settings-presentation' ),
			'title'       => __( 'Related Product Settings', 'mp' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'related_products[show]',
			'label'   => array( 'text' => __( 'Show Related Products?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'text', array(
			'name'        => 'related_products[show_limit]',
			'label'       => array( 'text' => __( 'Related Product Limit', 'mp' ) ),
			'conditional' => array(
				'name'   => 'related_products[show]',
				'value'  => '1',
				'action' => 'show',
			),
			'validation'  => array(
				'required' => true,
				'digits'   => 1,
			),
		) );
		$metabox->add_field( 'select', array(
			'name'        => 'related_products[relate_by]',
			'label'       => array( 'text' => __( 'Relate Products By', 'mp' ) ),
			'options'     => array(
				'both'     => __( 'Category &amp; Tags', 'mp' ),
				'category' => __( 'Category Only', 'mp' ),
				'tags'     => __( 'Tags Only', 'mp' ),
			),
			'conditional' => array(
				'name'   => 'related_products[show]',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'radio_group', array(
			'name'        => 'related_products[view]',
			'label'       => array( 'text' => __( 'Related Products Layout', 'mp' ) ),
			'message'     => __( 'Yes', 'mp' ),
			'options'     => array(
				'list' => __( 'Display as list', 'mp' ),
				'grid' => __( 'Display as grid', 'mp' ),
			),
			'default_value' => 'list',
			'conditional' => array(
				'name'   => 'related_products[show]',
				'value'  => '1',
				'action' => 'show',
			),
		) );

		$metabox->add_field( 'radio_group', array(
			'name'          => 'related_products[per_row]',
			'label'         => array( 'text' => __( 'How many products per row?', 'mp' ) ),
			'desc'          => __( 'Set the number of products that show up in a grid row to best fit your theme', 'mp' ),
			'default_value' => 3,
			'options'       => array(
				1 => __( 'One', 'mp' ),
				2 => __( 'Two', 'mp' ),
				3 => __( 'Three', 'mp' ),
				4 => __( 'Four', 'mp' ),
			),
			'conditional'   => array(
				'name'   => 'related_products[view]',
				'value'  => 'grid',
				'action' => 'show',
			),
		) );
	}

	/**
	 * Init the general settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_product_page_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-presentation-product-page',
			'page_slugs'  => array( 'store-settings-presentation', 'store-settings_page_store-settings-presentation' ),
			'title'       => __( 'Product Page Settings', 'mp' ),
			'desc'        => __( 'Settings related to the display of individual product pages.', 'mp' ),
			'option_name' => 'mp_settings',
		) );
		$metabox->add_field( 'radio_group', array(
			'name'    => 'product_button_type',
			'label'   => array( 'text' => __( 'Add To Cart Action', 'mp' ) ),
			'desc'    => __( 'MarketPress supports two "flows" for adding products to the shopping cart. After adding a product to their cart, two things can happen:', 'mp' ),
			'options' => array(
				'addcart' => __( 'Stay on current product page', 'mp' ),
				'buynow'  => __( 'Redirect to cart page for immediate checkout', 'mp' ),
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_quantity',
			'label'   => array( 'text' => __( 'Show Quantity Field?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
			'desc'    => __( 'If enabled, users will be able to choose how many of the product they want to purchase before adding to their cart. If not checked, quantity could be change later on the cart page.', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'          => 'show_single_excerpt',
			'label'         => array( 'text' => __( 'Show Excerpt?', 'mp' ) ),
			'message'       => __( 'Yes', 'mp' ),
			'desc'          => __( 'If enabled, description excerpt will be added above Add to cart.', 'mp' ),
			'default_value' => 1,
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_single_categories',
			'label'   => array( 'text' => __( 'Show Categories List?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
			'desc'    => __( 'Show Categories List?', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_single_tags',
			'label'   => array( 'text' => __( 'Show Tags List?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
			'desc'    => __( 'Show Tags List?', 'mp' ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'    => 'show_img',
			'label'   => array( 'text' => __( 'Show Product Image?', 'mp' ) ),
			'message' => __( 'Yes', 'mp' ),
		) );
		$metabox->add_field( 'select', array(
			'name'        => 'product_img_size',
			'label'       => array( 'text' => __( 'Image Size', 'mp' ) ),
			'options'     => array(
				'thumbnail' => sprintf( __( 'Thumbnail - %s', 'mp' ), $this->get_image_size_label( 'thumbnail' ) ),
				'medium'    => sprintf( __( 'Medium - %s', 'mp' ), $this->get_image_size_label( 'medium' ) ),
				'large'     => sprintf( __( 'Large - %s', 'mp' ), $this->get_image_size_label( 'large' ) ),
				'custom'    => __( 'Custom', 'mp' ),
			),
			'conditional' => array(
				'name'   => 'show_img',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$custom_size = $metabox->add_field( 'complex', array(
			'name'        => 'product_img_size_custom',
			'label'       => array( 'text' => __( 'Custom Image Size', 'mp' ) ),
			'conditional' => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'show_img',
					'value' => '1',
				),
				array(
					'name'  => 'product_img_size',
					'value' => 'custom',
				)
			),
		) );

		if ( $custom_size instanceof WPMUDEV_Field ) {
			$custom_size->add_field( 'text', array(
				'name'       => 'width',
				'label'      => array( 'text' => __( 'Width', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'digits'   => true,
					'min'      => 0,
				),
			) );
			$custom_size->add_field( 'text', array(
				'name'       => 'height',
				'label'      => array( 'text' => __( 'Height', 'mp' ) ),
				'validation' => array(
					'required' => true,
					'digits'   => true,
					'min'      => 0,
				),
			) );
		}

		$metabox->add_field( 'radio_group', array(
			'name'        => 'image_alignment_single',
			'label'       => array( 'text' => __( 'Image Alignment', 'mp' ) ),
			'options'     => array(
				//'alignnone'		 => __( 'None', 'mp' ),
				'alignleft'   => __( 'Left', 'mp' ),
				'aligncenter' => __( 'Center', 'mp' ),
				'alignright'  => __( 'Right', 'mp' ),
			),
			'default_value' => 'alignleft',
			'conditional' => array(
				'name'   => 'show_img',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'        => 'disable_large_image',
			'label'       => array( 'text' => __( 'Disable Large Image Display?', 'mp' ) ),
			'message'     => __( 'Yes', 'mp' ),
			'conditional' => array(
				'name'   => 'show_img',
				'value'  => '1',
				'action' => 'show',
			),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'        => 'show_lightbox',
			'label'       => array( 'text' => __( 'Use Built-In Lightbox for Images?', 'mp' ) ),
			'desc'        => __( 'If you are having conflicts with the lightbox library from your theme or another plugin you should uncheck this.', 'mp' ),
			'message'     => __( 'Yes', 'mp' ),
			'conditional' => array(
				'operator' => 'AND',
				'action'   => 'show',
				array(
					'name'  => 'show_img',
					'value' => '1',
				),
				array(
					'name'  => 'disable_large_image',
					'value' => '-1',
				),
			),
		) );
	}

	/**
	 * Init the general settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_general_settings() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'          => 'mp-settings-presentation-general',
			'page_slugs'  => array( 'store-settings-presentation', 'store-settings_page_store-settings-presentation' ),
			'title'       => __( 'General Settings', 'mp' ),
			'option_name' => 'mp_settings',
		) );

		$metabox->add_field( 'radio_group', array(
			'name'    => 'store_theme',
			'desc'    => sprintf( __( 'This option changes the built-in css styles for store pages. For a custom css style, save your css file with the <strong>/* MarketPress Style: Your CSS Theme Name Here */</strong> header line in the <strong>"%s"</strong> folder and it will appear in this list so you may select it. You should select "None" if you don\'t wish to use custom CSS styles or if you are using default theme templates or custom theme templates and css to make your own completely unique store design. For more information on custom theme templates click <a target="_blank" href="%s">here &raquo;</a>.', 'mp' ), trailingslashit( WP_CONTENT_DIR ) . 'marketpress-styles/', mp_plugin_url( 'ui/themes/Theming_MarketPress.txt' ) ),
			'label'   => array( 'text' => __( 'Store Style', 'mp' ) ),
			'options' => mp_get_theme_list() + array(
				'default' => __( 'Default - Using Default CSS-styles', 'mp' ),
				'none' => __( 'None - Without Special CSS-styles', 'mp' ),
				),
			'width'   => '50%',
		) );
		/*$metabox->add_field( 'checkbox', array(
			'name'		 => 'show_purchase_breadcrumbs',
			'label'		 => array( 'text' => __( 'Show Breadcrumbs?', 'mp' ) ),
			'message'	 => __( 'Yes', 'mp' ),
			'desc'		 => __( 'Shows previous, current and next steps when a customer is checking out -- shown below the title.', 'mp' ),
		) );*/
	}

}

MP_Store_Settings_Presentation::get_instance();
