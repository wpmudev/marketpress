
<div id="tab-products">
	<div class="postbox metabox-holder">
		<h3 class="hndle"><?php _e( 'Export ', 'mp' ); ?></h3>
		<div class="inside">
			<div class="setting-panel">
				<p class="mp-text">
					<label for="products-file-name">
						<span><?php _e( 'Define the filename for the export:', 'mp' ); ?></span>
						<input type="text" name="products-file-name" id="products-file-name" value="mp-products-export" size="40" />
						<span class="mp-helper"><?php _e( 'Allowed to use dynamic values: %%timestamp%%, %%month%% and %%date%%', 'mp' ); ?></span>
					</label>
				</p>
				<p class="mp-text">
					<label for="products-limit">
						<span><?php _e( 'Limit the number of products to export (-1 for all products):', 'mp' ); ?></span>
						<input type="text" name="products-limit" id="products-limit" value="-1" size="10" />
					</label>
				</p>
				<p class="mp-text">
					<label for="products-offset">
						<span><?php _e( 'Offset: (Applied only if limit is different than -1.)', 'mp' ); ?></span>
						<input type="text" name="products-offset" id="products-offset" value="0" size="10" />
					</label>
				</p>
				<p class="mp-label">
					<?php _e( 'Which fields/columns do you want to export?', 'mp' ); ?>
				</p>
				<p class="mp-checkboxes">
					<?php
						foreach ( $this->products_columns as $key => $value ) {
							if( ! $value['required'] ) {
								?>
					<label for="products-columns-<?php echo $key; ?>">
						<input type="checkbox" name="products-columns[<?php echo $key; ?>]" id="products-columns-<?php echo $key; ?>" checked="checked" value="1" />
						<span><?php echo $value['name']; ?></span>
					</label>
								<?php
							}
						}
					?>
				</p>
				<p class="mp-textarea">
					<label for="products-custom-fields">
						<span><?php _e( 'Custom fields:', 'mp' ); ?></span>
						<span class="mp-helper"><?php _e( 'Enter custom field name. One per line.', 'mp' ); ?></span>
						<textarea name="products-custom-fields" id="products-custom-fields" size="50"></textarea>
					</label>
				</p>
			</div>
		</div>
	</div>
</div>
