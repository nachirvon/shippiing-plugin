<?php

if (!defined('ABSPATH')) exit;

class AMDSP_Install {

    public function __construct() {
        add_action('admin_init', [$this, 'activated_plugin'], 20);
    }

    public function activated_plugin() {

        if (!file_exists(AMDSP_DIR . '/.activated')) {
            return;
        }

        if ('yes' === get_transient('amdsp_admin_installing')) {
            return;
        }

        set_transient('amdsp_admin_installing', 'yes', MINUTE_IN_SECONDS * 10);

        if (!AMDSP_Option::get_option('amdsp_settings')) {
            self::setup_default_options();
        }

        self::setup_default_zone();

        delete_transient('amdsp_admin_installing');

        update_option('amdsp_version', AMDSP_VERSION);

        amdsp_file_delete(AMDSP_DIR . '/.activated');

        self::redirect_to_plugin_options_page();
    }

    private function insert_terms($terms): array {
        global $wpdb;

        $insert_data = [];
        foreach ($terms as $slug => $name) {
            array_push($insert_data, $name, $slug);
        }

        $placeholders = array_fill(0, count($terms), "(%s, %s)");
        $placeholders = implode(', ', $placeholders);

        $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->terms} (name, slug) VALUES {$placeholders}", ...$insert_data));

        $created_terms = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->terms} WHERE slug IN (%s) ORDER BY term_id DESC LIMIT %d", implode("','", array_keys($terms)), count($terms)));

        return array_reverse($created_terms);
    }

    private function insert_taxonomy_terms($terms): array {
        global $wpdb;

        $insert_data = [];
        foreach ($terms as $term) {
            array_push($insert_data, $term->term_id, $term->parent_id, AMDSP_Province_City::TAXONOMY_NAME, $term->description);
        }

        $placeholders = array_fill(0, count($terms), "(%d, %d, %s, %s)");
        $placeholders = implode(', ', $placeholders);

        $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->term_taxonomy} (term_id, parent, taxonomy, description) VALUES {$placeholders}", ...$insert_data));

        $created_taxonomy_terms = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->term_taxonomy} WHERE term_id IN (%s) ORDER BY term_taxonomy_id DESC LIMIT %d", implode(', ', array_column($terms, 'term_id')), count($terms)));

        return array_reverse($created_taxonomy_terms);
    }

    public function install_cities() {
        global $wp_filter;

        unset($wp_filter['delete_state_city']);
        unset($wp_filter['edited_state_city']);
        unset($wp_filter['created_state_city']);

        if (get_option('amdsp_install_cities', 0)) {
            return;
        }

        require_once(AMDSP_DIR . '/data/amdsp_province_city.php');

        $states = amdsp_get_provinces();

        foreach ($states as $state_slug => $state) {
            $db_state = self::insert_terms([
                $state_slug => $state
            ])[0];

            $state_cities = amdsp_get_province_cities($state_slug);

            $db_state->parent_id = 0;
            $db_state->description = 'استان ' . $db_state->name;

            self::insert_taxonomy_terms([
                $db_state
            ]);

            $state_cities = array_combine(array_map('urlencode', $state_cities), $state_cities);

            $db_cities = self::insert_terms($state_cities);

            foreach ($db_cities as $city) {
                $city->parent_id = $db_state->term_id;
                $city->description = $db_state->name . ' - ' . $city->name;
            }

            self::insert_taxonomy_terms($db_cities);
        }

        clean_taxonomy_cache(AMDSP_Province_City::TAXONOMY_NAME);
        update_option('amdsp_install_cities', 1);
    }

    public function setup_default_options() {
        AMDSP_Option::set_section_option('settings.amdsp_allow_methods', AMDSP_ShippingMethod::get_methods());
        AMDSP_Option::set_section_option('settings.amdsp_source_city', AMDSP_Admin::DEFAULT_SOURCE_CITY_ID);
    }

    public function setup_default_zone() {
        $is_amadast_zone_loaded = false;
        $wp_amdsp_province_map = amdsp_get_wp_amdsp_province_map();

        foreach (WC_Shipping_Zones::get_zones() as $zone) {
            $zone_object = new WC_Shipping_Zone($zone['id']);

            $new_zone_locations = [];
            foreach ($zone_object->get_zone_locations() as $location) {
                if ($location->type === "state") {
                    $country_province = explode(':', $location->code);

                    if ($country_province[0] === 'IR') {
                        $found_amdsp_province_id = array_search($country_province[1], $wp_amdsp_province_map);

                        if ($found_amdsp_province_id) {
                            $new_zone_locations [] = [
                                'code' => $country_province[0] . ':amdsp-' . $found_amdsp_province_id,
                                'type' => 'state'
                            ];
                        }
                    }
                } else {
                    $new_zone_locations[] = (array)$location;
                }

                $zone_order = $zone_object->get_zone_order();

                $zone_object->set_zone_order($zone_order + 1);
                $zone_object->save();

                foreach ($zone_object->get_shipping_methods() as $shipping_method) {
                    if ($shipping_method->id === 'AMDSP_Online_Method') {
                        $is_amadast_zone_loaded = true;

                        $zone_object->set_zone_order(0);
                        $zone_object->save();
                    }
                }
            }

            // convert wp state slug to amdsp province id
            $zone_object->set_locations($new_zone_locations);
            $zone_object->save();
        }

        if ($is_amadast_zone_loaded) return;

        $zone = new WC_Shipping_Zone(null);
        $zone->set_zone_name('ایران | آمادست');
        $zone->set_zone_order(0);
        $zone->add_location("IR", "country");
        $zone->save();

        $zone->add_shipping_method('AMDSP_Online_Method');
    }

    public function redirect_to_plugin_options_page() {
        global $pagenow;

        if (!isset($_GET['page'])) {
            wp_safe_redirect(admin_url('admin.php?page=amdsp_settings'));
            exit;
        }

        if (($pagenow != 'admin.php') && ($_GET['page'] != 'amdsp_settings')) {
            wp_safe_redirect(admin_url('admin.php?page=amdsp_settings'));
            exit;
        }
    }

    public function un_install_cities() {
        global $wpdb;

        $wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE ('_transient%_amdsp_%_cities')");
        $wpdb->query("DELETE FROM $wpdb->options WHERE `option_name` LIKE ('_transient_timeout%_amdsp_%_cities')");
        delete_transient('amdsp_provinces');
        delete_transient('amdsp_cities');

        $cities = get_terms([
            'taxonomy'   => AMDSP_Province_City::TAXONOMY_NAME,
            'hide_empty' => false,
        ]);

        if (is_array($cities)) {
            $term_ids = array_column($cities, 'term_id');

            $temp = implode(', ', $term_ids);
            $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->terms WHERE `term_id` in (%s)", $temp));

            $term_taxonomy_ids = array_column($cities, 'term_taxonomy_id');

            $temp = implode(', ', $term_taxonomy_ids);
            $wpdb->query($wpdb->prepare("DELETE FROM $wpdb->term_taxonomy WHERE `term_taxonomy_id` in (%s)", $temp));
        }

        delete_option('amdsp_install_cities');
    }
}

new AMDSP_Install();
