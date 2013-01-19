<?php
/*
MarketPress Template Functions

Relevant functions for custom template files:

mp_product.php
  mp_product_image
  mp_product_price
  mp_buy_button
  mp_category_list

mp_cart.php
  mp_show_cart

mp_orderstatus.php
  mp_order_status

mp_productlist.php
  mp_products_filter
  mp_list_products
*/

/**
 * Display product tag cloud.
 *
 * The text size is set by the 'smallest' and 'largest' arguments, which will
 * use the 'unit' argument value for the CSS text size unit. The 'format'
 * argument can be 'flat' (default), 'list', or 'array'. The flat value for the
 * 'format' argument will separate tags with spaces. The list value for the
 * 'format' argument will format the tags in a UL HTML list. The array value for
 * the 'format' argument will return in PHP array type format.
 *
 * The 'orderby' argument will accept 'name' or 'count' and defaults to 'name'.
 * The 'order' is the direction to sort, defaults to 'ASC' and can be 'DESC'.
 *
 * The 'number' argument is how many tags to return. By default, the limit will
 * be to return the top 45 tags in the tag cloud list.
 *
 * The 'topic_count_text_callback' argument is a function, which, given the count
 * of the posts  with that tag, returns a text for the tooltip of the tag link.
 *
 * The 'exclude' and 'include' arguments are used for the {@link get_tags()}
 * function. Only one should be used, because only one will be used and the
 * other ignored, if they are both set.
 *
 * @param bool $echo Optional. Whether or not to echo.
 * @param array|string $args Optional. Override default arguments.
 */
function mp_tag_cloud($echo = true, $args = array()) {

  $args['echo'] = false;
  $args['taxonomy'] = 'product_tag';

  $cloud = '<div id="mp_tag_cloud">' . wp_tag_cloud( $args ) . '</div>';

  if ($echo)
    echo $cloud;
  else
    return $cloud;
}


/**
 * Display or retrieve the HTML list of product categories.
 *
 * The list of arguments is below:
 *     'show_option_all' (string) - Text to display for showing all categories.
 *     'orderby' (string) default is 'ID' - What column to use for ordering the
 * categories.
 *     'order' (string) default is 'ASC' - What direction to order categories.
 *     'show_last_update' (bool|int) default is 0 - See {@link
 * walk_category_dropdown_tree()}
 *     'show_count' (bool|int) default is 0 - Whether to show how many posts are
 * in the category.
 *     'hide_empty' (bool|int) default is 1 - Whether to hide categories that
 * don't have any posts attached to them.
 *     'use_desc_for_title' (bool|int) default is 1 - Whether to use the
 * description instead of the category title.
 *     'feed' - See {@link get_categories()}.
 *     'feed_type' - See {@link get_categories()}.
 *     'feed_image' - See {@link get_categories()}.
 *     'child_of' (int) default is 0 - See {@link get_categories()}.
 *     'exclude' (string) - See {@link get_categories()}.
 *     'exclude_tree' (string) - See {@link get_categories()}.
 *     'current_category' (int) - See {@link get_categories()}.
 *     'hierarchical' (bool) - See {@link get_categories()}.
 *     'title_li' (string) - See {@link get_categories()}.
 *     'depth' (int) - The max depth.
 *
 * @param bool $echo Optional. Whether or not to echo.
 * @param string|array $args Optional. Override default arguments.
 */
function mp_list_categories( $echo = true, $args = '' ) {
  $args['taxonomy'] = 'product_category';
  $args['echo'] = false;

  $list = '<ul id="mp_category_list">' . wp_list_categories( $args ) . '</ul>';

  if ($echo)
    echo $list;
  else
    return $list;
}


/**
 * Display or retrieve the HTML dropdown list of product categories.
 *
 * The list of arguments is below:
 *     'show_option_all' (string) - Text to display for showing all categories.
 *     'show_option_none' (string) - Text to display for showing no categories.
 *     'orderby' (string) default is 'ID' - What column to use for ordering the
 * categories.
 *     'order' (string) default is 'ASC' - What direction to order categories.
 *     'show_last_update' (bool|int) default is 0 - See {@link get_categories()}
 *     'show_count' (bool|int) default is 0 - Whether to show how many posts are
 * in the category.
 *     'hide_empty' (bool|int) default is 1 - Whether to hide categories that
 * don't have any posts attached to them.
 *     'child_of' (int) default is 0 - See {@link get_categories()}.
 *     'exclude' (string) - See {@link get_categories()}.
 *     'depth' (int) - The max depth.
 *     'tab_index' (int) - Tab index for select element.
 *     'name' (string) - The name attribute value for select element.
 *     'id' (string) - The ID attribute value for select element. Defaults to name if omitted.
 *     'class' (string) - The class attribute value for select element.
 *     'selected' (int) - Which category ID is selected.
 *     'taxonomy' (string) - The name of the taxonomy to retrieve. Defaults to category.
 *
 * The 'hierarchical' argument, which is disabled by default, will override the
 * depth argument, unless it is true. When the argument is false, it will
 * display all of the categories. When it is enabled it will use the value in
 * the 'depth' argument.
 *
 *
 * @param bool $echo Optional. Whether or not to echo.
 * @param string|array $args Optional. Override default arguments.
 */
function mp_dropdown_categories( $echo = true, $args = '' ) {
  $args['taxonomy'] = 'product_category';
  $args['echo'] = false;
  $args['id'] = 'mp_category_dropdown';

  $dropdown = wp_dropdown_categories( $args );
  $dropdown .= '<script type="text/javascript">
/* <![CDATA[ */
	var dropdown = document.getElementById("mp_category_dropdown");
	function onCatChange() {
		if ( dropdown.options[dropdown.selectedIndex].value > 0 ) {
			location.href = "'.get_home_url().'/?product_category="+dropdown.options[dropdown.selectedIndex].value;
		}
	}
	dropdown.onchange = onCatChange;
/* ]]> */
</script>';
  
  if ($echo)
    echo $dropdown;
  else
    return $dropdown;
}

/**
 * Displays a list of popular products ordered by sales.
 *
 * @param bool $echo Optional, whether to echo or return
 * @param int $num Optional, max number of products to display. Defaults to 5
 */
function mp_popular_products( $echo = true, $num = 5 ) {
  //The Query
  $custom_query = new WP_Query('post_type=product&post_status=publish&posts_per_page='.intval($num).'&meta_key=mp_sales_count&meta_compare=>&meta_value=0&orderby=meta_value&order=DESC');

  $content = '<ul id="mp_popular_products">';

  if (count($custom_query->posts)) {
    foreach ($custom_query->posts as $post) {
      $content .= '<li><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></li>';
    }
  } else {
    $content .= '<li>' . __('No Products', 'mp') . '</li>';
  }

  $content .= '</ul>';

  if ($echo)
    echo $content;
  else
    return $content;
}


