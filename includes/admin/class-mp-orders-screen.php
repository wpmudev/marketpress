<?php

class MP_Orders_Screen {
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
		if ( is_null(self::$_instance) ) {
			self::$_instance = new MP_Orders_Screen();
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
		if ( mp_doing_ajax() ) {
			//change order status
			add_action('wp_ajax_mp_change_order_status', array(&$this, 'change_order_status'));
			return;
		}
		
		//add menu items
		add_action('admin_menu', array(&$this, 'add_menu_items'), 9);
		//change the "enter title here" text
		add_filter('enter_title_here', array(&$this, 'enter_title_here'));
		//modify coupon list table columns/data
		add_filter('manage_mp_order_posts_columns', array(&$this, 'orders_column_headers'));
		add_action('manage_mp_order_posts_custom_column', array(&$this, 'orders_column_data'), 10, 2);
		add_filter('manage_edit-mp_order_sortable_columns', array(&$this, 'orders_sortable_columns'));
		add_action('pre_get_posts', array(&$this, 'modify_query'));
		//custom css/javascript
		add_action('admin_print_styles', array(&$this, 'print_css'));
		add_action('admin_print_footer_scripts', array(&$this, 'print_js'));
		//process custom bulk actions
		add_action('load-edit.php', array(&$this, 'process_bulk_actions'));
		//bulk update admin notice
		add_action('admin_notices', array(&$this, 'admin_notices'));
	}
	
	/**
	 * Displays the bulk update notice
	 *
	 * @since 3.0
	 * @access public
	 */
	public function admin_notices() {
		if ( get_current_screen()->id == 'edit-mp_order' ) {
			if ( mp_get_get_value('mp_order_status_updated') ) {
				echo '<div class="updated"><p>' . __('Order statuses successfully updated.', 'mp') . '</p></div>';
			}
			
			if ( $order_id = mp_get_get_value('mp_order_status_updated_single') ) {
				echo '<div class="updated"><p>' . sprintf(__('The order status for order ID <strong>%1$s</strong> was updated successfully.', 'mp'), $order_id) . '</p></div>';
			}
		}
	}
	
