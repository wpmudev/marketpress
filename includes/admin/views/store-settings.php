<?php
global $wpdb;

//double-check rights
if ( ! current_user_can('manage_options') ) {
	echo "<p>" . __('Nice Try...', 'mp') . "</p>";	//If accessed properly, this message doesn't appear.
	return;
} ?>
<div class="wrap">
	<div class="icon32"><img src="<?php echo mp_plugin_url('ui/images/settings.png'); ?>" /></div>
	<h2 class="mp-settings-title"><?php _e('Store Settings', 'mp'); ?></h2>
	<div class="clear"></div>
	<?php
 	if ( $updated ) : ?>
 	<div class="updated fade"><p><?php _e('Settings saved.', 'mp'); ?></p></div>
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
		</form>
	</div>
</div><!-- /.wrap -->