Magento payment module for Bitcoin integration using cavirtex.com
=================================================================

Notice: To use this payment module your shop must be running in CAD currency! If you are using any other currency, make sure you have exchange rates filled in your Magento admin panel by going to `System -> Manage Currency -> Rates` (to convert store currency to CAD)


Installation
------------

1. Copy all files from this repository into your magento root directory
2. Open your magento admin panel and go to `System -> Configuration -> Advanced -> Disable Modules Output` and make sure `Bitcoin_VirtEx` is listed
3. Go to `System -> Configuration -> Payment Methods`
4. Switch Enabled to yes, fill in your Merchant Secret and Key. This can be obtained from your [Merchant Page][MerchantInformation] on cavirtex.
5. Don't forget to save!
6. Fill in your IPN URL: `https://your-shop-domain.com/virtex/payment/ipn` in your [Merchant Page][MerchantInformation] on cavirtex.

You can now accept payments in Bitcoin using your cavirtex.com merchant account!

[MerchantInformation]: https://www.cavirtex.com/merchant_information