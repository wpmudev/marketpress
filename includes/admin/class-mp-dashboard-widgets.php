<?php

class MP_Dashboard_Widgets {

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
			self::$_instance = new MP_Dashboard_Widgets();
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
		//enqueue styles and scripts
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_styles_scripts' ) );
		add_action( 'wp_dashboard_setup', array( &$this, 'add_mp_dashboard_widgets' ) );
	}

	/**
	 * Enqueue styles and scripts
	 *
	 * @since 3.0
	 * @access public
	 */
	public function enqueue_styles_scripts() {
		global $pagenow, $mp;

		if ( !empty( $pagenow ) && ('index.php' === $pagenow) ) {
			wp_enqueue_style( 'mp-product-variation-admin', mp_plugin_url( 'includes/admin/ui/css/mp-dashboard-widgets.css' ), false, MP_VERSION );
			wp_enqueue_script( 'mp-product-variation-admin', mp_plugin_url( 'includes/admin/ui/js/mp-dashboard-widgets.js' ), array( 'jquery' ), MP_VERSION );
		}
	}

	public function add_mp_dashboard_widgets() {
		if ( !current_user_can( apply_filters( 'mp_can_view_dashboard_widgets_capability_needed', 'manage_options' ) ) ) {
			return;
		}
		wp_add_dashboard_widget( 'mp_store_report', __( 'Store Reports', 'mp' ), array( &$this, 'mp_store_report_display' ) );
		wp_add_dashboard_widget( 'mp_store_management', __( 'Store Management', 'mp' ), array( &$this, 'mp_store_management_display' ) );
	}

	public function mp_store_report_display() {
		global $wpdb;

		$today_date			 = date( "Y-m-d", time() );
		$yesterday_date		 = date( "Y-m-d", time() - 60 * 60 * 24 );
		$seven_days_date	 = date( "Y-m-d", time() - 60 * 60 * 24 * 7 );
		$thirty_days_date	 = date( "Y-m-d", time() - 60 * 60 * 24 * 30 );

		$day_current	 = date( 'd' );
		$month_current	 = date( 'm' );
		$year_current	 = date( 'Y' );

		$today					 = $wpdb->get_row( "SELECT count(p.ID) as count, sum(m.meta_value) as total, avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND p.post_date LIKE '" . $year_current . "-" . $month_current . "-" . $day_current . "%' AND p.post_status != 'trash'" );
		$yesterday				 = $wpdb->get_row( "SELECT count(p.ID) as count, sum(m.meta_value) as total, avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND p.post_date LIKE '" . $yesterday_date . "%' AND p.post_status != 'trash'" );
		$seven_days				 = $wpdb->get_row( "SELECT count(p.ID) as count, sum(m.meta_value) as total, avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND p.post_date >= '" . $seven_days_date . "' AND p.post_status != 'trash'" );
		$thirty_days			 = $wpdb->get_row( "SELECT count(p.ID) as count, sum(m.meta_value) as total, avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND p.post_date >= '" . $thirty_days_date . "' AND p.post_status != 'trash'" );
		?>
		<p><span><?php _e( "Welcome back! Here's a quick summary of your store's performance.", 'mp' ); ?></span></p>
		<div class="main store-report">
			<span class="mp-dashboard-section-title"><?php _e( 'Sales', 'mp' ); ?></span>
			<div class="mp-dashboard-stats-wrapper">
				<div class="mp-dashboard-square mp-dashboard-left">
					<span class="mp-dashboard-square-title"><?php _e( 'Today', 'mp' ); ?></span>
					<span class="mp-dashboard-square-amount"><?php echo mp_format_currency( '', $today->total ); ?></span>
					<span class="mp-dashboard-square-footer"><?php echo $today->count . __( ' Orders', 'mp' ); ?></span>
				</div>
				<div class="mp-dashboard-square mp-dashboard-right">
					<span class="mp-dashboard-square-title"><?php _e( 'Yesterday', 'mp' ); ?></span>
					<span class="mp-dashboard-square-amount"><?php echo mp_format_currency( '', $yesterday->total ); ?></span>
					<span class="mp-dashboard-square-footer"><?php echo $yesterday->count . __( ' Orders', 'mp' ); ?></span>
				</div>
				<div class="mp-dashboard-square mp-dashboard-left">
					<span class="mp-dashboard-square-title"><?php _e( 'Last 7 Days', 'mp' ); ?></span>
					<span class="mp-dashboard-square-amount"><?php echo mp_format_currency( '', $seven_days->total ); ?></span>
					<span class="mp-dashboard-square-footer"><?php echo $seven_days->count . __( ' Orders', 'mp' ); ?></span>
				</div>
				<div class="mp-dashboard-square mp-dashboard-right">
					<span class="mp-dashboard-square-title"><?php _e( 'Last 30 Days', 'mp' ); ?></span>
					<span class="mp-dashboard-square-amount"><?php echo mp_format_currency( '', $thirty_days->total ); ?></span>
					<span class="mp-dashboard-square-footer"><?php echo $thirty_days->count . __( ' Orders', 'mp' ); ?></span>
				</div>
			</div>

			<?php
			$count_posts			 = wp_count_posts( 'mp_order' );
			$inventory_threshhold	 = mp_get_setting( 'inventory_threshhold' );

			$custom_query = new WP_Query( array(
				'post_type'		 => array( MP_Product::get_post_type(), 'mp_product_variation' ),
				'post_status'	 => 'publish',
				'posts_per_page' => -1,
				'meta_query'	 => array(
					'relation' => 'AND',
					array(
						'key'		 => 'inventory_tracking',
						'value'		 => '1',
						'compare'	 => '=',
						'type'		 => 'NUMERIC',
					),
				),
			) );

			$received_orders	 = $count_posts->order_received;
			$paid_orders		 = $count_posts->order_paid;
			$low_stock_products	 = $custom_query->found_posts;
			?>
			<span class="mp-dashboard-section-title"><?php _e( 'Stock & Orders', 'mp' ); ?></span>

			<div class="mp-dashboard-section-stock-orders">
				<span class="mp-dashboard-stock-orders-title"><?php printf( _n( '%s order', '%s orders', $received_orders, 'mp' ), $received_orders ); ?></span>
				<span class="mp-dashboard-stock-orders-subtitle"><?php _e( 'received', 'mp' ); ?></span>
			</div>
			<div class="mp-dashboard-section-stock-orders">
				<span class="mp-dashboard-stock-orders-title"><?php printf( _n( '%s order', '%s orders', $paid_orders, 'mp' ), $paid_orders ); ?></span>
				<span class="mp-dashboard-stock-orders-subtitle"><?php _e( 'paid', 'mp' ); ?></span>
			</div>
			<div class="mp-dashboard-section-stock-orders">
				<span class="mp-dashboard-stock-orders-title"><?php printf( _n( '%s product', '%s products', $low_stock_products, 'mp' ), $low_stock_products ); ?></span>
				<span class="mp-dashboard-stock-orders-subtitle"><?php _e( 'low in stock', 'mp' ); ?></span>
			</div>
		</div>
		<br clear="both" />
		<?php
	}

	public function mp_store_management_display() {
		?>
		<p><span><?php _e( "Here's some quick links to manage your store and products.", 'mp' ); ?></span></p>
		<div class="main store-management">
			<ul class="store-management-left">
				<li><span><?php _e( 'Manage', 'mp' ); ?></span></li>
				<li><a href="<?php echo admin_url( 'edit.php?post_type=mp_order' ); ?>"><?php _e( 'Orders', 'mp' ); ?></a></li>
				<?php
				if ( MP_Addons::get_instance()->is_addon_enabled( 'MP_Coupons_Addon' ) ) {
					?>
					<li><a href="<?php echo admin_url( 'edit.php?post_type=mp_coupon' ); ?>"><?php _e( 'Coupons', 'mp' ); ?></a></li>
				<?php } ?>
				<li><a href="<?php echo admin_url( 'edit.php?post_type=' . MP_Product::get_post_type() ); ?>"><?php _e( 'Products', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'edit-tags.php?taxonomy=product_category&post_type=' . MP_Product::get_post_type() ); ?>"><?php _e( 'Categories', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'edit-tags.php?taxonomy=product_tag&post_type=' . MP_Product::get_post_type() ); ?>"><?php _e( 'Tags', 'mp' ); ?></a></li>
			</ul>
			<ul class="store-management-right">
				<li><span><?php _e( 'Configure', 'mp' ); ?></span></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=store-settings-presentation' ); ?>"><?php _e( 'Presentation', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=store-settings-notifications' ); ?>"><?php _e( 'Email Notifications', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=store-settings-shipping' ); ?>"><?php _e( 'Shipping Rates', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=store-settings-payments' ); ?>"><?php _e( 'Payment Gateways', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=store-settings-capabilities' ); ?>"><?php _e( 'User Capabilities', 'mp' ); ?></a></li>
				<li><a href="<?php echo admin_url( 'admin.php?page=store-settings-addons' ); ?>"><?php _e( 'Add-ons', 'mp' ); ?></a></li>
			</ul>
		</div>
		<br clear="both" />
		<?php
	}

}

MP_Dashboard_Widgets::get_instance();
