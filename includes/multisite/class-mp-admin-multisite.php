<?php

class MP_Admin_Multisite {

	/**
	 * Refers to a single instance of the class
	 *
	 * @since 3.0
	 * @access private
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Refers to the current build of the class
	 *
	 * @since 3.0
	 * @access public
	 * @var int
	 */
	var $build = 1;

	/**
	 * Gets the single instance of the class
	 *
	 * @since 3.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new MP_Admin_Multisite();
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
		if ( ! is_plugin_active_for_network( mp_get_plugin_slug() ) ) {
			return;
		}

		if ( is_network_admin() ) {
			add_action( 'init', array( &$this, 'init_metaboxes' ) );
			add_action( 'network_admin_menu', array( &$this, 'add_menu_items' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );
			add_action( 'wpmudev_field/print_scripts/network_store_page', array(
				&$this,
				'print_network_store_page_scripts'
			) );
			add_filter( 'wpmudev_field/after_field', array( &$this, 'display_create_page_button' ), 10, 2 );
			add_action( 'wpmudev_field/print_scripts', array( &$this, 'create_store_page_js' ) );
		}
		add_action( 'wp_ajax_mp_index_products', array( &$this, 'index_products' ) );
		if ( mp_get_network_setting( 'global_cart' ) ) {
			add_filter( 'wpmudev_field/get_value/gateways[allowed][' . mp_get_network_setting( 'global_gateway', '' ) . ']', array(
				&$this,
				'force_check_global_gateway'
			), 10, 4 );
		}
	}

	/**
	 * Get the Post Indexer nag notice html
	 *
	 * @since 3.0
	 * @access protected
	 * @global $wpmudev_un
	 */
	protected function _post_indexer_install_html() {
		global $wpmudev_un;

		$install_btn = '';
		if ( isset( $wpmudev_un ) ) {
			if ( $url = $wpmudev_un->auto_install_url( 30 ) ) {
				$install_btn = '<a class="button-primary" href="' . $url . '">' . __( 'Install Post Indexer', 'mp' ) . '</a>';
			}
		}

		$plugin_url = 'https://premium.wpmudev.org/project/post-indexer';

		return sprintf( __( '<strong>IMPORTANT!</strong> The MarketPress Global Cart requires the <a target="_blank" href="%s">Post Indexer</a> plugin to also be installed. This feature will not work until <a target="_blank" href="%s">Post Indexer</a> has been installed. %s', 'mp' ), $plugin_url, $plugin_url, $install_btn );
	}

	/**
	 * Force check the global gateway
	 *
	 * @since 3.0
	 * @access public
	 * @filter wpmudev_field/get_value/gateways[allowed][ {global_gateway} ]
	 */
	public function force_check_global_gateway( $value, $post_id, $raw, $field ) {
		return 1;
	}

	/**
	 * If global cart is enabled and the Post Indexer plugin is not installed, display an admin notice
	 *
	 * @since 3.0
	 * @access public
	 * @action network_admin_notices
	 */
	public function post_indexer_admin_notice() {
		echo '<div class="error"><p>' . $this->_post_indexer_install_html() . '</p></div>';
	}

