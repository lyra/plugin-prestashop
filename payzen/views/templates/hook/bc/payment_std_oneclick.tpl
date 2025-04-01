{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<div style="padding-left: 40px;" id="payzen_oneclick_payment_description">
  <ul id="payzen_oneclick_payment_description_1">
    <li>
      <span class="payzen_span">{l s='You will pay with your registered means of payment' mod='payzen'}<b> {$payzen_saved_payment_mean|escape:'html':'UTF-8'}. </b>{l s='No data entry is needed.' mod='payzen'}</span>
    </li>

    <li style="margin: 8px 0px 8px;">
      <span class="payzen_span">{l s='OR' mod='payzen'}</span>
    </li>

    <li>
      <p class="payzen_link" onclick="payzenOneclickPaymentSelect(0)">{l s='Click here to pay with another means of payment.' mod='payzen'}</p>
    </li>
  </ul>
{if ($payzen_std_card_data_mode == '2')}
  <script type="text/javascript">
    function payzenOneclickPaymentSelect(paymentByIdentifier) {
      if (paymentByIdentifier) {
        $('#payzen_oneclick_payment_description').show();
        $('#payzen_standard').hide();
        $('#payzen_payment_by_identifier').val('1');
      } else {
        $('#payzen_oneclick_payment_description').hide();
        $('#payzen_standard').show();
        $('#payzen_payment_by_identifier').val('0');
      }
    }
  </script>
{else}
  <ul id="payzen_oneclick_payment_description_2" style="display: none;">
    {if $payzen_std_card_data_mode == '7' || $payzen_std_rest_popin_mode == 'True'}
      <li>{l s='You will enter payment data after order confirmation.' mod='payzen'}</li>
    {/if}

      <li style="margin: 8px 0px 8px;">
        <span class="payzen_span">{l s='OR' mod='payzen'}</span>
      </li>
      <li>
        <p class="payzen_link" onclick="payzenOneclickPaymentSelect(1)">{l s='Click here to pay with your registered means of payment.' mod='payzen'}</p>
      </li>
  </ul>

  <script type="text/javascript">
    $(document).ready(function() {
       sessionStorage.setItem('payzenIdentifierToken', "{$payzen_rest_identifier_token|escape:'html':'UTF-8'}");
       sessionStorage.setItem('payzenToken', "{$payzen_rest_form_token|escape:'html':'UTF-8'}");
    });

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
          token = sessionStorage.getItem('payzenIdentifierToken');
        } else {
          token = sessionStorage.getItem('payzenToken');
        }

        KR.setFormConfig({ formToken: token, language: PAYZEN_LANGUAGE });
      {/if}
    }
    </script>
{/if}

{if ($payzen_std_card_data_mode != '7' && $payzen_std_card_data_mode != '8' && $payzen_std_card_data_mode != '9')}
    {if version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
      <input id="payzen_standard_link" value="{l s='Pay' mod='payzen'}" class="button" />
    {else}
      <button id="payzen_standard_link" class="button btn btn-default standard-checkout button-medium">
        <span>{l s='Pay' mod='payzen'}</span>
      </button>
    {/if}
{/if}
</div>