jQuery(document).ready(function ($) {

    // Tabs
    $('#mp-quick-setup-tabs').tabs();

    $('#mp-quick-setup-tab-locations .mp_tab_navigation a.mp-tab-nav-next').click(function (e) {
        $('#mp-quick-setup-tabs').tabs({active: 1});
        e.preventDefault();
    });

    $('#mp-quick-setup-tab-currency-and-tax .mp_tab_navigation a.mp-tab-nav-next').click(function (e) {
        $('#mp-quick-setup-tabs').tabs({active: 2});
        e.preventDefault();
    });

    $('#mp-quick-setup-tab-currency-and-tax .mp_tab_navigation a.mp-tab-nav-back').click(function (e) {
        $('#mp-quick-setup-tabs').tabs({active: 0});
        e.preventDefault();
    });

    $('#mp-quick-setup-tab-metric-system .mp_tab_navigation a.mp-tab-nav-back').click(function (e) {
        $('#mp-quick-setup-tabs').tabs({active: 1});
        e.preventDefault();
    });

    // Fields
    $(".mp_tab_content_locations").append($("#mp-quick-setup-wizard-location").html());
    $("#mp-quick-setup-wizard-location").remove();

    $(".mp_tab_content_countries").append($("#mp-quick-setup-wizard-countries").html());
    $("#mp-quick-setup-wizard-countries").remove();

    $(".mp_tab_content_currency").append($("#mp-quick-setup-wizard-currency").html());
    $("#mp-quick-setup-wizard-currency").remove();

    $(".mp_tab_content_tax").append($("#mp-quick-setup-wizard-tax").html());
    $("#mp-quick-setup-wizard-tax").remove();

    $(".mp_tab_content_system").append($("#mp-quick-setup-wizard-measurement-system").html());
    $("#mp-quick-setup-wizard-measurement-system").remove();

    $('body').on('change', 'select[name="base_country"]', function () {
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'mp_preset_currency_base_country',
                country: $(this).val()
            },
            success: function (data) {
                if (data.length > 0) {
                    if($('select[name="currency"] option[value='+data+']').size()) {
                        $('select[name="currency"]').val(data).change();
                    }else{
                        $('select[name="currency"]').val('USD').change();
                    }
                }
            }
        })
    })

});