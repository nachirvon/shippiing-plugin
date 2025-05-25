<?php

defined('ABSPATH') || exit;

class AMDSP_My_Account {

    public function __construct() {

        add_action('wp_ajax_amdsp_checkout_load_cities', [AMDSP_Province_City::class, 'ajax_cities_callback']);
        add_action('wp_ajax_nopriv_amdsp_checkout_load_cities', [AMDSP_Province_City::class, 'ajax_cities_callback']);
        add_filter('woocommerce_my_account_my_address_formatted_address', [$this, 'display_custom_fields_in_my_account'], 10, 3);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_my_account_edit_address_page_scripts'], 200);
    }

    public function enqueue_my_account_edit_address_page_scripts() {

        if (!is_wc_endpoint_url('edit-address')) {
            return false;
        }

        $cities_nonce = wp_create_nonce('ajax_cities_callback');

        wp_register_script('selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.min.js', ['jquery'], '1.0.10', ['in_footer' => true]);
        wp_enqueue_script('selectWoo');
        wp_register_style('select2', WC()->plugin_url() . '/assets/css/select2.css', [], '4.0.3');
        wp_enqueue_style('select2');

        wp_register_script('my-account-page', AMDSP_URL . 'assets/js/my-account.min.js', ['selectWoo', 'wp-i18n'], '2.1.1', ['in_footer' => true]);
        wp_localize_script('my-account-page', 'MY_ACCOUNT_OBJECT', [
            'request_url'       => admin_url('admin-ajax.php'),
            'list_cities_nonce' => $cities_nonce,
        ]);
        wp_enqueue_script('my-account-page');

        if (amdsp_need_translation()) {
            wp_set_script_translations('my-account-page', 'amadast-shipping-wp');
        }
    }

    public function display_custom_fields_in_my_account($address, $customer_id, $address_type) {

        $address['city'] = amdsp_get_city_name($address['city']);
        $address['state'] = amdsp_get_province_name($address['state']);

        return $address;
    }
}

new AMDSP_My_Account();
