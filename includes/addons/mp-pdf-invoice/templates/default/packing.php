<style type="text/css">
	@font-face {
		font-family: 'Open Sans';
		font-style: normal;
		font-weight: normal;
		src: local('Open Sans'), local('OpenSans'), url(http://themes.googleusercontent.com/static/fonts/opensans/v7/yYRnAC2KygoXnEC8IdU0gQLUuEpTyoUstqEm5AMlJo4.ttf) format('truetype');
	}

	.container {
		width: 750px;
		margin: auto;
		font-size: 12pt;
		color: #333;
		font-family: "Open Sans";
	}

	.header img {
		max-height: 60px;
		width: auto;
	}

	.header h3 {
		font-size: 20pt;
		font-weight: normal;
	}

	.clear {
		clear: both;
	}

	table {
		width: 100%;
	}

	.product-detail {
		border-collapse: separate;
		border-spacing: 7px;
	}

	.product-detail thead th {
		background-color: #3B7ADB;
		padding: 5px;
		color: #f5f5f5;
		text-align: left;
		font-weight: normal;
	}

	.product-detail tbody td {
		background-color: #CFCFCF;
		padding: 5px;
	}

	.product-detail tbody td.no-bg {
		background-color: transparent;
	}
</style>
<div class="container">
	<div class="header">
		{{logo}}

		<h3><?php _e( "Packing Slip", "mp" ) ?> #{{order_id}}</h3>
	</div>
	<table>
		<tr>
			<td>
				<strong><?php _e( "Billing Address", "mp" ) ?></strong><br>
				{{billing}}
			</td>
			<?php if ( $show_shipping == true ): ?>
				<td>
					<strong><?php _e( "Shipping Address", "mp" ) ?></strong><br>
					{{shipping}}
				</td>
			<?php endif; ?>
		</tr>
		<tr>
			<td colspan="2">{{email}}</td>
		</tr>
	</table>

	<div class="clear"></div>
	<br/>
	<table class="product-detail">
		<thead>
		<tr>
			<th><?php _e( "Product Name", "mp" ) ?></th>
			<th><?php _e( "Quantity", "mp" ) ?></th>
		</tr>
		</thead>
		<tbody>
		{{order_details}}
		</tbody>
	</table>
</div>