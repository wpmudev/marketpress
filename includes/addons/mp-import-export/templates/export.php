<div class="wrap theme-options">
	<h2><?php _e( 'MarketPress Export', 'mp' ); ?></h2><br />
	<form action="tools.php?page=marketpress_export" method="post">
		<p class="mp-select">
			<label for="export-types">
				<span><?php _e( 'What do you want to export:', 'mp' ); ?></span>
				<select type="text" name="export-types" id="export-types">
					<option value="all"><?php _e( 'All', 'mp' ); ?></option>
					<option value="products"><?php _e( 'Products', 'mp' ); ?></option>
					<option value="orders"><?php _e( 'Orders', 'mp' ); ?></option>
					<option value="customers"><?php _e( 'Customers', 'mp' ); ?></option>
				</select>
			</label>
		</p>
		<input type="hidden" name="action" value="process" />
		<div id="export-tabs">
			<ul>
				<li class="tab-products"><a href="#tab-products"><?php _e( 'Products', 'mp' ); ?></a></li>
				<li class="tab-orders"><a href="#tab-orders"><?php _e( 'Orders', 'mp' ); ?></a></li>
				<li class="tab-customers"><a href="#tab-customers"><?php _e( 'Customers', 'mp' ); ?></a></li>
			</ul>
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
									<input type="checkbox" name="products-columns[<?php echo $key; ?>]" id="products-columns-<?php echo $key; ?>" checked="checked" />
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
			<div id="tab-orders">
				<div class="postbox metabox-holder">
					<h3 class="hndle"><?php _e( 'Import external Gardens', 'mp' ); ?></h3>
					<div class="inside">
						<div class="setting-panel">
							<p>
								<?php _e( 'Choose CSV Data file', 'mp' ); ?>
								<input type="file" name="datafile" size="40" \>
							</p>
							<!--<p>
								<?php _e( 'CSV field separator', 'mp' ); ?>
								<input type="text" name="field_separator" size="40" \>
							</p>
							<p>
								<?php _e( 'CSV text separator', 'mp' ); ?>
								<input type="text" name="text_separator" size="40" \>
							</p>-->
						</div>
					</div>
				</div>
			</div>
			<div id="tab-customers">
				<div class="postbox metabox-holder">
					<h3 class="hndle"><?php _e( 'Import external Gardens', 'mp' ); ?></h3>
					<div class="inside">
						<div class="setting-panel">
							<p>
								<?php _e( 'Choose CSV Data file', 'mp' ); ?>
								<input type="file" name="datafile" size="40" \>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<p class="prev-next">
			<a href="#" class="mp-button mp-prev button-secondary"><?php _e( 'Previous', 'mp' ); ?></a>
			<a href="#" class="mp-button mp-next button-secondary"><?php _e( 'Next', 'mp' ); ?></a>
		</p>
		<p class="submit">
			<input type="submit" value="<?php _e( 'Export', 'mp' ); ?>" class="mp-button mp-submit button-primary" />
		</p>
		<ul id="response">
			<?php foreach( $this->messages as $message ) : ?>
				<li><?php echo $message; ?></li>
			<?php endforeach; ?>
		</ul>
	</form>
</div>