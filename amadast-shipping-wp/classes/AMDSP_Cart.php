<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMDSP_Cart {

    public function __construct() {

        add_filter('woocommerce_formatted_address_replacements', [$this, 'format_address'], 10, 2);
    }

    public static function get_shipping_items(): array {

        $cart_items = WC()->cart ? WC()->cart->get_cart() : [];

        foreach ($cart_items as $cart_id => $cart_item) {
            if (!$cart_item['data']->needs_shipping()) {
                unset($cart_items[$cart_id]);
            }
        }

        return $cart_items;
    }

    public static function get_weight(): float {
        $package_weight = floatval(AMDSP_Option::get_section_option('settings.amdsp_default_package_weight', AMDSP_Admin::DEFAULT_PACKAGE_WEIGHT));

        $total_weight = $package_weight;

        foreach (self::get_shipping_items() as $cart_item) {
            $total_weight += AMDSP_Product::get_weight($cart_item['data']) * $cart_item['quantity'];
        }

        return floatval(apply_filters('amdsp_cart_weight', $total_weight));
    }

    public function format_address($formatted_address, $args) {

        if (is_cart()) {
            $formatted_address['{city}'] = amdsp_get_city_name($args['city']);
            $formatted_address['{state}'] = amdsp_get_province_name($args['state']);
        }

        return $formatted_address;
    }
}

new AMDSP_Cart();
