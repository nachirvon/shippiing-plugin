<?php

if (!defined('ABSPATH')) exit;

class AMDSP_Notice
{

    public function __construct()
    {
        add_action('admin_notices', [$this, 'admin_notices'], 5);
    }

    public function admin_notices()
    {

        if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
            return;
        }

        foreach ($this->notices() as $notice) {

            if ($notice['condition'] == false) {
                continue;
            }

            $notice_id = $notice['id'];
            $dismissible = $notice['dismissible'] ? 'is-dismissible' : '';
            $notice_type = $notice['type'];
            $notice_content = $notice['content'];

            printf(
                '<div class="notice notice-%s %s" id="amdsp-%s"><p>%s</p></div>',
                esc_attr($notice_type),
                esc_attr($dismissible),
                esc_attr($notice_id),
                wp_kses_post($notice_content)
            );
        }
    }

    public function notices(): array
    {
        // type: success | error | warning | info

        return [
            [
                'id' => 'woocommerce-disable',
                'content' => __('برای کارکرد صحیح افزونه "آمادست"، باید افزونه ووکامرس فعال باشد.', 'amadast-shipping-wp'),
                'condition' => !is_plugin_active('woocommerce/woocommerce.php'),
                'type' => 'error',
                'dismissible' => false,
            ],
            [
                'id' => 'plugin-conflict',
                'content' => __('برای کارکرد صحیح افزونه "آمادست"، باید دیگر افزونه های حمل و نقل را غیر فعال کنید.', 'amadast-shipping-wp'),
                'condition' => is_plugin_active('persian-woocommerce-shipping/woocommerce-shipping.php'),
                'type' => 'warning',
                'dismissible' => true,
            ],
            [
                'id' => 'cart-page-compatibility',
                'content' => __('افزونه آمادست از ساختار صفحه سبد خرید شما، پشتیبانی نمیکند، به پشتیبانی آمادست پیام دهید.', 'amadast-shipping-wp'),
                'condition' => ![$this, 'is_cart_page_compatible'](),
                'type' => 'error',
                'dismissible' => false,
            ],
            [
                'id' => 'checkout-page-compatibility',
                'content' => __('افزونه آمادست از ساختار صفحه پرداخت شما، پشتیبانی نمیکند، به پشتیبانی آمادست پیام دهید.', 'amadast-shipping-wp'),
                'condition' => ![$this, 'is_checkout_page_compatible'](),
                'type' => 'error',
                'dismissible' => false,
            ],
        ];
    }

    public function is_cart_page_compatible(): bool
    {
        return true;
        $cart_page_id = wc_get_page_id('cart');

        $cart_page_content = get_post_field('post_content', $cart_page_id);

        if (str_contains($cart_page_content, '[woocommerce_cart]')) {
            return true;
        }

        if (str_contains($cart_page_content, 'wp:woocommerce/classic-shortcode')) {
            return true;
        }

        return false;
    }

    public function is_checkout_page_compatible(): bool
    {
        return true;
        $checkout_page_id = wc_get_page_id('checkout');

        $checkout_page_content = get_post_field('post_content', $checkout_page_id);

        if (str_contains($checkout_page_content, '[woocommerce_checkout]')) {
            return true;
        }

        if (str_contains($checkout_page_content, 'wp:woocommerce/classic-shortcode {"shortcode":"checkout"}')) {
            return true;
        }

        return false;
    }
}

new AMDSP_Notice();
