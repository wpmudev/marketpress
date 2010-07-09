/**** MarketPress Ajax JS *********/
jQuery(document).ready(function($) {
  //empty cart
  function mp_empty_cart() {
    $("a.mp_empty_cart").click(function() {
      var answer = confirm(MP_Ajax.emptyCartMsg);
      if (answer) {
        $.post(MP_Ajax.ajaxUrl, {action: 'mp-update-cart', empty_cart: 1}, function(data) {
          $("div.mp_cart_widget").html(data);
        });
      }
      return false;
    });
  }
  //add item to cart
  $("input.mp_button_addcart").click(function() {
    $.post(MP_Ajax.ajaxUrl, $(this).parents('form.mp_buy_form').serialize(), function(data) {
      $("div.mp_cart_widget").html(data);
      mp_empty_cart(); //re-init empty script as the widget was reloaded
    });
    return false;
  });
  //add listeners
  mp_empty_cart();
  
  //coupon codes
  $('#coupon-link').click(function() {
    $('#coupon-link').hide();
    $('#coupon-code').show();
    $('#coupon_code').focus();
    return false;
  });
  
  //payment method choice
  $('input.mp_choose_gateway').change(function() {
    var gid = $('input.mp_choose_gateway:checked').val();
    $('div.mp_gateway_form').hide();
    $('div#' + gid).show();
  });
});