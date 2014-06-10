<?php
//load single order view if id is set
if ( isset($_GET['order_id']) ) {
	include_once mp_plugin_dir('admin-pages/single-order.php');
	return;
}

//force post type
global $wpdb, $post_type, $wp_query, $wp_locale, $current_screen;
$post_type = 'mp_order';
$_GET['post_type'] = $post_type;

$post_type_object = get_post_type_object($post_type);

if ( !current_user_can($post_type_object->cap->edit_posts) )
wp_die(__('Cheatin&#8217; uh?'));

$pagenum = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
if ( empty($pagenum) )
$pagenum = 1;
$per_page = 'edit_' . $post_type . '_per_page';
$per_page = (int) get_user_option( $per_page );
if ( empty( $per_page ) || $per_page < 1 )
$per_page = 20;
// @todo filter based on type
$per_page = apply_filters( 'edit_' . $post_type . '_per_page', $per_page );

// Handle bulk actions
if ( isset($_GET['doaction']) || isset($_GET['doaction2']) || isset($_GET['bulk_edit']) || isset($_GET['action']) 
|| (isset($_GET['delete_all'])) || (isset($_GET['delete_all2'])) ) {
check_admin_referer('update-order-status');
$sendback = remove_query_arg( array('received', 'paid', 'shipped', 'closed', 'trash', 'delete', 'ids', 'delete_all', 'delete_all2'), wp_get_referer() );

if ( ( $_GET['action'] != -1 || $_GET['action2'] != -1 ) && ( isset($_GET['post']) || isset($_GET['ids']) ) ) {
	$post_ids = isset($_GET['post']) ? array_map( 'intval', (array) $_GET['post'] ) : explode(',', $_GET['ids']);
	$doaction = ($_GET['action'] != -1) ? $_GET['action'] : $_GET['action2'];
} else if ( isset( $_GET['delete_all'] ) || isset( $_GET['delete_all2'] ) )
$doaction = 'delete_all';

switch ( $doaction ) {
	case 'received':
		$received = 0;
		foreach( (array) $post_ids as $post_id ) {
			$this->update_order_status($post_id, 'received');
			$received++;
		}
		$msg = sprintf( _n( '%s order marked as Received.', '%s orders marked as Received.', $received, 'mp' ), number_format_i18n( $received ) );
		break;
	case 'paid':
		$paid = 0;
		foreach( (array) $post_ids as $post_id ) {
			$this->update_order_status($post_id, 'paid');
			$paid++;
		}
		$msg = sprintf( _n( '%s order marked as Paid.', '%s orders marked as Paid.', $paid, 'mp' ), number_format_i18n( $paid ) );
		break;
	case 'shipped':
		$shipped = 0;
		foreach( (array) $post_ids as $post_id ) {
			$this->update_order_status($post_id, 'shipped');
			$shipped++;
		}
		$msg = sprintf( _n( '%s order marked as Shipped.', '%s orders marked as Shipped.', $shipped, 'mp' ), number_format_i18n( $shipped ) );
		break;
	case 'closed':
		$closed = 0;
		foreach( (array) $post_ids as $post_id ) {
			$this->update_order_status($post_id, 'closed');
			$closed++;
		}
		$msg = sprintf( _n( '%s order Closed.', '%s orders Closed.', $closed, 'mp' ), number_format_i18n( $closed ) );
		break;

	case 'trash':
		$trashed = 0;
		foreach( (array) $post_ids as $post_id ) {
			$this->update_order_status($post_id, 'trash');
			$trashed++;
		}
		$msg = sprintf( _n( '%s order moved to Trash.', '%s orders moved to Trash.', $trashed, 'mp' ), number_format_i18n( $trashed ) );
		break;

	case 'delete':
		$deleted = 0;
		foreach( (array) $post_ids as $post_id ) {
			$this->update_order_status($post_id, 'delete');
			$deleted++;
		}
		$msg = sprintf( _n( '%s order Deleted.', '%s orders Deleted.', $deleted, 'mp' ), number_format_i18n( $deleted ) );
		break;

case 'delete_all':
	$mp_orders = get_posts('post_type=mp_order&post_status=trash&numberposts=-1');
	if ($mp_orders) {
 			$deleted = 0;
		foreach($mp_orders as $mp_order) {
 				$this->update_order_status($mp_order->ID, 'delete');						
 				$deleted++;
		}
 			$msg = sprintf( _n( '%s order Deleted.', '%s orders Deleted.', $deleted, 'mp' ), number_format_i18n( $deleted ) );
	}
	break;
}

}

