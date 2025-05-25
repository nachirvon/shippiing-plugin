<?php

if (!defined('ABSPATH')) exit;

class AMDSP_Online_Method extends AMDSP_Shipping_Method {

    public function __construct($instance_id = 0) {
        $this->id = 'AMDSP_Online_Method';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('ارسال آمادست', 'amadast-shipping-wp');
        $this->method_description = __('استعلام آنلاین قیمت ارسال با سرویس دهندگان مختلف از طریق سایت آمادست', 'amadast-shipping-wp');

        parent::__construct();
    }

    public function init() {
        parent::init();

        $this->source_state = AMDSP_Option::get_section_option('settings.amdsp_source_city', AMDSP_Admin::DEFAULT_SOURCE_CITY_ID);
        $this->allow_methods = AMDSP_Option::get_section_option('settings.amdsp_allow_methods', AMDSP_ShippingMethod::get_methods());
        $this->default_package_type = intval(AMDSP_Option::get_section_option('settings.amdsp_default_package_type'), AMDSP_Admin::DEFAULT_PACKAGE_TYPE);
        $this->extra_cost = intval(AMDSP_Option::get_section_option('settings.amdsp_extra_cost', AMDSP_Admin::DEFAULT_EXTRA_COST));
        $this->extra_cost_percent = intval(AMDSP_Option::get_section_option('settings.amdsp_extra_cost_percent', AMDSP_Admin::DEFAULT_EXTRA_COST_PERCENT));
        $this->api_down_cost = intval(AMDSP_Option::get_section_option('settings.amdsp_api_down_cost', AMDSP_Admin::DEFAULT_API_DOWN_COST));
        $this->api_extra_down_cost_per_kilo = intval(AMDSP_Option::get_section_option('settings.amdsp_api_extra_down_cost_per_kilo', AMDSP_Admin::DEFAULT_API_EXTRA_DOWN_COST_PER_KILO));

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }

    public function get_package_type($weight = 1000): int {

        if (!empty($this->default_package_type)) {
            return $this->default_package_type;
        }

        if ($weight >= 4000) return 5;

        return 1;
    }

    public function normalize_weight($weight): int {

        if ($weight < 10)
            return 10;

        return round($this->cart_weight);
    }

    public function show_api_down_cost($package): void {

        if (!empty($this->api_down_cost) && $this->api_down_cost > 0) {
            $weight_kilos = round($this->cart_weight / 1000);
            $final_down_cost = $this->api_down_cost + $weight_kilos * $this->api_extra_down_cost_per_kilo;

            $rate = apply_filters(
                'amdsp_add_rate',
                [
                    'id'    => 'id-extra_cost_percent',
                    'label' => __('هزینه ارسال', 'amadast-shipping-wp'),
                    'cost'  => self::show_currency($final_down_cost),
                ],
                $package,
                $this
            );

            $rate['cost'] = max($rate['cost'], 0);

            $this->add_rate($rate);
        }
    }

    public function calculate_shipping($package = []) {
        $origin_city_id = $this->source_state;
        $destination_city_id = $package['destination']['city'];

        if (empty($this->allow_methods)) {
            self::show_api_down_cost($package);
            return false;
        }

        $rs = wp_remote_post(AMDSP_API_URL, [
            'timeout' => 20,
            'body'    => [
                "from_city"    => $origin_city_id,
                "to_city"      => $destination_city_id,
                "weight"       => self::normalize_weight($this->cart_weight),
                "value"        => self::set_currency($this->cart_total),
                "package_type" => self::get_package_type($this->cart_weight),
                "couriers"     => array_values($this->allow_methods),
                "meta_data"    => [
                    "plugin_version"   => AMDSP_VERSION,
                    "site_url"         => get_site_url(),
                    "admin_email"      => get_bloginfo('admin_email'),
                    "site_name"        => get_bloginfo('blogname'),
                    "site_description" => get_bloginfo('blogdescription'),
                    'options'          => get_option('amdsp_settings'),
                ]
            ]
        ]);

        if (is_wp_error($rs) || wp_remote_retrieve_response_code($rs) != 200) {
            self::show_api_down_cost($package);
            return false;
        }

        $body = wp_remote_retrieve_body($rs);
        $response = json_decode($body);
        $data = $response->data;

        if (empty($data)) {
            self::show_api_down_cost($package);
            return false;
        }

        $allow_services = array_filter($data->items, function ($service) {
            return in_array($service->id, array_values($this->allow_methods)) && $service->price !== 0;
        });

        if (!count($allow_services)) {
            self::show_api_down_cost($package);
            return false;
        }

        foreach ($allow_services as $service) {
            $send_price = (!is_null($service->discounted_price) ? $service->discounted_price : $service->price);
            $send_price = $send_price + $this->extra_cost + ($send_price * $this->extra_cost_percent / 100);

            $rate = apply_filters(
                'amdsp_add_rate',
                [
                    'id'    => 'id-' . $service->id,
                    'label' => AMDSP_ShippingMethod::get_shipping_method_title($service->id),
                    'cost'  => amdsp_round_up_to_nearest_multiple(self::show_currency($send_price)),
                ],
                $package,
                $this
            );

            $rate['cost'] = max($rate['cost'], 0);

            $this->add_rate($rate);
        }
    }
}
