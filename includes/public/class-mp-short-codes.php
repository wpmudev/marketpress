<?php

class MP_Short_Codes {

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
			self::$_instance = new MP_Short_Codes();
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
		//register our shortcodes
		add_shortcode( 'mp_tag_cloud', array( &$this, 'mp_tag_cloud_sc' ) );
		add_shortcode( 'mp_list_categories', array( &$this, 'mp_list_categories_sc' ) );
		add_shortcode( 'mp_dropdown_categories', array( &$this, 'mp_dropdown_categories_sc' ) );
		add_shortcode( 'mp_featured_products', array( &$this, 'mp_featured_products_sc' ) );
		add_shortcode( 'mp_popular_products', array( &$this, 'mp_popular_products_sc' ) );
		add_shortcode( 'mp_related_products', array( &$this, 'mp_related_products_sc' ) );
		add_shortcode( 'mp_list_products', array( &$this, 'mp_list_products_sc' ) );
		add_shortcode( 'mp_product', array( &$this, 'mp_product_sc' ) );
		add_shortcode( 'mp_product_image', array( &$this, 'mp_product_image_sc' ) );
		add_shortcode( 'mp_buy_button', array( &$this, 'mp_buy_button_sc' ) );
		add_shortcode( 'mp_product_price', array( &$this, 'mp_product_price_sc' ) );
		add_shortcode( 'mp_product_meta', array( &$this, 'mp_product_meta_sc' ) );
		add_shortcode( 'mp_product_sku', array( &$this, 'mp_product_sku_sc' ) );
		add_shortcode( 'mp_product_stock', array( &$this, 'mp_product_stock_sc' ) );
		add_shortcode( 'mp_cart', array( &$this, 'mp_cart_sc' ) );
		add_shortcode( 'mp_cart_widget', array( &$this, 'mp_cart_widget_sc' ) );
		add_shortcode( 'mp_checkout', array( &$this, 'mp_checkout_sc' ) );
		add_shortcode( 'mp_order_status', array( &$this, 'mp_order_status_sc' ) );
		add_shortcode( 'mp_order_lookup_form', array( &$this, 'mp_order_lookup_form_sc' ) );

