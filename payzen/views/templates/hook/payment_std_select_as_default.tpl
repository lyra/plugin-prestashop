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

<script type="text/javascript">
  window.addEventListener('load', function(e) {
    options = document.getElementsByClassName('payment-option');
    if (options.length == 0) {
        options = document.getElementsByClassName('payment__option');
    }

    if ((typeof options !== null) && (options.length > 0)) {
      {if $payzen_std_select_by_default == 'True'}
        var methodTitle = '{$payzen_title|escape:'js'}';
        var spans = document.querySelectorAll("span");
        var found = null;
        spans.forEach(function(span) {
          if (span.textContent.trim() === methodTitle) {
            found = span;
          }
        });
        if (found) {
          var parentDiv = found.closest('div[id*="payment-option-"]');
          var id = parentDiv.getAttribute('id');
          var match = id && id.match(/payment-option-(\d+)/);
          if (match && match.length > 1) {
            var paymentOptionId = match[1];
            $('#payment-option-' + paymentOptionId).prop("checked", true);
          }
        }
      {/if}
    }
  });
</script>