	/**
	 * Print network_store_page scripts
	 *
	 * When changing the network_store_page value update the product_category and
	 * product_tag slug that is shown before those fields.
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field/print_scripts/network_store_page
	 */
	public function print_network_store_page_scripts( $field ) {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				$('.mp-create-page-button').click(function (e) {
					e.preventDefault();

					var $this = $(this),
						$select = $this.siblings('[name="network_store_page"]');

					$this.isWorking(true);

					$.getJSON($this.attr('href'), function (resp) {
						if (resp.success) {
							$select.attr('data-select2-value', resp.data.select2_value).select2('val', resp.data.post_id).trigger('change');
							$this.isWorking(false).replaceWith(resp.data.button_html);
							$('.mp-network-store-page-slug').html(resp.data.parent_slug);
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
	 * Enqueue admin styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_enqueue_scripts
	 */
	public function enqueue_styles_scripts() {
		// Styles
		wp_enqueue_style( 'mp-admin', mp_plugin_url( 'includes/admin/ui/css/admin.css' ), array(), MP_VERSION );
		// Scripts
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Initialize metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_general_settings_metabox();
		$this->init_indexer_metabox();
		$this->init_global_gateway_settings_metabox();
		$this->init_gateway_permissions_metabox();
		$this->init_theme_permissions_metabox();
		$this->init_network_pages();
		do_action( 'mp_multisite_init_metaboxes' );
	}

	/**
	 * Initialize general settings metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_general_settings_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'               => 'mp-network-settings-general',
			'page_slugs'       => array( 'network-store-settings' ),
			'title'            => __( 'General Settings', 'mp' ),
			'site_option_name' => 'mp_network_settings',
			'order'            => 0,
		) );
		$metabox->add_field( 'checkbox', array(
			'name'  => 'main_blog',
			'label' => array( 'text' => __( 'Limit Global Widgets/Shortcodes To Main Blog?', 'mp' ) ),
		) );
		$metabox->add_field( 'checkbox', array(
			'name'  => 'global_cart',
			'label' => array( 'text' => __( 'Enable Global Shopping Cart?', 'mp' ) ),
		) );
	}

	/**
	 * Display indexer information
	 */
	public function init_indexer_metabox() {
		$count = MP_Multisite::get_instance()->count();
		//$count='';
		$html = sprintf( __( "%d products have been indexed in whole network", "mp" ), $count ) . '<br/><br/>';
		$html .= '<button type="button" class="button mp_index_products">' . __( "Index Products", "mp" ) . '</button>';
		$html .= '<p class="index-status" style="display: none;">' . __( "Please hold on...", "mp" ) . '</p>';
		$metabox = new WPMUDEV_Metabox( array(
			'id'               => 'mp-post-indexer',
			'page_slugs'       => array( 'network-store-settings' ),
			'title'            => __( 'Product Indexer', 'mp' ),
			'desc'             => $html,
			'site_option_name' => '',
			'order'            => 0,
		) );

		add_action( 'admin_footer', array( &$this, 'index_products_script' ) );
	}

	function index_products_script() {
		if ( is_network_admin() ) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$('.mp_index_products').click(function () {
						var that = $(this);
						$.ajax({
							type: 'POST',
							data: {
								action: 'mp_index_products',
								_nonce: '<?php echo wp_create_nonce('mp_index_products') ?>'
							},
							url: ajaxurl,
							beforeSend: function () {
								that.attr('disabled', 'disabled');
								$('.index-status').css('display', 'block');
							},
							success: function (data) {
								that.removeAttr('disabled');
								$('.index-status').text(data.text);
							}
						})
					})
				})
			</script>
			<?php
		}
	}

	public function index_products() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}

		if ( ! wp_verify_nonce( mp_get_post_value( '_nonce' ), 'mp_index_products' ) ) {
			die();
		}

		$result = MP_Multisite::get_instance()->index_content();

		wp_send_json( array(
			'text' => sprintf( __( "%d products get indexed", "mp" ), $result['count'] )
		) );
	}

	/**
	 * Initialize global gateway metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_global_gateway_settings_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'               => 'mp-network-settings-global-gateway',
			'page_slugs'       => array( 'network-store-settings' ),
			'title'            => __( 'Global Gateway', 'mp' ),
			'site_option_name' => 'mp_network_settings',
			'order'            => 0,
			'conditional'      => array(
				'name'   => 'global_cart',
				'value'  => '1',
				'action' => 'show',
			),
		) );

		$all_gateways = MP_Gateway_API::get_gateways();
		$gateways     = array( '' => __( 'Choose a Gateway', 'mp' ) );

		foreach ( $all_gateways as $code => $gateway ) {

			if ( ! $gateway[2] ) {
				// Skip non-global gateways
				continue;
			}

			$gateways[ $code ] = $gateway[1];
		}

		$metabox->add_field( 'select', array(
			'name'    => 'global_gateway',
			'label'   => array( 'text' => __( 'Select a Gateway', 'mp' ) ),
			'options' => $gateways,
		) );
	}

	/**
	 * Initialize gateway permissions metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_gateway_permissions_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'               => 'mp-network-settings-gateway-permissions',
			'page_slugs'       => array( 'network-store-settings' ),
			'title'            => __( 'Gateway Permissions', 'mp' ),
			'site_option_name' => 'mp_network_settings',
			'order'            => 0,
			'conditional'      => array(
				'name'   => 'global_cart',
				'value'  => '1',
				'action' => 'hide',
			),
		) );

		$options_permissions = array(
			'full' => __( 'All Can Use', 'mp' ),
			'none' => __( 'No Access', 'mp' ),
		);

		/**
		 * Filter the gateway permissions options list
		 *
		 * @since 3.0
		 * @access public
		 *
		 * @param array $options_permissions An array of options.
		 */
		$options_permissions = apply_filters( 'mp_admin_multisite/gateway_permissions_options', $options_permissions );

		$gateways = MP_Gateway_API::get_gateways();

		foreach ( $gateways as $code => $gateway ) {
			if ( $code !== 'free_orders' ) {//we don't need to show free orders gateways since it will be automatically activated if needed
				$metabox->add_field( 'select', array(
					'name'    => 'allowed_gateways[' . $code . ']',
					'label'   => array( 'text' => $gateway[1] ),
					'options' => $options_permissions,
				) );
			}
		}
	}

