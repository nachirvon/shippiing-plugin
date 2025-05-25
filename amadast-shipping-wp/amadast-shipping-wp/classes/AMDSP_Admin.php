<?php

if (!defined('ABSPATH')) exit;

class AMDSP_Admin
{

    const DEFAULT_SOURCE_CITY_ID = 360;
    const DEFAULT_PRODUCT_WEIGHT = 1000;
    const DEFAULT_PACKAGE_WEIGHT = 100;
    const DEFAULT_PACKAGE_TYPE = null;
    const DEFAULT_EXTRA_COST = 0;
    const DEFAULT_EXTRA_COST_PERCENT = 0;
    const DEFAULT_API_DOWN_COST = 450000;
    const DEFAULT_API_EXTRA_DOWN_COST_PER_KILO = 40000;

    public function __construct()
    {
        $this->settings_api = new AMDSP_SETTINGS;

        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'));

        add_filter('plugin_action_links_' . plugin_basename(AMDSP_FILE), [$this, 'add_action_links']);
        add_filter('woocommerce_formatted_address_replacements', [$this, 'format_address'], 10, 2);
    }

    function admin_init()
    {
        $this->settings_api->set_sections($this->get_settings_sections());
        $this->settings_api->set_fields($this->get_settings_fields());

        $this->settings_api->admin_init();
    }

    function admin_menu()
    {

        add_menu_page(
            __('تنظیمات افزونه آمادست', 'amadast-shipping-wp'),
            __('آمادست', 'amadast-shipping-wp'),
            'manage_options',
            'amdsp_settings',
            array($this, 'plugin_page'),
            AMDSP_URL . 'assets/images/logo20.png',
            56
        );

        add_submenu_page(
            'amdsp_settings',
            __('تنظیمات', 'amadast-shipping-wp'),
            __('تنظیمات', 'amadast-shipping-wp'),
            'manage_options',
            'amdsp_settings',
            array($this, 'plugin_page')
        );

        add_submenu_page(
            'amdsp_settings',
            __('ملزومات پستی', 'amadast-shipping-wp'),
            __('ملزومات پستی', 'amadast-shipping-wp'),
            'manage_options',
            'amdsp_malzoomat',
            function () {
                wp_redirect('https://amadast.com/malzoomat?utm_source=wp_plugin&utm_medium=submenu&utm_campaign=wp_plugin_malzoomat_promotion');
                exit;
            }
        );

        add_submenu_page(
            'amdsp_settings',
            __('پشتیبانی', 'amadast-shipping-wp'),
            __('پشتیبانی', 'amadast-shipping-wp'),
            'manage_options',
            'amdsp_support',
            function () {
                wp_redirect('https://amadast.com/product/wordpress-plugin?utm_source=wp_plugin&utm_medium=support_link&utm_campaign=wp_plugin_landing_support');
                exit;
            }
        );
    }

    function get_settings_sections()
    {
        $sections = array(
            array(
                'id' => 'amdsp_settings',
                'title' => __('تنظیمات افزونه آمادست', 'amadast-shipping-wp')
            ),
        );

        return $sections;
    }

