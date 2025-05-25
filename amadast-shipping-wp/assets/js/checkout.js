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
    return $.post(AMDSP_CHECKOUT_OBJECT.request_url, data);
  }

  function on_province_change(type, state_id){
    const city_element = $(`#${ type }_city`);

    city_element.html("<option value=\"0\">" + __("در حال بارگذاری...", "amadast-shipping-wp") + "</option>");

    make_request({
      "action": "amdsp_checkout_load_cities",
      "_ajax_nonce": AMDSP_CHECKOUT_OBJECT.list_cities_nonce,
      "state_id": state_id,
      "type": type,
    }).then(function(response){
      city_element.html(response);

      if (!city_element.hasClass("select2-hidden-accessible"))
        initialize_select2(city_element);

      if (city_element.select2("isOpen")) {
        initialize_select2(city_element);
        city_element.select2("open");
      }
    }).catch((rs) => {

      if (rs.responseText === "-1") {
        if (window.confirm(__("خطا در بارگذاری شهرها؛ صفحه را رفرش کنید.", "amadast-shipping-wp"))) {
          window.location.reload();
        }
      }
    });
  }

  function on_city_changed(){
    $("body").trigger("update_checkout");
  }

  AMDSP_CHECKOUT_OBJECT.types.forEach(type => {
    initialize_select2($(`#${ type }_state`));
    initialize_select2($(`#${ type }_city`));
  });

  setTimeout(function(){

    $("select[id$='_state']").on("change", function(event){
      const type = $(this).attr("id").indexOf("billing") !== -1 ? "billing" : "shipping";
      const input_value = event.target.value;

      on_province_change(type, input_value);
    });

    $("select[id$='_city']").on("change", function(event){
      const type = $(this).attr("id").indexOf("billing") !== -1 ? "billing" : "shipping";
      const input_value = event.target.value;

      on_city_changed();
    });
  });
});
