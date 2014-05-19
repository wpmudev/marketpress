<?php

class MarketPress_Admin_Page_Presentation {
	function __construct() {
		add_action('marketpress_admin_page_presentation', array(&$this, 'settings'));
		add_filter('marketpress_save_settings_presentation', array(&$this, 'save_settings'));
		add_filter('marketpress_settings_tabs', array(&$this, 'tab'));	
	}
	
	function tab( $tabs ) {
		$tabs['presentation'] = array(
			'name' => __('Presentation', 'mp'),
			'cart_only' => false,
			'order' => 2,
		);
		
		return $tabs;
	}
	
	function save_settings( $settings ) {
		global $mp, $wpdb;
		
		//save settings
		if (isset($_POST['mp_settings_presentation_nonce'])) {
			check_admin_referer('mp_settings_presentation', 'mp_settings_presentation_nonce');
			
			//get old store slug
			$old_slug = mp_get_setting('slugs->store');

			//filter slugs
			$_POST['mp']['slugs'] = array_map('sanitize_title', (array)$_POST['mp']['slugs']);

			// Fixing http://premium.wpmudev.org/forums/topic/store-page-content-overwritten
			$new_slug = $_POST['mp']['slugs']['store'];
			$new_post_id = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = %s AND post_type = 'page'", $new_slug) );

			if ($new_slug != $old_slug && $new_post_id != 0) {
				echo '<div class="error fade"><p>'.__('Store base URL conflicts with another page', 'mp').'</p></div>';
			} else {
				$settings['show_purchase_breadcrumbs'] = mp_get_post_value('mp->show_purchase_breadcrumbs', 0);
				$settings['show_quantity'] = mp_get_post_value('mp->show_quantity', 0);
				$settings['show_img'] = mp_get_post_value('mp->show_img', 0);
				$settings['show_lightbox'] = mp_get_post_value('mp->show_lightbox', 0);
				$settings['disable_large_image'] = mp_get_post_value('mp->disable_large_image', 0);
				$settings['related_products']['show'] = mp_get_post_value('mp->related_products->show', 0);
				$settings['related_products']['simple_list'] = mp_get_post_value('mp->related_products->simple_list', 0);
				$settings['show_thumbnail'] = mp_get_post_value('mp->show_thumbnail', 0);
				$settings['paginate'] = mp_get_post_value('mp->paginate', 0);
				$settings['show_excerpt'] = mp_get_post_value('mp->show_excerpt', 0);
				$settings['show_filters'] = mp_get_post_value('mp->show_filters', 0);
				$settings = array_merge($settings, apply_filters('mp_presentation_settings_filter', $_POST['mp']));
				
				update_option('mp_settings', $settings);

				mp()->create_store_page($old_slug);

				//schedule flush rewrite rules due to product slugs on next page load (too late to do it here)
				update_option('mp_flush_rewrite', 1);

				echo '<div class="updated fade"><p>'.__('Settings saved.', 'mp').'</p></div>';
			}
		}
		
		return $settings;
	}
	
	function settings( $settings ) {
		
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$('.mp-related-products-row').find(':checkbox').on('ifToggled', function(){
				var $this = $(this),
					 	$section = $('.presentation-related-products-settings');
				
				if ( $this.is(':checked') )
					$section.slideDown(300);
				else
					$section.slideUp(300);
			});
			
			$('.mp-product-img-size').change(function(){
				var $this = $(this),
					  $row = $this.closest('tr').next('tr');
				
				if ( $this.val() == 'custom' ) {
					$row.show();
				} else {
					$row.hide();
				}
			});
			
			$('.mp-paginate-products-row').find(':checkbox').on('ifToggled', function(){
				var $this = $(this),
					  $row = $this.closest('tr').next('tr');
				
				if ( $this.is(':checked') ) {
					$row.show();
				} else {
					$row.hide();
				}
			});
		});
		</script>

		<form method="post" action="<?php echo add_query_arg(array()); ?>">
			<?php
			MP_Metabox::display(array('title' => __('General Settings', 'mp')));
			MP_Metabox::display(array('title' => __('Single Product Settings', 'mp')));
			MP_Metabox::display(array('title' => __('Related Products Settings', 'mp')));
			MP_Metabox::display(array('title' => __('Product List Settings', 'mp')));
			MP_Metabox::display(array('title' => __('Store URL Slugs', 'mp')));
			MP_Metabox::display(array('title' => __('Social', 'mp')));
			?>

			<?php do_action('mp_presentation_settings'); ?>

			<p class="submit">
				<input class="button-primary" type="submit" name="submit_settings" value="<?php _e('Save Changes', 'mp') ?>" />
			</p>
			
			<?php wp_nonce_field('mp_settings_presentation', 'mp_settings_presentation_nonce'); ?>
		</form>
		<?php
	}
}

mp_register_admin_page('MarketPress_Admin_Page_Presentation');
