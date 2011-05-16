<?php
/*
MarketPress Template Functions
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
  $settings = get_option('mp_settings');
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
    $tax_prices = array();
    foreach ($selected_cart as $bid => $cart) {

			if (is_multisite())
        switch_to_blog($bid);

      foreach ($cart as $product_id => $variations) {
        foreach ($variations as $variation => $data) {
          $totals[] = $data['price'] * $data['quantity'];
          
          $content .=  '<tr>';
          $content .=  '  <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id, 50 ) . '</td>';
          $content .=  '  <td class="mp_cart_col_product_table"><a href="' . $data['url'] . '">' . $data['name'] . '</a>';
          $content .=  '  <td class="mp_cart_col_price">' . $mp->format_currency('', $data['price'] * $data['quantity']) . '</td>';
          $content .=  '  <td class="mp_cart_col_quant"><input type="text" size="2" name="quant[' . $bid . ':' . $product_id . ':' . $variation . ']" value="' . $data['quantity'] . '" />&nbsp;<label><input type="checkbox" name="remove[]" value="' . $bid . ':' . $product_id . ':' . $variation . '" /> ' . __('Remove', 'mp') . '</label></td>';
          $content .=  '</tr>';
        }
      }
      
      if ( ($shipping_price = $mp->shipping_price()) !== false )
        $shipping_prices[] = $shipping_price;
        
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
      if (!$mp->global_cart && has_filter( 'mp_shipping_method_lbl' ))
        $shipping_method = ' (' . apply_filters( 'mp_shipping_method_lbl', '' ) . ')';
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="2">' . __('Shipping:', 'mp') . '</td>';
      $content .=  '  <td class="mp_cart_col_shipping">' . $mp->format_currency('', $shipping_price) . '</td>';
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
    $tax_prices = array();
    foreach ($selected_cart as $bid => $cart) {

			if (is_multisite())
        switch_to_blog($bid);

      foreach ($cart as $product_id => $variations) {
        foreach ($variations as $variation => $data) {
          $totals[] = $data['price'] * $data['quantity'];

          $content .=  '<tr>';
          $content .=  '  <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id, 75 ) . '</td>';
          $content .=  '  <td class="mp_cart_col_product_table"><a href="' . $data['url'] . '">' . $data['name'] . '</a>';
          $content .=  '  <td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
          $content .=  '  <td class="mp_cart_col_price">' . $mp->format_currency('', $data['price'] * $data['quantity']) . '</td>';
          $content .=  '</tr>';
        }
      }

      if ( ($shipping_price = $mp->shipping_price()) !== false )
        $shipping_prices[] = $shipping_price;

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
      $content .=  '<tr>';
      $content .=  '  <td class="mp_cart_subtotal_lbl" colspan="3">' . __('Shipping:', 'mp') . '</td>';
      $content .=  '  <td class="mp_cart_col_shipping">' . $mp->format_currency('', $shipping_price) . '</td>';
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
          $content .=  '  <td class="mp_cart_col_product_table"><a href="' . $data['url'] . '">' . $data['name'] . '</a>';
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
  $settings = get_option('mp_settings');
  
  $content = '';
  //don't show if logged in
  if (is_user_logged_in()) {
    $content .= '<p class="mp_cart_direct_checkout">';
    $content .= '<a class="mp_cart_direct_checkout_link" href="'.mp_checkout_step_url('shipping').'">'.__('Checkout Now &raquo;', 'mp').'</a>';
    $content .= '</p>';
  } else {
    $content .= '<table class="mp_cart_login">';
    $content .= '<thead><tr>';
    $content .= '<th class="mp_cart_login">'.__('Have a User Account?', 'mp').'</th>';
    $content .= '<th>&nbsp;</th>';
    if ($settings['force_login'])
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
    if ($settings['force_login'])
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
  $settings = get_option('mp_settings');

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
    $country = $settings['base_country'];
  $phone = (!empty($_SESSION['mp_shipping_info']['phone'])) ? $_SESSION['mp_shipping_info']['phone'] : $meta['phone'];

  $content = '';
  //don't show if logged in
  if (!is_user_logged_in() && $editable) {
    $content .= '<p class="mp_cart_login_msg">';
    $content .= __('Made a purchase here before?', 'mp').' <a class="mp_cart_login_link" href="'.wp_login_url(mp_checkout_step_url('checkout')).'">'.__('Login now to retrieve your saved info &raquo;', 'mp').'</a>';
    $content .= '</p>';
  }

  if ($editable) {
    $content .= '<form id="mp_shipping_form" method="post" action="">';

    $content .= apply_filters( 'mp_checkout_before_shipping', '' );

    $content .= '<table class="mp_cart_shipping">';
    $content .= '<thead><tr>';
    $content .= '<th colspan="2">'.__('Enter Your Shipping Information:', 'mp').'</th>';
    $content .= '</tr></thead>';
    $content .= '<tbody>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Email:', 'mp').'*</td><td>';
    $content .= apply_filters( 'mp_checkout_error_email', '' );
    $content .= '<input size="35" name="email" type="text" value="'.esc_attr($email).'" /></td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td align="right">'. __('Full Name:', 'mp').'*</td><td>';
    $content .= apply_filters( 'mp_checkout_error_name', '' );
    $content .= '<input size="35" name="name" type="text" value="'.esc_attr($name).'" /> </td>';
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
    $content .= '<input size="25" name="city" type="text" value="'.esc_attr($city).'" /></td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('State/Province/Region:', 'mp').'*</td><td>';
    $content .= apply_filters( 'mp_checkout_error_state', '' );
    $content .= '<input size="15" name="state" type="text" value="'.esc_attr($state).'" /></td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Postal/Zip Code:', 'mp').'*</td><td>';
    $content .= apply_filters( 'mp_checkout_error_zip', '' );
    $content .= '<input size="10" id="mp_zip" name="zip" type="text" value="'.esc_attr($zip).'" /></td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Country:', 'mp').'*</td><td>';
    $content .= apply_filters( 'mp_checkout_error_country', '' );
    $content .= '<select id="mp_country" name="country">';
    foreach ((array)$settings['shipping']['allowed_countries'] as $code) {
      $content .= '<option value="'.$code.'"'.selected($country, $code, false).'>'.esc_attr($mp->countries[$code]).'</option>';
    }
    $content .= '</select>';
    $content .= '</td>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<td align="right">'.__('Phone Number:', 'mp').'</td><td>';
    $content .= '<input size="20" name="phone" type="text" value="'.esc_attr($phone).'" /></td>';
    $content .= '</tr>';
    
    $content .= apply_filters( 'mp_checkout_shipping_field', '' );

    $content .= '</tbody>';
    $content .= '</table>';

    $content .= apply_filters( 'mp_checkout_after_shipping', '' ); 
    $content .= '<p class="mp_cart_direct_checkout">';
    $content .= '<input type="submit" name="mp_shipping_submit" id="mp_shipping_submit" value="'.__('Continue Checkout &raquo;', 'mp').'" />';
    $content .= '</p>';
    $content .= '</form>';
    
  } else { //is not editable
  
    $content .= '<table class="mp_cart_shipping">';
    $content .= '<thead><tr>';
    $content .= '<th>'.__('Shipping Information:', 'mp').'</th>';
    $content .= '<th align="right"><a href="'.mp_checkout_step_url('shipping').'">'.__('&laquo; Edit', 'mp').'</a></th>';
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
        } else {
          $content .= $plugin->public_name;
        }
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
      $content .= '<div class="mp_checkout_error">' . sprintf(__('Whoops, looks like you skipped a step! Please <a href="%s">go back and try again</a>.', 'mp'), mp_checkout_step_url('checkout')) . '</div>';
    }
    
    //gateway plugin message hook
    $content .= apply_filters( 'mp_checkout_payment_confirmation_' . $_SESSION['mp_payment_method'], '', $mp->get_order($_SESSION['mp_order']) );

    //tracking information
    $track_link = '<a href="' . mp_orderstatus_link(false, true) . $_SESSION['mp_order'] . '/' . '">' . mp_orderstatus_link(false, true) . $_SESSION['mp_order'] . '/' . '</a>';
    $content .= '<p>' . sprintf(__('You may track the latest status of your order(s) here:<br />%s', 'mp'), $track_link) . '</p>';

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
  $settings = get_option('mp_settings');

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
      
      //handle checkout steps
      switch($checkoutstep) {

        case 'shipping':
          $content .= $settings['msg']['shipping'];
          $content .= _mp_cart_shipping(true);
          break;

        case 'checkout':
          $content .=  $settings['msg']['checkout'];
          $content .= _mp_cart_payment('form');
          break;

        case 'confirm-checkout':
          $content .=  $settings['msg']['confirm_checkout'];
          $content .= _mp_cart_table('checkout');
          $content .= _mp_cart_shipping(false);
          $content .= _mp_cart_payment('confirm');
          break;

        case 'confirmation':
          $content .=  $settings['msg']['success'];
          $content .= _mp_cart_payment('confirmation');
          break;

        default:
          $content .= $settings['msg']['cart'];
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
    $content .= '<div class="mp_cart_empty">'.__('There are no items in your cart.', 'mp').'</div>';
    $content .= '<div id="mp_cart_actions_widget"><a class="mp_store_link" href="'.mp_store_link(false, true).'">'.__('Browse Products &raquo;', 'mp').'</a></div>';
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
  global $mp, $wp_query;
	$settings = get_option('mp_settings');
  echo $settings['msg']['order_status'];
  
  $order_id = ($wp_query->query_vars['order_id']) ? $wp_query->query_vars['order_id'] : $_GET['order_id'];
  
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
      $received = date(get_option('date_format') . ' - ' . get_option('time_format'), $order->mp_received_time);
      if ($order->mp_paid_time)
        $paid = date(get_option('date_format') . ' - ' . get_option('time_format'), $order->mp_paid_time);
      if ($order->mp_shipped_time)
        $shipped = date(get_option('date_format') . ' - ' . get_option('time_format'), $order->mp_shipped_time);
        
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
      $max_downloads = intval($settings['max_downloads']) ? intval($settings['max_downloads']) : 5;
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
	              echo '  <td class="mp_cart_col_product"><a href="' . get_permalink($product_id) . '">' . esc_attr($data['name']) . '</a></td>';
	              echo '  <td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
	              echo '  <td class="mp_cart_col_price">' . $mp->format_currency('', $data['price']) . '</td>';
	              echo '  <td class="mp_cart_col_subtotal">' . $mp->format_currency('', $data['price'] * $data['quantity']) . '</td>';
	              echo '  <td class="mp_cart_col_downloads"></td>';
	              echo '</tr>';
							} else {
								foreach ($variations as $variation => $data) {
		              echo '<tr>';
		              echo '  <td class="mp_cart_col_thumb">' . mp_product_image( false, 'widget', $product_id ) . '</td>';
		              echo '  <td class="mp_cart_col_product"><a href="' . get_permalink($product_id) . '">' . esc_attr($data['name']) . '</a></td>';
		              echo '  <td class="mp_cart_col_quant">' . number_format_i18n($data['quantity']) . '</td>';
		              echo '  <td class="mp_cart_col_price">' . $mp->format_currency('', $data['price']) . '</td>';
		              echo '  <td class="mp_cart_col_subtotal">' . $mp->format_currency('', $data['price'] * $data['quantity']) . '</td>';
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
        if ( $order->mp_shipping_total ) { ?>
        <li><?php _e('Shipping:', 'mp'); ?> <strong><?php echo $mp->format_currency('', $order->mp_shipping_total); ?></strong></li>
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
      </table>
      <?php } ?>
      
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
        echo '  <li><strong>' . date(get_option('date_format') . ' - ' . get_option('time_format'), $timestamp) . ':</strong> <a href="./' . trailingslashit($order['id']) . '">' . $order['id'] . '</a> - ' . $mp->format_currency('', $order['total']) . '</li>';
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


/*
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
function mp_list_products( $echo = true, $paginate = '', $page = '', $per_page = '', $order_by = '', $order = '', $category = '', $tag = '' ) {
  global $wp_query, $mp;
  $settings = get_option('mp_settings');

  //setup taxonomy if applicable
  if ($category) {
    $taxonomy_query = '&product_category=' . sanitize_title($category);
  } else if ($tag) {
    $taxonomy_query = '&product_tag=' . sanitize_title($tag);
  } else if ($wp_query->query_vars['taxonomy'] == 'product_category' || $wp_query->query_vars['taxonomy'] == 'product_tag') {
    $taxonomy_query = '&' . $wp_query->query_vars['taxonomy'] . '=' . get_query_var($wp_query->query_vars['taxonomy']);
  }

  //setup pagination
  $paged = false;
  if ($paginate) {
    $paged = true;
  } else if ($paginate === '') {
    if ($settings['paginate'])
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
      $paginate_query = '&posts_per_page='.$settings['per_page'];
		}
		
    //figure out page
    if ($wp_query->query_vars['paged'])
      $paginate_query .= '&paged='.intval($wp_query->query_vars['paged']);

    if (intval($page))
      $paginate_query .= '&paged='.intval($page);
    else if ($wp_query->query_vars['paged'])
      $paginate_query .= '&paged='.intval($wp_query->query_vars['paged']);
  }

  //get order by
  if (!$order_by) {
    if ($settings['order_by'] == 'price')
      $order_by_query = '&meta_key=mp_price&orderby=mp_price';
    else if ($settings['order_by'] == 'sales')
      $order_by_query = '&meta_key=mp_sales_count&orderby=mp_sales_count';
    else
      $order_by_query = '&orderby='.$settings['order_by'];
  } else {
    $order_by_query = '&orderby='.$order_by;
  }

  //get order direction
  if (!$order) {
    $order_query = '&order='.$settings['order'];
  } else {
    $order_query = '&orderby='.$order;
  }

  //The Query
  $custom_query = new WP_Query('post_type=product&post_status=publish' . $taxonomy_query . $paginate_query . $order_by_query . $order_query);

  //allows pagination links to work get_posts_nav_link()
  if ($wp_query->max_num_pages == 0 || $taxonomy_query)
    $wp_query->max_num_pages = $custom_query->max_num_pages;
  
  $content = '<div id="mp_product_list">';

  if ($last = count($custom_query->posts)) {
    $count = 1;
    foreach ($custom_query->posts as $post) {

			//add last css class for styling grids
			if ($count == $last)
			  $class = array('mp_product', 'last-product');
			else
			  $class = 'mp_product';
			  
      $content .= '<div '.mp_product_class(false, $class, $post->ID).'>';
      $content .= '<h3 class="mp_product_name"><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></h3>';
      $content .= '<div class="mp_product_content">';
      $product_content = mp_product_image( false, 'list', $post->ID );
      $product_content .= $mp->product_excerpt($post->post_excerpt, $post->post_content, $post->ID);
      $content .= apply_filters( 'mp_product_list_content', $product_content, $post->ID );
      $content .= '</div>';

      $content .= '<div class="mp_product_meta">';
      //price
      $meta = mp_product_price(false, $post->ID);
      //button
      $meta .= mp_buy_button(false, 'list', $post->ID);
      $content .= apply_filters( 'mp_product_list_meta', $meta, $post->ID );
      $content .= '</div>';
      
      $content .= '</div>';
      
      $count++;
    }
  } else {
    $content .= '<div id="mp_no_products">' . apply_filters( 'mp_product_list_none', __('No Products', 'mp') ) . '</div>';
  }

  $content .= '</div>';

  if ($echo)
    echo $content;
  else
    return $content;
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
		return __( 'Uncatagorized', 'mp' );
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
		if ( empty($cat->slug ) )
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

  $settings = get_option('mp_settings');
	$meta = get_post_custom($post_id);
  //unserialize
  foreach ($meta as $key => $val) {
	  $meta[$key] = maybe_unserialize($val[0]);
	  if (!is_array($meta[$key]) && $key != "mp_is_sale" && $key != "mp_track_inventory" && $key != "mp_product_link" && $key != "mp_file")
	    $meta[$key] = array($meta[$key]);
	}

  if ((is_array($meta["mp_price"]) && count($meta["mp_price"]) == 1) || !empty($meta["mp_file"])) {
    if ($meta["mp_is_sale"] && $meta["mp_sale_price"][0]) {
	    $price = '<span class="mp_special_price"><del class="mp_old_price">'.$mp->format_currency('', $meta["mp_price"][0]).'</del>';
	    $price .= '<span class="mp_current_price">'.$mp->format_currency('', $meta["mp_sale_price"][0]).'</span></span>';
	  } else {
	    $price = '<span class="mp_normal_price"><span class="mp_current_price">'.$mp->format_currency('', $meta["mp_price"][0]).'</span></span>';
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

  $settings = get_option('mp_settings');
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
    if (is_array($cart[$post_id])) {
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
  if ($product_link = $meta['mp_product_link']) {

    $button = '<a class="mp_link_buynow" href="' . esc_url($product_link) . '">' . __('Buy Now &raquo;', 'mp') . '</a>';

  } else {

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
	      } else if ($settings['list_button_type'] == 'addcart') {
	        $button .= '<input type="hidden" name="action" value="mp-update-cart" />';
	        $button .= '<input class="mp_button_addcart" type="submit" name="addcart" value="' . __('Add To Cart &raquo;', 'mp') . '" />';
	      } else if ($settings['list_button_type'] == 'buynow') {
	        $button .= '<input class="mp_button_buynow" type="submit" name="buynow" value="' . __('Buy Now &raquo;', 'mp') . '" />';
	      }
	    } else {
	    
	      $button .= $variation_select;
	      
	      //add quantity field if not downloadable
	      if ($settings['show_quantity'] && empty($meta["mp_file"])) {
	        $button .= '<span class="mp_quantity"><label>' . __('Quantity:', 'mp') . ' <input class="mp_quantity_field" type="text" size="1" name="quantity" value="1" /></label></span>&nbsp;';
	      }

	      if ($settings['product_button_type'] == 'addcart') {
	        $button .= '<input type="hidden" name="action" value="mp-update-cart" />';
	        $button .= '<input class="mp_button_addcart" type="submit" name="addcart" value="' . __('Add To Cart &raquo;', 'mp') . '" />';
	      } else if ($settings['product_button_type'] == 'buynow') {
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
  global $id;
  $post_id = ( NULL === $post_id ) ? $id : $post_id;

  $post = get_post($post_id);

  $settings = get_option('mp_settings');
  $post_thumbnail_id = get_post_thumbnail_id( $post_id );

  if ($context == 'list') {
    //quit if no thumbnails on listings
    if (!$settings['show_thumbnail'])
      return '';

    //size
    if ($settings['list_img_size'] == 'custom')
      $size = array($settings['list_img_width'], $settings['list_img_height']);
    else
      $size = $settings['list_img_size'];

    //link
    $link = get_permalink($post_id);

    $title = esc_attr($post->post_title);

  } else if ($context == 'single') {
    //size
    if ($settings['product_img_size'] == 'custom')
      $size = array($settings['product_img_width'], $settings['product_img_height']);
    else
      $size = $settings['product_img_size'];

    //link
    $temp = wp_get_attachment_image_src( $post_thumbnail_id, 'large' );
    $link = $temp[0];

    $title = __('View Larger Image &raquo;', 'mp');
    $class = ' class="mp_product_image_link mp_lightbox"';

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

  $image = get_the_post_thumbnail($post_id, $size, array('class' => 'alignleft mp_product_image_'.$context, 'title' => $title));

  //add the link
  if ($link)
    $image = '<a id="product_image-' . $post_id . '"' . $class . ' href="' . $link . '">' . $image . '</a>';

  if ($echo)
    echo $image;
  else
    return $image;
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
		$settings = get_option('mp_settings');
		$link = home_url( $settings['slugs']['store'] . '/' . $settings['slugs']['cart'] . '/' );
		restore_current_blog();
	} else {
    $settings = get_option('mp_settings');
		$link = home_url( $settings['slugs']['store'] . '/' . $settings['slugs']['cart'] . '/' );
	}

  if (!$url) {
    $text = ($link_text) ? $link_text : __('Shopping Cart', 'mp');
    $link = '<a href="' . $link . '" class="mp_cart_link">' . $text . '</a>';
  }

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
	$settings = get_option('mp_settings');
  $link = home_url(trailingslashit($settings['slugs']['store']));

  if (!$url) {
    $text = ($link_text) ? $link_text : __('Visit Store', 'mp');
    $link = '<a href="' . $link . '" class="mp_store_link">' . $text . '</a>';
  }

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
	$settings = get_option('mp_settings');
  $link = home_url( $settings['slugs']['store'] . '/' . $settings['slugs']['products'] . '/' );

  if (!$url) {
    $text = ($link_text) ? $link_text : __('View Products', 'mp');
    $link = '<a href="' . $link . '" class="mp_products_link">' . $text . '</a>';
  }

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
	$settings = get_option('mp_settings');
  $link = home_url( $settings['slugs']['store'] . '/' . $settings['slugs']['orderstatus'] . '/' );

  if (!$url) {
    $text = ($link_text) ? $link_text : __('Check Order Status', 'mp');
    $link = '<a href="' . $link . '" class="mp_orderstatus_link">' . $text . '</a>';
  }

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
  return mp_cart_link(false, true) . trailingslashit($checkout_step);
}

/**
 * Echos the current store navigation links.
 *
 * @param bool $echo Optional, whether to echo. Defaults to true
 */
function mp_store_navigation( $echo = true ) {
	$settings = get_option('mp_settings');
  
  //navigation
  if (!$settings['disable_cart']) {
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
