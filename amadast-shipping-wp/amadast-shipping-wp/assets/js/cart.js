(function($){
  $.fn.changeElementType = function(newType){
    const attrs = {};

    $.each(this[0].attributes, function(idx, attr){
      attrs[attr.nodeName] = attr.nodeValue;
    });

    this.replaceWith(function(){
      return $("<" + newType + "/>", attrs).append($(this).contents());
    });
  };
})(jQuery);

jQuery(document).ready(function($){
  const {__} = wp.i18n;

  function initialize_select2(element){
    element.selectWoo({
      "placeholder": element.attr("data-placeholder") || element.attr("placeholder") || "",
      "width": "100%",
      "language": {
        "noResults": function(){
          return __("گزینه ای برای انتخاب وجود ندارد", "amadast-shipping-wp");
        },
      },
    });
  }

  function make_request(data){
    return $.post(AMDSP_CART_OBJECT.request_url, data);
  }

  function initialize_city(){
    const $select_city = $("#calc_shipping_city");
    const $select_state = $("#calc_shipping_state");

    $select_city.changeElementType("select");
    initialize_select2($select_city);

    const state_value = $select_state.val();

    if (state_value) {
      on_province_change(state_value);
    }
  }

  function on_province_change(state_id){
    const $select_city = $("#calc_shipping_city");

    $select_city.val(null);
    $select_city.trigger("change");

    $select_city.html("<option value=\"0\">" + __("در حال بارگذاری...", "amadast-shipping-wp") + "</option>");

    make_request({
      "action": "amdsp_checkout_load_cities",
      "_ajax_nonce": AMDSP_CART_OBJECT.list_cities_nonce,
      "state_id": state_id,
      "type": "billing",
    }).then(function(response){
      $select_city.html(response);

      const city_val = $select_city.attr("value");

      $select_city.val(city_val);
      $select_city.trigger("change");

      if (!$select_city.hasClass("select2-hidden-accessible"))
        initialize_select2($select_city);

      if ($select_city.select2("isOpen")) {
        initialize_select2($select_city);
        $select_city.select2("open");
      }
    }).catch((rs) => {

      if (rs.responseText === "-1") {
        if (window.confirm(__("خطا در بارگذاری شهرها؛ صفحه را رفرش کنید.", "amadast-shipping-wp"))) {
          window.location.reload();
        }
      }
    });
  }

  $(document.body).on("change", "#calc_shipping_state", function(event){
    const input_value = event.target.value;

    on_province_change(input_value);
  });

  initialize_select2($("#calc_shipping_state"));
  initialize_city();

  $(document.body).on("updated_wc_div", function(){
    initialize_city();
  });
});
