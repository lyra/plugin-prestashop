{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<div style="padding-left: 40px;" id="payzen_sepa_oneclick_payment_description">
  <ul id="payzen_sepa_oneclick_payment_description_1">
    <li>
      <span class="payzen_span">{l s='You will pay with your registered means of payment' mod='payzen'}<b> {$payzen_sepa_saved_payment_mean|escape:'html':'UTF-8'}. </b>{l s='No data entry is needed.' mod='payzen'}</span>
    </li>

    <li style="margin: 8px 0px 8px;">
      <span class="payzen_span">{l s='OR' mod='payzen'}</span>
    </li>

    <li>
      <p class="payzen_link" onclick="payzenSepaOneclickPaymentSelect(0)">{l s='Click here to pay with another means of payment.' mod='payzen'}</p>
    </li>
  </ul>
  <ul id="payzen_sepa_oneclick_payment_description_2" style="display: none;">
    <li>{l s='You will enter payment data after order confirmation.' mod='payzen'}</li>
    <li style="margin: 8px 0px 8px;">
      <span class="payzen_span">{l s='OR' mod='payzen'}</span>
    </li>
    <li>
      <p class="payzen_link" onclick="payzenSepaOneclickPaymentSelect(1)">{l s='Click here to pay with your registered means of payment.' mod='payzen'}</p>
    </li>
  </ul>

  <script type="text/javascript">
    function payzenSepaOneclickPaymentSelect(paymentByIdentifier) {
      if (paymentByIdentifier) {
        $('#payzen_sepa_oneclick_payment_description_1').show();
        $('#payzen_sepa_oneclick_payment_description_2').hide()
        $('#payzen_sepa_payment_by_identifier').val('1');
      } else {
        $('#payzen_sepa_oneclick_payment_description_1').hide();
        $('#payzen_sepa_oneclick_payment_description_2').show();
        $('#payzen_sepa_payment_by_identifier').val('0');
      }
    }
  </script>
</div>