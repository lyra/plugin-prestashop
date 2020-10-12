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

<form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" style="margin-left: 2.875rem; margin-top: 1.25rem; margin-bottom: 1rem;">
  <input type="hidden" name="payzen_payment_type" value="oney{$suffix|escape:'html':'UTF-8'}">

  {assign var=first value=true}
  {foreach from=$payzen_oney_options key="key" item="option"}
    <div style="padding-bottom: 5px;">
      {if $payzen_oney_options|@count == 1}
        <input type="hidden" id="payzen_oney{$suffix|escape:'html':'UTF-8'}_option_{$key|escape:'html':'UTF-8'}" name="payzen_oney{$suffix|escape:'html':'UTF-8'}_option" value="{$key|escape:'html':'UTF-8'}" >
      {else}
        <input type="radio"
               id="payzen_oney{$suffix|escape:'html':'UTF-8'}_option_{$key|escape:'html':'UTF-8'}"
               name="payzen_oney{$suffix|escape:'html':'UTF-8'}_option"
               value="{$key|escape:'html':'UTF-8'}"
               style="vertical-align: middle;"
               {if $first == true} checked="checked"{/if}
               onclick="javascript: $('.payzen_oney{$suffix|escape:'html':'UTF-8'}_review').hide(); $('#payzen_oney{$suffix|escape:'html':'UTF-8'}_review_{$key|escape:'html':'UTF-8'}').show();">
      {/if}

      <label for="payzen_oney{$suffix|escape:'html':'UTF-8'}_option_{$key|escape:'html':'UTF-8'}" style="display: inline;">
        <span style="vertical-align: middle;">{$option.localized_label|escape:'html':'UTF-8'}</span>
      </label>

    {assign var=first value=false}
    </div>
  {/foreach}
</form>