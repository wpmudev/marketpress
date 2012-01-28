<?php
/*
MarketPress Table-Rate Shipping Plugin
Author: Nick Bunn (Salty Dog Interactive for Incsub)
Version: 1.1
*/

class MP_Shipping_Table_Rate extends MP_Shipping_API {
	
	//private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
	var $plugin_name = 'table-rate';
	
	//public name of your method, for lists and such.
	var $public_name = '';
	
	//set to true if you need to use the shipping_metabox() method to add per-product shipping options
	var $use_metabox = false;
	
	//set to true if you want to add per-product weight shipping field
	var $use_weight = false;
	
	/**
	 * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
	 */
	function on_creation() {
		//declare here for translation
		$this->public_name = __('Table Rate', 'mp');
	}
	
	/**
   * Echo anything you want to add to the top of the shipping screen
   */
	function before_shipping_form($content) {
		return $content;
  }
  
  /**
   * Echo anything you want to add to the bottom of the shipping screen
   */
	function after_shipping_form($content) {
		return $content;
  }
  
  /**
   * Echo a table row with any extra shipping fields you need to add to the shipping checkout form
   */
	function extra_shipping_field($content) {
		return $content;
  }
  
  /**
   * Use this to process any additional field you may add. Use the $_POST global,
   *  and be sure to save it to both the cookie and usermeta if logged in.
   */
	function process_shipping_form() {

  }
	
