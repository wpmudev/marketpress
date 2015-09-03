<?php

class MP_Admin {

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
			self::$_instance = new MP_Admin();
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
		$this->_init_dash_notices();
		add_action('init',array(&$this,'_includes'),1);

		//save orders screen options
		add_filter( 'set-screen-option', array( &$this, 'save_orders_screen_options' ), 10, 3 );
		//set custom post-updated messages
		add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );
		//enqueue styles and scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );

		add_action( 'admin_head', array( &$this, 'admin_head' ) );
		//add a notice for deprecated gateway
		if(get_option('mp_deprecated_gateway_notice_showed')!=1){
		add_action( 'admin_notices', array(&$this,'deprecated_gateway_notice') );
		add_action('admin_footer',array(&$this,'print_deprecated_notice_scripts'));
		add_action('wp_ajax_mp_dismissed_deprecated_message',array(&$this,'dismissed_deprecated_messag'));
		}
	}

	public function dismissed_deprecated_messag(){
		if(!current_user_can('manage_options')){
			return;
		}
		update_option('mp_deprecated_gateway_notice_showed',1);

	}

	public function deprecated_gateway_notice(){
	if(!current_user_can('manage_options')){
	return;
	}
		?>
		<div class="update-nag mp-deprecated-notice">
		<div class="mp-notice-text">
		<?php echo sprintf(__("The following payment gateways have been deprecated, Cubepoints, Bitpay, iDEAL, Skrill, Google Checkout. If you was using one of these gateways, please setup a new payment gateway <a href=\"%s\">here</a>.","marketpress"),admin_url('admin.php?page=store-settings-payments')) ?>
		</div>
		<a href="#" class="mp-dismissed-deprecated-notice"><i class="dashicons dashicons-no-alt"></i></a>
		</div>
		<?php
	}

	public function print_deprecated_notice_scripts(){
	if(!current_user_can('manage_options')){
	return;
	}
		?>
			<script type="text/javascript">
				jQuery(function($){
					$('.mp-dismissed-deprecated-notice').click(function(e){
					e.preventDefault();
					$.ajax({
						type:'POST',
						data:{
							action:'mp_dismissed_deprecated_message'
						},
						url:ajaxurl,
						success:function(){
							$('.mp-deprecated-notice').fadeOut(150)
						}
					})
					})
				})
			</script>
		<?php
	}

	function admin_head() {
		if ( 'mp_order' == get_post_type() ) {
			echo '<style type="text/css">
    .page-title-action {display:none;}
    </style>';
		}
	}

	/**
	 * Includes any necessary files
	 *
	 * @since 3.0
	 * @access public
	 */
	public function _includes() {
		require_once mp_plugin_dir( 'includes/admin/class-mp-orders-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-products-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-product-attributes-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-store-settings-admin.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-setup-wizard.php' );
		require_once mp_plugin_dir( 'includes/admin/class-mp-shortcode-builder.php' );
	}

	/**
	 * Initialize dash notices
	 *
	 * @since 3.0
	 * @access public
	 */
	protected function _init_dash_notices() {
		if ( MP_LITE ) {
			return;
		}

		//! TODO: update screen ids for dash notices
		global $wpmudev_notices;
		$wpmudev_notices[] = array(
			'id'		 => 144,
			'name'		 => 'MarketPress',
			'screens'	 => array(
				'edit-product',
				'edit-mp_product',
				'product',
				'mp_product',
				'edit-product_category',
				'edit-product_tag',
				'settings_page_marketpress-ms-network'
			)
		);

		require_once mp_plugin_dir( 'includes/admin/dash-notice/wpmudev-dash-notification.php' );
	}

	/**
	 * Adds the MarketPress help tab
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_help_tab() {
		get_current_screen()->add_help_tab( array(
			'id'		 => 'marketpress-help',
			'title'		 => __( 'MarketPress Instructions', 'mp' ),
			'content'	 => '<iframe src="//premium.wpmudev.org/wdp-un.php?action=help&id=144" width="100%" height="600px"></iframe>'
		) );
	}

	/**
	 * Displays the export orders form
	 *
	 * @since 3.0
	 * @access public
	 */
	public function export_orders_form() {
		global $wpdb;

		if ( !isset( $_GET[ 'post_status' ] ) || $_GET[ 'post_status' ] != 'trash' ) {
			?>
			<div class="icon32"><img src="<?php echo mp_plugin_url( 'ui/images/download.png' ); ?>" /></div>
			<h2><?php _e( 'Export Orders', 'mp' ); ?></h2>
			<?php if ( defined( 'MP_LITE' ) ) { ?>
				<a class="mp-pro-update" href="http://premium.wpmudev.org/project/e-commerce/" title="<?php _e( 'Upgrade Now', 'mp' ); ?> &raquo;"><?php _e( 'Upgrade to enable CSV order exports &raquo;', 'mp' ); ?></a><br />
			<?php } ?>
			<form action="<?php echo admin_url( 'admin-ajax.php?action=mp-orders-export' ); ?>" method="post">
				<?php
				$months = $wpdb->get_results( $wpdb->prepare( "
			SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
			FROM $wpdb->posts
			WHERE post_type = %s
			ORDER BY post_date DESC
		", 'mp_order' ) );

				$month_count = count( $months );

				if ( !$month_count || ( 1 == $month_count && 0 == $months[ 0 ]->month ) )
					{return;}

				$m = isset( $_GET[ 'm' ] ) ? (int) $_GET[ 'm' ] : 0;
				?>
				<select name='m'>
					<option<?php selected( $m, 0 ); ?> value='0'><?php _e( 'Show all dates' ); ?></option>
					<?php
					foreach ( $months as $arc_row ) {
						if ( 0 == $arc_row->year )
							{continue;}

						$month	 = zeroise( $arc_row->month, 2 );
						$year	 = $arc_row->year;

						printf( "<option %s value='%s'>%s</option>\n", selected( $m, $year . $month, false ), esc_attr( $arc_row->year . $month ), $wp_locale->get_month( $month ) . " $year"
						);
					}

					$status = isset( $_GET[ 'post_status' ] ) ? $_GET[ 'post_status' ] : 'all';
					?>
				</select>
				<select name="order_status">
					<option<?php selected( $status, 'all' ); ?> value="all" selected="selected"><?php _e( 'All Statuses', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_received' ); ?> value="order_received"><?php _e( 'Received', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_paid' ); ?> value="order_paid"><?php _e( 'Paid', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_shipped' ); ?> value="order_shipped"><?php _e( 'Shipped', 'mp' ); ?></option>
					<option<?php selected( $status, 'order_closed' ); ?> value="order_closed"><?php _e( 'Closed', 'mp' ); ?></option>
				</select>
				<input type="submit" value="<?php _e( 'Download &raquo;', 'mp' ); ?>" name="export_orders" class="button-secondary"<?php echo defined( 'MP_LITE' ) ? ' disabled="disabled"' : ''; ?> />
			</form>


			<br class="clear">
		<?php } ?>
		</div>
		<?php
	}

	/**
	 * Enqueue styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_styles_scripts() {
		global $pagenow, $post_type, $mp;

		//wp_enqueue_script( 'mp-chosen', mp_plugin_url( 'includes/admin/ui/chosen/chosen.jquery.min.js' ), array( 'jquery' ), MP_VERSION );

		if ( !empty( $pagenow ) && ('post-new.php' === $pagenow || 'post.php' === $pagenow ) ) {
			if ( $post_type == MP_Product::get_post_type() ) {
				wp_enqueue_style( 'mp-font-awesome', mp_plugin_url( 'includes/admin/ui/css/font-awesome.min.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'mp-product-admin', mp_plugin_url( 'includes/admin/ui/css/admin-product.css' ), array( 'mp-font-awesome' ), MP_VERSION );
				wp_enqueue_script( 'mp-repeatable-fields', mp_plugin_url( 'includes/admin/ui/js/repeatable-fields.js' ), array( 'jquery' ), MP_VERSION );

				wp_enqueue_style( 'jquery-smoothness', mp_plugin_url( 'includes/admin/ui/smoothness/jquery-ui-1.10.4.custom.css' ), '', MP_VERSION );
				wp_enqueue_script( 'mp-product-admin', mp_plugin_url( 'includes/admin/ui/js/admin-product.js' ), array( 'jquery', 'mp-repeatable-fields', 'jquery-ui-datepicker' ), MP_VERSION );

				$ajax_nonce = wp_create_nonce( "mp-ajax-nonce" );

				wp_localize_script( 'mp-product-admin', 'mp_product_admin_i18n', array(
					'ajaxurl'								 => admin_url( 'admin-ajax.php' ),
					'creating_vatiations_message'			 => __( 'Creating variations, please wait...', 'mp' ),
					'ajax_nonce'							 => $ajax_nonce,
					'bulk_update_prices_multiple_title'		 => sprintf( __( 'Update prices for %s product variants', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'bulk_update_prices_single_title'		 => sprintf( __( 'Update price for %s product variant', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'bulk_update_inventory_multiple_title'	 => sprintf( __( 'Update inventory for %s product variants', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'bulk_update_inventory_single_title'	 => sprintf( __( 'Update inventory for %s product variant', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'bulk_delete_multiple_title'			 => sprintf( __( 'Delete %s product variants', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'bulk_delete_single_title'				 => sprintf( __( 'Delete %s product variant', 'mp' ), '<span class="mp_variants_selected"></span>' ),
					'date_format'							 => WPMUDEV_Field_Datepicker::format_date_for_jquery( get_option( 'date_format' ) ),
					'message_valid_number_required'			 => __( 'Valid number is required', 'mp' ),
					'message_input_required'				 => __( 'Input is required', 'mp' ),
					'saving_message'						 => __( 'Please wait...saving in progress...', 'mp' ),
					'placeholder_image'						 => $mp->plugin_url( '/includes/admin/ui/images/img-placeholder.jpg' )
				) );

				//jquery textext
				wp_enqueue_script( 'textext.core', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.core.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.ajax', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.ajax.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.arrow', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.arrow.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.autocomplete', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.autocomplete.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.clear', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.clear.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.filter', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.filter.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.focus', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.focus.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.prompt', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.prompt.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.suggestions', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.suggestions.js' ), array( 'jquery' ), MP_VERSION );
				wp_enqueue_script( 'textext.plugin.tags', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/js/textext.plugin.tags.js' ), array( 'jquery' ), MP_VERSION );

				wp_enqueue_style( 'textext.core', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.core.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.arrow', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.arrow.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.autocomplete', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.autocomplete.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.clear', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.clear.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.focus', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.focus.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.prompt', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.prompt.css' ), array(), MP_VERSION );
				wp_enqueue_style( 'textext.plugin.tags', mp_plugin_url( 'includes/admin/ui/js/jquery-textext/src/css/textext.plugin.tags.css' ), array(), MP_VERSION );
			}


			if ( $post_type == MP_Product::get_variations_post_type() ) {
				wp_enqueue_style( 'mp-product-variation-admin', mp_plugin_url( 'includes/admin/ui/css/admin-product-variation.css' ), false, MP_VERSION );
				wp_enqueue_script( 'mp-product-variation-admin', mp_plugin_url( 'includes/admin/ui/js/admin-product-variation.js' ), array( 'jquery' ), MP_VERSION );
			}
		}

		$quick_setup = mp_get_get_value( 'quick_setup_step' );
		if ( isset( $quick_setup ) ) {
			wp_enqueue_style( 'mp-quick-setup', mp_plugin_url( 'includes/admin/ui/css/quick-setup.css' ), array(), MP_VERSION );
			wp_enqueue_script( 'mp-quick-setup', mp_plugin_url( 'includes/admin/ui/js/quick-setup.js' ), array( 'jquery' ), MP_VERSION );
			wp_enqueue_script( 'jquery-ui-tabs' );
		}

		wp_enqueue_style( 'mp-admin', mp_plugin_url( 'includes/admin/ui/css/admin.css' ), array(), MP_VERSION );
	}

	/**
	 * Modifies the post-updated messages for the mp_order, product and mp_coupon post types
	 *
	 * @since 3.0
	 * @access public
	 * @filter post_updated_messages
	 *
*@param array $messages
	 *
*@return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$post_type = get_post_type( $post_ID );

		if ( $post_type != 'mp_order' && $post_type != MP_Product::get_post_type() && $post_type != 'mp_coupon' ) {
			return $messages;
		}

		$obj		 = get_post_type_object( $post_type );
		$singular	 = $obj->labels->singular_name;

		$messages[ $post_type ] = array(
			0	 => '', // Unused. Messages start at index 1.
			1	 => sprintf( __( $singular . ' updated. <a href="%s">View ' . strtolower( $singular ) . '</a>' ), esc_url( get_permalink( $post_ID ) ) ),
			2	 => __( 'Custom field updated.' ),
			3	 => __( 'Custom field deleted.' ),
			4	 => __( $singular . ' updated.' ),
			5	 => isset( $_GET[ 'revision' ] ) ? sprintf( __( $singular . ' restored to revision from %s' ), wp_post_revision_title( (int) $_GET[ 'revision' ], false ) ) : false,
			6	 => sprintf( __( $singular . ' published. <a href="%s">View ' . strtolower( $singular ) . '</a>' ), esc_url( get_permalink( $post_ID ) ) ),
			7	 => __( 'Page saved.' ),
			8	 => sprintf( __( $singular . ' submitted. <a target="_blank" href="%s">Preview ' . strtolower( $singular ) . '</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9	 => sprintf( __( $singular . ' scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview ' . strtolower( $singular ) . '</a>' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
			10	 => sprintf( __( $singular . ' draft updated. <a target="_blank" href="%s">Preview ' . strtolower( $singular ) . '</a>' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}



}

MP_Admin::get_instance();