    function get_settings_fields()
    {
        $settings_fields = array(
            'amdsp_settings' => array(
                array(
                    'name' => 'amdsp_source_city',
                    'label' => __('شهر مبدا (فروشنده)', 'amadast-shipping-wp'),
                    'desc' => __('لطفا در این قسمت استان مبدا که محصولات از آنجا ارسال می شوند را انتخاب نمائید.', 'amadast-shipping-wp'),
                    'type' => 'select2',
                    'default' => self::DEFAULT_SOURCE_CITY_ID,
                    'options' => AMDSP_Province_City::cities(),
                    'sanitize_callback' => function ($value) {

                        if (!in_array($value, array_keys(AMDSP_Province_City::cities())))
                            return "";

                        return $value;
                    }
                ),
                array(
                    'name' => 'amdsp_allow_methods',
                    'label' => __('انتخاب سرویس دهنده', 'amadast-shipping-wp'),
                    'desc' => __('سرویس هایی که در زمان پرداخت هزینه، به مشتری نمایش داده میشوند.', 'amadast-shipping-wp'),
                    'type' => 'multicheck',
                    'default' => AMDSP_ShippingMethod::get_methods(),
                    'options' => AMDSP_ShippingMethod::get_shipping_method_options(),
                    'sanitize_callback' => function ($value) {

                        $value = array_map('intval', $value);

                        if (array_diff($value, array_keys(AMDSP_ShippingMethod::get_shipping_method_options())))
                            return [];

                        return $value;
                    }
                ),
                array(
                    'name' => 'amdsp_default_product_weight',
                    'label' => __('وزن پیش فرض محصولات (گرم)', 'amadast-shipping-wp'),
                    'desc' => __('زمانی که محصولی وزن نداشته باشد، از این مقدار استفاده میشود.', 'amadast-shipping-wp'),
                    'type' => 'number',
                    'default' => self::DEFAULT_PRODUCT_WEIGHT,
                    'sanitize_callback' => function ($value) {
                        return intval($value);
                    }
                ),
                array(
                    'name' => 'amdsp_default_package_weight',
                    'label' => __('وزن بسته بندی (گرم)', 'amadast-shipping-wp'),
                    'desc' => __('این وزن در هنگام محاسبه هزینه ارسال، به وزن کل محصولات اضافه میشود.', 'amadast-shipping-wp'),
                    'type' => 'number',
                    'default' => self::DEFAULT_PACKAGE_WEIGHT,
                    'sanitize_callback' => function ($value) {
                        return intval($value);
                    }
                ),
                array(
                    'name' => 'amdsp_default_package_type',
                    'label' => 'سایز بسته بندی پیش فرض',
                    'desc' => 'این سایز بسته بندی به عنوان سایز پیشفرض در قیمت دهی استفاده می شود.',
                    'type' => 'select',
                    'default' => self::DEFAULT_PACKAGE_TYPE,
                    'options' => [
                        "" => "انتخاب کنید",
                        "12" => "پاکت های پستی (A4،A5،A6)",
                        "14" => "پاکت پستی A3",
                        "1" => "کارتن سایز 1 (150 × 100 × 100 میلیمتر)",
                        "2" => "کارتن سایز 2 (200 × 150 × 100 میلیمتر)",
                        "3" => "کارتن سایز 3 (200 × 200 × 150 میلیمتر)",
                        "4" => "کارتن سایز 4 (300 × 200 × 200 میلیمتر)",
                        "5" => "کارتن سایز 5 (350 × 250 × 200 میلیمتر)",
                        "6" => "کارتن سایز 6 (450 × 250 × 200 میلیمتر)",
                        "7" => "کارتن سایز 7 (400 × 300 × 250 میلیمتر)",
                        "8" => "کارتن سایز 8 (450 × 400 × 300 میلیمتر)",
                        "9" => "کارتن سایز 9 (550 × 450 × 350 میلیمتر)",
                        "10" => "کارتن سایز 10",
                        "11" => "کارتن بزرگتر از سایز 10"
                    ],
                    'sanitize_callback' => function ($value) {
                        $VALUES = ["", "12", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11"];

                        return in_array($value, $VALUES) ? $value : '';
                    }
                ),
                array(
                    'name' => 'amdsp_extra_cost',
                    'label' => __('هزینه های اضافی (ریال)', 'amadast-shipping-wp'),
                    'desc' => __('هزینه های اضافی علاوه بر هزینه ارسال را می توانید وارد نمائید (مثلا: هزینه های بسته بندی و ... ).', 'amadast-shipping-wp'),
                    'type' => 'number',
                    'default' => self::DEFAULT_EXTRA_COST,
                    'sanitize_callback' => function ($value) {
                        return intval($value);
                    }
                ),
                array(
                    'name' => 'amdsp_extra_cost_percent',
                    'label' => __('هزینه های اضافی به درصد', 'amadast-shipping-wp'),
                    'desc' => __('هزینه های اضافی علاوه بر هزینه ارسال را می توانید به درصد وارد نمائید. (مثال: برای افزایش 2% مطابق با هزینه ارسال، عدد 2 را وارد نمائید)', 'amadast-shipping-wp'),
                    'type' => 'number',
                    'default' => self::DEFAULT_EXTRA_COST_PERCENT,
                    'sanitize_callback' => function ($value) {
                        return intval($value);
                    }
                ),
                array(
                    'name' => 'amdsp_api_down_cost',
                    'label' => __('هزینه پایه در زمان قطعی سرویس (ریال)', 'amadast-shipping-wp'),
                    'desc' => __('از آنجایی که هزینه ها به صورت آنلاین از سایت آمادست گرفته میشود، برای زمانی که سایت پاسخگو نباشد و یا سرویسی موجود نباشد از این گزینه استفاده میشود.', 'amadast-shipping-wp'),
                    'type' => 'number',
                    'default' => self::DEFAULT_API_DOWN_COST,
                    'sanitize_callback' => function ($value) {
                        return intval($value);
                    }
                ),
                array(
                    'name' => 'amdsp_api_extra_down_cost_per_kilo',
                    'label' => __('اضافه هزینه هر کیلوگرم در زمان قطعی سرویس (ریال)', 'amadast-shipping-wp'),
                    'desc' => __('این هزینه بر اساس هر کیلوگرم به هزینه پایه قطعی سرویس (فیلد بالا) اضافه میشود.', 'amadast-shipping-wp'),
                    'type' => 'number',
                    'default' => self::DEFAULT_API_EXTRA_DOWN_COST_PER_KILO,
                    'sanitize_callback' => function ($value) {
                        return intval($value);
                    }
                ),
            ),
        );

        return $settings_fields;
    }

    function plugin_page()
    {
        echo '<div class="wrap">';

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    function get_pages()
    {
        $pages = get_pages();
        $pages_options = array();
        if ($pages) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

    function add_action_links($links)
    {
        $pre_links = array(
            '<a href="' . admin_url('admin.php?page=amdsp_settings') . '">' . __('تنظیمات', 'amadast-shipping-wp') . '</a>',
            '<a href="' . "https://amadast.com/product/wordpress-plugin?utm_source=wp_plugin&utm_medium=support_link&utm_campaign=wp_plugin_landing_support" . '" target="_blank">' . __('پشتیبانی', 'amadast-shipping-wp') . '</a>',
        );

        return array_merge($pre_links, $links);
    }

    public function format_address($formatted_address, $args)
    {

        if (is_admin()) {
            $formatted_address['{state}'] = amdsp_get_province_name($args['state']);
        }

        return $formatted_address;
    }
}

new AMDSP_Admin();
