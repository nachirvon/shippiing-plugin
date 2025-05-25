<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class AMDSP_Uninstall {

    public function __construct() {

        register_deactivation_hook(AMDSP_FILE, [$this, 'deactivated_plugin']);
        //        add_action('register_uninstall_hook', [$this, 'uninstall_plugin'], 20);
    }

    public function deactivated_plugin() {
        $wp_amdsp_province_map = amdsp_get_wp_amdsp_province_map();

        foreach (WC_Shipping_Zones::get_zones() as $zone) {
            $zone_object = new WC_Shipping_Zone($zone['id']);

            $new_zone_locations = [];
            foreach ($zone_object->get_zone_locations() as $location) {
                if ($location->type === "state") {
                    $country_province = explode(':', $location->code);

                    if ($country_province[0] === 'IR' && str_contains($country_province[1], 'amdsp')) {
                        $found_wp_state_slug = $wp_amdsp_province_map[AMDSP_Province_City::normalize_id($country_province[1])];

                        if ($found_wp_state_slug) {
                            $new_zone_locations [] = [
                                'code' => $country_province[0] . ':' . $found_wp_state_slug,
                                'type' => 'state'
                            ];
                        }
                    }
                } else {
                    $new_zone_locations[] = (array)$location;
                }
            }

            // convert amdsp province id to wp state slug
            $zone_object->set_locations($new_zone_locations);
            $zone_object->save();
        }

        self::delete_amdsp_zone();
    }

    private function delete_amdsp_zone() {

        foreach (WC_Shipping_Zones::get_zones() as $zone) {
            $zone_object = new WC_Shipping_Zone($zone['id']);

            foreach ($zone_object->get_shipping_methods() as $shipping_method) {
                if ($shipping_method->id === 'AMDSP_Online_Method') {

                    $zone_object->delete();
                }
            }
        }
    }

    public function uninstall_plugin() {
        // TODO: ask for users about deleting Plugin data and delete it
    }
}

new AMDSP_Uninstall();
