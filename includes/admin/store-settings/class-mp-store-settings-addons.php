<?php

class MP_Store_Settings_Addons {	
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
			self::$_instance = new MP_Store_Settings_Addons();
		}
		return self::$_instance;
	}
	
	/**
	 * Display add-on settings
	 *
	 * @since 3.0
	 * @access public
	 */
	public function display_addon_settings() {
		$addon = mp_get_get_value('addon');
		$addon_obj = MP_Addons::get_instance()->get_addon($addon);
		
		if ( false === $addon_obj ) {
			wp_die();
		}
		?>
<div class="wrap mp-wrap">
	<?php
	require_once mp_plugin_dir('includes/addons/class-mp-addons-list-table.php');
	$list_table = new MP_Addons_List_Table();
	$list_table->prepare_items();	?>
	<div class="icon32"><img src="<?php echo mp_plugin_url('ui/images/settings.png'); ?>" /></div>
	<h2 class="mp-settings-title"><?php printf(__('Store Settings: Add Ons: %s', 'mp'), $addon_obj->label); ?></h2>
	<div class="clear"></div>
	<div class="mp-settings">
		<form method="post">
		<?php do_action('wpmudev_metabox/render_settings_metaboxes'); ?>
		</form>
	</div>
</div>
		<?php
	}

	/**
	 * Add metaboxes
	 *
	 * @since 3.0
	 * @access public
	 */
	public function display_list_table() {
		?>
<style type="text/css">
th.column-ID {
	width: 50px;
}
td.column-actions {
	text-align: right;
}
.mp-addon-status {
	background: #ccc;
	border-radius: 10px;
		-moz-box-shadow: inset 1px 1px 1px rgba(0, 0, 0, .4), 1px 1px 1px rgba(255, 255, 255, .8);
		-webkit-box-shadow: inset 1px 1px 1px rgba(0, 0, 0, .4), 1px 1px 1px rgba(255, 255, 255, .8);
	box-shadow: inset 1px 1px 1px rgba(0, 0, 0, .4), 1px 1px 1px rgba(255, 255, 255, .8);
	display: inline-block;
	height: 10px;
	margin-right: 5px;
	width: 10px;
}
.mp-addon-status.enabled {
	background: #1ead00;
}
.mp-addon-status.disabled {
	background: #ff0000;
}
</style>
<script type="text/javascript">
jQuery(document).ready(function($){
	$('.mp-enable-disable-addon').click(function(e){
		e.preventDefault();
		var $this = $(this),
				$row = $this.closest('tr'),
				$cb = $row.find(':checkbox'),
				$form = $this.closest('form'),
				$bulkActions = $form.find('[name="action"]');
		
		$cb.prop('checked', true);
		
		if ( $this.find('.enabled').length ) {
			$bulkActions.val('disable');
		}
		
		if ( $this.find('.disabled').length ) {
			$bulkActions.val('enable');
		}
		
		$form.submit();
	});
});
</script>
<div class="wrap mp-wrap">
	<?php
	require_once mp_plugin_dir('includes/addons/class-mp-addons-list-table.php');
	$list_table = new MP_Addons_List_Table();
	$list_table->prepare_items();	?>
	<div class="icon32"><img src="<?php echo mp_plugin_url('ui/images/settings.png'); ?>" /></div>
	<h2 class="mp-settings-title"><?php _e('Store Settings: Add Ons', 'mp'); ?></h2>
	<div class="clear"></div>
	<div class="mp-settings">
		<form method="get">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
			<?php
			$list_table->display(); ?>
		</form>
	</div>
</div>
	<?php
	}
	
	/**
	 * Display settings
	 *
	 * @since 3.0
	 * @access public
	 * @action 
	 */
	public function display_settings() {
		if ( $addon = mp_get_get_value('addon') ) {
			$this->display_addon_settings();
		} else {
			$this->display_list_table();
		}
	}
	
	/**
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action('store-settings_page_store-settings-addons', array(&$this, 'display_settings'));
	}
}

MP_Store_Settings_Addons::get_instance();