	/**
	 * Initialize theme permissions metabox
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_theme_permissions_metabox() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'               => 'mp-network-settings-theme-permissions',
			'page_slugs'       => array( 'network-store-settings' ),
			'title'            => __( 'Theme Permissions', 'mp' ),
			'site_option_name' => 'mp_network_settings',
			'desc'             => __( 'Set theme access permissions for network stores. For a custom css theme, save your css file with the <strong>MarketPress Theme: NAME</strong> header in the <strong>/marketpress/ui/themes/</strong> folder and it will appear in this list so you may select it.', 'mp' ),
			'order'            => 15,
		) );

		$theme_list = mp_get_theme_list();

		$options_permissions = array(
			'full' => __( 'All Can Use', 'mp' ),
			'none' => __( 'No Access', 'mp' ),
		);

		/**
		 * Filter the theme permissions options list
		 *
		 * @since 3.0
		 * @access public
		 *
		 * @param array $options_permissions An array of options.
		 */
		$options_permissions = apply_filters( 'mp_admin_multisite/theme_permissions_options', $options_permissions );

		foreach ( $theme_list as $value => $theme ) {
			$metabox->add_field( 'select', array(
				'name'    => 'allowed_themes[' . $value . ']',
				'label'   => array( 'text' => $theme['name'] ),
				'desc'    => $theme['path'],
				'options' => $options_permissions,
			) );
		}
	}

	/**
	 * Add menu items to the network admin menu
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_menu_items() {
		add_submenu_page( 'settings.php', __( 'Store Network Settings', 'mp' ), __( 'Store Network', 'mp' ), 'manage_network_options', 'network-store-settings', array(
			&$this,
			'network_store_settings'
		) );
	}

	/**
	 * Displays the network settings form/metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function network_store_settings() {
		?>
		<div class="wrap mp-wrap">
			<div class="icon32"><img src="<?php echo mp_plugin_url( 'ui/images/settings.png' ); ?>"/></div>
			<h2 class="mp-settings-title"><?php _e( 'Store Network Settings', 'mp' ); ?></h2>

			<div class="clear"></div>
			<div class="mp-settings">
				<form id="mp-main-form" method="post" action="<?php echo add_query_arg( array() ); ?>">
					<?php
					/**
					 * Render WPMUDEV Metabox settings
					 *
					 * @since 3.0
					 */
					do_action( 'wpmudev_metabox/render_settings_metaboxes' );
					?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Pages for network cart (marketplace,marketplace/categories etc)
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_network_pages() {
		$metabox = new WPMUDEV_Metabox( array(
			'id'               => 'mp-settings-network-pages-slugs',
			'page_slugs'       => array( 'network-store-settings' ),
			'title'            => __( 'Global Marketplace Pages', 'mp' ),
			'site_option_name' => 'mp_network_settings',
			'order'            => 2
		) );

		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[network_store_page]',
			'label'       => array( 'text' => __( 'Marketplace', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Choose a Page', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[network_categories]',
			'label'       => array( 'text' => __( 'Product Categories', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Choose a Page', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
		$metabox->add_field( 'post_select', array(
			'name'        => 'pages[network_tags]',
			'label'       => array( 'text' => __( 'Product Tags', 'mp' ) ),
			'query'       => array( 'post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC' ),
			'placeholder' => __( 'Choose a Page', 'mp' ),
			'validation'  => array(
				'required' => true,
			),
		) );
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
			case 'pages[network_store_page]' :
				$type = 'network_store_page';
				break;

			case 'pages[network_categories]' :
				$type = 'network_categories';
				break;

			case 'pages[network_tags]' :
				$type = 'network_tags';
				break;
		}

		if ( isset( $type ) ) {
			if ( ( $post_id = mp_get_network_setting( "pages->$type" ) ) && get_post_status( $post_id ) !== false ) {
				return '<a target="_blank" class="button mp-edit-page-button" href="' . add_query_arg( array(
					'post'   => $post_id,
					'action' => 'edit',
				), get_admin_url( null, 'post.php' ) ) . '">' . __( 'Edit Page' ) . '</a>';
			} else {
				return '<a class="button mp-create-page-button" href="' . wp_nonce_url( get_admin_url( null, 'admin-ajax.php?action=mp_create_store_page&type=' . $type ), 'mp_create_store_page' ) . '">' . __( 'Create Page' ) . '</a>';
			}
		}

		return $html;
	}

	/**
	 * Print scripts for creating store page
	 *
	 * @since 3.0
	 * @access public
	 * @action wpmudev_field/print_scripts
	 */
	public function create_store_page_js( $field ) {
		if ( $field->args['original_name'] !== 'pages[network_store_page]' ) {
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
							$select.attr('data-select2-value', resp.data.select2_value).select2('val', resp.data.post_id).trigger('change');
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
	 * Catch deprecated functions
	 */
	public function __call( $method, $args ) {
		switch ( $method ) {
			case 'is_main_site' :
				_deprecated_function( $method, '3.0', 'mp_is_main_site' );

				return call_user_func_array( 'mp_is_main_site', $args );
				break;

			default :
				trigger_error( 'Error! MP_Admin_Multisite doesn\'t have a ' . $method . ' method.', E_USER_ERROR );
				break;
		}
	}

}

$GLOBALS['mp_wpmu'] = MP_Admin_Multisite::get_instance();
