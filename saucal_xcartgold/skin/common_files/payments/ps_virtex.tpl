<h1>Virtex</h1>

{capture name=dialog}
<form action="cc_processing.php?cc_processor={$smarty.get.cc_processor|escape:"url"}" method="post">
    
	<table cellspacing="10">

      <tr>
        <td>API Key:</td>
        <td><input type="text" name="param01" value="{$module_data.param01|escape}" /></td>
      </tr>

      <tr>
        <td>Store Currency:</td>
        <td>
          <select name="param03">
            <option value="BTC"{if $module_data.param03 eq "BTC"} selected="selected"{/if}>Bitcoin (BTC)</option>
            <option value="USD"{if $module_data.param03 eq "USD"} selected="selected"{/if}>U.S. Dollars (USD)</option>
            <option value="EUR"{if $module_data.param03 eq "EUR"} selected="selected"{/if}>Euro (EUR)</option>
            <option value="GBP"{if $module_data.param03 eq "GBP"} selected="selected"{/if}>Pounds Sterling (GBP)</option>
            <option value="AUD"{if $module_data.param03 eq "AUD"} selected="selected"{/if}>Australian Dollars (AUD)</option>
            <option value="BGN"{if $module_data.param03 eq "BGN"} selected="selected"{/if}>Bulgarian Lev (BGN)</option>
            <option value="BRL"{if $module_data.param03 eq "BRL"} selected="selected"{/if}>Brazilian Real (BRL)</option>
            <option value="CAD"{if $module_data.param03 eq "CAD"} selected="selected"{/if}>Canadian Dollar (CAD)</option>
            <option value="CHF"{if $module_data.param03 eq "CHF"} selected="selected"{/if}>Swiss Franc (CHF)</option>
            <option value="CNY"{if $module_data.param03 eq "CNY"} selected="selected"{/if}>Chinese Yuan (CNY)</option>
            <option value="CZK"{if $module_data.param03 eq "CZK"} selected="selected"{/if}>Czech Koruna (CZK)</option>
            <option value="DKK"{if $module_data.param03 eq "DKK"} selected="selected"{/if}>Danish Krone (DKK)</option>
            <option value="HKD"{if $module_data.param03 eq "HKD"} selected="selected"{/if}>Hong Kong Dollar (HKD)</option>
            <option value="HRK"{if $module_data.param03 eq "HRK"} selected="selected"{/if}>Croatian Kuna (HRK)</option>
            <option value="HUF"{if $module_data.param03 eq "HUF"} selected="selected"{/if}>Hungarian Forint (HUF)</option>
            <option value="IDR"{if $module_data.param03 eq "IDR"} selected="selected"{/if}>Indonesian Rupiah (IDR)</option>
            <option value="ILS"{if $module_data.param03 eq "ILS"} selected="selected"{/if}>Israeli New Sheqel (ILS)</option>
            <option value="INR"{if $module_data.param03 eq "INR"} selected="selected"{/if}>Indian Rupee (INR)</option>
            <option value="JPY"{if $module_data.param03 eq "JPY"} selected="selected"{/if}>Yen (JPY)</option>
            <option value="KRW"{if $module_data.param03 eq "KRW"} selected="selected"{/if}>South Korean Won (KRW)</option>
            <option value="LTL"{if $module_data.param03 eq "LTL"} selected="selected"{/if}>Lithuanian Litas (LTL)</option>
            <option value="LVL"{if $module_data.param03 eq "LVL"} selected="selected"{/if}>Latvian Lats (LVL)</option>
            <option value="MXN"{if $module_data.param03 eq "MXN"} selected="selected"{/if}>Mexican Peso (MXN)</option>
            <option value="MYR"{if $module_data.param03 eq "MYR"} selected="selected"{/if}>Malaysian Ringgit (MYR)</option>
            <option value="NOK"{if $module_data.param03 eq "NOK"} selected="selected"{/if}>Norwegian Krone (NOK)</option>
            <option value="NZD"{if $module_data.param03 eq "NZD"} selected="selected"{/if}>New Zealand Dollar (NZD)</option>
            <option value="PLN"{if $module_data.param03 eq "PLN"} selected="selected"{/if}>Polish Zloty (PLN)</option>
            <option value="RON"{if $module_data.param03 eq "RON"} selected="selected"{/if}>New Romanian Leu (RON)</option>
            <option value="RUB"{if $module_data.param03 eq "RUB"} selected="selected"{/if}>Russian Rouble (RUB)</option>
            <option value="SEK"{if $module_data.param03 eq "SEK"} selected="selected"{/if}>Swedish Krona (SEK)</option>
            <option value="SGD"{if $module_data.param03 eq "SGD"} selected="selected"{/if}>Singapore Dollar (SGD)</option>
            <option value="THB"{if $module_data.param03 eq "THB"} selected="selected"{/if}>Thai Baht (THB)</option>
            <option value="TRY"{if $module_data.param03 eq "TRY"} selected="selected"{/if}>Turkish Lira (TRY)</option>
            <option value="ZAR"{if $module_data.param03 eq "ZAR"} selected="selected"{/if}>South African Rand (ZAR)</option>
          </select>
        </td>
      </tr>

    </table>

    <br />
    <br />

    <input type="submit" value="{$lng.lbl_update|strip_tags:false|escape}" />

</form>
{/capture}
{include file="dialog.tpl" title=$lng.lbl_cc_settings content=$smarty.capture.dialog extra='width="100%"'}
