=== Bitcoin Payment Gateway for WooCommerce ===

A WooCommerce payment gateway that allows customers to pay using Bitcoin via the Canadian Virtual Exchange (CaVirtEx).

== Installation ==

After placing the `woocommerce_cavirtex` plugin folder in the `wp-content/plugins` directory of your WooCommerce-enabled Wordpress site, activate it in the 'Plugins' menu, then proceed as follows:

1. Select the 'WooCommerce' > 'Settings' menu item
2. Select the 'Payment Gateways' tab
3. Select the 'Bitcoin' sub-tab
4. Enter your 'CaVirtEx Merchant Key'
5. Enter your 'CaVirtEx Merchant Secret Key'
6. Click the 'Save Changes' button

![Installation](assets/installation.png)

== Frequently Asked Questions ==

= What should my IPN URL be? =

IPN URL = Wordpress Site Address (URL) + **?wc-api=WC_Gateway_Cavirtex**

e.g. `http://example.com/wp?wc-api=WC_Gateway_Cavirtex`

You can find you `Site Address (URL)` in the 'Settings' > 'General' page of your Wordpress Admin Panel

= Where do I set my IPN URL? =

You'll need to set your IPN URL in your [CaVirtEx Merchant Profile page](https://www.cavirtex.com/merchant_information).