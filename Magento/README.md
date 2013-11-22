Magento payment module for Bitcoin integration using cavirtex.com
=================================================================


Notice: To use this payment module your shop must be running in CAD currency!


Installation
------------

1. Copy all files from this repository into your magento root directory
2. Open your magento admin panel and go to System -> Configuration ->
Advanced -> Disable Modules Output and make shure Bitcoin_VirtEx is
listed
3. Go to System -> Configuration -> Payment Methods
4. Switch Enabled to yes, fill in your Merchant Secret and Key
5. Don't forget to save!
6. In your cavirtex.com merchant profile go to "Merchant Profile"
7. Fill in your IPN URL: https://your-shop-domain.com/virtex/payment/ipn

You can now accept payments in Bitcoin using your cavirtex.com merchant
account!