//Prints cart table, for internal use
function _mp_cart_table($type = 'checkout', $echo = false) {
  global $mp, $blog_id;
  $blog_id = (is_multisite()) ? $blog_id : 1;
  $current_blog_id = $blog_id;

	$global_cart = $mp->get_cart_contents(true);
  if (!$mp->global_cart)  //get subset if needed
  	$selected_cart[$blog_id] = $global_cart[$blog_id];
  else
    $selected_cart = $global_cart;

  $content = '';
  if ($type == 'checkout-edit') {
    $content .= apply_filters( 'mp_cart_updated_msg', '' );

    $content .= '<form id="mp_cart_form" method="post" action="">';
    $content .= '<table class="mp_cart_contents"><thead><tr>';
    $content .= '<th class="mp_cart_col_product" colspan="2">'.__('Item:', 'mp').'</th>';
    $content .= '<th class="mp_cart_col_price">'.__('Price:', 'mp').'</th>';
    $content .= '<th class="mp_cart_col_quant">'.__('Quantity:', 'mp').'</th></tr></thead><tbody>';

    $totals = array();
    $shipping_prices = array();
    $shipping_tax_prices = array();
    $tax_prices = array();
    foreach ($selected_cart as $bid => $cart) {

			if (is_multisite())
        switch_to_blog($bid);

      foreach ($cart as $product_id => $variations) {
        foreach ($variations as $variation => $data) {
          $totals[] = $mp->before_tax_price($data['price'], $product_id) * $data['quantity'];

          $content .=  '<tr>';
          $content .=  '  <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id, 50 ) . '</td>';
          $content .=  '  <td class="mp_cart_col_product_table"><a href="' . apply_filters('mp_product_url_display_in_cart', $data['url'], $product_id) . '">' . apply_filters('mp_product_name_display_in_cart', $data['name'], $product_id) . '</a>' . '</td>'; // Added WPML
          $content .=  '  <td class="mp_cart_col_price">' . $mp->format_currency('', $data['price'] * $data['quantity']) . '</td>';
          $content .=  '  <td class="mp_cart_col_quant"><input type="text" size="2" name="quant[' . $bid . ':' . $product_id . ':' . $variation . ']" value="' . $data['quantity'] . '" />&nbsp;<label><input type="checkbox" name="remove[]" value="' . $bid . ':' . $product_id . ':' . $variation . '" /> ' . __('Remove', 'mp') . '</label></td>';
          $content .=  '</tr>';
        }
      }

      if ( ($shipping_price = $mp->shipping_price()) !== false )
        $shipping_prices[] = $shipping_price;

      if ( ($shipping_tax_price = $mp->shipping_tax_price($shipping_price)) !== false )
        $shipping_tax_prices[] = $shipping_tax_price;
      
      if ( ($tax_price = $mp->tax_price()) !== false )
        $tax_prices[] = $tax_price;
    }
    //go back to original blog
    if (is_multisite())
      switch_to_blog($current_blog_id);

    $total = array_sum($totals);

    //coupon line TODO - figure out how to apply them on global checkout
	  $coupon_code = $mp->get_coupon_code();
    if ( $coupon = $mp->coupon_value($coupon_code, $total) ) {
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="2">' . __('Subtotal:', 'mp') . '</td>';
      $content .=  '  <td class="mp_cart_col_subtotal">' . $mp->format_currency('', $total) . '</td>';
      $content .=  '  <td>&nbsp;</td>';
      $content .=  '</tr>';
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="2">' . __('Discount:', 'mp') . '</td>';
      $content .=  '  <td class="mp_cart_col_discount">' . $coupon['discount'] . '</td>';
      $content .=  '  <td class="mp_cart_remove_coupon"><a href="?remove_coupon=1">' . __('Remove Coupon &raquo;', 'mp') . '</a></td>';
      $content .=  '</tr>';
      $total = $coupon['new_total'];
    } else {
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="4">
            <a id="coupon-link" class="alignright" href="#coupon-code">' . __('Have a coupon code?', 'mp') . '</a>
            <div id="coupon-code" class="alignright" style="display: none;">
              <label for="coupon_code">' . __('Enter your code:', 'mp') . '</label>
              <input type="text" name="coupon_code" id="coupon_code" />
              <input type="submit" name="update_cart_submit" value="' . __('Apply &raquo;', 'mp') . '" />
            </div>
        </td>';
      $content .=  '</tr>';
    }

    //shipping line
    if ( $shipping_price = array_sum($shipping_prices) ) {
      $shipping_tax_price = array_sum($shipping_tax_prices);
      if (!$mp->global_cart && apply_filters( 'mp_shipping_method_lbl', '' ))
        $shipping_method = apply_filters( 'mp_shipping_method_lbl', '' );
      else
        $shipping_method = '';
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="2">' . __('Shipping:', 'mp') . '</td>';
      $content .=  '  <td class="mp_cart_col_shipping">' . $mp->format_currency('', $shipping_tax_price) . '</td>';
      $content .=  '  <td>' . $shipping_method . '</td>';
      $content .=  '</tr>';
      $total = $total + $shipping_price;
    }

    //tax line
    if ( $tax_price = array_sum($tax_prices) ) {
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="2">' . __('Taxes:', 'mp') . '</td>';
      $content .=  '  <td class="mp_cart_col_tax">' . $mp->format_currency('', $tax_price) . '</td>';
      $content .=  '  <td>&nbsp;</td>';
      $content .=  '</tr>';
      $total = $total + $tax_price;
    }

    $content .=  '</tbody><tfoot><tr>';
    $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="2">' . __('Cart Total:', 'mp') . '</td>';
    $content .=  '  <td class="mp_cart_col_total">' . $mp->format_currency('', $total) . '</td>';
    $content .=  '  <td class="mp_cart_col_updatecart"><input type="submit" name="update_cart_submit" value="' . __('Update Cart &raquo;', 'mp') . '" /></td>';
    $content .=  '</tr></tfoot>';

    $content .= '</table></form>';

  } else if ($type == 'checkout') {

    $content .= '<table class="mp_cart_contents"><thead><tr>';
    $content .= '<th class="mp_cart_col_product" colspan="2">'.__('Item:', 'mp').'</th>';
    $content .= '<th class="mp_cart_col_quant">'.__('Qty:', 'mp').'</th>';
    $content .= '<th class="mp_cart_col_price">'.__('Price:', 'mp').'</th></tr></thead><tbody>';

    $totals = array();
    $shipping_prices = array();
    $shipping_tax_prices = array();
    $tax_prices = array();
    foreach ($selected_cart as $bid => $cart) {

			if (is_multisite())
        switch_to_blog($bid);

      foreach ($cart as $product_id => $variations) {
        foreach ($variations as $variation => $data) {
          $totals[] = $mp->before_tax_price($data['price'], $product_id) * $data['quantity'];

          $content .=  '<tr>';
          $content .=  '  <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id, 75 ) . '</td>';
          $content .=  '  <td class="mp_cart_col_product_table"><a href="' . apply_filters('mp_product_url_display_in_cart', $data['url'], $product_id) . '">' . apply_filters('mp_product_name_display_in_cart', $data['name'], $product_id) . '</a>';

          // FPM: Output product custom field information
          $cf_key = $bid .':'. $product_id .':'. $variation;
          if (isset($_SESSION['mp_shipping_info']['mp_custom_fields'][$cf_key])) {
            $cf_item = $_SESSION['mp_shipping_info']['mp_custom_fields'][$cf_key];
    
            $mp_custom_field_label 		= get_post_meta($product_id, 'mp_custom_field_label', true);
            if (isset($mp_custom_field_label[$variation]))
              $label_text = $mp_custom_field_label[$variation];
            else
              $label_text = __('Product Extra Fields:', 'mp');
            
            $content .=  '<div class="mp_cart_custom_fields">'. $label_text .'<br /><ol>';
            foreach($cf_item as $item) {
              $content .=  '<li>'. $item .'</li>';
            }
            $content .=  '</ol></div>';
          }
          $content .=  '</td>'; // Added WPML

          $content .=  '  <td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
          $content .=  '  <td class="mp_cart_col_price">' . $mp->format_currency('', $data['price'] * $data['quantity']) . '</td>';
          $content .=  '</tr>';
        }
      }

      if ( ($shipping_price = $mp->shipping_price()) !== false )
        $shipping_prices[] = $shipping_price;
      
      if ( ($shipping_tax_price = $mp->shipping_tax_price($shipping_price)) !== false )
        $shipping_tax_prices[] = $shipping_tax_price; 

      if ( ($tax_price = $mp->tax_price()) !== false )
        $tax_prices[] = $tax_price;
    }
    //go back to original blog
    if (is_multisite())
      switch_to_blog($current_blog_id);

    $total = array_sum($totals);

    //coupon line TODO - figure out how to apply them on global checkout
	  $coupon_code = $mp->get_coupon_code();
    if ( $coupon = $mp->coupon_value($coupon_code, $total) ) {
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="3">' . __('Subtotal:', 'mp') . '</td>';
      $content .=  '  <td class="mp_cart_col_subtotal">' . $mp->format_currency('', $total) . '</td>';
      $content .=  '</tr>';
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="3">' . __('Discount:', 'mp') . '</td>';
      $content .=  '  <td class="mp_cart_col_discount">' . $coupon['discount'] . '</td>';
      $content .=  '</tr>';
      $total = $coupon['new_total'];
    }

    //shipping line
    if ( $shipping_price = array_sum($shipping_prices) ) {
      $shipping_tax_price = array_sum($shipping_tax_prices);
      if (!$mp->global_cart && apply_filters( 'mp_shipping_method_lbl', '' ))
        $shipping_method = ' (' . apply_filters( 'mp_shipping_method_lbl', '' ) . ')';
      else
        $shipping_method = '';
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="3">' . __('Shipping:', 'mp') . $shipping_method . '</td>';
      $content .=  '  <td class="mp_cart_col_shipping">' . $mp->format_currency('', $shipping_tax_price) . '</td>';
      $content .=  '</tr>';
      $total = $total + $shipping_price;
    }

    //tax line
    if ( $tax_price = array_sum($tax_prices) ) {
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="3">' . __('Taxes:', 'mp') . '</td>';
      $content .=  '  <td class="mp_cart_col_tax">' . $mp->format_currency('', $tax_price) . '</td>';
      $content .=  '</tr>';
      $total = $total + $tax_price;
    }

    $content .=  '<tr>';
    $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="3">' . __('Cart Total:', 'mp') . '</td>';
    $content .=  '  <td class="mp_cart_col_total">' . $mp->format_currency('', $total) . '</td>';
    $content .=  '</tr>';

    $content .= '</tbody></table>';

  } else if ($type == 'widget') {

    $content .= '<table class="mp_cart_contents_widget"><thead><tr>';
    $content .= '<th class="mp_cart_col_product" colspan="2">'.__('Item:', 'mp').'</th>';
    $content .= '<th class="mp_cart_col_quant">'.__('Qty:', 'mp').'</th>';
    $content .= '<th class="mp_cart_col_price">'.__('Price:', 'mp').'</th></tr></thead><tbody>';

    $totals = array();
    foreach ($selected_cart as $bid => $cart) {

			if (is_multisite())
        switch_to_blog($bid);

      foreach ($cart as $product_id => $variations) {
        foreach ($variations as $variation => $data) {
          $totals[] = $data['price'] * $data['quantity'];
          $content .=  '<tr>';
          $content .=  '  <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id, 25 ) . '</td>';
          $content .=  '  <td class="mp_cart_col_product_table"><a href="' . apply_filters('mp_product_url_display_in_cart', $data['url'], $product_id) . '">' . apply_filters('mp_product_name_display_in_cart', $data['name'], $product_id) . '</a>' . '</td>'; // Added WPML
          $content .=  '  <td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
          $content .=  '  <td class="mp_cart_col_price">' . $mp->format_currency('', $data['price'] * $data['quantity']) . '</td>';
          $content .=  '</tr>';
        }
      }
    }

		if (is_multisite())
      switch_to_blog($current_blog_id);

    $total = array_sum($totals);

    $content .=  '<tr>';
    $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="3">' . __('Subtotal:', 'mp') . '</td>';
    $content .=  '  <td class="mp_cart_col_total">' . $mp->format_currency('', $total) . '</td>';
    $content .=  '</tr>';

    $content .= '</tbody></table>';
  }

  if ($echo) {
    echo $content;
  } else {
    return $content;
  }
}

//Prints cart login/register form, for internal use
function _mp_cart_login($echo = false) {
  global $mp;
  
  $content = '';
  //don't show if logged in
  if ( is_user_logged_in() || defined('MP_HIDE_LOGIN_OPTION') ) {
    $content .= '<p class="mp_cart_direct_checkout">';
    $content .= '<a class="mp_cart_direct_checkout_link" href="'.mp_checkout_step_url('shipping').'">'.__('Checkout Now &raquo;', 'mp').'</a>';
    $content .= '</p>';
  } else {
    $content .= '<table class="mp_cart_login">';
    $content .= '<thead><tr>';
    $content .= '<th class="mp_cart_login">'.__('Have a User Account?', 'mp').'</th>';
    $content .= '<th>&nbsp;</th>';
    if ($mp->get_setting('force_login'))
      $content .= '<th>'.__('Register To Checkout', 'mp').'</th>';
		else
      $content .= '<th>'.__('Checkout Directly', 'mp').'</th>';
    $content .= '</tr></thead>';
    $content .= '<tbody>';
    $content .= '<tr>';
    $content .= '<td class="mp_cart_login">';
    $content .= '<form name="loginform" id="loginform" action="' . wp_login_url() .'" method="post">';
    $content .= '<label>'.__('Username', 'mp').'<br />';
    $content .= '<input type="text" name="log" id="user_login" class="input" value="" size="20" /></label>';
    $content .= '<br />';
    $content .= '<label>'.__('Password', 'mp').'<br />';
    $content .= '<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" /></label>';
    $content .= '<br />';
    $content .= '<input type="submit" name="wp-submit" id="mp_login_submit" value="'.__('Login and Checkout &raquo;', 'mp').'" />';
    $content .= '<input type="hidden" name="redirect_to" value="'.mp_checkout_step_url('shipping').'" />';
    $content .= '</form>';
    $content .= '</td>';
    $content .= '<td class="mp_cart_or_label">'.__('or', 'mp').'</td>';
    $content .= '<td class="mp_cart_checkout">';
    if ($mp->get_setting('force_login'))
    	$content .= apply_filters('register', '<a class="mp_cart_direct_checkout_link" href="'.site_url('wp-login.php?action=register', 'login').'">'.__('Register Now To Checkout &raquo;', 'mp').'</a>');
		else
      $content .= '<a class="mp_cart_direct_checkout_link" href="' . mp_checkout_step_url('shipping') . '">' . __('Checkout Now &raquo;', 'mp') . '</a>';
		$content .= '</td>';
    $content .= '</tr>';
    $content .= '</tbody>';
    $content .= '</table>';
  }
  if ($echo)
    echo  $content;
  else
    return $content;
}

