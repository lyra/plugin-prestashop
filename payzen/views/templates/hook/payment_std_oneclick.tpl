{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra-network.com/)
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
          $("#payzen_oneclick_payment_description_1").show();
          $("#payzen_standard").hide();
          $("#payzen_payment_by_identifier").val("1");
        } else {
          $("#payzen_oneclick_payment_description_1").hide();
          $("#payzen_standard").show();
          $("#payzen_payment_by_identifier").val("0");
         }
       }
     </script>
{else}
    <ul id="payzen_oneclick_payment_description_2" style="display: none;">
      {if ($payzen_std_card_data_mode != '5') || $payzen_rest_popin}
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
        $("#payzen_oneclick_payment_description_1").show();
        $("#payzen_oneclick_payment_description_2").hide()
        $("#payzen_payment_by_identifier").val("1");
      } else {
        $("#payzen_oneclick_payment_description_1").hide();
        $("#payzen_oneclick_payment_description_2").show();
        $("#payzen_payment_by_identifier").val("0");
      }

      {if ($payzen_std_card_data_mode == '5')}
         payzenUpdateRestToken();
           setTimeout(function () {
             payzenInitRestEvents();
           }, 200);
      {/if}
    }

    function payzenUpdateRestToken() {
      KR.removeForms();

      if ($("#payzen_payment_by_identifier").val() == '1') {
        var token = "{$payzen_rest_identifier_token|escape:'html':'UTF-8'}";
      } else {
        var token = "{$payzen_rest_form_token|escape:'html':'UTF-8'}";
      }

      var isPopin = document.getElementsByClassName('kr-popin-button');
      if (isPopin.length !== 0) {
        var button =  '<button type="button" id="payzen_hidden_button" class="kr-payment-button"></button>';
      } else {
        var button = '<div style="display: none;">'
                   + '    <button type="button" id="payzen_hidden_button" class="kr-payment-button"></button>'
                   + '</div>';
      }

      $("#payzen_standard_rest_wrapper").html(
            '  <div class="payzen kr-embedded" {if $payzen_rest_popin} kr-popin{/if} kr-form-token="' + token + '" >'
            + '  <div class="kr-pan"></div>'
            + '  <div class="kr-expiry"></div>'
            + '  <div class="kr-security-code"></div>'

            + button

            + '  <div class="kr-field processing" style="display: none; border: none !important;">'
            + '      <div style="background-image: url({$smarty.const._MODULE_DIR_|escape:'html':'UTF-8'}payzen/views/img/loading_big.gif);'
            + '                  margin: 0 auto; display: block; height: 35px; background-color: #ffffff; background-position: center;'
            + '                  background-repeat: no-repeat; background-size: 35px;">'
            + '      </div>'
            + '  </div>'
            + '  <div class="kr-form-error"></div>'
            + '</div>');
    }
  </script>
{/if}
</section>

{block name='javascript_bottom'}
  {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
{/block}

<script type="text/javascript">
$(document).ready(function() {
  $("input[data-module-name=payzen]").change(function() {
    if ($(this).is(':checked')) {
      payzenOneclickPaymentSelect(1);
    }
  });
});
</script>