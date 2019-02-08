{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra-network.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
<div class="row"><div class="col-xs-12{if version_compare($smarty.const._PS_VERSION_, '1.6.0.11', '<')} col-md-6{/if}">
{/if}

<div class="payment_module payzen {$payzen_tag|escape:'html':'UTF-8'}">
  <a href="javascript: void(0);" title="{l s='Click here to pay by credit card' mod='payzen'}" id="payzen_standard_link" class="payzen-standard-link">
    <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen" />{$payzen_title|escape:'html':'UTF-8'}
    <br />

    <iframe class="payzen-iframe" id="payzen_iframe" src="{$link->getModuleLink('payzen', 'iframe', ['content_only' => 1], true)|escape:'html':'UTF-8'}" style="display: none;">
    </iframe>
  </a>

  <script type="text/javascript">
    var done = false;
    function payzenShowIframe() {
      if (done) {
        return;
      }

      done = true;

      $('#payzen_iframe').parent().addClass('unclickable');
      $('.payzen-iframe').show();

      var url = "{$link->getModuleLink('payzen', 'redirect', ['content_only' => 1], true)|escape:'url':'UTF-8'}";
      $('#payzen_iframe').attr('src', decodeURIComponent(url));
    }

    function payzenHideIframe() {
      if (!done) {
        return;
      }

      done = false;

      $('#payzen_iframe').parent().removeClass('unclickable');
      $('.payzen-iframe').hide();

      var url = "{$link->getModuleLink('payzen', 'iframe', ['content_only' => 1], true)|escape:'url':'UTF-8'}";
      $('#payzen_iframe').attr('src', decodeURIComponent(url));
    }

    $(function() {
      $('#payzen_standard_link').click(payzenShowIframe);
      $('.payment_module a:not(.payzen-standard-link)').click(payzenHideIframe);
    });
  </script>
</div>

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
</div></div>
{/if}