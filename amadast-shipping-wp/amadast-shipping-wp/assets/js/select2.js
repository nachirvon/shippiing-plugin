jQuery(document).ready(function($){
  const {__} = wp.i18n;

  function initialize_select2(element){
    element.selectWoo({
      "placeholder": element.attr('placeholder') ?? 'انتخاب کنید',
      "language": {
        "noResults": function(){
          return __("گزینه ای برای انتخاب وجود ندارد", "amadast-shipping-wp");
        },
      },
    });
  }

  const $element = $(".amdsp-select2");

  initialize_select2($element);
});
