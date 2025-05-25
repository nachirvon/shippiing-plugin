<?php

if (!defined('ABSPATH')) exit;

class AMDSP_Version {

    public function __construct() {
        add_action('admin_init', [$this, 'install_versions']);
    }

    private function convert_to_numeric_version($str_version) {
        return (int)str_replace('.', '', $str_version);
    }

    public function install_versions() {
        $current_version = get_option('amdsp_version');

        if (empty($current_version))
            $current_version = get_option('amd_version', AMDSP_VERSION);

        if ($current_version == AMDSP_VERSION) {
            return;
        }

        if ('yes' === get_transient('amdsp_admin_updating')) {
            return;
        }

        set_transient('amdsp_admin_updating', 'yes', MINUTE_IN_SECONDS * 10);

        $current_version = self::convert_to_numeric_version($current_version);
        $amdsp_version = self::convert_to_numeric_version(AMDSP_VERSION);

        for ($version = $current_version; $version <= $amdsp_version; $version++) {
            if (method_exists($this, "update_{$version}")) {
                $this->{"update_{$version}"}();
            }
        }

        delete_transient('amdsp_admin_updating');

        update_option('amdsp_version', AMDSP_VERSION);
    }

    // set function version on updating plugin, example => update_${version_num}

    private function update_103() {
        $title_shipping_method_id = [
            "پست پیشتاز" => AMDSP_ShippingMethod::GATEWAY_PISHTAZ,
            "ماهکس"      => AMDSP_ShippingMethod::MAHEX,
            "تیپاکس"     => AMDSP_ShippingMethod::TIPAX,
            "فوروارد"    => AMDSP_ShippingMethod::FORWARD,
        ];

        $methods = AMDSP_Option::get_section_option('settings.amdsp_allow_methods');

        if (is_array($methods)) {
            $temp = [];

            foreach ($methods as $method) {
                $temp[$title_shipping_method_id[$method]] = $title_shipping_method_id[$method];
            }

            AMDSP_Option::set_section_option('settings.amdsp_allow_methods', $temp);
        }
    }

    private function update_104() {

        $options = [
            'awp_tools'   => 'amdsp_settings',
            'awp_version' => 'amdsp_version',
        ];

        foreach ($options as $old_name => $new_name) {
            AMDSP_Option::rename_option($old_name, $new_name);
        }
    }

    private function update_105() {
        $install_obj = new AMDSP_Install();

        $install_obj->un_install_cities();
    }

    private function update_116() {
        AMDSP_Option::rename_option('amd_settings', 'amdsp_settings');
        AMDSP_Option::rename_option('amd_version', 'amdsp_version');

        $changed_options = [];
        foreach (AMDSP_Option::get_option('amdsp_settings') as $key => $value) {
            $new_key = str_replace('amd_', 'amdsp_', $key);
            $changed_options[$new_key] = $value;
        }
        AMDSP_Option::update_option('amdsp_settings', $changed_options);
    }

    private function update_118() {
        // remove unavailable methods

        $available_methods = [
            AMDSP_ShippingMethod::GATEWAY_PISHTAZ,
            AMDSP_ShippingMethod::MAHEX,
            AMDSP_ShippingMethod::TIPAX,
            AMDSP_ShippingMethod::FORWARD,
        ];

        $methods = AMDSP_Option::get_section_option('settings.amdsp_allow_methods');

        if (is_array($methods)) {
            $temp = [];

            foreach ($methods as $method) {

                if (in_array($method, $available_methods)) {
                    $temp[] = $method;
                }
            }

            AMDSP_Option::set_section_option('settings.amdsp_allow_methods', $temp);
        }
    }

    private function update_119() {
        global $wpdb;

        // change AMD_Online_Method to AMDSP_Online_Method in woocommerce_shipping_zone_methods table
        $wpdb->query("UPDATE {$wpdb->base_prefix}woocommerce_shipping_zone_methods SET method_id = 'AMDSP_Online_Method' WHERE `method_id` = 'AMD_Online_Method'");

        // change AMD_ to AMDSP_ in user_mata table
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->usermeta} WHERE `meta_value` LIKE 'AMD_%'");

        foreach ($results as $result) {
            $meta_value = str_replace('AMD-', 'AMDSP-', $result->meta_value);
            update_user_meta($result->user_id, $result->meta_key, $meta_value);
        }
    }
}

new AMDSP_Version();
