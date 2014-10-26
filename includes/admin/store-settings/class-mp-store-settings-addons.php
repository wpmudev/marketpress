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
		
		if ( $this.has('.enabled') ) {
			$bulkActions.val('disable');
		}
		
		if ( $this.has('.disabled') ) {
			$bulkActions.val('enable');
		}
		
		$form.submit();
	});
});
</script>
<div class="wrap mp-wrap">
	<?php
	require_once mp_plugin_dir('includes/admin/class-mp-addons-list-table.php');
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
	 * Constructor function
	 *
	 * @since 3.0
	 * @access private
	 */
	private function __construct() {
		add_action('store-settings_page_store-settings-addons', array(&$this, 'display_list_table'));
	}
}

MP_Store_Settings_Addons::get_instance();