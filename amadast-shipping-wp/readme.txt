=== Amadast Shipping WP ===

Contributors: amadast, alih70442
Tags: shipping, delivery, online-shipping-calculation, online store, ecommerce
Requires at least: 5.8.0
Tested up to: 6.7.1
Stable tag: 2.1.1
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
A plugin that calculates shipping prices online with various sending methods.

== Description ==

A plugin that calculates shipping prices online with various sending methods.

== Screenshots ==

1- صفحه تنظیمات افزونه
2- نمایش قیمت به مشتریان در صفحه سبد خرید
3- نمایش قیمت به مشتریان در صفحه پرداخت

== External Service ==

Amadast plugin uses an external API for calculating different shipping method prices online.
Shipping method prices are changing according to origin, destination, package weight, package type and package value.
These data are sent to Amadast Calculator Core to retrieve exact and fresh prices.

These are links for more information:
- [Amadast site](https://amadast.com?utm_source=wp_plugin&utm_medium=plugin_page&utm_campaign=read_me)
- [Amadast terms](https://amadast.com/terms?utm_source=wp_plugin&utm_medium=plugin_page&utm_campaign=read_me)

The data that is being sent to Amadast from your site is:
- origin city
- destination city
- package weight
- package value
- package type
- selected couriers
- plugin version that is used
- your site URL
- your site admin email for contact
- your site name
- your site description
- Amadast plugin options
If the API is not responding, a default value is used.

== Installation ==

= Using The WordPress Dashboard =

1. Navigate to the Add New in the plugins dashboard
2. Search for Amadast
3. Click Install Now
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the Add New in the plugins dashboard
2. Navigate to the Upload area
3. Select amadast-shipping-wp.zip from your computer
4. Click Install Now
5. Activate the plugin in the Plugin dashboard