//Prints cart shipping form, for internal use
function _mp_cart_shipping($editable = false, $echo = false) {
  global $mp, $current_user;

  $meta = get_user_meta($current_user->ID, 'mp_shipping_info', true);
  //get address
  $email = (!empty($_SESSION['mp_shipping_info']['email'])) ? $_SESSION['mp_shipping_info']['email'] : (isset($meta['email']) ? $meta['email']: $current_user->user_email);
  $name = (!empty($_SESSION['mp_shipping_info']['name'])) ? $_SESSION['mp_shipping_info']['name'] : (isset($meta['name']) ? $meta['name'] : $current_user->user_firstname . ' ' . $current_user->user_lastname);
  $address1 = (!empty($_SESSION['mp_shipping_info']['address1'])) ? $_SESSION['mp_shipping_info']['address1'] : $meta['address1'];
  $address2 = (!empty($_SESSION['mp_shipping_info']['address2'])) ? $_SESSION['mp_shipping_info']['address2'] : $meta['address2'];
  $city = (!empty($_SESSION['mp_shipping_info']['city'])) ? $_SESSION['mp_shipping_info']['city'] : $meta['city'];
  $state = (!empty($_SESSION['mp_shipping_info']['state'])) ? $_SESSION['mp_shipping_info']['state'] : $meta['state'];
  $zip = (!empty($_SESSION['mp_shipping_info']['zip'])) ? $_SESSION['mp_shipping_info']['zip'] : $meta['zip'];
  $country = (!empty($_SESSION['mp_shipping_info']['country'])) ? $_SESSION['mp_shipping_info']['country'] : $meta['country'];
  if (!$country)
    $country = $mp->get_setting('base_country', 'US');
  $phone = (!empty($_SESSION['mp_shipping_info']['phone'])) ? $_SESSION['mp_shipping_info']['phone'] : $meta['phone'];
  $special_instructions = (!empty($_SESSION['mp_shipping_info']['special_instructions'])) ? $_SESSION['mp_shipping_info']['special_instructions'] : '';

  $content = '';
  //don't show if logged in
  if ( !is_user_logged_in() && !defined('MP_HIDE_LOGIN_OPTION') && $editable) {
    $content .= '<p class="mp_cart_login_msg">';
    $content .= __('Made a purchase here before?', 'mp').' <a class="mp_cart_login_link" href="'.wp_login_url(mp_checkout_step_url('shipping')).'">'.__('Login now to retrieve your saved info &raquo;', 'mp').'</a>';
    $content .= '</p>';
  }

  if ($editable) {
    $content .= '<form id="mp_shipping_form" method="post" action="">';

    $content .= apply_filters( 'mp_checkout_before_shipping', '' );

    $content .= '<table class="mp_cart_shipping">';
    $content .= '<thead><tr>';
    $content .= '<th colspan="2">'.($mp->download_only_cart($mp->get_cart_contents() && !$mp->global_cart) ? __('Enter Your Checkout Information:', 'mp') : __('Enter Your Shipping Information:', 'mp')).'</th>';
    $content .= '</tr></thead>';
    $content .= '<tbody>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Email:', 'mp').'*</td><td>';
    $content .= apply_filters( 'mp_checkout_error_email', '' );
    $content .= '<input size="35" name="email" type="text" value="'.esc_attr($email).'" /></td>';
    $content .= '</tr>';
    
    if ((!$mp->download_only_cart($mp->get_cart_contents()) || $mp->global_cart) && $mp->get_setting('shipping->method') != 'none') {
      $content .= '<tr>';
      $content .= '<td align="right">'. __('Full Name:', 'mp').'*</td><td>';
      $content .= apply_filters( 'mp_checkout_error_name', '' );
      $content .= '<input size="35" name="name" type="text" value="'.esc_attr($name).'" /> </td>';
      $content .= '</tr>';
      $content .= '<tr>';
      $content .= '<td align="right">'.__('Country:', 'mp').'*</td><td>';
      $content .= apply_filters( 'mp_checkout_error_country', '' );
      $content .= '<select id="mp_country" name="country" class="mp_shipping_field">';
      foreach ($mp->get_setting('shipping->allowed_countries', array()) as $code) {
        $content .= '<option value="'.$code.'"'.selected($country, $code, false).'>'.esc_attr($mp->countries[$code]).'</option>';
      }
      $content .= '</select>';
      $content .= '</td>';
      $content .= '</tr>';
      $content .= '<tr>';
      $content .= '<td align="right">'. __('Address:', 'mp').'*</td><td>';
      $content .= apply_filters( 'mp_checkout_error_address1', '' );
      $content .= '<input size="45" name="address1" type="text" value="'.esc_attr($address1).'" /><br />';
      $content .= '<small><em>'. __('Street address, P.O. box, company name, c/o', 'mp').'</em></small>';
      $content .= '</td>';
      $content .= '</tr>';
      $content .= '<tr>';
      $content .= '<td align="right">'. __('Address 2:', 'mp').'&nbsp;</td><td>';
      $content .= '<input size="45" name="address2" type="text" value="'.esc_attr($address2).'" /><br />';
      $content .= '<small><em>'.__('Apartment, suite, unit, building, floor, etc.', 'mp').'</em></small>';
      $content .= '</td>';
      $content .= '</tr>';
      $content .= '<tr>';
      $content .= '<td align="right">'.__('City:', 'mp').'*</td><td>';
      $content .= apply_filters( 'mp_checkout_error_city', '' );
      $content .= '<input size="25" id="mp_city" class="mp_shipping_field" name="city" type="text" value="'.esc_attr($city).'" /></td>';
      $content .= '</tr>';
      $content .= '<tr>';
      $content .= '<td align="right">'.__('State/Province/Region:', 'mp') . (($country == 'US' || $country == 'CA') ? '*' : '') . '</td><td id="mp_province_field">';
      $content .= apply_filters( 'mp_checkout_error_state', '' );
      $content .= mp_province_field($country, $state).'</td>';
      $content .= '</tr>';
      $content .= '<tr>';
      $content .= '<td align="right">'.__('Postal/Zip Code:', 'mp').'*</td><td>';
      $content .= apply_filters( 'mp_checkout_error_zip', '' );
      $content .= '<input size="10" class="mp_shipping_field" id="mp_zip" name="zip" type="text" value="'.esc_attr($zip).'" /></td>';
      $content .= '</tr>';
      $content .= '<tr>';
      $content .= '<td align="right">'.__('Phone Number:', 'mp').'</td><td>';
      $content .= '<input size="20" name="phone" type="text" value="'.esc_attr($phone).'" /></td>';
      $content .= '</tr>';
    }
    
    if ($mp->get_setting('special_instructions')) {
      $content .= '<tr>';
      $content .= '<td align="right">'.__('Special Instructions:', 'mp').'</td><td>';
      $content .= '<textarea name="special_instructions" rows="2" style="width: 100%;">'.esc_textarea($special_instructions).'</textarea></td>';
      $content .= '</tr>';
    }
    
    $content .= apply_filters( 'mp_checkout_shipping_field', '' );

    $content .= '</tbody>';
    $content .= '</table>';
    
    $content .= apply_filters( 'mp_checkout_after_shipping', '' );
    
    $content .= '<p class="mp_cart_direct_checkout">';
    $content .= '<input type="submit" name="mp_shipping_submit" id="mp_shipping_submit" value="'.__('Continue Checkout &raquo;', 'mp').'" />';
    $content .= '</p>';
    $content .= '</form>';

  } else if (!$mp->download_only_cart($mp->get_cart_contents())) { //is not editable and not download only

    $content .= '<table class="mp_cart_shipping">';
    $content .= '<thead><tr>';
    $content .= '<th>'.__('Shipping Information:', 'mp').'</th>';
    $content .= '<th align="right"><a href="'.mp_checkout_step_url('shipping').'">'.__('Edit', 'mp').'</a></th>';
    $content .= '</tr></thead>';
    $content .= '<tbody>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Email:', 'mp').'</td><td>';
    $content .= esc_attr($email).' </td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Full Name:', 'mp').'</td><td>';
    $content .= esc_attr($name).'</td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Address:', 'mp').'</td>';
    $content .= '<td>'.esc_attr($address1).'</td>';
    $content .= '</tr>';

    if ($address2) {
      $content .= '<tr>';
      $content .= '<td align="right">'.__('Address 2:', 'mp').'</td>';
      $content .= '<td>'.esc_attr($address2).'</td>';
      $content .= '</tr>';
    }

    $content .= '<tr>';
    $content .= '<td align="right">'.__('City:', 'mp').'</td>';
    $content .= '<td>'.esc_attr($city).'</td>';
    $content .= '</tr>';

    if ($state) {
      $content .= '<tr>';
      $content .= '<td align="right">'.__('State/Province/Region:', 'mp').'</td>';
      $content .= '<td>'.esc_attr($state).'</td>';
      $content .= '</tr>';
    }

    $content .= '<tr>';
    $content .= '<td align="right">'.__('Postal/Zip Code:', 'mp').'</td>';
    $content .= '<td>'.esc_attr($zip).'</td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td align="right">'.__('Country:', 'mp').'</td>';
    $content .= '<td>'.$mp->countries[$country].'</td>';
    $content .= '</tr>';

    if ($phone) {
      $content .= '<tr>';
      $content .= '<td align="right">'.__('Phone Number:', 'mp').'</td>';
      $content .= '<td>'.esc_attr($phone).'</td>';
      $content .= '</tr>';
    }
    
    $content .= apply_filters( 'mp_checkout_shipping_field_readonly', '' );
    
    $content .= '</tbody>';
    $content .= '</table>';
  }

  if ($echo) {
    echo $content;
  } else {
    return $content;
  }
}

//Prints cart payment gateway form, for internal use
function _mp_cart_payment($type, $echo = false) {
  global $mp, $blog_id, $mp_gateway_active_plugins;
  $blog_id = (is_multisite()) ? $blog_id : 1;

	$cart = $mp->get_cart_contents($mp->global_cart);

  $content = '';
  if ($type == 'form') {
    $content = '<form id="mp_payment_form" method="post" action="'.mp_checkout_step_url('checkout').'">';
    if (count((array)$mp_gateway_active_plugins) == 1) {
      $content .= '<input type="hidden" name="mp_choose_gateway" value="'.$mp_gateway_active_plugins[0]->plugin_name.'" />';
    } else if (count((array)$mp_gateway_active_plugins) > 1) {
      $content .= '<table class="mp_cart_payment_methods">';
      $content .= '<thead><tr>';
      $content .= '<th>'.__('Choose a Payment Method:', 'mp').'</th>';
      $content .= '</tr></thead>';
      $content .= '<tbody><tr><td>';
      foreach ((array)$mp_gateway_active_plugins as $plugin) {
        $content .= '<label>';
        $content .= '<input type="radio" class="mp_choose_gateway" name="mp_choose_gateway" value="'.$plugin->plugin_name.'" '.checked($_SESSION['mp_payment_method'], $plugin->plugin_name, false).'/>';
        if ($plugin->method_img_url) {
          $content .= '<img src="' . $plugin->method_img_url . '" alt="' . $plugin->public_name . '" />';
        }
        $content .= $plugin->public_name;
        $content .= '</label>';
      }
      $content .= '</td>';
      $content .= '</tr>';
      $content .= '</tbody>';
      $content .= '</table>';
    }

    $content .= apply_filters( 'mp_checkout_payment_form', '', $cart, $_SESSION['mp_shipping_info'] );

    $content .= '</form>';

  } else if ($type == 'confirm') {

    //if skipping a step
    if (empty($_SESSION['mp_payment_method'])) {
      $content .= '<div class="mp_checkout_error">' . sprintf(__('Whoops, looks like you skipped a step! Please <a href="%s">go back and try again</a>.', 'mp'), mp_checkout_step_url('checkout')) . '</div>';
      return $content;
    }
    $content .= '<form id="mp_payment_form" method="post" action="'.mp_checkout_step_url('confirm-checkout').'">';

    $content .= apply_filters( 'mp_checkout_confirm_payment_' . $_SESSION['mp_payment_method'], $cart, $_SESSION['mp_shipping_info'] );

    $content .= '<p class="mp_cart_direct_checkout">';
    $content .= '<input type="submit" name="mp_payment_confirm" id="mp_payment_confirm" value="'.__('Confirm Payment &raquo;', 'mp').'" />';
    $content .= '</p>';
    $content .= '</form>';

  } else if ($type == 'confirmation') {

    //if skipping a step
    if (empty($_SESSION['mp_payment_method'])) {
      //$content .= '<div class="mp_checkout_error">' . sprintf(__('Whoops, looks like you skipped a step! Please <a href="%s">go back and try again</a>.', 'mp'), mp_checkout_step_url('checkout')) . '</div>';
    }

    //gateway plugin message hook
    $content .= apply_filters( 'mp_checkout_payment_confirmation_' . $_SESSION['mp_payment_method'], '', $mp->get_order($_SESSION['mp_order']) );
    
    if (!$mp->global_cart) {
      //tracking information
      $track_link = '<a href="' . mp_orderstatus_link(false, true) . $_SESSION['mp_order'] . '/' . '">' . mp_orderstatus_link(false, true) . $_SESSION['mp_order'] . '/' . '</a>';
      $content .= '<p>' . sprintf(__('You may track the latest status of your order(s) here:<br />%s', 'mp'), $track_link) . '</p>';
    }
    
    //add ecommerce JS
    $mp->create_ga_ecommerce( $mp->get_order($_SESSION['mp_order']) );

    //clear cart session vars
    unset($_SESSION['mp_payment_method']);
    unset($_SESSION['mp_order']);
  }

  if ($echo) {
    echo $content;
  } else {
    return $content;
  }
}

/**
 * Echos the current shopping cart contents. Use in the cart template.
 *
 * @param string $context Optional. Possible values: widget, checkout
 * @param string $checkoutstep Optional. Possible values: checkout-edit, shipping, checkout, confirm-checkout, confirmation
 * @param bool $echo Optional. default true
 */