	/**
	 * Echo a settings meta box with whatever settings you need for you shipping module.
	 *  Form field names should be prefixed with mp[shipping][plugin_name], like "mp[shipping][plugin_name][mysetting]".
	 *  You can access saved settings via $settings array.
	 */
	function shipping_settings_box($settings) {
		global $mp;
		?>
		<script type="text/javascript">
				jQuery(document).ready(function ($) {
					$("#mp-table-rate-rows").change(function() {
						$("#mp-shipping-form").submit();
					});
				});
		</script>
		<div id="mp_table_rate" class="postbox">
		<h3 class='hndle'><span><?php _e('Table Rate Settings', 'mp'); ?></span></h3>
		<div class="inside">
			<span class="description"><?php _e('Be sure to enter a shipping price for every option or those customers may get free shipping. Each layer must be a higher price than the previous.', 'mp') ?></span>
		<table class="form-table">
		<tr>
			<td scope="row"><?php _e('Number of Layers:', 'mp');?>
				<select name="mp[shipping][table-rate][rowcount]" id="mp-table-rate-rows">
					<?php 
					for ( $k = 1; $k <= 20; $k++ )	
					{	
					?>
						<option value="<?php echo $k; ?>" <?php selected($settings['shipping']['table-rate']['rowcount'], $k); ?>><?php echo $k; ?></option>
					<?php 
					}	
					?>
					</select>
			</td>
		</tr>
		<tr>
			<th>
				<?php _e('Each layer must be a higher price than the one above it.', 'mp') ?>
			</th>
		</tr>
		<?php
		switch ($settings['base_country']) 
		{
				case 'US':
		?>
			<tr>
				<td scope="row">
				<?php _e('If price is ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][0][mincost]" value="0.01" size="5" maxlength="10" disabled="disabled" /><?php _e(' and above.', 'mp'); ?>
				<?php _e('Shipping Cost - Lower 48 States:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][lower_48]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['lower_48']); ?>" size="5" maxlength="10" />
				<?php _e('Hawaii and Alaska:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][hi_ak]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['hi_ak']); ?>" size="5" maxlength="10" />
				<?php _e('Canada:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][canada]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['canada']); ?>" size="5" maxlength="10" />
				<?php _e('International:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][international]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['international']); ?>" size="5" maxlength="10" />
				</td>
			</tr>
			<?php 
			for ( $i = 1; $i < $settings['shipping']['table-rate']['rowcount']; $i++ ) 
			{ 
			?>
			<tr>
					<td scope="row">
					<?php _e('If price is ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][<?php echo $i; ?>][mincost]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['mincost']); ?>" size="5" maxlength="10" /><?php _e(' and above.', 'mp'); ?>
					<?php _e('Shipping Cost - Lower 48 States:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][lower_48]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['lower_48']); ?>" size="5" maxlength="10" />
					<?php _e('Hawaii and Alaska:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][hi_ak]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['hi_ak']); ?>" size="5" maxlength="10" />
					<?php _e('Canada:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][canada]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['canada']); ?>" size="5" maxlength="10" />
					<?php _e('International:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][international]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['international']); ?>" size="5" maxlength="10" />
					</td>
			</tr>
			<?php
			}
			break;
			case 'CA':
					?>
				<tr>
					<td scope="row">
						<?php _e('If price is ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][0][mincost]" value="0.01" size="5" maxlength="10" disabled="disabled" /><?php _e(' and above.', 'mp'); ?>
						<?php _e('Shipping Cost - In Country:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][in_country]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['in_country']); ?>" size="5" maxlength="10" />
						<?php _e('United States:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][usa]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['usa']); ?>" size="5" maxlength="10" />
						<?php _e('International:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][international]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['international']); ?>" size="5" maxlength="10" />
					</td>
				</tr>
				<?php 
				for ( $i = 1; $i < $settings['shipping']['table-rate']['rowcount']; $i++ ) 
				{ 
				?>
					<tr>
						<td scope="row">
							<?php _e('If price is ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][<?php echo $i; ?>][mincost]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['mincost']); ?>" size="5" maxlength="10" /><?php _e(' and above.', 'mp'); ?>
							<?php _e('Shipping Cost - In Country:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][in_country]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['in_country']); ?>" size="5" maxlength="10" />
							<?php _e('United States:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][usa]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['usa']); ?>" size="5" maxlength="10" />
							<?php _e('International:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][international]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['international']); ?>" size="5" maxlength="10" />
						</td>
					</tr>
				<?php
				}
			break;
			default:
				if ( in_array($settings['base_country'], $mp->eu_countries) ) 
				{
				?>
					<tr>
						<td scope="row">
							<?php _e('If price is ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][0][mincost]" value="0.01" size="5" maxlength="10" disabled="disabled" /><?php _e(' and above.', 'mp'); ?>
							<?php _e('Shipping Cost - In Country:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][in_country]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['in_country']); ?>" size="5" maxlength="10" />
							<?php _e('European Union:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][eu]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['eu']); ?>" size="5" maxlength="10" />
							<?php _e('International:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][international]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['international']); ?>" size="5" maxlength="10" />
						</td>
					</tr>
					<?php 
					for ( $i = 1; $i < $settings['shipping']['table-rate']['rowcount']; $i++ ) 
					{ 
					?>
						<tr>
							<td scope="row">
								<?php _e('If price is ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][<?php echo $i; ?>][mincost]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['mincost']); ?>" size="5" maxlength="10" /><?php _e(' and above.', 'mp'); ?>
									<?php _e('Shipping Cost - In Country:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][in_country]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['in_country']); ?>" size="5" maxlength="10" />
								<?php _e('European Union:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][eu]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['eu']); ?>" size="5" maxlength="10" />
								<?php _e('International:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][international]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['international']); ?>" size="5" maxlength="10" />
								</td>
							</tr>
					<?php
					}
				}
				else
				{
					?>
					<tr>
						<td scope="row">
							<?php _e('If price is ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][0][mincost]" value="0.01" size="5" maxlength="10" disabled="disabled" /><?php _e(' and above.', 'mp'); ?>
							<?php _e('Shipping Cost - In Country:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][in_country]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['in_country']); ?>" size="5" maxlength="10" />
							<?php _e('International:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][0][international]" value="<?php echo esc_attr($settings['shipping']['table-rate']['0']['international']); ?>" size="5" maxlength="10" />
						</td>
					</tr>
					<?php 
					for ( $i = 1; $i < $settings['shipping']['table-rate']['rowcount']; $i++ ) 
					{ 
					?>
						<tr>
							<td scope="row">
								<?php _e('If price is ', 'mp'); echo $mp->format_currency(); ?><input type="text" name="mp[shipping][table-rate][<?php echo $i; ?>][mincost]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['mincost']); ?>" size="5" maxlength="10" /><?php _e(' and above.', 'mp'); ?>
									<?php _e('Shipping Cost - In Country:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][in_country]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['in_country']); ?>" size="5" maxlength="10" />
								<?php _e('International:', 'mp'); echo $mp->format_currency(); ?><input on type="text" name="mp[shipping][table-rate][<?php echo $i?>][international]" value="<?php echo esc_attr($settings['shipping']['table-rate'][$i]['international']); ?>" size="5" maxlength="10" />
								</td>
							</tr>
					<?php
					}
				}
			}
					?>
				</table>
		</div>
		</div>
		<?php
	}
	
	/**
	 * Filters posted data from your form. Do anything you need to the $settings['shipping']['plugin_name']
	 *  array. Don't forget to return!
	 */
	function process_shipping_settings($settings) {
	
		return $settings;
	}
	
	/**
	 * Echo any per-product shipping fields you need to add to the product edit screen shipping metabox
	 *
	 * @param array $shipping_meta, the contents of the post meta. Use to retrieve any previously saved product meta
	 * @param array $settings, access saved settings via $settings array.
	 */
	function shipping_metabox($shipping_meta, $settings) {

	}
	
	/**
	 * Save any per-product shipping fields from the shipping metabox using update_post_meta
	 *
	 * @param array $shipping_meta, save anything from the $_POST global
	 * return array $shipping_meta
	 */
	function save_shipping_metabox($shipping_meta) {

		return $shipping_meta;
	}
	
	/**
		* Use this function to return your calculated price as an integer or float
		*
		* @param int $price, always 0. Modify this and return
		* @param float $total, cart total after any coupons and before tax
		* @param array $cart, the contents of the shopping cart for advanced calculations
		* @param string $address1
		* @param string $address2
		* @param string $city
		* @param string $state, state/province/region
		* @param string $zip, postal code
		* @param string $country, ISO 3166-1 alpha-2 country code
		* @param string $selected_option, if a calculated shipping module, passes the currently selected sub shipping option if set
		*
		* return float $price
		*/
	function calculate_shipping($price, $total, $cart, $address1, $address2, $city, $state, $zip, $country, $selected_option) {
		global $mp;
		$settings = get_option('mp_settings');
		
		switch ($settings['base_country']) 
		{
			case 'US':
			for ($i = $settings['shipping']['table-rate']['rowcount'] - 1; $i >= 0; $i--) 
				{
					if ($total >= $settings['shipping']['table-rate'][$i]['mincost']) 
					{
						if ($country == 'US') 
						{
							if($state == 'HI' || $state == 'AK')
								$price = $settings['shipping']['table-rate'][$i]['hi_ak'];	
							else
								$price = $settings['shipping']['table-rate'][$i]['lower_48'];
						}
						else if ($country == 'CA')
							$price = $settings['shipping']['table-rate'][$i]['canada'];
						else
							$price = $settings['shipping']['table-rate'][$i]['international'];
					break;
					}
				}
		break;
		case 'CA':
			for ($i = $settings['shipping']['table-rate']['rowcount'] - 1; $i >= 0; $i--) 
				{
				if ($total >= $settings['shipping']['table-rate'][$i]['mincost']) 
					{
					if ($country == 'CA') 
						$price = $settings['shipping']['table-rate'][$i]['in_country'];
					else if ($country == 'US')
						$price = $settings['shipping']['table-rate'][$i]['usa'];
					else
						$price = $settings['shipping']['table-rate'][$i]['international'];
				}
				break;
				}
		break;
		default:
			for ($i = $settings['shipping']['table-rate']['rowcount'] - 1; $i >= 0; $i--) 
				{
				if ($total >= $settings['shipping']['table-rate'][$i]['mincost']) 
					{
					if ( in_array($settings['base_country'], $mp->eu_countries) )  //in european union
						{
						if ($country == $settings['base_country']) 
							$price = $settings['shipping']['table-rate'][$i]['in_country'];
						else if (in_array($country, $mp->eu_countries))
							$price = $settings['shipping']['table-rate'][$i]['eu'];
						else
							$price = $settings['shipping']['table-rate'][$i]['international'];
						}
						else  //all other countries
						{
						if ($country == $settings['base_country']) 
							$price = $settings['shipping']['table-rate'][$i]['in_country'];
						else
							$price = $settings['shipping']['table-rate'][$i]['international'];
					}
					break;
					}
			}
		}
	
		return $price;
	}

}

//register plugin - uncomment to register
mp_register_shipping_plugin( 'MP_Shipping_Table_Rate', 'table-rate', __('Table Rate', 'mp') );
?>