# CAVIRTEX Bitcoin Payment Gateway Module for WHMCS
Written by [Kevin Mark][kmark] <<kevin@versobit.com>>

This module is a simple and secure payment gateway for those wanting to offer payment in
Bitcoin through VirtEx from within WHMCS. Its basic operation is outlined as follows:

1. User clicks "Pay with Bitcoin"
2. Module calls the `merchant_purchase` API.
3. VirtEx `order_key` is stored within a custom table, which links our internal WHMCS invoice ID with VirtEx's invoice.
4. User is redirected to VirtEx's `merchant_invoice` page, which displays payment processing information and directions in a very understandable format.
5. User completes the payment by sending the full Bitcoin value to a specified address.
6. VirtEx receives this payment and notifies WHMCS via the merchant's IPN URL.
7. Module checks for the `order_key` in the database and matches it with the corresponding WHMCS invoice.
8. Module checks with VirtEx's `merchant_confirm_ipn` API to confirm the IPN data.
9. Module marks the invoice as "paid" and logs the transaction.


## Installation

Due to WHMCS's simple payment gateway interface and VirtEx's current API limitations,
additional setup is required to provide a reliable transaction gateway. Most notably
this includes manually executing a `CREATE TABLE` MySQL query.

1. Move all files and directories except this `README.md` into the `whmcs/modules/gateways directory`. The directory structure needs to remain intact during this move.

2. Replace the `GoDaddyClass2CA.crt` in the `whmcs/modules/gateways/cavirtex` directory with GoDaddy's Class 2 Root CA certificate in PEM format. This is optional if you **a)** trust me **b)** trust where you obtained this code from **c)** and it's before the certificate's expiry on June 29, 2034 at 5:06:20 PM UTC. So yeah, it's optional.

3. Run [the below SQL query][MySQLInstallationQuery] on your WHMCS database. This can be easily done from phpMyAdmin. You can find a link to phpMyAdmin on your account's cPanel if applicable.

4. Go to your WHMCS admin panel and then navigate to `Setup -> Payments -> Payment Gateways`.

5. Select VirtEx from the Activate Module dropdown and click the Activate button.

6. Enter your Merchant Key and Secret Key, both of which can be found on your [Merchant Information][MerchantInformation] page.

7. Copy and paste your given IPN URL into the proper text box on your [Merchant Information][MerchantInformation] page and submit that form. If you paste that link in to your browser and it returns a blank page (but not a 404!) then the automatically generated IPN URL is probably correct. It should look something like the following: `http://example.com/yourwhmcsdirectory/modules/gateways/callback/cavirtex.php`

8. Select CAD in the "Convert To For Processing" box. If you don't see CAD, you should set up that currency in `Setup -> Payments -> Currencies`. Exactly how to do that resides outside the scope of this installation tutorial.

9. Click the Save Changes button. Installation is complete. You can now use VirtEx just like any other WHMCS payment module.

## MySQL Installation Query

You only need to run this once during the initial setup of the VirtEx module.

    CREATE TABLE IF NOT EXISTS `tblcavirtex` (
      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `whmcsid` int(10) NOT NULL,
      `orderkey` varchar(50) NOT NULL,
      `btc` varchar(20) NOT NULL,
      `expires` int(10) unsigned NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `orderkey` (`orderkey`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


[kmark]: http://github.com/kmark
[MerchantInformation]: https://www.cavirtex.com/merchant_information
[MySQLInstallationQuery]: #mysql-installation-query