$avail_post_stati = wp_edit_posts_query();

$num_pages = $wp_query->max_num_pages;

$mode = 'list';
?>

<div class="wrap">
<div class="icon32"><img src="<?php echo mp_plugin_url('images/shopping-cart.png'); ?>" /></div>
<h2><?php _e('Manage Orders', 'mp');
if ( isset($_GET['s']) && $_GET['s'] )
printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', get_search_query() ); ?>
</h2>

<?php if ( isset($msg) ) { ?>
<div class="updated fade"><p>
<?php echo $msg; ?>
</p></div>
<?php } ?>

<form id="posts-filter" action="<?php echo admin_url('edit.php'); ?>" method="get">

<ul class="subsubsub">
<?php
if ( empty($locked_post_status) ) :
$status_links = array();
$num_posts = wp_count_posts( $post_type, 'readable' );
$class = '';
$allposts = '';

$total_posts = array_sum( (array) $num_posts );

// Subtract post types that are not included in the admin all list.
foreach ( get_post_stati( array('show_in_admin_all_list' => false) ) as $state )
	$total_posts -= $num_posts->$state;

$class = empty($class) && empty($_GET['post_status']) ? ' class="current"' : '';
$status_links[] = "<li><a href='edit.php?page=marketpress-orders&post_type=product{$allposts}'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_posts, 'posts' ), number_format_i18n( $total_posts ) ) . '</a>';

