{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

{if $payzen_std_rest_popin_mode == 'True'}
  <style type="text/css">
    .kr-smart-button-wrapper, button.kr-smart-form-modal-button {
      display: none !important;
    }
  </style>
{/if}

<!-- This meta tag is mandatory to avoid encoding problems caused by \PrestaShop\PrestaShop\Core\Payment\PaymentOptionFormDecorator -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<section id="payzen_standard_rest_wrapper" style="margin-bottom: 3rem;">
  {if $payzen_std_card_data_mode == '5'}
    <div class="payzen kr-embedded"{if $payzen_std_rest_popin_mode == 'True'}kr-popin{/if} kr-form-token="{$payzen_rest_identifier_token|escape:'html':'UTF-8'}">
      <div class="kr-pan"></div>
      <div class="kr-expiry"></div>
      <div class="kr-security-code"></div>
      <button type="button" class="kr-payment-button" {if $payzen_std_rest_popin_mode != 'True'}style="display: none;"{/if}></button>
      <div class="kr-field processing" style="display: none; border: none !important;">
        <div style="background-image: url('{$smarty.const._MODULE_DIR_|escape:'html':'UTF-8'}payzen/views/img/loading_big.gif');
                  margin: 0 auto; display: block; height: 35px; background-color: #ffffff; background-position: center;
                  background-repeat: no-repeat; background-size: 35px;">
        </div>
      </div>

      <div class="kr-form-error"></div>
    </div>
  {elseif $payzen_std_card_data_mode === '7' || $payzen_std_card_data_mode === '8' || $payzen_std_card_data_mode === '9'}
    <div class="kr-smart-form" {if $payzen_std_rest_popin_mode == 'True'} kr-popin {/if} {if $payzen_std_card_data_mode === '8' || $payzen_std_card_data_mode === '9'} kr-card-form-expanded {/if} {if $payzen_std_card_data_mode === '9'} kr-no-card-logo-header {/if} kr-form-token="{$payzen_rest_identifier_token|escape:'html':'UTF-8'}"></div>
  {/if}
</section>

<script type="text/javascript">
  $(document).ready(function() {
    $('input[type="radio"][name="payment-option"]').on('click', function(e) {
      payzenManageButtonDisplay();
    });

    var paymentOptions = $('.payment-option');
    if (paymentOptions && paymentOptions.length == 1) {
      $("#payment-option-1").prop("checked", true);
      $('#payment-option-1-additional-information').addClass('payzen-show-options');
      {if $payzen_std_display_title != 'True'}
        $('#payment-option-1-container').hide();
      {/if}
    } else {
      $('#payment-option-1-additional-information').removeClass('payzen-show-options');
    }

    {if $payzen_std_smartform_compact_mode == 'True'}
      KR.setFormConfig({ cardForm: { layout: 'compact' }, smartForm: { layout: 'compact'} });
    {/if}

    {if $payzen_std_smartform_payment_means_grouping_threshold != 'False'}
      KR.setFormConfig({ smartForm: { groupingThreshold: "{$payzen_std_smartform_payment_means_grouping_threshold|escape:'html':'UTF-8'}" } });
    {/if}

    KR.onFormReady(() => {
      payzenManageButtonDisplay();

      {if $payzen_std_rest_popin_mode == 'True'}
        var element = $(".kr-smart-button");
        if (element.length > 0) {
          element.hide();
        } else {
          element = $(".kr-smart-form-modal-button");
          if (element.length > 0) {
            element.hide();
          }
        }
      {/if}
    })
  });

  var payzenManageButtonDisplay = async function() {
    {if ($payzen_std_rest_popin_mode === 'True') || ($payzen_std_card_data_mode === '5')}
      return;
    {/if}
 
    var methods = await KR.getPaymentMethods().then(function(result) {
       return result;
    });

    // If only the card form is available, hide our payment button and use Prestashop button.
    if ((methods.paymentMethods.length == 1) && (methods.paymentMethods[0] == 'CARDS')) {
      $(".kr-payment-button").hide();
      return;
    }

    var currentOptionId = $("input[type='radio'][name='payment-option']:checked").attr('id');
    if ($("#" + currentOptionId + "-additional-information").find("#payzen_standard_rest_wrapper").length > 0) {
      $("#payment-confirmation").addClass('payzen-hide-confirmation');
    } else {
      $("#payment-confirmation").removeClass('payzen-hide-confirmation');
    }
  };

  var payzenSubmit = async function(e) {
    e.preventDefault();

    if (!$('#payzen_standard').data('submitted')) {
      var isSmartform = $('.kr-smart-form');
      var smartformModalButton = $('.kr-smart-form-modal-button');

      {if $payzen_is_valid_std_identifier && $payzen_std_rest_popin_mode != 'True'}
        $('#payzen_oneclick_payment_description').hide();
      {/if}

      {if $payzen_std_rest_popin_mode == 'True'}
        KR.openPopin();

        $('#payment-confirmation button').removeAttr('disabled');
      {else}
        $('#payzen_standard').data('submitted', true);
        $('.payzen .processing').css('display', 'block');
        $('#payment-confirmation button').attr('disabled', 'disabled');

        if (PAYZEN_LAST_CART == false) {
            await payzenRefreshToken(true);
        }

        KR.submit();
      {/if}
    }

    return false;
  };
</script>