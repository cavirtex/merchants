commerceSEO v2 CE VirtEx Module
===============================

Permission is hereby granted to any person obtaining a copy of this software and associated documentation for use and/or modification in association with the cavirtex.com service.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Bitcoin payment module using the cavirtex.com service.

Installation
============

+ Copy the callback, includes, and lang folders into your site directory (it should merge the folders into the existing commerceSEO folders).

Configuration
=============

+ Create an API key at cavirtex.com under My Account > API Access Keys

+ In your commerceSEO admin panel under Modules > Payment, install the "Bitcoin" module

+ Fill out all of the configuration information:

	+ Verify that the module is enabled.

	+ Copy/Paste the cavirtex.com API key you created into the API Key field

	+ Choose a status for unpaid and paid orders (or leave the default values as defined).

	+ Verify that the currencies displayed corresponds to what you want and to those accepted by cavirtex.com (the defaults are what VirtEx accepts as of this writing).

Usage
=====

+ When a user chooses the "Bitcoin" payment method, they will be presented with an order summary as the next step (prices are shown in whatever currency they've selected for shopping). Upon confirming their order, the system takes the user to cavirtex.com. Once payment is received, a link is presented to the shopper that will take them back to your website.

+ In your Admin control panel, you can see the orders made via Bitcoins just as you could see for any other payment mode. The status you selected in the configuration steps above will indicate whether the order has been paid for.

**Note:** This extension does not provide a means of automatically pulling a current BTC exchange rate for presenting BTC prices to shoppers.