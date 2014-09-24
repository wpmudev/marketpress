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
	 * Add menu items to the network admin menu
	 *
	 * @since 3.0
	 * @access public
	 */
	public function add_menu_items() {
		add_submenu_page('settings.php', __('Store Network Settings', 'mp'), __('Store Network', 'mp'), 'manage_network_options', 'network-store-settings', array(&$this, 'network_store_settings'));
	}

	/**
	 * Gets an appropriate message by it's key
	 *
	 * @since 3.0
	 * @access public
	 */
	public function get_message_by_key( $key ) {
		$messages = array(
		);
		
		return ( isset($messages[$key]) ) ? $messages[$key] : sprintf(__('An appropriate message for key "%s" could not be found.', 'mp'), $key);
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
	<?php
 	if ( $message_key = mp_get_get_value('mp_message') ) : ?>
 	<div class="updated"><p><?php echo $this->get_message_by_key($message_key); ?></p></div>
 	<?php
 	endif; ?> 	
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