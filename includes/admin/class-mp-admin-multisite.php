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
		if ( is_null(self::$_instance) ) {
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
		$this->maybe_update();
		
		add_action('init', array(&$this, 'init_metaboxes'));
		add_action('network_admin_menu', array(&$this, 'add_menu_items'));
		add_filter('wpmudev_field/after_field', array(&$this, 'display_create_page_button'), 10, 2);
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_styles_scripts'));
		add_action('wpmudev_field/print_scripts/network_store_page', array(&$this, 'print_network_store_page_scripts'));
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
jQuery(document).ready(function($){
	$('input[name="network_store_page"]').on('change', function(e){
		var $this = $(this);
		
		$this.select2('enable', false).isWorking(true);
		
		$.post(ajaxurl, {
			"page_id" : e.val,
			"nonce" : "<?php echo wp_create_nonce('mp_get_page_slug'); ?>",
			"action" : "mp_get_page_slug"
		}).done(function(resp){
			$this.select2('enable', false).isWorking(false);
			
			if ( resp.success ) {
				$('.mp-network-store-page-slug').html(resp.data);
			}
		})
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
		wp_enqueue_style('mp-admin', mp_plugin_url('includes/admin/ui/css/admin.css'), array(), MP_VERSION);
		// Scripts
		wp_enqueue_script('jquery');
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
			case 'network_store_page' :
				return '<a class="button mp-create-page-button" href="' . admin_url('post-new.php?post_type=page') . '">' . __('Create Page') . '</a>';
				break;
		}
		
		return $html;
	}
	
	/**
	 * Determines if the update script should be run
	 *
	 * @since 3.0
	 * @access public
	 */
	public function maybe_update() {
		if ( ! is_null($this->build) && $this->build != mp_get_network_setting('build') ) {
    	$old_settings = get_site_option('mp_network_settings', array());
	    $settings = $this->update($old_settings);
	    $settings['build'] = $this->build;
	    update_site_option('mp_network_settings', $settings);
    }
	}
	
	/**
	 * Updates any necessary settings
	 *
	 * @since 3.0
	 * @access public
	 * @param array $settings
	 * @return array
	 */
	public function update( $settings ) {
		if ( $pro_levels = mp_get_network_setting('gateways_pro_level') ) {
			foreach ( $pro_levels as $gateway => $level ) {
				$settings['allowed_gateways'][$gateway] = 'psts_level_' . $level;
			}
			
			unset($settings['gateways_pro_level']);
		}
		
		return $settings;
	}
	
	/**
	 * Initialize metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_metaboxes() {
		$this->init_general_settings_metaboxes();
		$this->init_global_gateway_settings_metaboxes();
		$this->init_gateway_permissions_metaboxes();
		$this->init_theme_permissions_metaboxes();
		$this->init_marketplace_slugs_metaboxes();
	}

	/**
	 * Initialize general settings metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	
	public function init_general_settings_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-network-settings-general',
			'screen_ids' => array('network-store-settings-network', 'settings_page_network-store-settings-network'),
			'title' => __('General Settings', 'mp'),
			'site_option_name' => 'mp_network_settings',
			'order' => 0,
		));
		$metabox->add_field('checkbox', array(
			'name' => 'main_blog',
			'label' => array('text' => __('Limit Global Widgets/Shortcodes To Main Blog?', 'mp')),
		));
		$metabox->add_field('checkbox', array(
			'name' => 'global_cart',
			'label' => array('text' => __('Enable Global Shopping Cart?', 'mp')),
		));
	}

	/**
	 * Initialize global gateway metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	
	public function init_global_gateway_settings_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-network-settings-global-gateway',
			'screen_ids' => array('network-store-settings-network', 'settings_page_network-store-settings-network'),
			'title' => __('Global Gateway', 'mp'),
			'site_option_name' => 'mp_network_settings',
			'order' => 0,
			'conditional' => array(
				'name' => 'global_cart',
				'value' => '1',
				'action' => 'show',
			),
		));
		
		$all_gateways = MP_Gateway_API::get_gateways();
		$gateways = array('' => __('Chose a Gateway', 'mp'));
		
		foreach ( $all_gateways as $code => $gateway ) {
			if ( ! $gateway[2] ) {
				// Skip non-global gateways
				continue;
			}
			
			$gateways[$code] = $gateway[1];
		}
		
		$metabox->add_field('select', array(
			'name' => 'global_gateway',
			'label' => array('text' => __('Select a Gateway', 'mp')),
			'options' => $gateways,
		));
	}

	/**
	 * Initialize gateway permissions metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function init_gateway_permissions_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-network-settings-gateway-permissions',
			'screen_ids' => array('network-store-settings-network', 'settings_page_network-store-settings-network'),
			'title' => __('Gateway Permissions', 'mp'),
			'site_option_name' => 'mp_network_settings',
			'order' => 0,
			'conditional' => array(
				'name' => 'global_cart',
				'value' => '1',
				'action' => 'hide',
			),
		));
		
		$options_permissions = array(
			'full' => __('All Can Use', 'mp'),
			'none' => __('No Access', 'mp'),
		);
		
		if ( function_exists('psts_levels_select') ) {
			$levels = get_site_option('psts_levels');
			$options_levels = array();
			
			if ( is_array($levels) ) {
				foreach ( $levels as $level => $value ) {
					$options_levels['psts_level_' . $level] = $level . ':' . $value['name'];
				}
			}
		
			$options_permissions['supporter'] = array(
				'group_name' => __('Pro Site Level', 'mp'),
				'options' => $options_levels,
			);
		}
		
		$gateways = MP_Gateway_API::get_gateways();
		foreach ( $gateways as $code => $gateway ) {
			$metabox->add_field('select', array(
				'name' => 'allowed_gateways[' . $code . ']',
				'label' => array('text' => $gateway[1]),
				'options' => $options_permissions,
			));
		}
	}

	/**
	 * Initialize theme permissions metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */	
	public function init_theme_permissions_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-network-settings-theme-permissions',
			'screen_ids' => array('network-store-settings-network', 'settings_page_network-store-settings-network'),
			'title' => __('Theme Permissions', 'mp'),
			'site_option_name' => 'mp_network_settings',
			'desc' => __('Set theme access permissions for network stores. For a custom css theme, save your css file with the <strong>MarketPress Theme: NAME</strong> header in the <strong>/marketpress/ui/themes/</strong> folder and it will appear in this list so you may select it.', 'mp'),
			'order' => 15,
		));
		
		$theme_list = mp_get_theme_list();

		$options_permissions = array(
			'full' => __('All Can Use', 'mp'),
			'none' => __('No Access', 'mp'),
		);
		
		if ( function_exists('psts_levels_select') ) {
			$levels = get_site_option('psts_levels');
			$options_levels = array();
			
			if ( is_array($levels) ) {
				foreach ( $levels as $level => $value ) {
					$options_levels['psts_level_' . $level] = $level . ':' . $value['name'];
				}
			}
		
			$options_permissions['supporter'] = array(
				'group_name' => __('Pro Site Level', 'mp'),
				'options' => $options_levels,
			);
		}
		
		foreach ( $theme_list as $value => $theme ) {
			$metabox->add_field('select', array(
				'name' => 'allowed_themes[' . $value . ']',
				'label' => array('text' => $theme['name']),
				'desc' => $theme['path'],
				'options' => $options_permissions,
			));
		}		
	}

	/**
	 * Initialize marketplace pages metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */	
	public function init_marketplace_slugs_metaboxes() {
		$metabox = new WPMUDEV_Metabox(array(
			'id' => 'mp-network-settings-slugs',
			'screen_ids' => array('network-store-settings-network', 'settings_page_network-store-settings-network'),
			'title' => __('Global MarketPlace Pages', 'mp'),
			'site_option_name' => 'mp_network_settings',
			'order' => 15,
		));
				
		$metabox->add_field('post_select', array(
			'name' => 'network_store_page',
			'label' => array('text' => __('Network Store Page', 'mp')),
			'desc' => __('This page will be used as the root for your global market place', 'mp'),
			'query' => array('post_type' => 'page', 'orderby' => 'title', 'order' => 'ASC'),
			'placeholder' => __('Choose a Page', 'mp'),
		));
		
		$network_page_slug = home_url();
		if ( $network_page = mp_get_network_setting('network_store_page') ) {
			$network_page_slug = get_permalink($network_page);
		}
		
		$metabox->add_field('text', array(
			'name' => 'slugs[categories]',
			'label' => array('text' => __('Product Categories', 'mp')),
			'custom' => array('style' => 'width:150px'),
			'before_field' => '<span class="mp-network-store-page-slug">' . $network_page_slug . '</span>',
		));
		$metabox->add_field('text', array(
			'name' => 'slugs[tags]',
			'label' => array('text' => __('Product Tags', 'mp')),
			'custom' => array('style' => 'width:150px'),
			'before_field' => '<span class="mp-network-store-page-slug">' . $network_page_slug . '</span>',
		));
	}
	
	/**
	 * Add menu items to the network admin menu
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_menu_items() {
		add_submenu_page('settings.php', __('Store Network Settings', 'mp'), __('Store Network', 'mp'), 'manage_network_options', 'network-store-settings', array(&$this, 'network_store_settings'));
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
	<div class="icon32"><img src="<?php echo mp_plugin_url('ui/images/settings.png'); ?>" /></div>
	<h2 class="mp-settings-title"><?php _e('Store Network Settings', 'mp'); ?></h2>
	<div class="clear"></div>
	<div class="mp-settings">
	 	<form id="mp-main-form" method="post" action="<?php echo add_query_arg(array()); ?>">
			<?php
			/**
			 * Render WPMUDEV Metabox settings
			 *
			 * @since 3.0
			 */
			do_action('wpmudev_metabox/render_settings_metaboxes'); ?>
		</form>
	</div>
</div>
		<?php
	}
}

MP_Admin_Multisite::get_instance();