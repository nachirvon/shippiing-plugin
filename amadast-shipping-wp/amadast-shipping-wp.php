<?php
/**
 * Plugin Name: Amadast Shipping WP
 * Plugin URI: https://amadast.com/product/wordpress-plugin
 * Description: افزونه آمادست، هزینه ارسال دقیق را بر اساس وزن و مقصد مرسوله محاسبه می کند و به مشتریان اجازه میدهد روش ارسال دلخواه خود را انتخاب کنند.
 * Version: 2.1.1
 * Author: amadast.com
 * Author URI: https://amadast.com?utm_source=wp_plugin&utm_medium=plugin_page&utm_campaign=install_wp_plugin
 * Text Domain: amadast-shipping-wp
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins: woocommerce
 * Requires at least: 5.8.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0.0
 * WC tested up to: 9.5.1
 */

if (!defined('ABSPATH')) exit;

if (!defined('AMDSP_VERSION')) {
    define('AMDSP_VERSION', '2.1.1');
}

if (!defined('AMDSP_DIR')) {
    define('AMDSP_DIR', __DIR__);
}

if (!defined('AMDSP_FILE')) {
    define('AMDSP_FILE', __FILE__);
}

if (!defined('AMDSP_URL')) {
    define('AMDSP_URL', plugin_dir_url(__FILE__));
}

if (!defined('AMDSP_API_URL')) {
    define('AMDSP_API_URL', 'https://api.amadast.com/api/v1.0/tool/shipping-calculator/plugin');
}

include('utilities/index.php');
include('helpers/index.php');

function AMDSP() {
    return AMDSP_Core::get_instance();
}

add_action('woocommerce_loaded', function () {
    include('classes/woocommerce_classes.php');

    AMDSP();
}, 20);

register_activation_hook(AMDSP_FILE, function () {
    amdsp_file_put_contents(AMDSP_DIR . '/.activated', '');
});

add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', AMDSP_FILE, true);
    }
});
