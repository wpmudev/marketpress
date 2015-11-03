
<div class="wrap theme-options">
	<h2><?php _e( 'MarketPress Export', 'mp' ); ?></h2><br />
	
	<?php if ( ! empty( $this->messages ) ): ?>	
		<?php add_thickbox(); ?>
		<div id="response-wrapper" style="display:none;">
			<ul id="response">
				<?php foreach( $this->messages as $message ) : ?>
					<li><?php echo $message; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<a href="#TB_inline?width=600&height=250&inlineId=response-wrapper" title="<?php _e( 'MarketPress Export Report', 'mp' ); ?>" id="thickbox-launcher" class="thickbox button-primary"><?php _e( 'MarketPress Export Report', 'mp' ); ?></a>
	<?php endif; ?>

	<form action="edit.php?post_type=<?php echo MP_Product::get_post_type(); ?>&page=marketpress_export" method="post">
		<input type="hidden" name="action" value="process" />
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
		<div id="export-tabs">
			<ul>
				<li class="tab-products"><a href="#tab-products"><?php _e( 'Products', 'mp' ); ?></a></li>
				<li class="tab-orders"><a href="#tab-orders"><?php _e( 'Orders', 'mp' ); ?></a></li>
				<li class="tab-customers"><a href="#tab-customers"><?php _e( 'Customers', 'mp' ); ?></a></li>
			</ul>

			<?php $this->products_export_view(); ?>

			<?php $this->orders_export_view(); ?>

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
	</form>
</div>