function mp_show_cart($context = '', $checkoutstep = null, $echo = true) {
  global $mp, $blog_id;
  $content = '';

  if ( $checkoutstep == null )
    $checkoutstep = get_query_var( 'checkoutstep' );

  if ( mp_items_in_cart() || $checkoutstep == 'confirmation') {

    if ($context == 'widget') {
      $content .= _mp_cart_table('widget');
      $content .= '<div class="mp_cart_actions_widget">';
      $content .= '<a class="mp_empty_cart" href="'.mp_cart_link(false, true).'?empty-cart=1" title="'.__('Empty your shopping cart', 'mp').'">'.__('Empty Cart', 'mp').'</a>';
      $content .= '<a class="mp_checkout_link" href="'.mp_cart_link(false, true).'" title="'.__('Go To Checkout Page', 'mp').'">'.__('Checkout &raquo;', 'mp').'</a>';
      $content .= '</div>';
    } else if ($context == 'checkout') {

      //generic error message context for plugins to hook into
      $content .= apply_filters( 'mp_checkout_error_checkout', '' );

      if( $mp->get_setting('show_purchase_breadcrumbs')==1 ){
        $content .= mp_cart_breadcrumbs($checkoutstep);
      }

      //handle checkout steps
      switch($checkoutstep) {

        case 'shipping':
          $content .= do_shortcode($mp->get_setting('msg->shipping'));
          $content .= _mp_cart_shipping(true);
          break;

        case 'checkout':
          $content .=  do_shortcode($mp->get_setting('msg->checkout'));
          $content .= _mp_cart_payment('form');
          break;

        case 'confirm-checkout':
          $content .=  do_shortcode($mp->get_setting('msg->confirm_checkout'));
          $content .= _mp_cart_table('checkout');
          $content .= _mp_cart_shipping(false);
          $content .= _mp_cart_payment('confirm');
          break;

        case 'confirmation':
          $content .=  do_shortcode($mp->get_setting('msg->success'));
          $content .= _mp_cart_payment('confirmation');
          break;

        default:
          $content .= do_shortcode($mp->get_setting('msg->cart'));
          $content .= _mp_cart_table('checkout-edit');
          $content .= _mp_cart_login(false);
          break;
      }

    } else {
      $content .= _mp_cart_table('checkout');
      $content .= '<div class="mp_cart_actions">';
      $content .= '<a class="mp_empty_cart" href="'.mp_cart_link(false, true).'?empty-cart=1" title="'.__('Empty your shopping cart', 'mp').'">'.__('Empty Cart', 'mp').'</a>';
			$content .= '<a class="mp_checkout_link" href="'.mp_cart_link(false, true).'" title="'.__('Go To Checkout Page', 'mp').'">'.__('Checkout &raquo;', 'mp').'</a>';
      $content .= '</div>';
    }
  } else {
    if ($context != 'widget')
      $content .= do_shortcode($mp->get_setting('msg->cart'));
      
    $content .= '<div class="mp_cart_empty">'.__('There are no items in your cart.', 'mp').'</div>';
    $content .= '<div id="mp_cart_actions_widget"><a class="mp_store_link" href="'.mp_products_link(false, true).'">'.__('Browse Products &raquo;', 'mp').'</a></div>';
  }

  if ($echo) {
    echo $content;
  } else {
    return $content;
  }
}

/**
 * Echos the order status page. Use in the mp_orderstatus.php template.
 *
 */
