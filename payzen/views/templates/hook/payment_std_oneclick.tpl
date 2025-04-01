{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<!-- This meta tag is mandatory to avoid encoding problems caused by \PrestaShop\PrestaShop\Core\Payment\PaymentOptionFormDecorator -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<section style="margin-bottom: 2rem;">
<div id="payzen_oneclick_payment_description">
  <ul id="payzen_oneclick_payment_description_1">
    <li>
      <span>{l s='You will pay with your registered means of payment' mod='payzen'}<b> {$payzen_saved_payment_mean|escape:'html':'UTF-8'}. </b>{l s='No data entry is needed.' mod='payzen'}</span>
    </li>

    <li style="margin: 8px 0px 8px;">
      <span>{l s='OR' mod='payzen'}</span>
    </li>

    <li>
      <a href="javascript: void(0);" onclick="payzenOneclickPaymentSelect(0)">{l s='Click here to pay with another means of payment.' mod='payzen'}</a>
    </li>
  </ul>
{if ($payzen_std_card_data_mode == '2')}
  </div>
    <script type="text/javascript">
      function payzenOneclickPaymentSelect(paymentByIdentifier) {
        if (paymentByIdentifier) {
          $('#payzen_oneclick_payment_description_1').show();
          $('#payzen_standard').hide();
          $('#payzen_payment_by_identifier').val('1');
        } else {
          $('#payzen_oneclick_payment_description_1').hide();
          $('#payzen_standard').show();
          $('#payzen_payment_by_identifier').val('0');
         }
       }
     </script>
{else}
    <ul id="payzen_oneclick_payment_description_2" style="display: none;">
      {if $payzen_std_rest_popin_mode == 'True' || $payzen_std_card_data_mode == '7'}
        <li>{l s='You will enter payment data after order confirmation.' mod='payzen'}</li>
      {/if}

      <li style="margin: 8px 0px 8px;">
        <span>{l s='OR' mod='payzen'}</span>
      </li>
      <li>
        <a href="javascript: void(0);" onclick="payzenOneclickPaymentSelect(1)">{l s='Click here to pay with your registered means of payment.' mod='payzen'}</a>
      </li>
    </ul>
  </div>

  <script type="text/javascript">
    function payzenOneclickPaymentSelect(paymentByIdentifier) {
      if (paymentByIdentifier) {
        $('#payzen_oneclick_payment_description_1').show();
        $('#payzen_oneclick_payment_description_2').hide()
        $('#payzen_payment_by_identifier').val('1');
      } else {
        $('#payzen_oneclick_payment_description_1').hide();
        $('#payzen_oneclick_payment_description_2').show();
        $('#payzen_payment_by_identifier').val('0');
      }

      {if ($payzen_std_card_data_mode == '7' || $payzen_std_card_data_mode == '8' || $payzen_std_card_data_mode == '9')}
        $('.payzen .kr-form-error').html('');

        var token;
        if ($('#payzen_payment_by_identifier').val() == '1') {
          token = "{$payzen_rest_identifier_token|escape:'html':'UTF-8'}";
        } else {
          token = "{$payzen_rest_form_token|escape:'html':'UTF-8'}";
        }

        KR.setFormConfig({ formToken: token, language: PAYZEN_LANGUAGE });
      {/if}
    }
  </script>
{/if}
</section>

<script type="text/javascript">
  window.onload = function() {
      $("input[data-module-name=payzen]").change(function() {
        if ($(this).is(':checked')) {
          payzenOneclickPaymentSelect(1);
          if (typeof payzenSepaOneclickPaymentSelect == 'function') {
            payzenSepaOneclickPaymentSelect(1);
          }
        }
      });
  };
</script>