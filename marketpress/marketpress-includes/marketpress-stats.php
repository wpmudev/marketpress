<?php
/*
MarketPress Statistics
*/

class MarketPress_Stats {
	
	var $mp;
	
  function __construct() {
		global $mp;

		add_action( 'wp_dashboard_setup', array(&$this, 'register_dashboard_widget') );
		
	}

  function install() {

  }
	
	function register_dashboard_widget() {
		if ( !current_user_can('manage_options') )
			return;
		
		$screen = get_current_screen();
		add_meta_box( 'mp_stats_widget', (is_multisite() ? __( 'Store Statistics', 'mp' ) : __( 'MarketPress Statistics', 'mp' )), array(&$this, 'dashboard_widget'), $screen->id, 'normal', 'core' );
	}
	
	function dashboard_widget() {
		global $wpdb, $mp;
		$year = date('Y');
		$month = date('m');
		$this_month = $wpdb->get_row("SELECT count(p.ID) as count, sum(m.meta_value) as total, avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND YEAR(p.post_date) = $year AND MONTH(p.post_date) = $month");
		
		$year = date('Y', strtotime('-1 month'));
		$month = date('m', strtotime('-1 month'));
		$last_month = $wpdb->get_row("SELECT count(p.ID) as count, sum(m.meta_value) as total, avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' AND YEAR(p.post_date) = $year AND MONTH(p.post_date) = $month");	
		
		//later get full stats and graph
		//$stats = $wpdb->get_results("SELECT DATE_FORMAT(p.post_date, '%Y-%m') as date, count(p.ID) as count, sum(m.meta_value) as total, avg(m.meta_value) as average FROM $wpdb->posts p JOIN $wpdb->postmeta m ON p.ID = m.post_id WHERE p.post_type = 'mp_order' AND m.meta_key = 'mp_order_total' GROUP BY YEAR(p.post_date), MONTH(p.post_date) ORDER BY date DESC");
		?>
		<div class="table table_content">
			<p class="sub"><?php printf(__('This Month (%s)', 'mp'), date_i18n('M, Y')); ?></p>
			<table>
				<tbody>
					<tr class="first">
						<td class="first b<?php echo ($this_month->count >= $last_month->count) ? ' green' : ' red'; ?>"><?php echo intval($this_month->count); ?></td>
						<td class="t"><?php _e('Orders', 'mp'); ?></td>
					</tr>	
					<tr>
						<td class="first b<?php echo ($this_month->total >= $last_month->total) ? ' green' : ' red'; ?>"><?php echo $mp->format_currency(false, $this_month->total); ?></td>
						<td class="t"><?php _e('Orders Total', 'mp'); ?></td>
					</tr>
					<tr>
						<td class="first b<?php echo ($this_month->average >= $last_month->average) ? ' green' : ' red'; ?>"><?php echo $mp->format_currency(false, $this_month->average); ?></td>
						<td class="t"><?php _e('Average Order', 'mp'); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
	
		<div class="table table_discussion">
			<p class="sub"><?php printf(__('Last Month (%s)', 'mp'), date_i18n('M, Y', strtotime('-1 month'))); ?></p>
			<table>
				<tbody>
					<tr class="first">
						<td class="first b"><?php echo intval($last_month->count); ?></td>
						<td class="t"><?php _e('Orders', 'mp'); ?></td>
					</tr>	
					<tr>
						<td class="first b"><?php echo $mp->format_currency(false, $last_month->total); ?></td>
						<td class="t"><?php _e('Orders Total', 'mp'); ?></td>
					</tr>
					<tr>
						<td class="first b"><?php echo $mp->format_currency(false, $last_month->average); ?></td>
						<td class="t"><?php _e('Average Order', 'mp'); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<br class="clear"/>
		<?php
	}

}
$mp_stats = new MarketPress_Stats();
?>