function mp_order_status() {
  global $mp, $wp_query, $blog_id;

	$bid = (is_multisite()) ? $blog_id : 1; // FPM: Used for Custom Field Processing
	
  echo do_shortcode($mp->get_setting('msg->order_status'));

  $order_id = isset($wp_query->query_vars['order_id']) ? $wp_query->query_vars['order_id'] : (isset($_GET['order_id']) ? $_GET['order_id'] : '');

  if (!empty($order_id)) {
    //get order
    $order = $mp->get_order($order_id);

    if ($order) { //valid order
      echo '<h2><em>' . sprintf( __('Order Details (%s):', 'mp'), htmlentities($order_id)) . '</em></h2>';
      ?>
      <h3><?php _e('Current Status', 'mp'); ?></h3>
      <ul>
      <?php
      //get times
      $received = isset($order->mp_received_time) ? date_i18n(get_option('date_format') . ' - ' . get_option('time_format'), $order->mp_received_time) : '';
      if (!empty($order->mp_paid_time))
        $paid = date_i18n(get_option('date_format') . ' - ' . get_option('time_format'), $order->mp_paid_time);
      if (!empty($order->mp_shipped_time))
        $shipped = date_i18n(get_option('date_format') . ' - ' . get_option('time_format'), $order->mp_shipped_time);

      if ($order->post_status == 'order_received') {
        echo '<li>' . __('Received:', 'mp') . ' <strong>' . $received . '</strong></li>';
      } else if ($order->post_status == 'order_paid') {
        echo '<li>' . __('Paid:', 'mp') . ' <strong>' . $paid . '</strong></li>';
        echo '<li>' . __('Received:', 'mp') . ' <strong>' . $received . '</strong></li>';
      } else if ($order->post_status == 'order_shipped' || $order->post_status == 'order_closed') {
        echo '<li>' . __('Shipped:', 'mp') . ' <strong>' . $shipped . '</strong></li>';
        echo '<li>' . __('Paid:', 'mp') . ' <strong>' . $paid . '</strong></li>';
        echo '<li>' . __('Received:', 'mp') . ' <strong>' . $received . '</strong></li>';
      }

      $order_paid = $order->post_status != 'order_received';
      $max_downloads = $mp->get_setting('max_downloads', 5);
      ?>
      </ul>

      <h3><?php _e('Payment Information:', 'mp'); ?></h3>
      <ul>
        <li>
          <?php _e('Payment Method:', 'mp'); ?>
          <strong><?php echo $order->mp_payment_info['gateway_public_name']; ?></strong>
        </li>
        <li>
          <?php _e('Payment Type:', 'mp'); ?>
          <strong><?php echo $order->mp_payment_info['method']; ?></strong>
        </li>
        <li>
          <?php _e('Transaction ID:', 'mp'); ?>
          <strong><?php echo $order->mp_payment_info['transaction_id']; ?></strong>
        </li>
        <li>
          <?php _e('Payment Total:', 'mp'); ?>
          <strong><?php echo $mp->format_currency($order->mp_payment_info['currency'], $order->mp_payment_info['total']) . ' ' . $order->mp_payment_info['currency']; ?></strong>
        </li>
      </ul>


      <h3><?php _e('Order Information:', 'mp'); ?></h3>
      <table id="mp-order-product-table" class="mp_cart_contents">
        <thead><tr>
          <th class="mp_cart_col_thumb">&nbsp;</th>
          <th class="mp_cart_col_product"><?php _e('Item', 'mp'); ?></th>
          <th class="mp_cart_col_quant"><?php _e('Quantity', 'mp'); ?></th>
          <th class="mp_cart_col_price"><?php _e('Price', 'mp'); ?></th>
          <th class="mp_cart_col_subtotal"><?php _e('Subtotal', 'mp'); ?></th>
          <th class="mp_cart_col_downloads"><?php _e('Download', 'mp'); ?></th>
        </tr></thead>
        <tbody>
        <?php
          if (is_array($order->mp_cart_info) && count($order->mp_cart_info)) {
						foreach ($order->mp_cart_info as $product_id => $variations) {
							//for compatibility for old orders from MP 1.x
							if (isset($variations['name'])) {
              	$data = $variations;
                echo '<tr>';
	              echo '  <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id ) . '</td>';
	              echo '  <td class="mp_cart_col_product"><a href="' . apply_filters('mp_product_url_display_in_cart', get_permalink($product_id), $product_id) . '">' . apply_filters('mp_product_name_display_in_cart', $data['name'], $product_id) . '</a>' . '</td>'; // Added WPML (This differs than other code)
	              echo '  <td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
	              echo '  <td class="mp_cart_col_price">' . $mp->format_currency('', $data['price']) . '</td>';
	              echo '  <td class="mp_cart_col_subtotal">' . $mp->format_currency('', $data['price'] * $data['quantity']) . '</td>';
	              echo '  <td class="mp_cart_col_downloads"></td>';
	              echo '</tr>';
							} else {
								foreach ($variations as $variation => $data) {
		              echo '<tr>';
		              echo '  <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id ) . '</td>';
		              echo '  <td class="mp_cart_col_product"><a href="' . apply_filters('mp_product_url_display_in_cart', get_permalink($product_id), $product_id) . '">' . apply_filters('mp_product_name_display_in_cart', $data['name'], $product_id) . '</a>';
		
                  // Output product custom field information
                  $cf_key = $bid .':'. $product_id .':'. $variation;
                  if (isset($order->mp_shipping_info['mp_custom_fields'][$cf_key])) {
                    $cf_item = $order->mp_shipping_info['mp_custom_fields'][$cf_key];
      
                    $mp_custom_field_label 		= get_post_meta($product_id, 'mp_custom_field_label', true);
                    if (isset($mp_custom_field_label[$variation]))
                      $label_text = $mp_custom_field_label[$variation];
                    else
                      $label_text = __('Product Personalization:', 'mp');
      
                    echo '<div class="mp_cart_custom_fields">'. $label_text .'<br />';
                    foreach($cf_item as $item) {
                      echo $item;
                    }
                    echo '</div>';
                  }
					
		              echo '</td>'; // Added WPML (This differs than other code)

                  $price = get_display_price($order, $data);

		              echo '  <td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
		              echo '  <td class="mp_cart_col_price">' . $mp->format_currency('', $price) . '</td>';
		              echo '  <td class="mp_cart_col_subtotal">' . $mp->format_currency('', $price * $data['quantity']) . '</td>';
									if (is_array($data['download']) && $download_url = $mp->get_download_url($product_id, $order->post_title)) {
                    if ($order_paid) {
                      //check for too many downloads
											if (intval($data['download']['downloaded']) < $max_downloads)
												echo '  <td class="mp_cart_col_downloads"><a href="' . $download_url . '">' . __('Download&raquo;', 'mp') . '</a></td>';
											else
											  echo '  <td class="mp_cart_col_downloads">' . __('Limit Reached', 'mp') . '</td>';
										} else {
										  echo '  <td class="mp_cart_col_downloads">' . __('Awaiting Payment', 'mp') . '</td>';
										}
									} else {
										echo '  <td class="mp_cart_col_downloads"></td>';
									}
		              echo '</tr>';
								}
							}
            }
          } else {
            echo '<tr><td colspan="6">' . __('No products could be found for this order', 'mp') . '</td></tr>';
          }
          ?>
        </tbody>
      </table>
      <ul>
        <?php //coupon line
        if ( $order->mp_discount_info ) { ?>
        <li><?php _e('Coupon Discount:', 'mp'); ?> <strong><?php echo $order->mp_discount_info['discount']; ?></strong></li>
        <?php } ?>

        <?php //shipping line
        if ( $order->mp_shipping_total ){ ?>
          <li><?php _e('Shipping:', 'mp'); ?> <strong><?php echo $mp->format_currency('', $mp->get_display_shipping($order)); ?></strong></li>
        <?php } ?>

        <?php //tax line
        if ( $order->mp_tax_total ) { ?>
        <li><?php _e('Taxes:', 'mp'); ?> <strong><?php echo $mp->format_currency('', $order->mp_tax_total); ?></strong></li>
        <?php } ?>

        <li><?php _e('Order Total:', 'mp'); ?> <strong><?php echo $mp->format_currency('', $order->mp_order_total); ?></strong></li>
      </ul>

      <?php if (!defined('MP_HIDE_ORDERSTATUS_SHIPPING')) { ?>
      <h3><?php _e('Shipping Information:', 'mp'); ?></h3>
      <table>
        <tr>
      	<td align="right"><?php _e('Full Name:', 'mp'); ?></td><td>
        <?php echo esc_attr($order->mp_shipping_info['name']); ?></td>
      	</tr>

      	<tr>
      	<td align="right"><?php _e('Address:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['address1']); ?></td>
      	</tr>

        <?php if ($order->mp_shipping_info['address2']) { ?>
      	<tr>
      	<td align="right"><?php _e('Address 2:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['address2']); ?></td>
      	</tr>
        <?php } ?>

      	<tr>
      	<td align="right"><?php _e('City:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['city']); ?></td>
      	</tr>

      	<?php if ($order->mp_shipping_info['state']) { ?>
      	<tr>
      	<td align="right"><?php _e('State/Province/Region:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['state']); ?></td>
      	</tr>
        <?php } ?>

      	<tr>
      	<td align="right"><?php _e('Postal/Zip Code:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['zip']); ?></td>
      	</tr>

      	<tr>
      	<td align="right"><?php _e('Country:', 'mp'); ?></td>
        <td><?php echo $mp->countries[$order->mp_shipping_info['country']]; ?></td>
      	</tr>

        <?php if ($order->mp_shipping_info['phone']) { ?>
      	<tr>
      	<td align="right"><?php _e('Phone Number:', 'mp'); ?></td>
        <td><?php echo esc_attr($order->mp_shipping_info['phone']); ?></td>
      	</tr>
        <?php } ?>
        
        <?php if (isset($order->mp_shipping_info['tracking_num'])) { ?>
      	<tr>
      	<td align="right"><?php _e('Tracking Number:', 'mp'); ?></td>
        <td><?php echo mp_tracking_link($order->mp_shipping_info['tracking_num'], $order->mp_shipping_info['method']); ?></td>
      	</tr>
        <?php } ?>
      </table>
      <?php } ?>
      
      <?php if (isset($order->mp_order_notes)) { ?>
      <h3><?php _e('Order Notes:', 'mp'); ?></h3>
      <?php echo wpautop($order->mp_order_notes); ?>
      <?php } ?>
      
      <?php do_action('mp_order_status_output', $order); ?>
      
      <?php mp_orderstatus_link(true, false, __('&laquo; Back', 'mp')); ?>
      <?php

    } else { //not valid order id
      echo '<h3>' . __('Invalid Order ID. Please try again:', 'mp') . '</h3>';
      ?>
      <form action="<?php mp_orderstatus_link(true, true); ?>" method="get">
    		<label><?php _e('Enter your 12-digit Order ID number:', 'mp'); ?><br />
    		<input type="text" name="order_id" id="order_id" class="input" value="" size="20" /></label>
    		<input type="submit" id="order-id-submit" value="<?php _e('Look Up &raquo;', 'mp'); ?>" />
      </form>
      <?php
    }

  } else {

    //get from usermeta
    $user_id = get_current_user_id();
    if ($user_id) {
      if (is_multisite()) {
        global $blog_id;
        $meta_id = 'mp_order_history_' . $blog_id;
      } else {
        $meta_id = 'mp_order_history';
      }
      $orders = get_user_meta($user_id, $meta_id, true);
    } else {
      //get from cookie
      if (is_multisite()) {
        global $blog_id;
        $cookie_id = 'mp_order_history_' . $blog_id . '_' . COOKIEHASH;
      } else {
        $cookie_id = 'mp_order_history_' . COOKIEHASH;
      }

      if (isset($_COOKIE[$cookie_id]))
        $orders = unserialize($_COOKIE[$cookie_id]);
    }

    if (is_array($orders) && count($orders)) {
      krsort($orders);
      //list orders
      echo '<h3>' . __('Your Recent Orders:', 'mp') . '</h3>';
      echo '<ul id="mp-order-list">';
      foreach ($orders as $timestamp => $order)
        echo '  <li><strong>' . date_i18n(get_option('date_format') . ' - ' . get_option('time_format'), $timestamp) . ':</strong> <a href="./' . trailingslashit($order['id']) . '">' . $order['id'] . '</a> - ' . $mp->format_currency('', $order['total']) . '</li>';
      echo '</ul>';

      ?>
      <form action="<?php mp_orderstatus_link(true, true); ?>" method="get">
    		<label><?php _e('Or enter your 12-digit Order ID number:', 'mp'); ?><br />
    		<input type="text" name="order_id" id="order_id" class="input" value="" size="20" /></label>
    		<input type="submit" id="order-id-submit" value="<?php _e('Look Up &raquo;', 'mp'); ?>" />
      </form>
      <?php

    } else {

      if (!is_user_logged_in()) {
        ?>
        <table class="mp_cart_login">
          <thead><tr>
            <th class="mp_cart_login" colspan="2"><?php _e('Have a User Account? Login To View Your Order History:', 'mp'); ?></th>
            <th>&nbsp;</th>
          </tr></thead>
          <tbody>
          <tr>
            <td class="mp_cart_login">
              <form name="loginform" id="loginform" action="<?php echo wp_login_url(); ?>" method="post">
            		<label><?php _e('Username', 'mp'); ?><br />
            		<input type="text" name="log" id="user_login" class="input" value="" size="20" /></label>
                <br />
            		<label><?php _e('Password', 'mp'); ?><br />
            		<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" /></label>
                <br />
            		<input type="submit" name="wp-submit" id="mp_login_submit" value="<?php _e('Login &raquo;', 'mp'); ?>" />
            		<input type="hidden" name="redirect_to" value="<?php mp_orderstatus_link(true, true); ?>" />
              </form>
            </td>
            <td class="mp_cart_or_label"><?php _e('or', 'mp'); ?></td>
            <td class="mp_cart_checkout">
              <form action="<?php mp_orderstatus_link(true, true); ?>" method="get">
            		<label><?php _e('Enter your 12-digit Order ID number:', 'mp'); ?><br />
            		<input type="text" name="order_id" id="order_id" class="input" value="" size="20" /></label>
            		<input type="submit" id="order-id-submit" value="<?php _e('Look Up &raquo;', 'mp'); ?>" />
              </form>
            </td>
          </tr>
          </tbody>
        </table>
        <?php
      } else {
        ?>
        <form action="<?php mp_orderstatus_link(true, true); ?>" method="get">
      		<label><?php _e('Enter your 12-digit Order ID number:', 'mp'); ?><br />
      		<input type="text" name="order_id" id="order_id" class="input" value="" size="20" /></label>
      		<input type="submit" id="order-id-submit" value="<?php _e('Look Up &raquo;', 'mp'); ?>" />
        </form>
        <?php
      }

    }
  }
}

/**
 * if tax_inclusive prices enabled, show product line prices with tax to match the review/confirm cart pages
 * @param  object $order post-order object
 * @param  array $data  data for one product line
 * @return float price to display on order tracking and emails for admin/customers
 */
function get_display_price($order, $data) {
	return isset($order->mp_tax_inclusive) && $order->mp_tax_inclusive==1 && isset($data['price_db']) ?
	            $data['price_db'] :
	            $data['price'];
}


/*
 * function mp_tracking_link
 * @param string $tracking_number The tracking number string to turn into a link
 * @param string $method Shipping method, can be UPS, FedEx, USPS, DHL, or other (default)
 */
function mp_tracking_link($tracking_number, $method = 'other') {
  $tracking_number = esc_attr($tracking_number);
  if ($method == 'UPS')
    return '<a title="'.__('Track your UPS package &raquo;', 'mp').'" href="http://wwwapps.ups.com/WebTracking/processInputRequest?sort_by=status&tracknums_displayed=1&TypeOfInquiryNumber=T&loc=en_us&InquiryNumber1='.$tracking_number.'&track.x=0&track.y=0" target="_blank">'.$tracking_number.'</a>';
  else if ($method == 'FedEx')
    return '<a title="'.__('Track your FedEx package &raquo;', 'mp').'" href="http://www.fedex.com/Tracking?language=english&cntry_code=us&tracknumbers='.$tracking_number.'" target="_blank">'.$tracking_number.'</a>';
  else if ($method == 'USPS')
    return '<a title="'.__('Track your USPS package &raquo;', 'mp').'" href="http://trkcnfrm1.smi.usps.com/PTSInternetWeb/InterLabelInquiry.do?origTrackNum='.$tracking_number.'" target="_blank">'.$tracking_number.'</a>';
  else if ($method == 'DHL')
    return '<a title="'.__('Track your DHL package &raquo;', 'mp').'" href="http://www.dhl.com/content/g0/en/express/tracking.shtml?brand=DHL&AWB='.$tracking_number.'" target="_blank">'.$tracking_number.'</a>';
  else
    return apply_filters('mp_shipping_tracking_link', $tracking_number, $method);
}

/*
 * function mp_province_field
 * @param string $country two-digit country code
 * @param string $selected state code form value to be shown/selected
 */
function mp_province_field($country = 'US', $selected = null) {
  global $mp;
  
  if (defined('DOING_AJAX') && DOING_AJAX && isset($_POST['country']))
    $country = $_POST['country'];
  
  $list = false;
  if ($country == 'US')
    $list = $mp->usa_states;
  else if ($country == 'CA')
    $list = $mp->canadian_provinces;
  else if ($country == 'AU')
    $list = $mp->australian_states;
  
  $content = ''; 
  if ($list) {
    $content .= '<select id="mp_state" class="mp_shipping_field" name="state">';
    $content .= '<option value="">'.__('Select:', 'mp').'</option>';
    foreach ($list as $abbr => $label)
      $content .= '<option value="'.$abbr.'"'.selected($selected, $abbr, false).'>'.esc_attr($label).'</option>';
    $content .= '</select>';
  } else {
    $content .= '<input size="15" id="mp_state" name="state" type="text" value="'.esc_attr($selected).'" />'; 
  }

  //if ajax 
  if (defined('DOING_AJAX') && DOING_AJAX)
    die($content);
  else
    return $content;
}


/*
 * function mp_list_products
 * Displays a list of products according to preference. Optional values default to the values in Presentation Settings -> Product List
 *
 * @param bool $echo Optional, whether to echo or return
 * @param bool $paginate Optional, whether to paginate
 * @param int $page Optional, The page number to display in the product list if $paginate is set to true.
 * @param int $per_page Optional, How many products to display in the product list if $paginate is set to true.
 * @param string $order_by Optional, What field to order products by. Can be: title, date, ID, author, price, sales, rand
 * @param string $order Optional, Direction to order products by. Can be: DESC, ASC
 * @param string $category Optional, limit to a product category
 * @param string $tag Optional, limit to a product tag
 */
function mp_list_products( $echo = true, $paginate = '', $page = '', $per_page = '', $order_by = '', $order = '', $category = '', $tag = '', $list_view = false ) {
  global $wp_query, $mp;

  //setup taxonomy if applicable
  if ($category) {
    $taxonomy_query = '&product_category=' . sanitize_title($category);
  } else if ($tag) {
    $taxonomy_query = '&product_tag=' . sanitize_title($tag);
  } else if (isset($wp_query->query_vars['taxonomy']) && ($wp_query->query_vars['taxonomy'] == 'product_category' || $wp_query->query_vars['taxonomy'] == 'product_tag')) {
    $term = get_queried_object(); //must do this for number tags
    $taxonomy_query = '&' . $term->taxonomy . '=' . $term->slug;
  } else {
    $taxonomy_query = '';
  }
  
  //setup pagination
  $paged = false;
  if ($paginate) {
    $paged = true;
  } else if ($paginate === '') {
    if ($mp->get_setting('paginate'))
      $paged = true;
    else
      $paginate_query = '&nopaging=true';
  } else {
    $paginate_query = '&nopaging=true';
  }

  //get page details
  if ($paged) {
    //figure out perpage
    if (intval($per_page)) {
      $paginate_query = '&posts_per_page='.intval($per_page);
    } else {
      $paginate_query = '&posts_per_page='.$mp->get_setting('per_page');
		}

    //figure out page
    if (isset($wp_query->query_vars['paged']) && $wp_query->query_vars['paged'])
      $paginate_query .= '&paged='.intval($wp_query->query_vars['paged']);

    if (intval($page))
      $paginate_query .= '&paged='.intval($page);
    else if ($wp_query->query_vars['paged'])
      $paginate_query .= '&paged='.intval($wp_query->query_vars['paged']);
  }

  //get order by
  if (!$order_by) {
    if ($mp->get_setting('order_by') == 'price')
      $order_by_query = '&meta_key=mp_price_sort&orderby=meta_value_num';
    else if ($mp->get_setting('order_by') == 'sales')
      $order_by_query = '&meta_key=mp_sales_count&orderby=meta_value_num';
    else
      $order_by_query = '&orderby='.$mp->get_setting('order_by');
  } else {
  	if ('price' == $order_by)
  		$order_by_query = '&meta_key=mp_price_sort&orderby=meta_value_num';
    else if('sales' == $order_by)
      $order_by_query = '&meta_key=mp_sales_count&orderby=meta_value_num';
    else
    	$order_by_query = '&orderby='.$order_by;
  }

  //get order direction
  if (!$order) {
    $order_query = '&order='.$mp->get_setting('order');
  } else {
    $order_query = '&order='.$order;
  }

  //The Query
  $custom_query = new WP_Query('post_type=product&post_status=publish' . $taxonomy_query . $paginate_query . $order_by_query . $order_query);

  //allows pagination links to work get_posts_nav_link()
  if ($wp_query->max_num_pages == 0 || $taxonomy_query)
    $wp_query->max_num_pages = $custom_query->max_num_pages;

  // get layout type for products
  $setting = $mp->get_setting('list_view');
  $layout_type = get_product_layout_type(array($list_view, $setting));

  $content = '<div id="mp_product_list" class="mp_'.$layout_type.'">';

  if ($last = $custom_query->post_count){

		$content .= $layout_type == 'grid' ?
									get_products_html_grid($custom_query->posts) :
									get_products_html_list($custom_query->posts);
  	
  }else{
    $content .= '<div id="mp_no_products">' . apply_filters( 'mp_product_list_none', __('No Products', 'mp') ) . '</div>';
  }

  $content .= '</div>';

  if ($echo)
    echo $content;
  else
    return $content;
}

/**
 * returns current product list layout based on setting/shortcode attribute
 */
function get_product_layout_type($ar=array()){
	foreach($ar as $layout){
		if(in_array($layout, array('list','grid'))){
			return $layout;
		}
	}
	return 'list';
}

function get_products_html_list($post_array=array()){
  global $mp;
  $html='';
  $total = count($post_array);
  $count = 0;
  foreach($post_array as $post){
      $count++;

      //add last css class for styling grids
      if ($count == $total)
          $class = array('mp_product', 'last-product');
      else
          $class = 'mp_product';

      $html .= '<div '.mp_product_class(false, $class, $post->ID).'>';
      $html .= '<h3 class="mp_product_name"><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></h3>';
      $html .= '<div class="mp_product_content">';
      $product_content = mp_product_image( false, 'list', $post->ID );
      if ($mp->get_setting('show_excerpt'))
          $product_content .= $mp->product_excerpt($post->post_excerpt, $post->post_content, $post->ID);
      $html .= apply_filters( 'mp_product_list_content', $product_content, $post->ID );
      $html .= '</div>';

      $html .= '<div class="mp_product_meta">';
      //price
      $meta = mp_product_price(false, $post->ID);
      //button
      $meta .= mp_buy_button(false, 'list', $post->ID);
      $html .= apply_filters( 'mp_product_list_meta', $meta, $post->ID );
      $html .= '</div>';

      $html .= '</div>';
  }

  return $html;
}

function get_products_html_grid($post_array=array()){
  global $mp;
  $html='';
  
  //get image width
  if ($mp->get_setting('list_img_size') == 'custom'){
    $width = $mp->get_setting('list_img_width');
  }
  else {
    $size = $mp->get_setting('list_img_size');
    $width = get_option($size."_size_w");
  }
  foreach ($post_array as $post){
    
    $img = mp_product_image(false, 'list', $post->ID);
    $excerpt = $mp->get_setting('show_excerpt') ?
                      '<p class="mp_excerpt">'.$mp->product_excerpt($post->post_excerpt, $post->post_content, $post->ID, '').'</p>' :
                      '';
    $mp_product_list_content = apply_filters( 'mp_product_list_content', $excerpt, $post->ID );
    
    $class=array();
    $class[] = strlen($img)>0?'mp_thumbnail':'';
    $class[] = strlen($excerpt)>0?'mp_excerpt':'';
    $class[] = has_price_variations($post->ID) ? 'mp_price_variations':'';
    /*
    $html .= '<div class="mp_one_tile '.implode($class, ' ').'">
                <div class="mp_one_product">
                  '.$img.'
                  
                  <h3 class="mp_product_name">
                    <a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a>
                  </h3>
                  
                  '.$mp_product_list_content.'

                  <div class="mp_price_buy">
                    '.mp_product_price(false, $post->ID).'
                    '.mp_buy_button(false, 'list', $post->ID).'
                    '.apply_filters( 'mp_product_list_meta', '', $post->ID ).'
                  </div>

                </div>
              </div>';
    */
    $html .= '<div class="mp_one_tile '.implode($class, ' ').'">
                <div class="mp_one_product" style="width: '.$width.'px;">
                
                  <div class="mp_product_detail" style="width: '.$width.'px;">
                    '.$img.'
                  
                    <h3 class="mp_product_name">
                      <a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a>
                    </h3>
                  
                    '.$mp_product_list_content.'
                  </div>

                  <div class="mp_price_buy" style="width: '.$width.'px; margin-left:-'.$width.'px;">
                    '.mp_product_price(false, $post->ID).'
                    '.mp_buy_button(false, 'list', $post->ID).'
                    '.apply_filters( 'mp_product_list_meta', '', $post->ID ).'
                  </div>

                </div>
              </div>';

  }

  $html .= (count($post_array)>0?'<div class="clear"></div>':'');

  return $html;
}

function has_price_variations($post_id){
  $mp_price = maybe_unserialize(get_post_meta($post_id, 'mp_price', true));
  return (is_array($mp_price) && count($mp_price) > 1);
}


/*
 * function mp_product
 * Displays a single product according to preference
 * 
 * @param bool $echo Optional, whether to echo or return
 * @param int $product_id the ID of the product to display
 * @param bool $title Whether to display the title
 * @param bool/string $content Whether and what type of content to display. Options are false, 'full', or 'excerpt'. Default 'full'
 * @param bool/string $image Whether and what context of image size to display. Options are false, 'single', or 'list'. Default 'single'
 * @param bool $meta Whether to display the product meta
 */
function mp_product($echo = true, $product_id, $title = true, $content = 'full', $image = 'single', $meta = true) {
  global $mp;
  $post = get_post($product_id);

  $return = '<div itemscope itemtype="http://schema.org/Product" '.mp_product_class(false, 'mp_product', $post->ID).'>';
  if ($title)
    $return .= '<h3 itemprop="name" class="mp_product_name"><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></h3>';
  
  if ($content) {
    $return .= '<div itemprop="description" class="mp_product_content">';
    if ($image)
      $return .= mp_product_image( false, $image, $post->ID );
    if ($content == 'excerpt')
      $return .= $mp->product_excerpt($post->post_excerpt, $post->post_content, $post->ID);
    else
      $return .= apply_filters('the_content', $post->post_content);
    $return .= '</div>';
  }
  
  if ($meta) {
    $return .= '<div itemprop="offers" itemscope itemtype="http://schema.org/Offer" class="mp_product_meta">';
    //price
    $return .= mp_product_price(false, $post->ID);
    //button
    $return .= mp_buy_button(false, 'single', $post->ID);
    $return .= '</div>';
  }
  $return .= '</div>';
      
  if ($echo)
    echo $return;
  else
    return $return;
}

/**
 * Retrieve product's category list in either HTML list or custom format.
 *
 * @param int $product_id Optional. Post ID to retrieve categories.
 * @param string $before Optional. Before list.
 * @param string $sep Optional. Separate items using this.
 * @param string $after Optional. After list.
 */
function mp_category_list( $product_id = false, $before = '', $sep = ', ', $after = '' ) {
  $terms = get_the_term_list( $product_id, 'product_category', $before, $sep, $after );
  if ( $terms )
    return $terms;
  else
		return __( 'Uncategorized', 'mp' );
}


/**
 * Retrieve product's tag list in either HTML list or custom format.
 *
 * @param int $product_id Optional. Post ID to retrieve categories.
 * @param string $before Optional. Before list.
 * @param string $sep Optional. Separate items using this.
 * @param string $after Optional. After list.
 */
function mp_tag_list( $product_id = false, $before = '', $sep = ', ', $after = '' ) {
  $terms = get_the_term_list( $product_id, 'product_tag', $before, $sep, $after );
  if ( $terms )
    return $terms;
  else
		return __( 'No Tags', 'mp' );
}

/**
 * Display the classes for the product div.
 *
 * @param bool $echo Whether to echo class.
 * @param string|array $class One or more classes to add to the class list.
 * @param int $post_id The post_id for the product. Optional if in the loop
 */
function mp_product_class( $echo = true, $class = '', $post_id = null ) {
	// Separates classes with a single space, collates classes for post DIV
	$content = 'class="' . join( ' ', mp_get_product_class( $class, $post_id ) ) . '"';

	if ($echo)
    echo $content;
  else
    return $content;
}


/**
 * Retrieve the list of classes for the product as an array.
 *
 * The class names are add are many. If the post is a sticky, then the 'sticky'
 * class name. The class 'hentry' is always added to each post. For each
 * category, the class will be added with 'category-' with category slug is
 * added. The tags are the same way as the categories with 'tag-' before the tag
 * slug. All classes are passed through the filter, 'post_class' with the list
 * of classes, followed by $class parameter value, with the post ID as the last
 * parameter.
 *
 *
 * @param string|array $class One or more classes to add to the class list.
 * @param int $post_id The post_id for the product. Optional if in the loop
 * @return array Array of classes.
 */
function mp_get_product_class( $class = '', $post_id = null ) {
  global $id;
  $post_id = ( NULL === $post_id ) ? $id : $post_id;

	$post = get_post($post_id);

	$classes = array();

	if ( empty($post) )
		return $classes;

	$classes[] = 'product-' . $post->ID;
	$classes[] = $post->post_type;
	$classes[] = 'type-' . $post->post_type;

	// sticky for Sticky Posts
	if ( is_sticky($post->ID))
		$classes[] = 'sticky';

	// hentry for hAtom compliace
	$classes[] = 'hentry';

	// Categories
	$categories = get_the_terms($post->ID, "product_category");
	foreach ( (array) $categories as $cat ) {
		if ( empty($cat->slug) || !isset($cat->cat_ID) )
			continue;
		$classes[] = 'category-' . sanitize_html_class($cat->slug, $cat->cat_ID);
	}

	// Tags
	$tags = get_the_terms($post->ID, "product_tag");
	foreach ( (array) $tags as $tag ) {
		if ( empty($tag->slug ) )
			continue;
		$classes[] = 'tag-' . sanitize_html_class($tag->slug, $tag->term_id);
	}

	if ( !empty($class) ) {
		if ( !is_array( $class ) )
			$class = preg_split('#\s+#', $class);
		$classes = array_merge($classes, $class);
	}

	$classes = array_map('esc_attr', $classes);

	return $classes;
}


/*
 * Displays the product price (and sale price)
 *
 * @param bool $echo Optional, whether to echo
 * @param int $post_id The post_id for the product. Optional if in the loop
 * @param sting $label A label to prepend to the price. Defaults to "Price: "
 */
function mp_product_price( $echo = true, $post_id = NULL, $label = true ) {
  global $id, $mp;
  $post_id = ( NULL === $post_id ) ? $id : $post_id;

  $label = ($label === true) ? __('Price: ', 'mp') : $label;

	$meta = get_post_custom($post_id);
  //unserialize
  foreach ($meta as $key => $val) {
	  $meta[$key] = maybe_unserialize($val[0]);
	  if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file" && $key != "mp_price_sort")
	    $meta[$key] = array($meta[$key]);
	}

  if ((is_array($meta["mp_price"]) && count($meta["mp_price"]) == 1) || !empty($meta["mp_file"])) {
    if ($meta["mp_is_sale"]) {
	    $price = '<span class="mp_special_price"><del class="mp_old_price">'.$mp->format_currency('', $meta["mp_price"][0]).'</del>';
	    $price .= '<span itemprop="price" class="mp_current_price">'.$mp->format_currency('', $meta["mp_sale_price"][0]).'</span></span>';
	  } else {
	    $price = '<span itemprop="price" class="mp_normal_price"><span class="mp_current_price">'.$mp->format_currency('', $meta["mp_price"][0]).'</span></span>';
	  }
	} else if (is_array($meta["mp_price"]) && count($meta["mp_price"]) > 1 && !is_singular('product')) { //only show from price in lists
    
    if ($meta["mp_is_sale"]) {
      //do some crazy stuff here to get the lowest price pair ordered by sale prices
      asort($meta["mp_sale_price"], SORT_NUMERIC);
      $lowest = array_slice($meta["mp_sale_price"], 0, 1, true);
      $keys = array_keys($lowest);
      $mp_price = $meta["mp_price"][$keys[0]];
      $mp_sale_price = array_pop($lowest);
	    $price = __('from', 'mp').' <span class="mp_special_price"><del class="mp_old_price">'.$mp->format_currency('', $mp_price).'</del>';
	    $price .= '<span itemprop="price" class="mp_current_price">'.$mp->format_currency('', $mp_sale_price).'</span></span>';
	  } else {
      sort($meta["mp_price"], SORT_NUMERIC);
	    $price = __('from', 'mp').' <span itemprop="price" class="mp_normal_price"><span class="mp_current_price">'.$mp->format_currency('', $meta["mp_price"][0]).'</span></span>';
	  }
	} else {
		return '';
	}

  $price = apply_filters( 'mp_product_price_tag', '<span class="mp_product_price">' . $label . $price . '</span>', $post_id, $label );

  if ($echo)
    echo $price;
  else
    return $price;
}


/*
 * Displays the buy or add to cart button
 *
 * @param bool $echo Optional, whether to echo
 * @param string $context Options are list or single
 * @param int $post_id The post_id for the product. Optional if in the loop
 */
function mp_buy_button( $echo = true, $context = 'list', $post_id = NULL ) {
  global $id, $mp;
  $post_id = ( NULL === $post_id ) ? $id : $post_id;

  $meta = get_post_custom($post_id);
  //unserialize
  foreach ($meta as $key => $val) {
	  $meta[$key] = maybe_unserialize($val[0]);
	  if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file")
	    $meta[$key] = array($meta[$key]);
	}

  //check stock
  $no_inventory = array();
  $all_out = false;
  if ($meta['mp_track_inventory']) {
    $cart = $mp->get_cart_contents();
    if (isset($cart[$post_id]) && is_array($cart[$post_id])) {
	    foreach ($cart[$post_id] as $variation => $data) {
	      if ($meta['mp_inventory'][$variation] <= $data['quantity'])
	        $no_inventory[] = $variation;
			}
			foreach ($meta['mp_inventory'] as $key => $stock) {
	      if (!in_array($key, $no_inventory) && $stock <= 0)
	        $no_inventory[] = $key;
			}
		}

		//find out of stock items that aren't in the cart
		foreach ($meta['mp_inventory'] as $key => $stock) {
      if (!in_array($key, $no_inventory) && $stock <= 0)
        $no_inventory[] = $key;
		}

		if (count($no_inventory) >= count($meta["mp_price"]))
		  $all_out = true;
  }

  //display an external link or form button
  if (isset($meta['mp_product_link']) && $product_link = $meta['mp_product_link']) {

    $button = '<a class="mp_link_buynow" href="' . esc_url($product_link) . '">' . __('Buy Now &raquo;', 'mp') . '</a>';

  } else if ($mp->get_setting('disable_cart')) {
    
    $button = '';
    
  } else {
    $variation_select = '';
    $button = '<form class="mp_buy_form" method="post" action="' . mp_cart_link(false, true) . '">';

    if ($all_out) {
      $button .= '<span class="mp_no_stock">' . __('Out of Stock', 'mp') . '</span>';
    } else {

	    $button .= '<input type="hidden" name="product_id" value="' . $post_id . '" />';

			//create select list if more than one variation
		  if (is_array($meta["mp_price"]) && count($meta["mp_price"]) > 1 && empty($meta["mp_file"])) {
	      $variation_select = '<select class="mp_product_variations" name="variation">';
				foreach ($meta["mp_price"] as $key => $value) {
				  $disabled = (in_array($key, $no_inventory)) ? ' disabled="disabled"' : '';
				  $variation_select .= '<option value="' . $key . '"' . $disabled . '>' . esc_html($meta["mp_var_name"][$key]) . ' - ';
					if ($meta["mp_is_sale"] && $meta["mp_sale_price"][$key]) {
		        $variation_select .= $mp->format_currency('', $meta["mp_sale_price"][$key]);
		      } else {
		        $variation_select .= $mp->format_currency('', $value);
		      }
		      $variation_select .= "</option>\n";
		    }
	      $variation_select .= "</select>&nbsp;\n";
	 		} else {
	      $button .= '<input type="hidden" name="variation" value="0" />';
			}

	    if ($context == 'list') {
	      if ($variation_select) {
        	$button .= '<a class="mp_link_buynow" href="' . get_permalink($post_id) . '">' . __('Choose Option &raquo;', 'mp') . '</a>';
	      } else if ($mp->get_setting('list_button_type') == 'addcart') {
	        $button .= '<input type="hidden" name="action" value="mp-update-cart" />';
	        $button .= '<input class="mp_button_addcart" type="submit" name="addcart" value="' . __('Add To Cart &raquo;', 'mp') . '" />';
	      } else if ($mp->get_setting('list_button_type') == 'buynow') {
	        $button .= '<input class="mp_button_buynow" type="submit" name="buynow" value="' . __('Buy Now &raquo;', 'mp') . '" />';
	      }
	    } else {

	      $button .= $variation_select;

	      //add quantity field if not downloadable
	      if ($mp->get_setting('show_quantity') && empty($meta["mp_file"])) {
	        $button .= '<span class="mp_quantity"><label>' . __('Quantity:', 'mp') . ' <input class="mp_quantity_field" type="text" size="1" name="quantity" value="1" /></label></span>&nbsp;';
	      }

	      if ($mp->get_setting('product_button_type') == 'addcart') {
	        $button .= '<input type="hidden" name="action" value="mp-update-cart" />';
	        $button .= '<input class="mp_button_addcart" type="submit" name="addcart" value="' . __('Add To Cart &raquo;', 'mp') . '" />';
	      } else if ($mp->get_setting('product_button_type') == 'buynow') {
	        $button .= '<input class="mp_button_buynow" type="submit" name="buynow" value="' . __('Buy Now &raquo;', 'mp') . '" />';
	      }
	    }

    }

    $button .= '</form>';
  }

  $button = apply_filters( 'mp_buy_button_tag', $button, $post_id, $context );

  if ($echo)
    echo $button;
  else
    return $button;
}


/*
 * Displays the product featured image
 *
 * @param bool $echo Optional, whether to echo
 * @param string $context Options are list, single, or widget
 * @param int $post_id The post_id for the product. Optional if in the loop
 * @param int $size An optional width/height for the image if contect is widget
 */
function mp_product_image( $echo = true, $context = 'list', $post_id = NULL, $size = NULL ) {
  global $id, $mp;
  $post_id = ( NULL === $post_id ) ? $id : $post_id;
  // Added WPML
  $post_id = apply_filters('mp_product_image_id', $post_id);

  $post = get_post($post_id);

  $post_thumbnail_id = get_post_thumbnail_id( $post_id );
  $class = $title = $link = '';
  
  if ($context == 'list') {
    //quit if no thumbnails on listings
    if (!$mp->get_setting('show_thumbnail'))
      return '';

    //size
    if (intval($size)) {
      $size = array(intval($size), intval($size));
    } else {
      if ($mp->get_setting('list_img_size') == 'custom')
        $size = array($mp->get_setting('list_img_width'), $mp->get_setting('list_img_height'));
      else
        $size = $mp->get_setting('list_img_size');
    }
    
    //link
    $link = get_permalink($post_id);

    $title = esc_attr($post->post_title);

  } else if ($context == 'single') {
    //size
    if ($mp->get_setting('product_img_size') == 'custom')
      $size = array($mp->get_setting('product_img_width'), $mp->get_setting('product_img_height'));
    else
      $size = $mp->get_setting('product_img_size');

    //link
    $temp = wp_get_attachment_image_src( $post_thumbnail_id, 'large' );
    $link = $temp[0];

    $title = __('View Larger Image &raquo;', 'mp');
    $class = ' class="mp_product_image_link mp_lightbox" rel="lightbox"';

  } else if ($context == 'widget') {
    //size
    if (intval($size))
      $size = array(intval($size), intval($size));
    else
      $size = array(50, 50);

    //link
    $link = get_permalink($post_id);

    $title = esc_attr($post->post_title);

  }

  $image = get_the_post_thumbnail($post_id, $size, array('itemprop' => 'image', 'class' => 'alignleft mp_product_image_'.$context, 'title' => $title));
  
  if ( empty($image) && $context != 'single') {
    if ( !is_array($size) ) {
      $size = array( get_option($size."_size_w"), get_option($size."_size_h") );
    }
    $image = '<img width="'.$size[0].'" height="'.$size[1].'" itemprop="image" title="'.esc_attr($title).'" class="alignleft mp_product_image_'.$context.' wp-post-image" src="'.apply_filters('mp_default_product_img', $mp->plugin_url.'images/default-product.png').'">';
  }
  
  //add the link
  if ($link)
    $image = '<a class="mp_img_link" id="product_image-' . $post_id . '"' . $class . ' href="' . $link . '">' . $image . '</a>';

  if ($echo)
    echo $image;
  else
    return $image;
}

/**
 * Displays the product list filter dropdowns
 * 
 * @return string   html for filter/order products select elements.
 */
function mp_products_filter(){
      global $mp;

      $terms = wp_dropdown_categories(array(
        'name' => 'filter-term',
        'taxonomy' => 'product_category',
        'show_option_none' => __('Show All', 'mp'),
        'show_count' => 1,
        'orderby' => 'name',
        'selected' => '',
        'echo' => 0,
        'hierarchical' => true
      ));

      $options=array(
        array('0', '', __('Default', 'mp')),
        array('date', 'desc', __('Release Date', 'mp')),
        array('title', 'asc', __('Name', 'mp')),
        array('price', 'asc', __('Price (Low to High)', 'mp')),
        array('price', 'desc', __('Price (High to Low)', 'mp')),
        array('sales', 'desc', __('Popularity', 'mp'))
      );

      return 
      ' <div class="mp_list_filter">
            <form name="mp_product_list_refine" class="mp_product_list_refine" method="get">
                <div class="one_filter">
                  <span>'.__('Category', 'mp').'</span>
                  '.$terms.'
                </div>

                <div class="one_filter">
                  <span>'.__('Order By', 'mp').'</span>
                  <select name="order">
                    '.get_filter_order_options($options).'
                  </select>
                </div>
            </form>
        </div>';
}

/**
 * @param  array $options 2d array, each child array contains: 0: column, 1: order (asc|desc), 2: human readable value
 * @return string html of select element options
 */
function get_filter_order_options($options){
  global $mp;
  $html='';
  $current_order = strtolower($mp->get_setting('order_by').'-'.$mp->get_setting('order'));

  foreach($options as $k => $t){
    $value = $t[0].'-'.$t[1];
    $selected = $current_order == $value ? 'selected' : '';

    $html.='<option value="'.$value.'" '.$selected.'>
              '.$t[2].'
            </option>';
  }
  return $html;
}

  
/**
 * Echos the current shopping cart link. If global cart is on reflects global location
 * @param bool $echo Optional, whether to echo. Defaults to true
 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
 * @param string $link_text Optional, text to show in link.
 */
function mp_cart_link($echo = true, $url = false, $link_text = '') {
	global $mp, $mp_wpmu;

	if ( $mp->global_cart && is_object($mp_wpmu) && !$mp_wpmu->is_main_site() && function_exists('mp_main_site_id') ) {
		switch_to_blog(mp_main_site_id());
		$link = home_url( $mp->get_setting('slugs->store') . '/' . $mp->get_setting('slugs->cart') . '/' );
		restore_current_blog();
	} else {
		$link = home_url( $mp->get_setting('slugs->store') . '/' . $mp->get_setting('slugs->cart') . '/' );
	}

  if (!$url) {
    $text = ($link_text) ? $link_text : __('Shopping Cart', 'mp');
    $link = '<a href="' . $link . '" class="mp_cart_link">' . $text . '</a>';
  }

  $link = apply_filters( 'mp_cart_link', $link, $echo, $url, $link_text );

  if ($echo)
    echo $link;
  else
    return $link;
}

/**
 * Echos the current store link.
 * @param bool $echo Optional, whether to echo. Defaults to true
 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
 * @param string $link_text Optional, text to show in link.
 */
function mp_store_link($echo = true, $url = false, $link_text = '') {
  global $mp;
  $link = home_url( trailingslashit( $mp->get_setting('slugs->store') ) );

  if (!$url) {
    $text = ($link_text) ? $link_text : __('Visit Store', 'mp');
    $link = '<a href="' . $link . '" class="mp_store_link">' . $text . '</a>';
  }

  $link = apply_filters( 'mp_store_link', $link, $echo, $url, $link_text );

  if ($echo)
    echo $link;
  else
    return $link;
}

/**
 * Echos the current product list link.
 * @param bool $echo Optional, whether to echo. Defaults to true
 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
 * @param string $link_text Optional, text to show in link.
 */
function mp_products_link($echo = true, $url = false, $link_text = '') {
  global $mp;
  $link = home_url( $mp->get_setting('slugs->store') . '/' . $mp->get_setting('slugs->products') . '/' );

  if (!$url) {
    $text = ($link_text) ? $link_text : __('View Products', 'mp');
    $link = '<a href="' . $link . '" class="mp_products_link">' . $text . '</a>';
  }

  $link = apply_filters( 'mp_products_link', $link, $echo, $url, $link_text );

  if ($echo)
    echo $link;
  else
    return $link;
}

/**
 * Echos the current order status link.
 * @param bool $echo Optional, whether to echo. Defaults to true
 * @param bool $url Optional, whether to return a link or url. Defaults to show link.
 * @param string $link_text Optional, text to show in link.
 */
function mp_orderstatus_link($echo = true, $url = false, $link_text = '') {
  global $mp;
  $link = home_url( $mp->get_setting('slugs->store') . '/' . $mp->get_setting('slugs->orderstatus') . '/' );

  if (!$url) {
    $text = ($link_text) ? $link_text : __('Check Order Status', 'mp');
    $link = '<a href="' . $link . '" class="mp_orderstatus_link">' . $text . '</a>';
  }

  $link = apply_filters( 'mp_orderstatus_link', $link, $echo, $url, $link_text );

  if ($echo)
    echo $link;
  else
    return $link;
}

/**
 * Returns the current shopping cart link with checkout step.
 *
 * @param string $checkoutstep. Possible values: checkout-edit, shipping, checkout, confirm-checkout, confirmation
 */
function mp_checkout_step_url($checkout_step) {
  return apply_filters('mp_checkout_step_url', mp_cart_link(false, true) . trailingslashit($checkout_step));
}

/**
 * @return string HTML that shows the user their current position in the purchase process.
 */
function mp_cart_breadcrumbs($current_step){	
	$steps = array(
		'checkout-edit'=>__('Review Cart','mp'),
		'shipping'=>__('Shipping','mp'),
		'checkout'=>__('Checkout','mp'),
		'confirm-checkout'=>__('Confirm','mp'),
		'confirmation'=>__('Thankyou','mp')
	);

	$order = array_keys($steps);
	$current = array_search($current_step, $order);
	$all = array();

	foreach($steps as $str => $human){
		$i = array_search($str, $order);

		if($i >= $current){
			// incomplete
			$all[] = '<span class="incomplete '.($i==$current?'current':'').'">'.$human.'</span>';
		}else{
			// done
			$all[] = '<a class="done" href="'.mp_checkout_step_url($str).'">'.$human.'</a>';
		}
	}
	
	return '<div class="mp_cart_breadcrumbs">
				'.implode(
            '<span class="sep">'.apply_filters('mp_cart_breadcrumbs_seperator', '&raquo;').'</span>', 
            $all).'
			</div>';
}

/**
 * Echos the current store navigation links.
 *
 * @param bool $echo Optional, whether to echo. Defaults to true
 */
function mp_store_navigation( $echo = true ) {
  global $mp;
  
  //navigation
  if (!$mp->get_setting('disable_cart')) {
    $nav = '<ul class="mp_store_navigation"><li class="page_item"><a href="' . mp_products_link(false, true) . '" title="' . __('Products', 'mp') . '">' . __('Products', 'mp') . '</a></li>';
		$nav .= '<li class="page_item"><a href="' . mp_cart_link(false, true) . '" title="' . __('Shopping Cart', 'mp') . '">' . __('Shopping Cart', 'mp') . '</a></li>';
    $nav .= '<li class="page_item"><a href="' . mp_orderstatus_link(false, true) . '" title="' . __('Order Status', 'mp') . '">' . __('Order Status', 'mp') . '</a></li>
</ul>';
  } else {
    $nav = '<ul class="mp_store_navigation">
<li class="page_item"><a href="' . mp_products_link(false, true) . '" title="' . __('Products', 'mp') . '">' . __('Products', 'mp') . '</a></li>
</ul>';
  }

  if ($echo)
    echo $nav;
  else
    return $nav;
}

/**
 * Determine if on a MarketPress shop page
 *
 * @retuns bool whether current page is a MarketPress store page.
 */
function mp_is_shop_page() {
  global $mp;
  return $mp->is_shop_page;
}

/**
 * Determine if there are any items in the cart
 *
 * @retuns bool whether items are in the cart for the current user.
 */
function mp_items_in_cart() {
	if (mp_items_count_in_cart())
  	return true;
	else
	  return false;
}

/**
 * Determine count of any items in the cart
 *
 * @retuns int number of items that are in the cart for the current user.
 */
function mp_items_count_in_cart() {
  global $mp, $blog_id;
  $blog_id = (is_multisite()) ? $blog_id : 1;

  $global_cart = $mp->get_cart_contents(true);
  if (!$mp->global_cart)
  	$selected_cart[$blog_id] = $global_cart[$blog_id];
  else
    $selected_cart = $global_cart;

  if (is_array($selected_cart) && count($selected_cart)) {
    $count = 0;
    foreach ($selected_cart as $cart) {
      if (is_array($cart) && count($cart)) {
        foreach ($cart as $variations) {
          if (is_array($variations) && count($variations)) {
            foreach ($variations as $item) {
              $count += $item['quantity'];
            }
          }
        }
      }
    }
    return $count;
  } else {
    return 0;
	}
}

/**
 * Determine the number of published products
 *
 * @retuns int number of published products.
 */
function mp_products_count() {
  $custom_query = new WP_Query('post_type=product&post_status=publish');
  return $custom_query->post_count;
}

/**
* This function hook into the shipping filter to add any product custom fields. Checks the cart items
 * If any cart items have associated custom fields then they will be displayed in a new section 'Product extra fields'
 * shown below the shipping form inputs. The custom fields will be one for each quantity. Via the product admin each 
 * custom field can be made required or optional. Standard error handling is provided per Market Press standard processing.
 *
 * @since 2.6.0
 * @see 
 *
 * @param $content - output content passed from caller (_mp_cart_shipping)
 * @return $content - Revised content with added information
 */

function mp_custom_fields_checkout_after_shipping($content='') {
	global $mp, $blog_id, $current_user;
	
	if (isset($_SESSION['mp_shipping_info']['mp_custom_fields'])) {
		$mp_custom_fields = $_SESSION['mp_shipping_info']['mp_custom_fields'];
	} else {
		$mp_custom_fields = array();
	}
  
	$blog_id = (is_multisite()) ? $blog_id : 1;
	
	$current_blog_id = $blog_id;

	$global_cart = $mp->get_cart_contents(true);
	if (!$mp->global_cart)  //get subset if needed
		$selected_cart[$blog_id] = $global_cart[$blog_id];
	else
    $selected_cart = $global_cart;
  
	$content_product = '';
	
  foreach ($selected_cart as $bid => $cart) {

		if (is_multisite())
			switch_to_blog($bid);

      foreach ($cart as $product_id => $variations) {
	
        // Load the meta info for the custom fields for this product
        $mp_has_custom_field = get_post_meta($product_id, 'mp_has_custom_field', true);
        $mp_custom_field_required = get_post_meta($product_id, 'mp_custom_field_required', true);
        $mp_custom_field_per = get_post_meta($product_id, 'mp_custom_field_per', true);
        $mp_custom_field_label = get_post_meta($product_id, 'mp_custom_field_label', true);
    
        foreach ($variations as $variation => $data) {
		
          if (isset($mp_has_custom_field[$variation]) && $mp_has_custom_field[$variation]) {
  
            if (!empty($mp_custom_field_label[$variation]))
              $label_text = esc_attr($mp_custom_field_label[$variation]);
            else
              $label_text = "";
            
            if (isset($mp_custom_field_required[$variation]) && $mp_custom_field_required[$variation])
              $required_text = __('required', 'mp');
            else
              $required_text = __('optional', 'mp');									
              
            $content_product .= '<tr class="mp_product_name"><td align="right" colspan="2">';
            $content_product .= apply_filters( 'mp_checkout_error_custom_fields_'. $product_id .'_'. $variation, '' );
            $content_product .= $data['name'];
            $content_product .= '</td></tr>';
            $content_product .= '<tr class="mp_product_custom_fields" style="border-width: 0px">';
            $content_product .= '<td style="border-width: 0px">';
            $content_product .= $label_text .' ('. $required_text .')<br />';
            //$content_product .=  '</td></tr>';
            //$content_product .= '<tr><td style="border-width: 0px">';
            
            // If the mp_custom_field_per is set to 'line' we only show one input field per item in the cart. 
            // This input field will be a simply unordered list (<ul>). However, if the mp_custom_field_per
            // Then we need to show an input field per the quantity items. In this case we use an ordered list
            // to show the numbers to the user. 0-based.
            if ($mp_custom_field_per[$variation] == "line") {
              //$content_product .= '<ul>';
              $cf_limit = 1;
              
            } else if ($mp_custom_field_per[$variation] == "quantity") {
              //$content_product .= '<ol>';
              $cf_limit = $data['quantity'];
            }
            
            $output_cnt = 0;
            while($output_cnt < $cf_limit) {
  
              $cf_key = $bid .':'. $product_id .':'. $variation;
              if (isset($mp_custom_fields[$cf_key][$output_cnt])) 
                $output_value = $mp_custom_fields[$cf_key][$output_cnt];
              else
                $output_value = '';
                
              $content_product .= '<input type="text" style="width: 90%;" value="'. $output_value .'" name="mp_custom_fields[' . $bid . ':' . $product_id . ':' . $variation . ']['. $output_cnt .']" />';
              $output_cnt += 1;
            }
            /*
            if ($mp_custom_field_per[$variation] == "line")
              $content_product .= '<ul>';
            else if ($mp_custom_field_per[$variation] == "quantity")
              $content_product .= '<ol>';
            */
            $content_product .=  '</td>';
            $content_product .=  '</tr>';
          }
        }
      }

	    //go back to original blog
	    if (is_multisite())
	      switch_to_blog($current_blog_id);
	}
	
	if (strlen($content_product)) {
		
	    $content .= '<table class="mp_product_shipping_custom_fields">';
	    $content .= '<thead><tr><th colspan="2">'. __('Product Personalization:', 'mp') .'</th></tr></thead>';
	    $content .= '<tbody>';
	    $content .= $content_product;
	    $content .= '</tbody>';
	    $content .= '</table>';		
	}
	return $content;
}
add_filter('mp_checkout_after_shipping', 'mp_custom_fields_checkout_after_shipping');

/* Not used. This code will show the custom fields input at the view cart page instead of shipping */
function mp_custom_fields_single_order_display_box($order) {
	global $blog_id;
	
	// If this order doesn't have custom fields then return...
	if (!isset($order->mp_shipping_info['mp_custom_fields']))
		return;

	// IF no order items. Not sure this can happend but just in case. 
	if (!isset($order->mp_cart_info))
		return;
	
	//echo "order<pre>"; print_r($order); echo "</pre>";

	$content_product = '';
	
	$bid = (is_multisite()) ? $blog_id : 1;
	foreach ($order->mp_cart_info as $product_id => $variations) {
		foreach ($variations as $variation => $data) {
			$content_product .= '<h3>'. $data['name'] .'</h3>';
			$cf_key = $bid .':'. $product_id .':'. $variation;
			if (isset($order->mp_shipping_info['mp_custom_fields'][$cf_key])) {
				$cf_items = $order->mp_shipping_info['mp_custom_fields'][$cf_key];
				$content_product .= '<ol>';
				foreach($cf_items as $cf_item) {
					$content_product .= '<li>'. $cf_item .'</li>';
				}
				$content_product .= '</ol>';
			}
		}
	}
	if (strlen($content_product)) {
		?>
		<div id="mp-order-custom-fields-info" class="postbox">
			<h3 class='hndle'><span><?php _e('Product Extra Fields', 'mp'); ?></span></h3>
			<div class="inside">
			<?php echo $content_product; ?>
	   		</div>
		</div>
		<script type="text/javascript">
			jQuery('table#mp-order-product-table').after('<p><a href="#mp-order-custom-fields-info">View Product Extra Fields</a></p>');
		</script>
		<?php
	}
}
//add_action('mp_single_order_display_box', 'mp_custom_fields_single_order_display_box');
