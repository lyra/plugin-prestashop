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

<section id="payzen_standard_rest_wrapper" style="margin-bottom: 2rem;">
  <div class="payzen kr-embedded"{if $payzen_rest_popin} kr-popin{/if} kr-form-token="{$payzen_rest_identifier_token|escape:'html':'UTF-8'}">
     <div class="kr-pan"></div>
     <div class="kr-expiry"></div>
     <div class="kr-security-code"></div>

     {if !$payzen_rest_popin}
       <div style="display: none;">
     {/if}
     <button type="button" id="payzen_hidden_button" class="kr-payment-button"></button>
     {if !$payzen_rest_popin}
       </div>
     {/if}

     <div class="kr-field processing" style="display: none; border: none !important;">
       <div style="background-image: url('{$smarty.const._MODULE_DIR_|escape:'html':'UTF-8'}payzen/views/img/loading_big.gif');
                  margin: 0 auto; display: block; height: 35px; background-color: #ffffff; background-position: center;
                  background-repeat: no-repeat; background-size: 35px;">
       </div>
     </div>

     <div class="kr-form-error"></div>
  </div>
</section>

<script type="text/javascript">
  var payzenSubmit = function(e) {
    e.preventDefault();

    if (!$('#payzen_standard').data('submitted')) {
      var isPopin = document.getElementsByClassName('kr-popin-button');

      {if $payzen_saved_identifier}
        if (isPopin.length === 0) {
          $('#payzen_oneclick_payment_description').hide();
        }
      {/if}

      if (isPopin.length > 0) {
        $('.kr-popin-button').click();
      } else {
        $('#payzen_standard').data('submitted', true);
        $('.payzen .processing').css('display', 'block');
        $('#payment-confirmation button').attr('disabled', 'disabled');
        $('#payzen_hidden_button').click();
      }
    }

    return false;
  };
</script>