<?php

if (!defined('ABSPATH')) exit;

include_once AMDSP_DIR . '/data/amdsp_province_city.php';

class AMDSP_Province_City {

    const TAXONOMY_NAME = 'province_city';

    public static function normalize_id($id) {
        $id = preg_replace('/[^0-9]/', '', $id);

        if (self::is_province_id_valid($id) || self::is_city_id_valid($id))
            return $id;

        return 0;
    }

    public static function provinces() {
        $provinces = amdsp_get_provinces();

        return apply_filters('amdsp_provinces', $provinces);
    }

    public static function cities() {
        $cities = amdsp_get_provinces_cities_with_full_name();

        return apply_filters('amdsp_cities', $cities);
    }

    public static function is_province_id_valid($province_id) {
        $provinces = AMDSP_Province_City::provinces();

        return key_exists($province_id, $provinces);
    }

    public static function is_city_id_valid($city_id) {
        $cities = self::cities();

        return array_key_exists($city_id, $cities);
    }

    public static function get_province_cities($province_id) {
        $province_cities = amdsp_get_province_cities($province_id);

        return apply_filters('amdsp_cities', $province_cities, $province_id);
    }

    public static function sort_provinces($a, $b) {

        if ($a == $b) {
            return 0;
        }

        $provinces = amdsp_get_provinces();

        $a = str_replace(['ي', 'ك', 'ة'], ['ی', 'ک', 'ه'], $a);
        $b = str_replace(['ي', 'ك', 'ة'], ['ی', 'ک', 'ه'], $b);

        $a_key = array_search(trim($a), $provinces);
        $b_key = array_search(trim($b), $provinces);

        return $a_key < $b_key ? -1 : 1;
    }

    public static function ajax_cities_callback() {

        if (!check_ajax_referer('ajax_cities_callback')) {
            die();
        }

        if (!isset($_POST['state_id'])) {
            die();
        }

        $province_id = self::normalize_id(sanitize_text_field(wp_unslash($_POST['state_id'])));

        if (!$province_id) {
            die();
        }

        $cities = AMDSP_Province_City::get_province_cities($province_id);

        $type = isset($_POST['type']) && sanitize_text_field(wp_unslash($_POST['type'])) == 'billing' ? 'billing' : 'shipping';

        $term_id = WC()->checkout()->get_value($type . '_city');

        if (intval($term_id) == 0) {
            $term_id = apply_filters('amdsp_default_city', 0, $type, $province_id);
        }

        printf("<option value='0'>%s</option>", esc_html__('لطفا شهر خود را انتخاب نمایید', 'amadast-shipping-wp'));

        foreach ($cities as $id => $name) {
            printf("<option value='%d' %s>%s</option>", esc_attr($id), selected($term_id, $id, false), esc_html($name));
        }

        die();
    }
}
