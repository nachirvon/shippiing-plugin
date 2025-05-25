jQuery(document).ready(function($){
  const {__} = wp.i18n;

  $.fn.changeElementType = function(newType){
    var newElements = [];

    $(this).each(function(){
      var attrs = {};

      $.each(this.attributes, function(idx, attr){
        attrs[attr.nodeName] = attr.nodeValue;
      });

      var newElement = $("<" + newType + "/>", attrs).append($(this).contents());

      $(this).replaceWith(newElement);

      newElements.push(newElement);
    });

    return $(newElements);
  };

  $(`[id$='_city']`).attr("data-placeholder", __("لطفا ابتدا استان خود را انتخاب نمایید", "amadast-shipping-wp"));

  function initialize_select2(element){
    const js_tag = element.get(0);

    if (!js_tag) return;

    if (js_tag.tagName !== "SELECT") {
      element = element.changeElementType("select");
    }

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

  function make_request(data, callback){

    return $.post(MY_ACCOUNT_OBJECT.request_url, data, function(response){
      callback(response);
    });
  }

  function on_province_change(state_id){
    const city_element = $(`[id$='_city']`);
    const type = city_element.attr("id").indexOf("billing") !== -1 ? "billing" : "shipping";

    city_element.html("<option value=\"0\">" + __("در حال بارگذاری...", "amadast-shipping-wp") + "</option>");

    make_request({
      "action": "amdsp_checkout_load_cities",
      "_ajax_nonce": MY_ACCOUNT_OBJECT.list_cities_nonce,
      "state_id": state_id,
      "type": type,
    }, function(response){
      city_element.html(response);

      if (!city_element.hasClass("select2-hidden-accessible")) {
        initialize_select2(city_element);
      }

      if (city_element.select2("isOpen")) {
        initialize_select2(city_element);
        city_element.select2("open");
      }
    }).catch((rs) => {
      console.log(rs);

      if (rs.responseText === "-1") {
        if (window.confirm(__("خطا در بارگذاری شهرها؛ صفحه را رفرش کنید.", "amadast-shipping-wp"))) {
          window.location.reload();
        }
      }
    });
  }

  initialize_select2($("[id$='_state']"));
  initialize_select2($("[id$='_city']"));

  setTimeout(function(){
    $("[id$='_state']").on("change", function(event){
      const input_value = event.target.value;

      on_province_change(input_value);
    });

    if ($("[id$='_state']").val()) {
      on_province_change($("[id$='_state']").val());
    }
  });
});
