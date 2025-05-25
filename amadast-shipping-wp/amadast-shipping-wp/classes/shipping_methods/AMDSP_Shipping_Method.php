<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMDSP_Shipping_Method extends WC_Shipping_Method {

    public $cart_total = 0;
    public $cart_weight = 0;
    public $is_available = true;

    public function __construct() {
        $this->supports = ['shipping-zones', 'instance-settings', 'instance-settings-modal'];

        $this->init();
    }

    public function init() {
        $this->init_settings();
        //        $this->instance_form_fields = ['title' => ['title' => __('عنوان روش', 'amadast-shipping-wp'), 'type' => 'text', 'default' => $this->method_title]];
        $this->init_form_fields();

        $this->instance_form_fields = apply_filters('amdsp_method_fields', $this->instance_form_fields, $this);
        $this->title = $this->get_option('title', $this->method_title);
        $this->cart_total = isset(WC()->cart) ? WC()->cart->get_cart_contents_total() : 0;
        $this->cart_weight = AMDSP_Cart::get_weight();
    }

    public function is_available($package) {

        $available = $this->is_enabled() && $this->is_available;

        if (empty($package)) {
            $available = false;
        }

        if ($package['destination']['country'] != 'IR') {
            $available = false;
        }

        if (is_null(AMDSP_Province_City::is_province_id_valid($package['destination']['state']))) {
            $available = false;
        }

        if (empty($package['destination']['city'])) {
            $available = false;
        }

        if (!AMDSP_Province_City::is_city_id_valid($package['destination']['city'])) {
            $available = false;
        }

        $available = apply_filters('amdsp_woocommerce_shipping_methods_is_available', $available, $package, $this);

        return apply_filters('amdsp_woocommerce_shipping_' . $this->id . '_is_available', $available, $package, $this);
    }

    protected function show_currency(int $price): int {
        $woo_currency = get_woocommerce_currency();

        if ($woo_currency === 'IRT') return $price / 10;

        if ($woo_currency === 'IRR') return $price;

        return $price;
    }

    protected function set_currency(int $price): int {
        $woo_currency = get_woocommerce_currency();

        if ($woo_currency === 'IRT') return $price * 10;

        if ($woo_currency === 'IRR') return $price;

        return $price;
    }
}
