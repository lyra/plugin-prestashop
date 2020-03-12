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

<section style="margin-top: -12px;">
  <iframe class="payzen-iframe" id="payzen_iframe" src="{$link->getModuleLink('payzen', 'iframe', array(), true)|escape:'html':'UTF-8'}" style="display: none;">
  </iframe>

   {if $payzen_can_cancel_iframe}
       <a id="payzen_cancel_iframe" class="payzen-iframe" style="margin-bottom: 8px; display: none;" href="javascript:payzenInit();">
           {l s='< Cancel and return to payment choice' mod='payzen'}
       </a>
   {/if}
</section>
<br />

<script type="text/javascript">
  var payzenSubmit = function(e) {
    e.preventDefault();

    if (!$('#payzen_standard').data('submitted')) {
      $('#payzen_standard').data('submitted', true);
      $('#payment-confirmation button').attr('disabled', 'disabled');
      $('.payzen-iframe').show();
      $('#payzen_oneclick_payment_description').hide();

      var url = decodeURIComponent("{$link->getModuleLink('payzen', 'redirect', ['content_only' => 1], true)|escape:'url':'UTF-8'}") + '&' + Date.now();
      {if $payzen_saved_identifier}
        url = url + '&payzen_payment_by_identifier=' + $('#payzen_payment_by_identifier').val();
      {/if}

      $('#payzen_iframe').attr('src', url);
    }

    return false;
  }

  setTimeout(function() {
    $('input[type="radio"][name="payment-option"]').change(function() {
      payzenInit();
    });
  }, 0);

  function payzenInit() {
    if (!$('#payzen_standard').data('submitted')) {
      return;
    }

    $('#payzen_standard').data('submitted', false);
    $('#payment-confirmation button').removeAttr('disabled');
    $('.payzen-iframe').hide();
    $('#payzen_oneclick_payment_description').show();

    var url = decodeURIComponent("{$link->getModuleLink('payzen', 'iframe', ['content_only' => 1], true)|escape:'url':'UTF-8'}") + '&' + Date.now();
    $('#payzen_iframe').attr('src', url);
  }
</script>