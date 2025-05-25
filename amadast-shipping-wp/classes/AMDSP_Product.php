<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMDSP_Product {

    public static function get_weight(WC_Product $product): float {

        if ($product->is_virtual()) {
            $weight = 0;
        } else if ($product->has_weight()) {
            $weight = wc_get_weight($product->get_weight(), 'g');
        } else {
            $weight = intval(AMDSP_Option::get_section_option('settings.amdsp_default_product_weight', AMDSP_Admin::DEFAULT_PRODUCT_WEIGHT));
        }

        return floatval(apply_filters('amdsp_product_weight', $weight, $product));
    }
}
