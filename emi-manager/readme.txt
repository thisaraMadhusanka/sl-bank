=== EMI Manager for WooCommerce ===
Contributors: emimanager
Tags: woocommerce, emi, installment, payment plans, banks
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 8.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Configure and display dynamic installment (EMI) payment plans per bank for your WooCommerce store.

== Description ==

EMI Manager for WooCommerce allows store owners to configure and display dynamic installment (EMI) payment plans per bank on product pages.

**Key Features:**

* Global EMI toggle with rounding and tax configuration
* Add unlimited banks with logos (WordPress media uploader)
* Define installment plans per bank (months, surcharge %, fixed fee)
* Per-product EMI overrides (allow/block specific banks, custom surcharge)
* Dynamic EMI recalculation for variable products
* Beautiful accordion UI inspired by leading e-commerce platforms
* REST API endpoint for headless/custom integrations
* Fully responsive mobile-first design
* Accessible markup with ARIA attributes
* Transient caching for optimal performance

**Supported Product Types:**

* Simple products
* Variable products
* Grouped products
* Bundled products (basic support)

== Installation ==

1. Upload the `emi-manager` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Dashboard → EMI Manager** to configure banks and plans
4. EMI information will automatically appear on product pages

== Frequently Asked Questions ==

= Does this plugin support variable products? =

Yes! When a customer selects a product variation, the EMI table updates dynamically without page reload.

= Can I override EMI settings for specific products? =

Yes. Each product has an EMI Settings meta box where you can use global settings, select specific banks, override surcharge percentages, or disable EMI entirely.

= Is there a REST API? =

Yes. The plugin exposes `GET /wp-json/emi/v1/product/{id}?variation={id}&bank={id}` for headless integrations.

== Changelog ==

= 1.0.0 =
* Initial release
* Global EMI settings with toggle, rounding, and tax mode
* Bank management with logo upload and installment plans
* Product-level EMI overrides
* Frontend accordion display
* Dynamic variation price updates
* REST API endpoint
* Uninstall cleanup

== Upgrade Notice ==

= 1.0.0 =
Initial release.