foreach ( get_post_stati(array(), 'objects') as $status_key => $status ) {
	$class = '';

	$status_name = $status->name;

	if ( !in_array( $status_name, $avail_post_stati ) )
		continue;

	if ( empty( $num_posts->$status_name ) )
		continue;

	if ( isset($_GET['post_status']) && $status_name == $_GET['post_status'] )
		$class = ' class="current"';

	$status_links[$status_key] = "<li><a href='edit.php?page=marketpress-orders&amp;post_status=$status_name&amp;post_type=product'$class>" . sprintf( _n( $status->label_count[0], $status->label_count[1], $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
}

// Kludge. There has to be a better way to order stati. If present we want to 'trash' key always at the end. 
// Maybe if we were properly inheriting WP_List_Table.
if (isset($status_links['trash'])) {
$trash_item = $status_links['trash'];
unset($status_links['trash']);
$status_links['trash'] = $trash_item;
}
echo implode( " |</li>\n", $status_links ) . '</li>';
unset( $status_links );
endif;
?>
</ul>

<p class="search-box">
	<label class="screen-reader-text" for="post-search-input"><?php _e('Search Orders', 'mp'); ?>:</label>
	<input type="text" id="post-search-input" name="s" value="<?php the_search_query(); ?>" />
	<input type="submit" value="<?php _e('Search Orders', 'mp'); ?>" class="button" />
</p>

<input type="hidden" name="post_type" class="post_status_page" value="product" />
<input type="hidden" name="page" class="post_status_page" value="marketpress-orders" />
<?php if (!empty($_GET['post_status'])) { ?>
<input type="hidden" name="post_status" class="post_status_page" value="<?php echo esc_attr($_GET['post_status']); ?>" />
<?php } ?>

<?php if ( have_posts() ) { ?>

<div class="tablenav">
<?php
$page_links = paginate_links( array(
	'base' => add_query_arg( 'paged', '%#%' ),
	'format' => '',
	'prev_text' => __('&laquo;'),
	'next_text' => __('&raquo;'),
	'total' => $num_pages,
	'current' => $pagenum
));

?>

<div class="alignleft actions">
<select name="action">
<option value="-1" selected="selected"><?php _e('Change Status', 'mp'); ?></option>
<option value="received"><?php _e('Received', 'mp'); ?></option>
<option value="paid"><?php _e('Paid', 'mp'); ?></option>
<option value="shipped"><?php _e('Shipped', 'mp'); ?></option>
<option value="closed"><?php _e('Closed', 'mp'); ?></option>
<?php if ((isset($_GET['post_status'])) && ($_GET['post_status'] == 'trash')) { ?>
<option value="delete"><?php _e('Delete', 'mp'); ?></option>
<?php } else { ?>
<option value="trash"><?php _e('Trash', 'mp'); ?></option>			
<?php } ?>
</select>
<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
<?php wp_nonce_field('update-order-status'); ?>

<?php // view filters
if ( !is_singular() ) {
	$arc_query = $wpdb->prepare("SELECT DISTINCT YEAR(post_date) AS yyear, MONTH(post_date) AS mmonth FROM $wpdb->posts WHERE post_type = %s ORDER BY post_date DESC", $post_type);

	$arc_result = $wpdb->get_results( $arc_query );

	$month_count = count($arc_result);

	if ( $month_count && !( 1 == $month_count && 0 == $arc_result[0]->mmonth ) ) {
	$m = isset($_GET['m']) ? (int)$_GET['m'] : 0;
	?>
	<select name='m'>
	<option<?php selected( $m, 0 ); ?> value='0'><?php _e('Show all dates'); ?></option>
	<?php
	foreach ($arc_result as $arc_row) {
		if ( $arc_row->yyear == 0 )
			continue;
		$arc_row->mmonth = zeroise( $arc_row->mmonth, 2 );

		if ( $arc_row->yyear . $arc_row->mmonth == $m )
			$default = ' selected="selected"';
		else
			$default = '';

		echo "<option$default value='" . esc_attr("$arc_row->yyear$arc_row->mmonth") . "'>";
		echo $wp_locale->get_month($arc_row->mmonth) . " $arc_row->yyear";
		echo "</option>\n";
	}
	?>
	</select>
	<?php } ?>

	<input type="submit" id="post-query-submit" value="<?php esc_attr_e('Filter'); ?>" class="button-secondary" />
<?php } ?>

<?php 
if ((isset($_GET['post_status'])) && ($_GET['post_status'] == 'trash')) {
submit_button( __( 'Empty Trash' ), 'button-secondary apply', 'delete_all', false );
} 
?>
</div>

<?php if ( $page_links ) { ?>
<div class="tablenav-pages"><?php
	$count_posts = $post_type_object->hierarchical ? $wp_query->post_count : $wp_query->found_posts;
	$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
						number_format_i18n( ( $pagenum - 1 ) * $per_page + 1 ),
						number_format_i18n( min( $pagenum * $per_page, $count_posts ) ),
						number_format_i18n( $count_posts ),
						$page_links
						);
	echo $page_links_text;
	?></div>
<?php } ?>

<div class="clear"></div>
</div>

<div class="clear"></div>

<table class="widefat <?php echo $post_type_object->hierarchical ? 'page' : 'post'; ?> fixed" cellspacing="0">
	<thead>
	<tr>
<?php print_column_headers( $current_screen ); ?>
	</tr>
	</thead>

	<tfoot>
	<tr>
<?php print_column_headers($current_screen, false); ?>
	</tr>
	</tfoot>

	<tbody>
<?php
	if ( function_exists('post_rows') ) {
		post_rows();
	} else {
		$wp_list_table = _get_list_table('WP_Posts_List_Table');
		$wp_list_table->display_rows();
	}
 ?>
	</tbody>
</table>

<div class="tablenav">

<?php
if ( $page_links )
	echo "<div class='tablenav-pages'>$page_links_text</div>";
?>

<div class="alignleft actions">
<select name="action2">
<option value="-1" selected="selected"><?php _e('Change Status', 'mp'); ?></option>
<option value="received"><?php _e('Received', 'mp'); ?></option>
<option value="paid"><?php _e('Paid', 'mp'); ?></option>
<option value="shipped"><?php _e('Shipped', 'mp'); ?></option>
<option value="closed"><?php _e('Closed', 'mp'); ?></option>
<?php if ((isset($_GET['post_status'])) && ($_GET['post_status'] == 'trash')) { ?>
<option value="delete"><?php _e('Delete', 'mp'); ?></option>
<?php } else { ?>
<option value="trash"><?php _e('Trash', 'mp'); ?></option>			
<?php } ?>
</select>
<input type="submit" value="<?php esc_attr_e('Apply'); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
<?php 
if ((isset($_GET['post_status'])) && ($_GET['post_status'] == 'trash')) {
submit_button( __( 'Empty Trash' ), 'button-secondary apply', 'delete_all2', false );
} 
?>

<br class="clear" />
</div>
<br class="clear" />
</div>

<?php } else { // have_posts() ?>
<div class="clear"></div>
<p><?php _e('No Orders Yet', 'mp'); ?></p>
<?php } ?>

</form>

<?php $this->export_orders_form(); ?>