	/**
	 * Processes bulk actions
	 *
	 * @since 3.0
	 * @access public
	 */
	public function process_bulk_actions() {
		if ( get_current_screen()->id != 'edit-mp_order' ) {
			return;
		}
		
		$wp_list_table = _get_list_table('WP_Posts_List_Table');
		$action = $wp_list_table->current_action();
		$posts = mp_get_get_value('post', array());
		$valid_actions = array('order_received', 'order_paid', 'order_shipped', 'order_closed');
		$pagenum = $wp_list_table->get_pagenum();		
		
		if ( empty($action) ) {
			//bail - no action specified
			return;
		}
		
		check_admin_referer('bulk-posts');
		
		if ( ! in_array($action, $valid_actions) ) {
			wp_die(__('An invalid bulk action was requested. Please go back and try again.', 'mp'));
		}
		
		foreach ( $posts as $post_id ) {
			wp_update_post(array(
				'ID' => $post_id,
				'post_status' => $action
			));
		}
	
		$sendback = remove_query_arg(array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view', 'mp_order_status_updated_single'), wp_get_referer());
		
		if ( ! $sendback ) {
			$sendback = admin_url('edit.php?post_type=mp_order');
		}

		$sendback = add_query_arg(array('paged' => $pagenum, 'mp_order_status_updated' => 1), $sendback);
		
		wp_redirect($sendback);
		exit;
	}
	
	/**
	 * Changes the given order's status
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_ajax_mp_change_order_status
	 */
	public function change_order_status() {
		$post_id = mp_get_get_value('post_id');
		$order_id = mp_get_get_value('order_id');
		$order_status = mp_get_get_value('order_status');
		$msg = sprintf(__('The order status could not be updated due to unexpected error. Please try again.', 'mp'), $order_id);
		
		if ( ! check_ajax_referer('mp-change-order-status', '_wpnonce', false) || false === $order_id || false === $order_status ) {
			wp_die($msg);
		}
		
		$order_status_old = get_post_status($post_id);
		$result = wp_update_post(array(
			'ID' => $post_id,
			'post_status' => $order_status,
		), true);
		
		if ( is_wp_error($result) ) {
			wp_die($msg);
		} else {
			$sendback = remove_query_arg('mp_order_status_updated', wp_get_referer());
			
			if ( empty($sendback) ) {
				$sendback = admin_url('edit.php?post_type=mp_order');
			}
			
			wp_redirect(add_query_arg('mp_order_status_updated_single', $order_id, $sendback));
		}
		
		exit;
	}
	
	/**
	 * Modifies the query object for orders
	 *
	 * @since 3.0
	 * @access public
	 * @action pre_get_posts
	 * @param object $query
	 */
	public function modify_query( $query ) {
		if ( $query->get('post_type') != 'mp_order' || get_current_screen()->id != 'edit-mp_order' ) {
			//bail
			return;
		}
		
		//set post status
		$post_status =  mp_get_get_value('post_status', array('order_received', 'order_paid', 'order_shipped'));
		$query->set('post_status', $post_status);
				
		switch ( get_query_var('orderby') ) {
			case 'product_coupon_discount' :
				$query->set('orderby', 'meta_value_num');
				$query->set('meta_key', 'discount_amount'); 
			break;
			
			case 'product_coupon_used' :
				$query->set('orderby', 'meta_value_num');
				$query->set('meta_key', 'times_used'); 
			break;
		}
	}
	
	/**
	 * Defines the product coupon sortable columns
	 *
	 * @since 3.0
	 * @access public
	 * @action manage_edit-product_coupon_sortable_columns
	 * @param array $columns
	 * @return array
	 */
	public function orders_sortable_columns( $columns ) {
		return array_merge($columns, array(
			'discount' => 'product_coupon_discount',
		 	'used' => 'product_coupon_used',
		));
	}
	
	/**
	 * Prints applicable CSS
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_print_styles
	 */
	public function print_css() {
		if ( get_current_screen()->post_type != 'mp_order' ) return;
		?>
<style type="text/css">
	.wrap .add-new-h2 {
		display: none;
	}
	
	div.mp_order_status {
		position: relative;
	}
	
	div.mp_order_status img {
		display: none;
		margin: 2px 0;
	}
	
	div.mp_order_status .mp_order_status_menu {
		position: absolute;
			top: -10px;
			left: 40px;
			z-index: 10;
		background: #fff;
		border: 1px solid #ccc;
			-webkit-box-shadow: 0 0 5px rgba(0, 0, 0, .2);
		box-shadow: 0 0 5px rgba(0, 0, 0, .2);
		display: none;
		margin: 0;
		padding: 10px;
		white-space: nowrap;
		text-align: left;
	}
	
	div.mp_order_status .mp_order_status_menu a,
	div.mp_order_status .mp_order_status_menu span {
		display: block;
		line-height: 1;
		margin: 0;
		padding: 5px 8px;
	}
	
	div.mp_order_status .mp_order_status_menu span {
		color: #999;
	}
	
	div.mp_order_status .mp_order_status_menu li {
		position: relative;
		display: inline-block;
		margin: 0;
		padding: 0;
	}
	
	div.mp_order_status .mp_order_status_menu li.current span {
		color: #999;
	}
	
	div.mp_order_status .mp_order_status_menu li:before {
		position: absolute;
			top: 1px;
			left: -4px;
		color: #ccc;
		content: "|";
	}
	
	div.mp_order_status .mp_order_status_menu li:first-child:before,
	div.mp_order_status .mp_order_status_menu li:first-child + li:before {
		display: none;
	}

	div.mp_order_status:hover .mp_order_status_menu {
		display: block;
	}
		
	div.mp_order_status:before {
		display: inline-block;
		font: 400 20px/1 dashicons;
		-webkit-font-smoothing: antialiased;
	}
	
	div.mp_order_status.loading img {
		display: inline-block;
	}
	
	div.mp_order_status.loading:before {
		display: none;
	}
	
	div.mp_order_status.order_received:before {
		content: "\f154";
	}
	
	div.mp_order_status.order_paid:before {
		content: "\f459";
	}
	
	div.mp_order_status.order_shipped:before,
	div.mp_order_status.order_closed:before {
		content: "\f155";
	}
	
	.widefat .column-mp_orders_status,
	.widefat .column-mp_orders_items {
		text-align: center;
		width: 50px;
	}
	
	.widefat td.mp_orders_status {
		overflow: visible;
	}
</style>		
		<?php
	}
	
	/**
	 * Prints applicable javascript
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_print_footer_scripts
	 */
	public function print_js() {
		if ( get_current_screen()->post_type != 'mp_order' ) return;
		?>
<script type="text/javascript">
	jQuery(document).ready(function($){
		var setActiveAdminMenu = function(){
			$('#menu-posts-product, #menu-posts-product > a')
				.addClass('wp-menu-open wp-has-current-submenu')
				.find('a[href="edit.php?post_type=mp_order"]').parent().addClass('current');
		};
		
		var modifyBulkActionsInput = function(){
			var $select = $('select[name="action"],select[name="action2"]'),
					options = {
						"-1" : "<?php _e('Change Status', 'mp'); ?>",
						"order_received" : "<?php _e('Received', 'mp'); ?>",
						"order_paid" : "<?php _e('Paid', 'mp'); ?>",
						"order_shipped" : "<?php _e('Shipped', 'mp'); ?>",
						"order_closed" : "<?php _e('Closed', 'mp'); ?>",
					};
					
			$select.find('option').remove();
			
			$.each(options, function(key, value){
				$select.append('<option value="' + key + '">' + value + '</option>');
			});
		};
		
		setActiveAdminMenu();
		modifyBulkActionsInput();
	});
</script>
		<?php
	}
	
	/**
	 * Changes the "enter title here" text when editing/adding coupons
	 *
	 * @since 3.0
	 * @access public
	 * @action enter_title_here
	 * @param string $title The default title
	 * @return string
	 */
	public function enter_title_here( $title ) {
		if ( get_current_screen()->post_type != 'mp_order' ) {
			return $title;
		}
		
		return __('Enter coupon code here', 'mp');
	}
	
	/**
	 * Adds menu items to the admin menu
	 *
	 * @since 3.0
	 * @access public
	 * @action admin_menu
	 */
	public function add_menu_items() {
		$order_cap = apply_filters('mp_orders_cap', 'edit_others_orders');
	 
		if ( current_user_can($order_cap) && ! mp_get_setting('disable_cart') ) {
			$num_posts = wp_count_posts('mp_order'); //get order count
			$count = $num_posts->order_received + $num_posts->order_paid;
			
			if ( $count > 0 ) {
				$count_output = '&nbsp;<span class="update-plugins"><span class="updates-count count-' . $count . '">' . $count . '</span></span>';
			} else {
				$count_output = '';
			}
				
			$orders_page = add_submenu_page('edit.php?post_type=product', __('Manage Orders', 'mp'), __('Manage Orders', 'mp') . $count_output, $order_cap, 'edit.php?post_type=mp_order');
		}
	}
	
	/**
	 * Defines the column headers for the product coupon list table
	 *
	 * @since 3.0
	 * @access public
	 * @action manage_mp_order_posts_columns
	 * @param array $columns The default columns as specified by WP
	 * @return array
	 */
	public function orders_column_headers( $columns ) {
		return array(
			'cb'									=> '<input type="checkbox" />',
			'mp_orders_status' 		=> __('Status', 'mp'),
			'mp_orders_id' 				=> __('Order ID', 'mp'),
			'mp_orders_date' 			=> __('Order Date', 'mp'),
			'mp_orders_name' 			=> __('From', 'mp'),
			'mp_orders_items' 		=> __('Items', 'mp'),
			'mp_orders_shipping' 	=> __('Shipping', 'mp'),
			'mp_orders_tax' 			=> __('Tax', 'mp'),
			'mp_orders_discount' 	=> __('Discount', 'mp'),
			'mp_orders_total' 		=> __('Total', 'mp'),
		);		
	}
	
	/**
	 * Defines the list table data for product coupons
	 *
	 * @since 3.0
	 * @access public
	 * @action manage_mp_order_posts_custom_column
	 * @uses $post
	 * @param string $column The current column name
	 * @param int $post_id The current post id
	 */
	public function orders_column_data( $column, $post_id ) {
		global $post;
		
		switch ( $column ) {
			//! Order Status
			case 'mp_orders_status' :
				switch ( $post->post_status ) {
		    	case 'order_received' :
		    		$text = __('Received', 'mp');
		    	break;
		    	
		    	case 'order_paid' :
		    		$text = __('Paid', 'mp');
		    	break;
		    	
		    	case 'order_shipped' :
		    		$text = __('Shipped', 'mp');
		    	break;
		    	
		    	case 'order_closed' :
		    		$text = __('Closed', 'mp');
		    	break;
		    	
		    	case 'trash' :
		    		$text = __('Trashed', 'mp');
		    	break;
				}
				
    		$actions = array(
    			'order_received' => __('Received', 'mp'),
    			'order_paid' => __('Paid', 'mp'),
    			'order_shipped' => __('Shipped', 'mp'),
    			'order_closed' => __('Closed', 'mp'),
    		);
				
				echo '<div class="mp_order_status ' . get_post_status() . '">';
				
				if ( isset($actions) ) {
					echo '<ul class="mp_order_status_menu">';
					echo '<li class="item">' . __('Flag as:', 'mp') . '</li>';
					
					foreach ( $actions as $action => $label ) {
						if ( $action == $post->post_status ) {
							echo '<li class="item current"><span>' . $label . '</span></li>';
						} else {
							echo '<li class="item"><a href="' . wp_nonce_url(add_query_arg(array('action' => 'mp_change_order_status', 'order_status' => $action, 'order_id' => get_the_title(), 'post_id' => $post_id), admin_url('admin-ajax.php')), 'mp-change-order-status') . '">' . $label . '</a></li>';							
						}
					}
					
					echo '</ul>';
				}
				
					echo '<img src="' . mp_plugin_url('ui/images/ajax-loader.gif') . '" alt="" />';
				echo '</div>';
			break;
			
			//! Order ID
			case 'mp_orders_id' :
				$title = _draft_or_post_title($post_id);
				echo '<strong><a class="row-title" href="' . get_edit_post_link($post_id) . '" title="' . esc_attr(sprintf(__('View order &#8220;%s&#8221;', 'mp'), $title)) . '">' . $title . '</a></strong>';
			break;
			
			//! Order Date
			case 'mp_orders_date' :
				echo get_the_time(get_option('date_format'));
			break;
			
			//! Order From
			case 'mp_orders_name' :
				$shipping_info = get_post_meta($post_id, 'mp_shipping_info', true);
				echo '<a href="mailto:' . urlencode($shipping_info['name']) . ' &lt;' . esc_attr($shipping_info['email']) . '&gt;?subject=' . urlencode(sprintf(__('Regarding Your Order (%s)', 'mp'), get_the_title($post_id))) . '">' . esc_html($shipping_info['name']) . '</a>';		
			break;
			
			//! Order Items
			case 'mp_orders_items' :
				$items = get_post_meta($post_id, 'mp_order_items', true);
				echo number_format_i18n($items);
			break;
			
			//! Order Shipping
			case 'mp_orders_shipping' :
				$shipping = get_post_meta($post_id, 'mp_shipping_total', true);
				echo mp_format_currency('', $shipping);
			break;
			
			//! Order Tax
			case 'mp_orders_tax' :
				$tax = get_post_meta($post_id, 'mp_tax_total', true);
				echo mp_format_currency('', $tax);
			break;
			
			//! Order Discount
			case 'mp_orders_discount' :
				$discount = get_post_meta($post_id, 'mp_discount_info', true);
				if ( $discount ) {
					echo $discount['discount'] . ' (' . strtoupper($discount['code']) . ')';
				} else {
					_e('N/A', 'mp');
				}
			break;
			
			//! Order Total
			case 'mp_orders_total' :
				$total = get_post_meta($post_id, 'mp_order_total', true);
				echo mp_format_currency('', $total);
			break;
		}
	}
}

MP_Orders_Screen::get_instance();