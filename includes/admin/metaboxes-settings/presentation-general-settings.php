<?php

$mp_settings_metaboxes['presentation_general'] = new MP_Settings_Metabox_Presentation_General_Settings();

class MP_Settings_Metabox_Presentation_General_Settings extends MP_Settings_Metabox {
	function init_vars() {
		$this->title = __('General Presentation Settings', 'mp');
		$this->tab = 'presentation';
	}
	
	function on_creation() {
		
	}
	
	function save_settings() {
		
	}
	
	function display_inside() {
		
		?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text">
						<p><?php _e('This option changes the built-in css styles for store pages.', 'mp') ?></p>
						<p class="last"><?php printf(__('For a custom css style, save your css file with the "MarketPress Style: NAME" header in the "%s/marketpress-styles/" folder and it will appear in this list so you may select it. You can also select "None" and create custom theme templates and css to make your own completely unique store design. More information on that <a href="%sthemes/Themeing_MarketPress.txt">here &raquo;</a>', 'mp'), WP_CONTENT_DIR, mp_plugin_url()); ?></p>
					</div>
					<?php _e('Store Style', 'mp') ?>
				</th>
				<td>
					<?php if ( defined( 'MP_LITE' ) ) { ?>
					<a class="mp-pro-update" href="http://premium.wpmudev.org/project/e-commerce/" title="<?php _e('Upgrade Now', 'mp'); ?> &raquo;"><?php _e('Upgrade to enable all styles &raquo;', 'mp'); ?></a><br />
					<?php } ?>
					<?php mp()->store_themes_select(); ?>
				</td>
			</tr>
			<?php
			if ( (is_multisite() && current_user_can('manage_network_themes')) || (!is_multisite() && current_user_can('switch_themes')) ) :
				$installed_themes = wp_get_themes(array('allowed' => true)); ?>	
			<tr>
				<th scope="row"><?php _e('Full-featured MarketPress Themes:', 'mp') ?></th>
				<td>
					<?php
					if ( array_key_exists('gridmarket', $installed_themes) || array_key_exists('framemarket', $installed_themes) ) :
						if ( is_multisite() ) {
							$url = network_admin_url('themes.php');
						} else {
							$url = admin_url('themes.php');
						} ?>
					<a class="mp-theme-preview" title="<?php _e('Activate FrameMarket/Gridmarket', 'mp') ?>" href="<?php echo $url; ?>">
						<h4><?php _e('FrameMarket/GridMarket', 'mp') ?></h4>
						<img alt="FrameMarket Theme" src="//premium.wpmudev.org/wp-content/projects/219/listing-image-thumb.png" />
						<?php _e('<p>The ultimate MarketPress theme brings visual perfection to WordPress e-commerce. This professional front-end does all the work for you!</p><p><span class="button-primary">Activate</span></p>', 'mp') ?></p>
					</a>
					<?php else : ?>
					<a class="mp-theme-preview" target="_blank" title="<?php _e('Download FrameMarket/Gridmarket', 'mp') ?>" href="http://premium.wpmudev.org/project/frame-market-theme/">
						<h4><?php _e('FrameMarket/GridMarket', 'mp') ?></h4>
						<img alt="FrameMarket Theme" src="//premium.wpmudev.org/wp-content/projects/219/listing-image-thumb.png" />
						<?php _e('<p>The ultimate MarketPress theme brings visual perfection to WordPress e-commerce. This professional front-end does all the work for you!</p><p><span class="button-primary">Download</span></p>', 'mp') ?></p>
					</a>
					<?php endif; ?>
					
					<div class="clear"></div>
					
					<?php
					if ( array_key_exists('simplemarket', $installed_themes) ) :
						if ( is_multisite() ) {
							$url = network_admin_url('themes.php');
						} else {
							$url = admin_url('themes.php');
						} ?>
					<a class="mp-theme-preview last" target="_blank" title="<?php _e('Activate SimpleMarket Now', 'mp') ?>" target="_blank" href="http://premium.wpmudev.org/project/simplemarket/">								
						<h4><?php _e('SimpleMarket', 'mp') ?></h4>
						<img alt="SimpleMarket Theme" src="//premium.wpmudev.org/wp-content/projects/237/listing-image-thumb.png" />
						<?php _e('<p>The SimpleMarket Theme uses an HTML 5 responsive design so your e-commerce site looks great across all screen-sizes and devices such as smartphones or tablets!<p><span class="button-primary">Activate</span></p>', 'mp') ?></p>
					</a>								
					<?php else : ?>
					<a class="mp-theme-preview last" target="_blank" title="<?php _e('Download SimpleMarket Now', 'mp') ?>" target="_blank" href="http://premium.wpmudev.org/project/simplemarket/">								
						<h4><?php _e('SimpleMarket', 'mp') ?></h4>
						<img alt="SimpleMarket Theme" src="//premium.wpmudev.org/wp-content/projects/237/listing-image-thumb.png" />
						<?php _e('<p>The SimpleMarket Theme uses an HTML 5 responsive design so your e-commerce site looks great across all screen-sizes and devices such as smartphones or tablets!<p><span class="button-primary">Download</span></p>', 'mp') ?></p>
					</a>
					<?php endif; ?>
				</td>
			</tr>
			<?php endif; ?>
			<tr>
				<th scope="row">
					<a class="mp-help-icon" href="javascript:;"></a>
					<div class="mp-help-text"><?php _e('Show previous, current and next steps when a customer is purchasing their cart, shown below the title.', 'mp') ?></div>
					<?php _e('Show breadcrumbs for purchase process?', 'mp') ?>
				</th>
				<td>
					<?php
					new MP_Field_Checkbox(array(
						'name' => 'mp[show_purchase_breadcrumbs]',
						'checked' => mp_get_setting('show_purchase_breadcrumbs'),
						'value' => 1,
						'label' => array('text' => __('Yes', 'mp')),
					));
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}
