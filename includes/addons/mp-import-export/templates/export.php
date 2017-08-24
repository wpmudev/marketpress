<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="mp-export-products-wrapper">
		<h2><?php esc_html_e( 'Export products to a CSV file', 'mp' ); ?></h2>
		<p>
			<?php esc_html_e( 'This tool allows you to generate and download a CSV file containing a list of all products.', 'mp' ); ?>
		</p>
		<form>
			<table class="form-table">
				<tbody>
				<tr>
					<th scope="row">
						<label for="mp-export-product-type">
							<?php esc_html_e( 'Product types to export', 'mp' ); ?>
						</label>
					</th>
					<td>
						<select id="mp-export-product-type" name="mp-product-types" multiple>
							<option value="physical">
								<?php esc_html_e( 'Physical products' ); ?>
							</option>
							<option value="digital">
								<?php esc_html_e( 'Digital products' ); ?>
							</option>
							<option value="external">
								<?php esc_html_e( 'External products' ); ?>
							</option>
							<option value="variable">
								<?php esc_html_e( 'Variable products' ); ?>
							</option>
							<option value="variations">
								<?php esc_html_e( 'Variations' ); ?>
							</option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label>
							<?php esc_html_e( 'Export custom meta', 'mp' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" id="mp-exporter-meta" value="1">
						<label for="mp-exporter-meta">
							<?php esc_html_e( 'Yes, export all custom meta', 'mp' ); ?>
						</label>
					</td>
				</tr>
				</tbody>
			</table>

			<button class="button-primary" type="submit"><?php esc_html_e( 'Export', 'mp' ); ?></button>
		</form>
	</div>
</div>
