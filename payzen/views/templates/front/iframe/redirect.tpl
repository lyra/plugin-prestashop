{*
 * PayZen V2-Payment Module version 1.9.0 for PrestaShop 1.5-1.7. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @category  payment
 * @package   payzen
 *}

<form action="{$payzen_url|escape:'html':'UTF-8'}" method="post" id="payzen_form" name="payzen_form">
  {foreach from=$payzen_params key='key' item='value'}
    <input type="hidden" name="{$key|escape:'html':'UTF-8'}" value="{$value|escape:'html':'UTF-8'}" />
  {/foreach}

  <p>
    {if version_compare($smarty.const._PS_VERSION_, '1.7', '>=')}
      {include file="module:payzen/views/templates/front/iframe/loader.tpl"}
    {else}
      {include file="./loader.tpl"}
    {/if}
  </p>
</form>

<script type="text/javascript">
  window.onload = function() {
    document.getElementById('payzen_form').submit();
  };
</script>
