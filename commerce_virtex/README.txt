INSTALLATION INSTRUCTIONS

1. Download and enable the module and its standard prerequisites (Drupal 7, 
  Drupal Commerce etc).

2. Navigate to the administrative store settings page for payment methods
  (admin/commerce/config/payment-methods).

3. Edit the 'Checkout using Virtex' method.

4. Edit the action. Set the 'Merchant Key' and 'Secret Key' values, based on 
  those in your Virtex account. Save the action.

5. This part is important - Virtex only accepts Canadian Dollars, so you need
  to set your store currency to CAD. This can be done from the store currency 
  settings page (admin/commerce/config/currency). You should ensure that any
  products that you added previously are also set to CAD.

6. In your Virtex merchant account, you should set the IPN callback URL. 
  Typically the url should be in the form: 
  http(s)://[your_site_url]/commerce_virtex/ipn

7. Done!