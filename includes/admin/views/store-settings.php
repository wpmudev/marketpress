<?php
global $wpdb;

//double-check rights
if ( ! current_user_can('manage_options') ) {
	echo "<p>" . __('Nice Try...', 'mp') . "</p>";	//If accessed properly, this message doesn't appear.
	return;
}

/*********************************************************
 * Setup tabs 
 *********************************************************/
$core_tabs = array(
	'general' => array(
		'name' => __('General', 'mp'),
		'cart_only' => false,
		'order' => 0,
	),
	'presentation' => array(
		'name' => __('Presentation', 'mp'),
		'cart_only' => false,
		'order' => 1,
	),
	'messages' => array(
		'name' => __('Messages', 'mp'),
		'cart_only' => true,
		'order' => 2,
	),
	'shipping' => array(
		'name' => __('Shipping', 'mp'),
		'cart_only' => true,
		'order' => 3,
	),
	'gateways' => array(
		'name' => __('Payments', 'mp'),
		'cart_only' => true,
		'order' => 4,
	),
	'shortcodes' => array(
		'name' => __('Shortcodes', 'mp'),
		'cart_only' => false,
		'order' => 5,
	),
	'importers' => array(
		'name' => __('Importers', 'mp'),
		'cart_only' => false,
		'order' => 6,
	),
);
$settings = get_option('mp_settings');
$current_tab = ( !empty($_GET['tab']) ) ? $_GET['tab'] : 'general';
$tabs_new = apply_filters('mp_settings_tabs', $core_tabs, $settings); //tab syntax > 3.0
$tabs_sorted = $tabs_old = $stubs = array();
$disable_cart = mp_get_setting('disable_cart', false);
$tab_order = 1;

//for backwards compatibility 
foreach ( (array) $tabs_new as $stub => $tab ) {
	$tabs_old[$stub] = $tab['name'];
}

//allow plugins to add/remove tabs (pre 3.0)
$tabs_old = apply_filters('marketpress_tabs', $tabs_old);

if ( is_array($tabs_new) ) {
	foreach ( $tabs_new as $stub => $tab ) {
		if ( ($disable_cart && $tab['cart_only']) || (count($tabs_old) && !isset($tabs_old[$stub])) )
			/*
			skip this tab when shopping cart has been disabled and it's a cart-only tab OR
			it's been removed after applying the marketpress_tabs filter
			*/
			continue;
		
		$stubs[] = $stub;
		$tab_order = intval($tab['order']);
		$tabs_sorted[$tab['order']][$stub] = $tab;	
	}
}

if ( is_array($tabs_old) ) {
	//tack on tabs that were added using old marketpress_tabs filter
	foreach ( $tabs_old as $stub => $tab ) {
		if ( in_array($stub, $stubs) )
			//don't add duplicate tabs
			continue;
		
		$tabs_sorted[$tab_order+1][$stub] = array(
			'name' => $tab,
		);
	}
}

ksort($tabs_sorted);

//allow plugins to change sort order of tabs before display
$tabs_sorted = apply_filters('mp_settings_tabs_sorted', $tabs_sorted, $settings);

/*********************************************************
 * Setup settings 
 *********************************************************/

// save settings
if ( isset($_POST["mp_settings_{$current_tab}_nonce"]) ) {
	check_admin_referer("mp_settings_{$current_tab}", "mp_settings_{$current_tab}_nonce");
	$settings = apply_filters("mp_settings_{$current_tab}_save", array_merge($settings, mp_get_post_value('mp', array())));
}

//trim all leading/trailing whitespace from each settings value
$settings = mp_array_map_recursive('trim', $settings);

//update options - it's ok to run this like this because update_option() won't do anything unless settings have changed 
$updated = update_option('mp_settings', $settings);
?>
<div class="wrap mp-wrap">
	<div class="icon32"><img src="<?php echo mp_plugin_url('ui/images/settings.png'); ?>" /></div>
	<h2 class="mp-settings-title"><?php _e('Store Settings', 'mp'); ?></h2>
	<div class="clear"></div>
	<?php
 	if ( $updated ) : ?>
 	<div class="updated fade"><p><?php _e('Settings saved.', 'mp'); ?></p></div>
 	<?php
 	endif;
 	
	if ( count($tabs_sorted) ) : ?>
	<ul class="mp-tabs">
		<?php
			foreach ( $tabs_sorted as $tab_group ) :
				foreach ( $tab_group as $stub => $tab ) :
		?>
		<li class="mp-tab<?php echo ($current_tab == $stub ) ? ' active' : ''; ?>"><a href="<?php echo add_query_arg('tab', $stub); ?>" class="mp-tab-link"><?php echo $tab['name']; ?></a></li>
		<?php
				endforeach;
			endforeach;
		?>
	</ul>
	<?php
	endif; ?>
	<div class="mp-settings">
	 	<form id="mp-main-form" method="post" action="<?php echo add_query_arg(array()); ?>">
			<?php
				//add settings metaboxes
				do_action("mp_settings_{$current_tab}_metaboxes");
			?>
			<p class="submit">
				<input class="button-primary" type="submit" name="submit_settings" value="<?php _e('Save Changes', 'mp') ?>" />
			</p>
			<?php wp_nonce_field("mp_settings_{$current_tab}", "mp_settings_{$current_tab}_nonce"); ?>
		</form>
	</div>
</div><!-- /.wrap -->