<?php
/*
MarketPress Flat-Rate Shipping Plugin
Author: Aaron Edwards (Incsub)
*/

class MP_Shipping_Flat_Rate extends MP_Shipping_API {

  //private shipping method name. Lowercase alpha (a-z) and dashes (-) only please!
  var $plugin_name = 'flat-rate';
  
  //public name of your method, for lists and such.
  var $public_name = '';
  
  //set to true if you need to use the shipping_metabox() method to add per-product shipping options
  var $use_metabox = true;

  /**
   * Runs when your class is instantiated. Use to setup your plugin instead of __construct()
   */
  function on_creation() {
    //set name here to be able to translate
    $this->public_name = __('Flat Rate', 'mp');
	}

  /**
   * Echo anything you want to add to the top of the shipping screen
   */
	function before_shipping_form() {

  }
  
  /**
   * Echo anything you want to add to the bottom of the shipping screen
   */
	function after_shipping_form() {

  }
  
  /**
   * Echo a table row with any extra shipping fields you need to add to the shipping checkout form
   */
	function extra_shipping_field() {

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
    <div id="mp_flat_rate" class="postbox">
      <h3 class='hndle'><span><?php _e('Flat Rate Settings', 'mp'); ?></span></h3>
      <div class="inside">
        <span class="description"><?php _e('Be sure to enter a shipping price for every option or those customers may get free shipping.', 'mp') ?></span>
        <table class="form-table">
    <?php
    switch ($settings['base_country']) {
      case 'US':
        ?>
          <tr>
  				<th scope="row"><?php _e('Lower 48 States', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][lower_48]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['lower_48']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
          <tr>
  				<th scope="row"><?php _e('Hawaii and Alaska', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][hi_ak]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['hi_ak']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
          <tr>
  				<th scope="row"><?php _e('Canada', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][canada]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['canada']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
          <tr>
  				<th scope="row"><?php _e('International', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][international]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['international']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
        <?php
        break;

      case 'CA':
        ?>
          <tr>
  				<th scope="row"><?php _e('In Country', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][in_country]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['in_country']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
          <tr>
  				<th scope="row"><?php _e('United States', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][usa]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['usa']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
          <tr>
  				<th scope="row"><?php _e('International', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][international]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['international']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
        <?php
        break;

      default:
        //in european union
        if ( in_array($settings['base_country'], $mp->eu_countries) ) {
          ?>
          <tr>
  				<th scope="row"><?php _e('In Country', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][in_country]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['in_country']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
          <tr>
  				<th scope="row"><?php _e('European Union', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][eu]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['eu']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
          <tr>
  				<th scope="row"><?php _e('International', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][international]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['international']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
          <?php
        } else { //all other countries
          ?>
          <tr>
  				<th scope="row"><?php _e('In Country', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][in_country]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['in_country']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
          <tr>
  				<th scope="row"><?php _e('International', 'mp') ?></th>
  				<td>
  				<?php echo $mp->format_currency(); ?><input type="text" name="mp[shipping][flat-rate][international]" value="<?php echo esc_attr($settings['shipping']['flat-rate']['international']); ?>" size="5" maxlength="10" />
    			</td>
          </tr>
          <?php
        }
        break;
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
    global $mp;
    ?>
    <label><?php _e('Extra Shipping Cost', 'mp'); ?>:<br />
    <?php echo $mp->format_currency(); ?><input type="text" size="6" id="mp_extra_shipping_cost" name="mp_extra_shipping_cost" value="<?php echo ($shipping_meta['extra_cost']) ? $mp->display_currency($shipping_meta['extra_cost']) : '0.00'; ?>" />
    </label>
    <?php
  }

  /**
   * Save any per-product shipping fields from the shipping metabox using update_post_meta
   *
   * @param array $shipping_meta, save anything from the $_POST global
   * return array $shipping_meta
   */
	function save_shipping_metabox($shipping_meta) {
    //process extra per item shipping
    $shipping_meta['extra_cost'] = (!empty($_POST['mp_extra_shipping_cost'])) ? round($_POST['mp_extra_shipping_cost'], 2) : 0;

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
   *
   * return float $price
   */
  function calculate_shipping($price, $total, $cart, $address1, $address2, $city, $state, $zip, $country) {
    global $mp;
    $settings = get_option('mp_settings');
    
    //don't charge shipping if only digital products
    if ( $mp->download_only_cart($cart) )
      return 0;
    
    switch ($settings['base_country']) {
      case 'US':
        if ($country == 'US') {
          //price based on state
          if ($state == 'HI' || $state == 'AK')
            $price = $settings['shipping']['flat-rate']['hi_ak'];
          else
            $price = $settings['shipping']['flat-rate']['lower_48'];
        } else if ($country == 'CA') {
          $price = $settings['shipping']['flat-rate']['canada'];
        } else {
          $price = $settings['shipping']['flat-rate']['international'];
        }
        break;
      
      case 'CA':
        if ($country == 'CA') {
          $price = $settings['shipping']['flat-rate']['in_country'];
        } else if ($country == 'US') {
          $price = $settings['shipping']['flat-rate']['usa'];
        } else {
          $price = $settings['shipping']['flat-rate']['international'];
        }
        break;

      default:
        //in european union
        if ( in_array($settings['base_country'], $mp->eu_countries) ) {
          if ($country == $settings['base_country']) {
            $price = $settings['shipping']['flat-rate']['in_country'];
          } else if (in_array($country, $mp->eu_countries)) {
            $price = $settings['shipping']['flat-rate']['eu'];
          } else {
            $price = $settings['shipping']['flat-rate']['international'];
          }
        } else { //all other countries
          if ($country == $settings['base_country']) {
            $price = $settings['shipping']['flat-rate']['in_country'];
          } else {
            $price = $settings['shipping']['flat-rate']['international'];
          }
        }
        break;
    }

    //calculate extra shipping
    $extras = array();
    foreach ($cart as $product_id => $variations) {
	    $shipping_meta = get_post_meta($product_id, 'mp_shipping', true);
			foreach ($variations as $variation => $data) {
			  if (!$data['download'])
	      	$extras[] = $shipping_meta['extra_cost'] * $data['quantity'];
			}
    }
    $extra = array_sum($extras);

    //merge
    $price = round($price + $extra, 2);
    
    return $price;
  }
}

//register plugin
mp_register_shipping_plugin( 'MP_Shipping_Flat_Rate', 'flat-rate', __('Flat Rate', 'mp') );
?>