{*
 * PayZen V2-Payment Module version 1.10.1 for PrestaShop 1.5-1.7. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @category  payment
 * @package   payzen
*}

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
<div class="row"><div class="col-xs-12{if version_compare($smarty.const._PS_VERSION_, '1.6.0.11', '<')} col-md-6{/if}">
{/if}

<div class="payment_module payzen {$payzen_tag|escape:'html':'UTF-8'}">
  <a href="javascript: void(0);" title="{l s='Click here to pay by credit card' mod='payzen'}" id="payzen_standard_link">
    <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen" />{$payzen_title|escape:'html':'UTF-8'}
    <br />

    <div class="payzen-iframe" id="payzen_iframe_overlay" style="display: none;"></div>

    <div class="payzen-iframe" id="payzen_iframe_actions" style="display: none;">
      <a>{l s='< Cancel and return to payment choice' mod='payzen'}</a>
    </div>

    <iframe class="payzen-iframe" id="payzen_iframe" src="{$link->getModuleLink('payzen', 'iframe', ['content_only' => 1], true)|escape:'html':'UTF-8'}" style="display: none;">
    </iframe>

    <div class="payzen-iframe" id="payzen_iframe_warn" style="display: none;">
      {l s='Please do not refresh the page until you complete payment.' mod='payzen'}
    </div>
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
      done = false;

      $('#payzen_iframe').parent().removeClass('unclickable');
      $('.payzen-iframe').hide();

      var url = "{$link->getModuleLink('payzen', 'iframe', ['content_only' => 1], true)|escape:'url':'UTF-8'}";
      $('#payzen_iframe').attr('src', decodeURIComponent(url));
    }

    $(function() {
      $('#payzen_standard_link').click(payzenShowIframe);
      $('#payzen_iframe_actions a').click(payzenHideIframe);
    });
  </script>
</div>

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
</div></div>
{/if}
