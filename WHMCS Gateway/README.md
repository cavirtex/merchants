# CAVIRTEX Bitcoin Payment Gateway Module for WHMCS

This module is a simple and secure payment gateway for those wanting to offer payment in Bitcoin through VirtEx from within WHMCS. Minimum PHP version is 5.2.0.

Its basic operation is outlined as follows:

1. User clicks "Pay with Bitcoin"
2. User is redirected to VirtEx's `merchant_invoice` page, which displays payment processing information and directions in a very understandable format.
3. User completes the payment by sending the full Bitcoin value to a specified address.
4. VirtEx receives this payment and notifies WHMCS via the merchant's IPN URL.
5. Module uses custom data to find the corresponding WHMCS invoice in the database.
6. Module checks with VirtEx's `merchant_confirm_ipn` API to confirm the IPN data.
8. Module marks the invoice as "paid" and logs the transaction.

Theoretically the module can accept multiple VirtEx transactions to fulfill a single WHMCS invoice but this is untested.

## Installation

1. Move all files and directories in gateways folder to the `whmcs/modules/gateways` directory. The directory structure needs to remain intact during this move.

2. You may want to replace the `GoDaddyClass2CA.crt` in the `whmcs/modules/gateways/cavirtex` directory with GoDaddy's Class 2 Root CA certificate in PEM format. This is optional if you **a)** trust where you obtained this code from **b)** and it's before the certificate's expiry on June 29, 2034 at 5:06:20 PM UTC.

3. Go to your WHMCS admin panel and then navigate to `Setup -> Payments -> Payment Gateways`.

4. Select VirtEx from the Activate Module dropdown and click the Activate button.

5. Enter your Merchant Key and Secret Key, both of which can be found on your [Merchant Information][MerchantInformation] page.

6. Copy and paste your given IPN URL into the proper text box on your [Merchant Information][MerchantInformation] page and submit that form. If you paste that link in to your browser and it returns a blank page (but not a 404!) then the automatically generated IPN URL is probably correct. It should look something like the following: `http://example.com/yourwhmcsdirectory/modules/gateways/callback/cavirtex.php` The exact link will be available to you on the 'Payment Gateways' page above.

7. Select CAD in the "Convert To For Processing" box. If you don't see CAD, you should set up that currency in `Setup -> Payments -> Currencies`. Exactly how to do that resides outside the scope of this installation tutorial.

8. Click the Save Changes button. Installation is complete. You can now use VirtEx just like any other WHMCS payment module.


[MerchantInformation]: https://www.cavirtex.com/merchant_information