/**** MarketPress Ajax JS *********/
jQuery(document).ready(function($) {
  //empty cart
  function mp_empty_cart() {
    if ($("a.mp_empty_cart").attr("onClick") != undefined) {
      return;
    }

    $("a.mp_empty_cart").click(function() {
      var answer = confirm(MP_Ajax.emptyCartMsg);
      if (answer) {
        $(this).html('<img src="'+MP_Ajax.imgUrl+'" />');
        $.post(MP_Ajax.ajaxUrl, {action: 'mp-update-cart', empty_cart: 1}, function(data) {
          $("div.mp_cart_widget_content").html(data);
        });
      }
      return false;
    });
  }
  //add item to cart
  function mp_cart_listeners() {
    $("input.mp_button_addcart").click(function() {
      var input = $(this);
      var formElm = $(input).parents('form.mp_buy_form');
      var tempHtml = formElm.html();
      var serializedForm = formElm.serialize();
      formElm.html('<img src="'+MP_Ajax.imgUrl+'" alt="'+MP_Ajax.addingMsg+'" />');
      $.post(MP_Ajax.ajaxUrl, serializedForm, function(data) {
        var result = data.split('||', 2);
        if (result[0] == 'error') {
          alert(result[1]);
          formElm.html(tempHtml);
          mp_cart_listeners();
        } else {
          formElm.html('<span class="mp_adding_to_cart">'+MP_Ajax.successMsg+'</span>');
          $("div.mp_cart_widget_content").html(result[1]);
          if (result[0] > 0) {
            formElm.fadeOut(2000, function(){
              formElm.html(tempHtml).fadeIn('fast');
              mp_cart_listeners();
            });
          } else {
            formElm.fadeOut(2000, function(){
              formElm.html('<span class="mp_no_stock">'+MP_Ajax.outMsg+'</span>').fadeIn('fast');
              mp_cart_listeners();
            });
          }
          mp_empty_cart(); //re-init empty script as the widget was reloaded
        }
      });
      return false;
    });
  }
  //add listeners
  mp_empty_cart();
  mp_cart_listeners();
  
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

  //province field choice
  $('#mp_country').change(function() {
    $("#mp_province_field").html('<img src="'+MP_Ajax.imgUrl+'" />');
    var country = $(this).val();
    $.post(MP_Ajax.ajaxUrl, {action: 'mp-province-field', country: country}, function(data) {
      $("#mp_province_field").html(data);
    });
  });
});