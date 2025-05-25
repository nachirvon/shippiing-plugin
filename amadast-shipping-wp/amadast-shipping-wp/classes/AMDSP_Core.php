<?php

if (!defined('ABSPATH')) exit;

class AMDSP_Core {

    private static $SHIPPING_METHODS = [
        'AMDSP_Online_Method'
    ];

    private static $instances = [];

    public static function get_instance() {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }

    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public function __construct() {
        add_action('wp_ajax_amdsp_checkout_load_cities', [AMDSP_Province_City::class, 'ajax_cities_callback']);
        add_action('wp_ajax_nopriv_amdsp_checkout_load_cities', [AMDSP_Province_City::class, 'ajax_cities_callback']);
        add_action('woocommerce_shipping_init', [$this, 'init_shipping_methods']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_checkout_page_scripts'], 200);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_cart_page_scripts'], 200);

        add_filter('woocommerce_shipping_methods', [$this, 'add_shipping_method']);
        add_filter('woocommerce_states', [$this, 'init_provinces'], 40);
        add_filter('woocommerce_checkout_fields', [$this, 'localize_checkout_city_fields'], 40);
        add_filter('woocommerce_checkout_update_order_meta', [$this, 'checkout_update_order_meta'], 40);
        add_filter('woocommerce_checkout_process', [$this, 'checkout_process'], 40);
        add_filter('woocommerce_form_field_billing_city', [$this, 'checkout_cities_field'], 40, 4);
        add_filter('woocommerce_form_field_shipping_city', [$this, 'checkout_cities_field'], 40, 4);
        add_filter('woocommerce_checkout_create_order', [$this, 'normalize_order_data'], 20, 1);
        add_filter('woocommerce_package_rates', [$this, 'hide_methods_when_free'], 100);
        add_filter('woocommerce_checkout_get_value', [$this, 'override_checkout_values'], 20, 2);
    }

    public function init_shipping_methods() {
        require_once AMDSP_DIR . '/classes/shipping_methods/AMDSP_Shipping_Method.php';
        require_once AMDSP_DIR . '/classes/shipping_methods/AMDSP_Online_Method.php';
    }

    public function enqueue_checkout_page_scripts() {
        if (!is_checkout()) {
            return false;
        }

        $cities_nonce = wp_create_nonce('ajax_cities_callback');

        wp_register_script('selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.min.js', ['jquery'], '1.0.10', ['in_footer' => true]);
        wp_enqueue_script('selectWoo');
        wp_register_style('select2', WC()->plugin_url() . '/assets/css/select2.css', [], '4.0.3');
        wp_enqueue_style('select2');

        wp_register_script('checkout-page', AMDSP_URL . 'assets/js/checkout.min.js', ['selectWoo', 'wp-i18n'], '2.1.1', ['in_footer' => true]);
        wp_localize_script('checkout-page', 'AMDSP_CHECKOUT_OBJECT', [
            'request_url'       => admin_url('admin-ajax.php'),
            'list_cities_nonce' => $cities_nonce,
            'types'             => $this->sending_types(),
        ]);
        wp_enqueue_script('checkout-page');

        if (amdsp_need_translation()) {
            wp_set_script_translations('checkout-page', 'amadast-shipping-wp');
        }
    }

    public function enqueue_cart_page_scripts() {
        if (!is_cart()) {
            return false;
        }

        $cities_nonce = wp_create_nonce('ajax_cities_callback');

        wp_register_script('selectWoo', WC()->plugin_url() . '/assets/js/selectWoo/selectWoo.min.js', ['jquery'], '1.0.10', ['in_footer' => true]);
        wp_enqueue_script('selectWoo');
        wp_register_style('select2', WC()->plugin_url() . '/assets/css/select2.css', [], '4.0.3');
        wp_enqueue_style('select2');

        wp_register_script('cart-page', AMDSP_URL . 'assets/js/cart.min.js', ['selectWoo', 'wp-i18n'], '2.1.1', ['in_footer' => true]);
        wp_localize_script('cart-page', 'AMDSP_CART_OBJECT', [
            'request_url'       => admin_url('admin-ajax.php'),
            'list_cities_nonce' => $cities_nonce,
        ]);
        wp_enqueue_script('cart-page');

        if (amdsp_need_translation()) {
            wp_set_script_translations('cart-page', 'amadast-shipping-wp');
        }
    }

    public function add_shipping_method($methods) {

        foreach (self::$SHIPPING_METHODS as $shipping_method) {
            if (class_exists($shipping_method)) {
                $methods[$shipping_method] = $shipping_method;
            }
        }

        return $methods;
    }

    public function init_provinces($states) {
        $provinces = AMDSP_Province_City::provinces();
        $province_ids = array_keys($provinces);

        $normalized_province_ids = array_map(function ($province_id) {
            return 'AMDSP-' . $province_id;
        }, $province_ids);

        $states['IR'] = array_combine($normalized_province_ids, array_values($provinces));

        return $states;
    }

    public function localize_checkout_city_fields($fields) {
        $sending_types = $this->sending_types();

        foreach ($sending_types as $sending_type) {

            if (!isset($fields[$sending_type]["{$sending_type}_city"])) {
                continue;
            }

            $fields[$sending_type]["{$sending_type}_state"]['placeholder'] = __('استان خود را انتخاب نمایید', 'amadast-shipping-wp');

            $default_state_id = apply_filters('amdsp_default_state', 0, $sending_type);

            if ($default_state_id) {
                $fields[$sending_type]["{$sending_type}_state"]['default'] = $default_state_id;
            }

            $class = is_array($fields[$sending_type]["{$sending_type}_city"]['class']) ? $fields[$sending_type]["{$sending_type}_city"]['class'] : [];

            $fields[$sending_type]["{$sending_type}_city"] = [
                'type'        => "{$sending_type}_city",
                'label'       => __('شهر', 'amadast-shipping-wp'),
                'placeholder' => __('لطفا ابتدا استان خود را انتخاب نمایید', 'amadast-shipping-wp'),
                'required'    => true,
                'id'          => "{$sending_type}_city",
                'class'       => apply_filters('amdsp_city_class', $class),
                'default'     => apply_filters('amdsp_default_city', 0, $sending_type, null),
                'priority'    => apply_filters('amdsp_city_priority', $fields[$sending_type]["{$sending_type}_city"]['priority']),
            ];
        }

        $checkout_nonce = wp_create_nonce('amadast_checkout_process');

        $fields['billing']['amdsp_checkout_nonce'] = [
            'type'     => "hidden",
            'required' => true,
            'default'  => $checkout_nonce,
            'value'    => $checkout_nonce,
        ];

        return $fields;
    }

    public function checkout_update_order_meta($order_id) {
        $sending_types = $this->sending_types();
        $fields = ['state', 'city'];

        foreach ($sending_types as $type) {

            foreach ($fields as $field) {

                $term_id = get_post_meta($order_id, "_{$type}_{$field}", true);
                $term = get_term(intval($term_id));

                if (!is_wp_error($term) && !is_null($term)) {
                    update_post_meta($order_id, "_{$type}_{$field}", $term->name);
                    update_post_meta($order_id, "_{$type}_{$field}_id", $term_id);
                }
            }
        }

        if (wc_ship_to_billing_address_only()) {

            foreach ($fields as $field) {

                $label = get_post_meta($order_id, "_billing_{$field}", true);
                $id = get_post_meta($order_id, "_billing_{$field}_id", true);

                update_post_meta($order_id, "_shipping_{$field}", $label);
                update_post_meta($order_id, "_shipping_{$field}_id", $id);
            }
        }
    }

    public function checkout_process() {

        if (!isset($_POST['amdsp_checkout_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['amdsp_checkout_nonce'])), 'amadast_checkout_process')) {
            wc_add_notice(__('بررسی امنیتی ناموفق بود؛ لطفا صفحه را رفرش و دوباره تلاش کنید.', 'amadast-shipping-wp'), 'error');
            return;
        }

        $sending_types = $this->sending_types();

        $fields = ['state' => __('استان', 'amadast-shipping-wp'), 'city' => __('شهر', 'amadast-shipping-wp')];

        $type_label = ['billing' => __('صورتحساب', 'amadast-shipping-wp'), 'shipping' => __('حمل و نقل', 'amadast-shipping-wp')];

        if (!isset($_POST['ship_to_different_address']) && count($sending_types) == 2) {
            unset($sending_types[1]);
        }

        foreach ($sending_types as $sending_type) {
            $label = $type_label[$sending_type];

            foreach ($fields as $field => $name) {
                $key = $sending_type . '_' . $field;

                if (!isset($_POST[$key])) continue;

                $sending_type_field = sanitize_text_field(wp_unslash($_POST[$key]));

                if (strlen($sending_type_field)) {

                    $term_id = AMDSP_Province_City::normalize_id($sending_type_field);

                    if ($term_id == 0) {
                        $message = sprintf(
                        /* translators: 1: state or city 2: billing or shipping  */
                            __('لطفا <b>%1$s %2$s</b> خود را انتخاب نمایید.', 'amadast-shipping-wp'),
                            esc_html($name),
                            esc_html($label)
                        );
                        wc_add_notice($message, 'error');

                        continue;
                    }

                    $term = null;

                    if (array_key_exists($term_id, amdsp_get_provinces())) {
                        $term = amdsp_get_provinces()[$term_id];

                        // TODO: check if province and city match
                    } else if (array_key_exists($term_id, amdsp_get_all_cities())) {
                        $term = amdsp_get_all_cities()[$term_id];
                    }

                    if (is_null($term)) {
                        $message = sprintf(
                        /* translators: 1: state or city 2: billing or shipping  */
                            __('<b>%1$s %2$s</b> انتخاب شده معتبر نمی باشد.', 'amadast-shipping-wp'),
                            esc_html($name),
                            esc_html($label)
                        );
                        wc_add_notice($message, 'error');
                    }
                }
            }
        }
    }

    function override_checkout_values($value, $input) {

        if ($input === 'billing_state') {
            $cart_state_id = WC()->session->get('customer')['shipping_state'];
            $account_state_id = WC()->checkout()->get_value('shipping_state');
            $final_state = $cart_state_id ?? $account_state_id ?? apply_filters('amdsp_default_state', 0, 'billing_state');

            $value = $final_state;
        }

        return $value;
    }

    public function checkout_cities_field($field, $key, $args, $value): string {
        $field_html = '';

        [$type, $name] = explode('_', $args['type']);

        $default_state_id = apply_filters('amdsp_default_state', 0, $type);

        $cart_state_id = WC()->session->get('customer')['shipping_state'];
        $cart_city_id = WC()->session->get('customer')['shipping_city'];

        $account_state_id = WC()->checkout()->get_value($type . '_state');
        $account_city_id = WC()->checkout()->get_value($type . '_city');

        $final_state = $cart_state_id ?? $account_state_id ?? $default_state_id;
        $final_city = $cart_city_id ?? $account_city_id ?? $args['default'];

        $options = AMDSP_Province_City::get_province_cities($final_state);

        $value = $final_city;

        $args['class'][] = 'validate-required';
        $required = '&nbsp;<abbr class="required" title="' . esc_attr__('ضروری', 'amadast-shipping-wp') . '">*</abbr>';

        if (is_string($args['label_class'])) {
            $args['label_class'] = [$args['label_class']];
        }

        $custom_attributes = [];

        if (!empty($args['custom_attributes']) && is_array($args['custom_attributes'])) {
            foreach ($args['custom_attributes'] as $attribute => $attribute_value) {
                $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
            }
        }

        if (!empty($args['validate'])) {
            foreach ($args['validate'] as $validate) {
                $args['class'][] = 'validate-' . $validate;
            }
        }

        $sort = $args['priority'] ?? '';
        $field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr($sort) . '">%3$s</p>';

        if (is_array($options)) {
            $field .= '<select name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" class="' . esc_attr(implode(' ', $args['input_class'])) . '" ' . implode(' ', $custom_attributes) . ' data-placeholder="' . esc_attr($args['placeholder']) . '">';

            if (count($options)) {
                $field .= '<option value="0">' . esc_html($args['placeholder']) . '</option>';
            }

            foreach ($options as $id => $label) {
                if ($name == 'city') {
                    $field .= '<option value="' . esc_attr($id) . '" ' . selected($value, $id, false) . '>' . esc_html($label) . '</option>';
                }
            }

            $field .= '</select>';
        }

        if ($args['label']) {
            $field_html .= '<label for="' . esc_attr($args['id']) . '" class="' . esc_attr(implode(' ', $args['label_class'])) . '">' . $args['label'] . $required . '</label>';
        }

        $field_html .= $field;

        if ($args['description']) {
            $field_html .= '<span class="description">' . esc_html($args['description']) . '</span>';
        }

        $container_class = 'form-row ' . esc_attr(implode(' ', $args['class']));
        $container_id = esc_attr($args['id']) . '_field';

        $after = !empty($args['clear']) ? '<div class="clear"></div>' : '';

        return sprintf($field_container, $container_class, $container_id, $field_html) . $after;
    }

    public function normalize_order_data(WC_Order $order) {
        $billing_province = $order->get_billing_state();
        $shipping_province = $order->get_shipping_state();
        $billing_city = $order->get_billing_city();
        $shipping_city = $order->get_shipping_city();

        $order->set_billing_state(amdsp_get_province_name($billing_province));
        $order->set_shipping_state(amdsp_get_province_name($shipping_province));
        $order->set_billing_city(amdsp_get_city_name($billing_city));
        $order->set_shipping_city(amdsp_get_city_name($shipping_city));

        return $order;
    }

    public function sending_types() {
        $sending_types = ['billing'];

        if (!wc_ship_to_billing_address_only()) {
            $sending_types[] = 'shipping';
        }

        return $sending_types;
    }

    public function hide_methods_when_free(array $rates): array {
        $free_rates = [];

        foreach ($rates as $rate_id => $rate) {
            if ($rate->method_id === 'free_shipping' && $rate->cost == 0) {
                $free_rates[$rate_id] = $rate;
            }
        }

        return count($free_rates) ? $free_rates : $rates;
    }
}
