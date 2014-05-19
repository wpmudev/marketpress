<div class="wrap">
	<div class="icon32"><img src="<?php echo mp_plugin_url('images/shopping-cart.png'); ?>" alt="" /></div>
	<h2><?php _e('Manage Orders', 'mp'); ?></h2>
	
	<form>
		<input type="hidden" value="product" name="post_type" />
		<input type="hidden" value="orders" name="page" />
		<?php
		require_once mp_plugin_dir('list-tables/class-mp-orders-list-table.php');
		$mp_orders_list_table = new MP_Orders_List_Table();
		$mp_orders_list_table->prepare_items();
		$mp_orders_list_table->display();
		?>
	</form>
</div>