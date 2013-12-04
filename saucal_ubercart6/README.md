
Drupal 6.x Ubercart 2.x Plugin
==============================

Permission is hereby granted to any person obtaining a copy of this software and associated documentation for use and/or modification in association with the cavirtex.com service.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Bitcoin payment module using the cavirtex.com service.

Installation
============
+ Copy these files into sites/all/modules/ in your Drupal directory.

Configuration
=============
+ Sign up for a merchant account with Virtex, at https://cavirtex.com. Be sure to read all provided information thoroughly, and to understand the fees that will be charged.

+ On your Virtex merchant page, provide deposit information. This can be information for your bank account, or a forwarding bitcoin address, or some mixture thereof (you can set the funds to be converted to different currencies in differing proportions.)

+ Create an API key at https://cavirtex.com by clicking Merchant > Merchant Profile > Merchant Key.

+ Under Administer > Site Building > Modules, verify that the Virtex module is enabled under the Ubercart - payment section.

+ Under Store Administration > Configuration > Payment Settings > Payment Methods, enable the Virtex payment method, and then go to the Virtex settings menu.

+ Enter your API Key under the Administrator settings dropdown menu, and enter other settings as desired.


Usage
=====
+ When a shopper chooses the Bitcoin payment method, they will be presented with an order summary as the next step (prices are shown in whatever currency they've selected for shopping).

+ Here, the shopper can either pay to the one-time-use address given, scan the QR code to pay, or use the Pay With Bitcoin button if they're using a URI-compatible wallet.

**Note:** This extension does not provide a means of automatically pulling a current BTC exchange rate for presenting BTC prices to shoppers.
