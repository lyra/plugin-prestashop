{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra-network.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<!-- this meta tag is mandatory to avoid encoding problems caused by \PrestaShop\PrestaShop\Core\Payment\PaymentOptionFormDecorator -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<section style="margin-bottom: 2rem;">
  <div class="payzen kr-embedded" kr-form-token="{$payzen_rest_form_token|escape:'html':'UTF-8'}">
    <div class="kr-pan"></div>
    <div class="kr-expiry"></div>
    <div class="kr-security-code"></div>

    <div style="display: none;">
        <button type="button" id="payzen_hidden_button" class="kr-payment-button"></button>
    </div>

    <div class="kr-field processing" style="display: none; border: none !important;">
        <div style="background-image: url('{$smarty.const._MODULE_DIR_|escape:'html':'UTF-8'}payzen/views/img/loading_big.gif');
                    margin: 0 auto; display: block; height: 35px; background-color: #ffffff; background-position: center;
                    background-repeat: no-repeat; background-size: 35px;"></div>
    </div>

    <div class="kr-form-error"></div>
  </div>
</section>

<script type="text/javascript">
  $(function() {
    {if ($payzen_rest_theme === 'material')}
        KR.onFormReady(function(e) {
          // worarround bug relative to wrappers size with material theme
          $('.payzen .kr-iframe-wrapper').css('display', '');
        });
    {/if}

    $('#payzen_standard').submit(function(e) {
      e.preventDefault();

      $('.payzen .processing').css('display', 'block');

      if (!$('#payzen_standard').data('submitted')) {
        $('#payzen_standard').data('submitted', true);
        $('#payzen_hidden_button').click();
      }

      return false;
    });

    KR.onError(function(e) {
      $('.payzen .processing').css('display', 'none');

      $('#payment-confirmation button').removeAttr('disabled');
      $('#payzen_standard').data('submitted', false);
    });

    KR.onSubmit(function(e) {
      $('#payment-confirmation button').attr('disabled', 'disabled');
      return true;
    });
  });
</script>