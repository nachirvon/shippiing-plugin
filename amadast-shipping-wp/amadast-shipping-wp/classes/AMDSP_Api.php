<?php

if (!defined('ABSPATH')) exit;

class AMDSP_Api
{

    public function __construct()
    {

        add_filter('woocommerce_rest_prepare_shop_order_object', [$this, 'add_weight_to_order_api'], 10, 3);
    }

    public function add_weight_to_order_api($response, $object, $request)
    {
        $items = $object->get_items();

        $products_weight = 0;
        $default_product_weight = floatval(AMDSP_Option::get_section_option('settings.amdsp_default_product_weight', AMDSP_Admin::DEFAULT_PRODUCT_WEIGHT));
        $default_package_weight = floatval(AMDSP_Option::get_section_option('settings.amdsp_default_package_weight', AMDSP_Admin::DEFAULT_PACKAGE_WEIGHT));

        $line_items = [];
        foreach ($items as $item) {
            $product = $item->get_product();
            $product_weight = $default_product_weight;

            if ($product) {
                $product_weight = $product->has_weight() ? AMDSP_Product::get_weight($product) : $default_product_weight;
                $products_weight += $product_weight * $item->get_quantity();
            }

            $line_items[] = array_merge($item->get_data(), [
                'weight' => $product_weight,
                'total_weight' => $product_weight * $item->get_quantity(),
            ]);
        }

        $response->data['line_items'] = $line_items;
        $response->data['products_weight'] = $products_weight;
        $response->data['package_weight'] = $default_package_weight;
        $response->data['total_weight'] = $products_weight + $default_package_weight;

        return $response;
    }
}

new AMDSP_Api();