		//store links
		add_shortcode( 'mp_cart_link', array( &$this, 'mp_cart_link_sc' ) );
		add_shortcode( 'mp_store_link', array( &$this, 'mp_store_link_sc' ) );
		add_shortcode( 'mp_products_link', array( &$this, 'mp_products_link_sc' ) );
		add_shortcode( 'mp_orderstatus_link', array( &$this, 'mp_orderstatus_link_sc' ) );
		add_shortcode( 'mp_store_navigation', array( &$this, 'mp_store_navigation_sc' ) );
	}

	public function shortcode_wrap( $content, $class = '' ) {
		return '<div class="mp-shortcode-wrap ' . esc_attr( $class ) . '">' . $content . '</div>';
	}

	public function cart_needed() {
		add_filter( 'mp_cart_needed_on_page', array( &$this, 'cart_needed_return' ) );
	}

	public function cart_needed_return() {
		return true;
	}

	/**
	 * Enqueue frontend styles and scripts for shortcodes
	 * Useful when a shortcode is called on non-MP pages
	 *
	 * @since 3.0
	 * @access public
	 */
	public function shortcodes_frontend_styles_scripts() {
		wp_enqueue_script( 'lightslider', mp_plugin_url( 'ui/lightslider/js/lightslider.js' ), array( 'jquery' ), MP_VERSION );
		wp_enqueue_style( 'lightslider', mp_plugin_url( 'ui/lightslider/css/lightslider.css' ), array(), MP_VERSION );
		wp_enqueue_script( 'lightgallery', mp_plugin_url( 'ui/lightgallery/js/lightgallery.js' ), array( 'jquery' ), MP_VERSION );
		wp_enqueue_style( 'lightgallery', mp_plugin_url( 'ui/lightgallery/css/lightgallery.css' ), array(), MP_VERSION );
		// CSS.
		wp_register_style( 'jquery-ui', mp_plugin_url( 'ui/css/jquery-ui.min.css' ), false, MP_VERSION );
		wp_enqueue_style( 'mp-base', mp_plugin_url( 'ui/css/marketpress.css' ), false, MP_VERSION );

		if ( mp_get_setting( 'store_theme' ) == 'default' ) {
			wp_enqueue_style( 'mp-theme', mp_plugin_url( 'ui/themes/' . mp_get_setting( 'store_theme' ) . '.css' ), array(), MP_VERSION );
		} elseif ( mp_get_setting( 'store_theme' ) != 'none' ) {
			wp_enqueue_style( 'mp-theme', content_url( 'marketpress-styles/' . mp_get_setting( 'store_theme' ) . '.css' ), array(), MP_VERSION );
		}

		wp_enqueue_style( 'mp-select2', mp_plugin_url( 'ui/select2/select2.css' ), false, MP_VERSION );

		// JS.
		wp_register_script( 'hover-intent', mp_plugin_url( 'ui/js/hoverintent.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'mp-select2', mp_plugin_url( 'ui/select2/select2.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'colorbox', mp_plugin_url( 'ui/js/jquery.colorbox-min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_enqueue_script( 'mp-frontend', mp_plugin_url( 'ui/js/frontend.js' ), array( 'jquery-ui-tooltip', 'colorbox', 'hover-intent', 'mp-select2', 'jquery-validate' ), MP_VERSION, true );

		$grid_with_js = apply_filters('mp-do_grid_with_js', true);

		if ( 'true' == $grid_with_js ) {
			wp_enqueue_script( 'mp-equal-height', mp_plugin_url( 'ui/js/mp-equal-height.js' ), array( 'jquery' ), MP_VERSION );
		}

		// Get product category links.
		$terms = get_terms( 'product_category' );
		$cats  = array();
		foreach ( $terms as $term ) {
			$cats[ $term->term_id ] = get_term_link( $term );
		}

		// Localize js.
		wp_localize_script( 'mp-frontend', 'mp_i18n', array(
			'ajaxurl'      => mp_get_ajax_url(),
			'loadingImage' => mp_plugin_url( 'ui/images/loading.gif' ),
			'productsURL'  => mp_store_page_url( 'products', false ),
			'productCats'  => $cats,
			'validation'   => array(
				'required'    => __( 'This field is required.', 'mp' ),
				'remote'      => __( 'Please fix this field.', 'mp' ),
				'email'       => __( 'Please enter a valid email address.', 'mp' ),
				'url'         => __( 'Please enter a valid URL.', 'mp' ),
				'date'        => __( 'Please enter a valid date.', 'mp' ),
				'dateISO'     => __( 'Please enter a valid date (ISO).', 'mp' ),
				'number'      => __( 'Please enter a valid number.', 'mp' ),
				'digits'      => __( 'Please enter only digits.', 'mp' ),
				'creditcard'  => __( 'Please enter a valid credit card number.', 'mp' ),
				'equalTo'     => __( 'Please enter the same value again.', 'mp' ),
				'accept'      => __( 'Please enter a value with a valid extension.', 'mp' ),
				'maxlength'   => __( 'Please enter no more than {0} characters.', 'mp' ),
				'minlength'   => __( 'Please enter at least {0} characters.', 'mp' ),
				'rangelength' => __( 'Please enter a value between {0} and {1} characters long.', 'mp' ),
				'range'       => __( 'Please enter a value between {0} and {1}.', 'mp' ),
				'max'         => __( 'Please enter a value less than or equal to {0}.', 'mp' ),
				'min'         => __( 'Please enter a value greater than or equal to {0}.', 'mp' ),
			),
		) );

		// Styles
		wp_enqueue_style( 'colorbox', mp_plugin_url( 'ui/css/colorbox.css' ), false, MP_VERSION );

		// Scripts
		wp_register_script( 'jquery-validate', mp_plugin_url( 'ui/js/jquery.validate.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'jquery-validate-methods', mp_plugin_url( 'ui/js/jquery.validate.methods.min.js' ), array( 'jquery-validate' ), MP_VERSION, true );
		wp_register_script( 'ajaxq', mp_plugin_url( 'ui/js/ajaxq.min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_register_script( 'colorbox', mp_plugin_url( 'ui/js/jquery.colorbox-min.js' ), array( 'jquery' ), MP_VERSION, true );
		wp_enqueue_script( 'mp-cart', mp_plugin_url( 'ui/js/mp-cart.js' ), array( 'ajaxq', 'colorbox', 'jquery-validate' ), MP_VERSION, true );

		// Localize scripts
		wp_localize_script( 'mp-cart', 'mp_cart_i18n', array(
			'ajaxurl'                  => mp_get_ajax_url(),
			'ajax_loader'              => '<span class="mp_ajax_loader"><img src="' . mp_plugin_url( 'ui/images/ajax-loader.gif' ) . '" alt=""> ' . __( 'Adding...', 'mp' ) . '</span>',
			'cart_updated_error_limit' => __( 'Cart update notice: this item has a limit per order or you have reached the stock limit.', 'mp' ),
			'is_cart_page'             => mp_is_shop_page( 'cart' ),
		) );
	}

	/**
	 * Parse shortcode parameters
	 *
	 * @since 3.0
	 * @access protected
	 * @param array $atts The atts to parse.
	 * @return array
	 */
	protected function _parse_atts( $atts ) {
		if ( !is_array( $atts ) ) {
			return array();
		}

		foreach ( $atts as $key => &$value ) {
			if ( 'true' == $value || '1' == $value ) {
				$value = true;
			} elseif ( 'false' == $value || '0' == $value ) {
				$value = false;
			}
		}

		return $atts;
	}

	/**
	 * Display order lookup form
	 *
	 * @since 3.0
	 * @access public
	 */
	public function mp_order_lookup_form_sc( $atts, $content = '' ) {
		$this->shortcodes_frontend_styles_scripts();
		return mp_order_lookup_form( array(
			'echo'		 => false,
			'content'	 => $content,
		) );
	}

	/**
	 * Display order status
	 *
	 * @since 3.0
	 * @access public
	 * @param array $atts {
	 * 		Optional, an array of attributes
	 *
	 * 		@type string $order_id Optional, the specific order id to show.
	 * }
	 */
	public function mp_order_status_sc( $atts, $content = null ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts = shortcode_atts( array(
			'echo' => false
		), $atts );
		
		return mp_order_status( $atts );
	}

	/**
	 * Display checkout form
	 *
	 * @since 3.0
	 * @access public
	 */
	public function mp_checkout_sc( $atts, $content = null ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts = shortcode_atts( array(
			'echo' => false
		), $atts );
		
		return mp_checkout()->display( $atts );
	}

	/**
	 * Display cart contents
	 *
	 * @since 3.0
	 * @access public
	 */
	public function mp_cart_sc( $atts, $content = null ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts = shortcode_atts( array(
			'echo' => false
		), $atts );
		
		return MP_Cart::get_instance()->display( $atts );
	}

	/**
	 * Display a cart widget.
	 *
	 * The list of arguments is below:
	 *     "title" (string) - Text to display as title.
	 *     "custom_text" (string) - Custom text to display before cart.
	 *
	 */
	function mp_cart_widget_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts = $this->_parse_atts( $atts );
		return mp_cart_widget( false, $atts );
	}

	/**
	 * Display product tag cloud.
	 *
	 * The text size is set by the 'smallest' and 'largest' arguments, which will
	 * use the 'unit' argument value for the CSS text size unit. The 'format'
	 * argument can be 'flat' (default), 'list', or 'array'. The flat value for the
	 * 'format' argument will separate tags with spaces. The list value for the
	 * 'format' argument will format the tags in a UL HTML list. The array value for
	 * the 'format' argument will return in PHP array type format.
	 *
	 * The 'orderby' argument will accept 'name' or 'count' and defaults to 'name'.
	 * The 'order' is the direction to sort, defaults to 'ASC' and can be 'DESC'.
	 *
	 * The 'number' argument is how many tags to return. By default, the limit will
	 * be to return the top 45 tags in the tag cloud list.
	 *
	 * The 'topic_count_text_callback' argument is a function, which, given the count
	 * of the posts  with that tag, returns a text for the tooltip of the tag link.
	 *
	 * The 'exclude' and 'include' arguments are used for the {@link get_tags()}
	 * function. Only one should be used, because only one will be used and the
	 * other ignored, if they are both set.
	 *
	 */
	function mp_tag_cloud_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts = $this->_parse_atts( $atts );
		return mp_tag_cloud( false, $atts );
	}

	/**
	 * Display or retrieve the HTML list of product categories.
	 *
	 * The list of arguments is below:
	 *     "show_option_all" (string) - Text to display for showing all categories.
	 *     "orderby" (string) default is "ID" - What column to use for ordering the
	 * categories.
	 *     "order" (string) default is "ASC" - What direction to order categories.
	 *     "show_last_update" (bool|int) default is 0 - See {@link
	 * walk_category_dropdown_tree()}
	 *     "show_count" (bool|int) default is 0 - Whether to show how many posts are
	 * in the category.
	 *     "hide_empty" (bool|int) default is 1 - Whether to hide categories that
	 * don"t have any posts attached to them.
	 *     "use_desc_for_title" (bool|int) default is 1 - Whether to use the
	 * description instead of the category title.
	 *     "feed" - See {@link get_categories()}.
	 *     "feed_type" - See {@link get_categories()}.
	 *     "feed_image" - See {@link get_categories()}.
	 *     "child_of" (int) default is 0 - See {@link get_categories()}.
	 *     "exclude" (string) - See {@link get_categories()}.
	 *     "exclude_tree" (string) - See {@link get_categories()}.
	 *     "current_category" (int) - See {@link get_categories()}.
	 *     "hierarchical" (bool) - See {@link get_categories()}.
	 *     "title_li" (string) - See {@link get_categories()}.
	 *     "depth" (int) - The max depth.
	 *
	 */
	function mp_list_categories_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts = $this->_parse_atts( $atts );
		return mp_list_categories( false, $atts );
	}

	/**
	 * Display or retrieve the HTML dropdown list of product categories.
	 *
	 * The list of arguments is below:
	 *     "show_option_all" (string) - Text to display for showing all categories.
	 *     "show_option_none" (string) - Text to display for showing no categories.
	 *     "orderby" (string) default is "ID" - What column to use for ordering the
	 * categories.
	 *     "order" (string) default is "ASC" - What direction to order categories.
	 *     "show_last_update" (bool|int) default is 0 - See {@link get_categories()}
	 *     "show_count" (bool|int) default is 0 - Whether to show how many posts are
	 * in the category.
	 *     "hide_empty" (bool|int) default is 1 - Whether to hide categories that
	 * don"t have any posts attached to them.
	 *     "child_of" (int) default is 0 - See {@link get_categories()}.
	 *     "exclude" (string) - See {@link get_categories()}.
	 *     "depth" (int) - The max depth.
	 *     "tab_index" (int) - Tab index for select element.
	 *     "name" (string) - The name attribute value for select element.
	 *     "id" (string) - The ID attribute value for select element. Defaults to name if omitted.
	 *     "class" (string) - The class attribute value for select element.
	 *     "selected" (int) - Which category ID is selected.
	 *     "taxonomy" (string) - The name of the taxonomy to retrieve. Defaults to category.
	 *
	 * The "hierarchical" argument, which is disabled by default, will override the
	 * depth argument, unless it is true. When the argument is false, it will
	 * display all of the categories. When it is enabled it will use the value in
	 * the "depth" argument.
	 *
	 */
	function mp_dropdown_categories_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts = $this->_parse_atts( $atts );
		return mp_dropdown_categories( false, $atts );
	}

	/**
	 * Displays a list of featured products
	 *
	 * @param bool paginate Optional, whether to paginate
	 * @param int page Optional, The page number to display in the product list if paginate is set to true.
	 * @param int per_page Optional, How many products to display in the product list if $paginate is set to true.
	 * @param string order_by Optional, What field to order products by. Can be: title, date, ID, author, price, sales, rand
	 * @param string order Optional, Direction to order products by. Can be: DESC, ASC
	 * @param string category Optional, limit to a product category
	 * @param string tag Optional, limit to a product tag
	 * @param bool list_view Optional, show as list. Grid default
	 * @param bool filters Optional, show filters
	 */
	function mp_featured_products_sc( $atts ) {
		$this->cart_needed();
		$this->shortcodes_frontend_styles_scripts();
		$args = shortcode_atts( mp()->defaults[ 'featured_list_products_sc' ], $atts );
		$args = $this->_parse_atts( $args );

		return mp_featured_products( $args );
	}

	/**
	 * Displays a list of popular products ordered by sales.
	 *
	 * @param int num Optional, max number of products to display. Defaults to 5
	 */
	function mp_popular_products_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'number' => 5,
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );

		return mp_popular_products( false, $number );
	}

	/**
	 * Displays related products for the passed product id
	 *
	 * @param int $product_id. 
	 * @param string $relate_by Optional, whether to limit related to the same category, tags or both.
	 * @param bool $echo. Optional, whether to echo or return the results
	 * @param int $limit. Optional The number of products we want to retrieve.
	 * @param bool $simple_list Optional, whether to show the related products based on the "list_view" setting or as a simple unordered list
	 */
	function mp_related_products_sc( $atts ) {
		$this->cart_needed();
		$this->shortcodes_frontend_styles_scripts();
		$atts = shortcode_atts( array(
			'echo' => false
		), $atts );

		$args				 = $this->_parse_atts( $atts );
		$related_products	 = '';

		$product_id = isset( $atts[ 'product_id' ] ) ? $atts[ 'product_id' ] : 0;
		if ( $product_id > 0 ) {
			$product			 = new MP_Product( $product_id );
			$related_products	 = '<div id="mp-related-products" class="mp-multiple-products"><div class="mp_product_tab_content_products mp_products mp_products-related mp_products-list">' . $product->related_products( $args ) . '</div></div>';
		} else {
			$related_products = __( 'product_id must be defined', 'mp' );
		}
		return $related_products;
	}

	/*
	 * Displays a list of products according to preference. Optional values default to the values in Presentation Settings -> Product List
	 *
	 * @param bool paginate Optional, whether to paginate
	 * @param int page Optional, The page number to display in the product list if paginate is set to true.
	 * @param int per_page Optional, How many products to display in the product list if $paginate is set to true.
	 * @param string order_by Optional, What field to order products by. Can be: title, date, ID, author, price, sales, rand
	 * @param string order Optional, Direction to order products by. Can be: DESC, ASC
	 * @param string category Optional, limit to a product category
	 * @param string tag Optional, limit to a product tag
	 * @param bool list_view Optional, show as list. Grid default
	 * @param bool filters Optional, show filters
	 */

	function mp_list_products_sc( $atts ) {
		$this->cart_needed();
		$this->shortcodes_frontend_styles_scripts();
		$args = shortcode_atts( mp()->defaults[ 'list_products_sc' ], $atts );
		$args = $this->_parse_atts( $args );

		return mp_list_products( $args );
	}

	/*
	 * Displays a single product according to preference
	 * 
	 * @param int $product_id the ID of the product to display
	 * @param bool $title Whether to display the title
	 * @param bool/string $content Whether and what type of content to display. Options are false, 'full', or 'excerpt'. Default 'full'
	 * @param bool/string $image Whether and what context of image size to display. Options are false, 'single', or 'list'. Default 'single'
	 * @param bool $meta Whether to display the product meta
	 */

	function mp_product_sc( $atts ) {
		$this->cart_needed();
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'product_id' => false,
			'title'		 => true,
			'content'	 => 'full',
			'image'		 => 'single',
			'meta'		 => true
		), $atts );

		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );

		return mp_product( false, $product_id, $title, $content, $image, $meta );
	}

	/**
	 * Displays the product featured image
	 *
	 * @param string $context Options are list, single, or widget
	 * @param int $product_id The post_id for the product. Optional if in the loop
	 * @param int $size An optional width/height for the image if contect is widget
	 * @param string $align An option alignment of the image
	 */
	function mp_product_image_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'context'	 => 'single',
			'product_id' => NULL,
			'size'		 => NULL,
			'align'		 => NULL,
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );
		$product = new MP_Product( $product_id );
		$image	 = $product->image( false, $context, $size, $align );
		return $image; //mp_product_image( false, $context, $product_id, $size, $align );
	}

	/*
	 * Displays the buy or add to cart button
	 *
	 * @param string $context Options are list or single
	 * @param int $post_id The post_id for the product. Optional if in the loop
	 */

	function mp_buy_button_sc( $atts ) {
		$this->cart_needed();
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'context'	 => 'single',
			'product_id' => NULL
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );

		return $this->shortcode_wrap( mp_buy_button( false, $context, $product_id ) );
	}

	/*
	 * Displays the product price (and sale price)
	 *
	 * @param int $product_id The post_id for the product. Optional if in the loop
	 * @param sting $label A label to prepend to the price. Defaults to "Price: "
	 */

	function mp_product_price_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'label'		 => true,
			'product_id' => NULL
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );

		return mp_product_price( false, $product_id, $label );
	}

	/*
	 * Displays the product meta box
	 *
	 * @param string $context Options are list or single
	 * @param int $product_id The post_id for the product. Optional if in the loop
	 * @param sting $label A label to prepend to the price. Defaults to "Price: "
	 */

	function mp_product_meta_sc( $atts ) {
		$this->cart_needed();
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'context'	 => 'single',
			'label'		 => true,
			'product_id' => NULL
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );

		$content = '<div class="mp_product_meta">';
		//$content .= $product->display_price( false );
		//$content .= $product->buy_button( false );
		$content .= mp_product_price( false, $product_id, $label );
		$content .= mp_buy_button( false, $context, $product_id );
		$content .= '</div>';
		return $this->shortcode_wrap( $content );//, 'mp-multiple-products'
	}

	/*
	 * Displays the product SKU
	 *
	 * @param int $product_id The post_id for the product. Optional if in the loop
	 * @param string $seperator The seperator to put between skus, default ', '
	 */

	function mp_product_sku_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'seperator'	 => false,
			'product_id' => NULL
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );

		return mp_product_sku( false, $product_id, $seperator );
	}
	
	/*
	 * Displays the product stock quantity
	 *
	 * @param int $product_id The post_id for the product.
	 */

	function mp_product_stock_sc( $atts ) {
		$atts	 = shortcode_atts( array(
			'product_id' => NULL
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );
		
		$product = new MP_Product( $product_id );
		
		$variations = $product->get_variations();
		
		if ( $product->has_variations() ) {
			$stock = 0;
			foreach ( $variations as $variation ) {
				$stock_val = $variation->get_meta( 'inventory', '&mdash;' );
				if ( is_numeric( $stock_val ) ) {
					$stock = $stock + $variation->get_meta( 'inventory', '&mdash;' );
				} else {
					$stock = '&mdash;';
				}
			}
		} else {
			$stock = $product->get_meta( 'inventory', '&mdash;' );
		}
		
		if( $stock == '&mdash;' ) {
			return __( 'Unlimited stock', 'mp' );
		}
		
		return sprintf( __( 'Only %s left in stock...', 'mp' ), $stock );
	}

	/**
	 * Returns the current shopping cart link.
	 * @param bool url Optional, whether to return a link or url. Defaults to show link.
	 * @param string link_text Optional, text to show in link.
	 */
	function mp_cart_link_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'url'		 => false,
			'link_text'	 => '',
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );

		return $this->shortcode_wrap( mp_cart_link( false, $url, $link_text ) );
	}

	/**
	 * Returns the current store link.
	 * @param bool url Optional, whether to return a link or url. Defaults to show link.
	 * @param string link_text Optional, text to show in link.
	 */
	function mp_store_link_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'url'		 => false,
			'link_text'	 => '',
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );

		return mp_store_link( false, $url, $link_text );
	}

	/**
	 * Returns the current product list link.
	 * @param bool url Optional, whether to return a link or url. Defaults to show link.
	 * @param string link_text Optional, text to show in link.
	 */
	function mp_products_link_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'url'		 => false,
			'link_text'	 => '',
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );

		return mp_products_link( false, $url, $link_text );
	}

	/**
	 * Returns the current order status link.
	 * @param bool url Optional, whether to return a link or url. Defaults to show link.
	 * @param string link_text Optional, text to show in link.
	 */
	function mp_orderstatus_link_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		$atts	 = shortcode_atts( array(
			'url'		 => false,
			'link_text'	 => '',
		), $atts );
		$atts	 = $this->_parse_atts( $atts );

		extract( $atts );

		return mp_orderstatus_link( false, $url, $link_text );
	}

	/**
	 * Returns the current store navigation links.
	 *
	 */
	function mp_store_navigation_sc( $atts ) {
		$this->shortcodes_frontend_styles_scripts();
		//! TODO: add mp_store_navigation function
		return mp_store_navigation( false );
	}

}

MP_Short_Codes::get_instance();
