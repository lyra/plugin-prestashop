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
{if $payzen_saved_identifier}
  <a class="unclickable payzen-standard-link" title="{l s='Choose pay with registred means of payment or enter payment information and click « Pay » button' mod='payzen'}">
    <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen" />{$payzen_title|escape:'html':'UTF-8'}
{else}
  <a href="javascript: void(0);" title="{l s='Click here to pay by credit card' mod='payzen'}" id="payzen_standard_link" class="payzen-standard-link">
    <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen" />{$payzen_title|escape:'html':'UTF-8'}
    <br />
{/if}

    {if $payzen_saved_identifier}
      {include file="./payment_std_oneclick.tpl"}
      <input id="payzen_payment_by_identifier" type="hidden" name="payzen_payment_by_identifier" value="1" />
    {/if}

    <iframe class="payzen-iframe" id="payzen_iframe" src="{$link->getModuleLink('payzen', 'iframe', ['content_only' => 1], true)|escape:'html':'UTF-8'}" style="display: none;">
    </iframe>

    {if $payzen_can_cancel_iframe}
        <button class="payzen-iframe" id="payzen_cancel_iframe" style="display: none;"">{l s='< Cancel and return to payment choice' mod='payzen'}</button>
    {/if}
  </a>

  <script type="text/javascript">
    var done = false;
    function payzenShowIframe() {
      if (done) {
        return;
      }

      done = true;

      {if !$payzen_saved_identifier}
        $('#payzen_iframe').parent().addClass('unclickable');
      {/if}

      $('.payzen-iframe').show();
      $('#payzen_oneclick_payment_description').hide();

      var url = "{$link->getModuleLink('payzen', 'redirect', ['content_only' => 1], true)|escape:'url':'UTF-8'}";
      {if $payzen_saved_identifier}
            url = url + '&payzen_payment_by_identifier=' + $('#payzen_payment_by_identifier').val();
      {/if}

      $('#payzen_iframe').attr('src', decodeURIComponent(url) + '&' + Date.now());
    }

    function payzenHideIframe() {
      if (!done) {
        return;
      }

      done = false;

      {if !$payzen_saved_identifier}
        $('#payzen_iframe').parent().removeClass('unclickable');
      {/if}

      $('.payzen-iframe').hide();
      $('#payzen_oneclick_payment_description').show();

      var url = "{$link->getModuleLink('payzen', 'iframe', ['content_only' => 1], true)|escape:'url':'UTF-8'}";
      $('#payzen_iframe').attr('src', decodeURIComponent(url) + '&' + Date.now());
    }

      $(function() {
        $('#payzen_standard_link').click(payzenShowIframe);
        $('#payzen_cancel_iframe').click(function() {
          payzenHideIframe();
          return false;
        });
        $('.payment_module a:not(.payzen-standard-link)').click(payzenHideIframe);
      });
  </script>
</div>

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
</div></div>
{/if}