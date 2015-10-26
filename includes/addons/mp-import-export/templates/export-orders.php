
<div id="tab-orders">
	<div class="postbox metabox-holder">
		<h3 class="hndle"><?php _e( 'Export ', 'mp' ); ?></h3>
		<div class="inside">
			<div class="setting-panel">
				<p class="mp-text">
					<label for="orders-file-name">
						<span><?php _e( 'Define the filename for the export:', 'mp' ); ?></span>
						<input type="text" name="orders-file-name" id="orders-file-name" value="mp-orders-export" size="40" />
						<span class="mp-helper"><?php _e( 'Allowed to use dynamic values: %%timestamp%%, %%month%% and %%date%%', 'mp' ); ?></span>
					</label>
				</p>
				<p class="mp-label">
					<?php _e( 'Which order status do you want to export?', 'mp' ); ?>
				</p>
				<p class="mp-checkboxes">
					<?php
						$stati = get_post_stati( array( 'post_type' => 'mp_order' ), 'names' );

						foreach ( $stati as $status ) {
								?>
					<label for="orders-status-<?php echo $status; ?>">
						<input type="checkbox" name="orders-stati[<?php echo $status; ?>]" id="orders-status-<?php echo $status; ?>" checked="checked" value="1" />
						<span><?php echo ucfirst( str_replace( 'order_', '', $status ) ); ?></span>
					</label>
								<?php
						}
					?>
				</p>
				<p class="mp-text">
					<label for="orders-limit">
						<span><?php _e( 'Limit the number of orders to export (-1 for all orders):', 'mp' ); ?></span>
						<input type="text" name="orders-limit" id="orders-limit" value="-1" size="10" />
					</label>
				</p>
				<p class="mp-text">
					<label for="orders-offset">
						<span><?php _e( 'Offset: (Applied only if limit is different than -1.)', 'mp' ); ?></span>
						<input type="text" name="orders-offset" id="orders-offset" value="0" size="10" />
					</label>
				</p>
				<p class="mp-label">
					<?php _e( 'Which fields/columns do you want to export?', 'mp' ); ?>
				</p>
				<p class="mp-checkboxes">
					<?php
						foreach ( $this->orders_columns as $key => $value ) {
							if( ! $value['required'] ) {
								?>
					<label for="orders-columns-<?php echo $key; ?>">
						<input type="checkbox" name="orders-columns[<?php echo $key; ?>]" id="orders-columns-<?php echo $key; ?>" checked="checked" value="1" />
						<span><?php echo $value['name']; ?></span>
					</label>
								<?php
							}
						}
					?>
				</p>
				<p class="mp-textarea">
					<label for="orders-custom-fields">
						<span><?php _e( 'Custom fields:', 'mp' ); ?></span>
						<span class="mp-helper"><?php _e( 'Enter custom field name. One per line.', 'mp' ); ?></span>
						<textarea name="orders-custom-fields" id="orders-custom-fields" size="50"></textarea>
					</label>
				</p>
			</div>
		</div>
	</div>
</div>
