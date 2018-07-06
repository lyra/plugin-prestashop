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

<!-- this meta tag is mandatory to avoid encoding problems caused by \PrestaShop\PrestaShop\Core\Payment\PaymentOptionFormDecorator -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<section>
  <div class="payzen-iframe" id="payzen_iframe_overlay" style="display: none;"></div>

  <div class="payzen-iframe" id="payzen_iframe_actions" style="display: none;">
    <a>{l s='< Cancel and return to payment choice' mod='payzen'}</a>
  </div>

  <iframe class="payzen-iframe" id="payzen_iframe" src="{$link->getModuleLink('payzen', 'iframe', array(), true)|escape:'html':'UTF-8'}" style="display: none;">
  </iframe>

  <div class="payzen-iframe" id="payzen_iframe_warn" style="display: none;">
    {l s='Please do not refresh the page until you complete payment.' mod='payzen'}
  </div>
</section>

{block name='javascript_bottom'}
  {include file="_partials/javascript.tpl" javascript=$javascript.bottom}
{/block}

<script type="text/javascript">
  $(document).ready(function() {
    $('#payzen_standard').submit(function(e) {
      e.preventDefault();

      if (!$('#payzen_standard').data('submitted')) {
        $('#payzen_standard').data('submitted', true);

        $('#payment-confirmation button').attr('disabled', 'disabled');

        $('.payzen-iframe').show();

        var url = decodeURIComponent("{$link->getModuleLink('payzen', 'redirect', ['content_only' => 1], true)|escape:'url':'UTF-8'}");
        $('#payzen_iframe').attr('src', url);
      }

      return false;
    });

    $('#payzen_iframe_actions a').click(function(e) {
      $('#payzen_standard').data('submitted', false);

      $('#payment-confirmation button').removeAttr('disabled');

      $('.payzen-iframe').hide();

      var url = decodeURIComponent("{$link->getModuleLink('payzen', 'iframe', ['content_only' => 1], true)|escape:'url':'UTF-8'}");
      $('#payzen_iframe').attr('src', url);
    });
  });
</script>