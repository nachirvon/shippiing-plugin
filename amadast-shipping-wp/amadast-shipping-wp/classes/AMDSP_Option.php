<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMDSP_Option {

    public static function get_section_option(string $option_name, $default = null) {

        [$section, $option] = explode('.', $option_name);

        $options = get_option('amdsp_' . $section, []);

        if (isset($options[$option])) {
            return $options[$option];
        }

        return $default;
    }

    public static function set_section_option(string $option_name, $value) {

        [$section, $option] = explode('.', $option_name);

        $options = get_option('amdsp_' . $section, []);
        $options = empty($options) ? [] : $options;

        $options[$option] = $value;

        update_option('amdsp_' . $section, $options);
    }

    public static function rename_option(string $old_name, string $new_name) {
        $option_data = get_option($old_name);

        if ($option_data) {
            update_option($new_name, $option_data);
        }

        delete_option($old_name);
    }

    public static function get_option(string $option_name) {
        return get_option($option_name);
    }

    public static function update_option(string $option_name, $option_value) {
        return update_option($option_name, $option_value);
    }

    public static function delete_option(string $option_name) {
        delete_option($option_name);
    }
}
