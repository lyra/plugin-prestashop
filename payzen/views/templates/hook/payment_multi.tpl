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

<form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" style="margin-left: 2.875rem; margin-top: 1.25rem; margin-bottom: 1rem;">
  <input type="hidden" name="payzen_payment_type" value="multi">

  {if $payzen_multi_card_mode == 2}
    <p style="margin-bottom: .4rem;">{if $payzen_avail_cards|@count == 1}{l s='Payment Mean' mod='payzen'}{else}{l s='Choose your payment mean' mod='payzen'}{/if}</p>

    {assign var=first value=true}
    {foreach from=$payzen_avail_cards key="key" item="label"}
      <div style="display: inline-block;">
        {if $payzen_avail_cards|@count == 1}
          <input type="hidden" id="payzen_multi_card_type_{$key|escape:'html':'UTF-8'}" name="payzen_card_type" value="{$key|escape:'html':'UTF-8'}" >
        {else}
          <input type="radio" id="payzen_multi_card_type_{$key|escape:'html':'UTF-8'}" name="payzen_card_type" value="{$key|escape:'html':'UTF-8'}" style="vertical-align: middle;"{if $first == true} checked="checked"{/if} >
        {/if}

        <label for="payzen_multi_card_type_{$key|escape:'html':'UTF-8'}" class="payzen_card">
          {assign var=img_file value=$smarty.const._PS_MODULE_DIR_|cat:'payzen/views/img/':{$key|lower|escape:'html':'UTF-8'}:'.png'}

          {if file_exists($img_file)}
            <img src="{$smarty.const._MODULE_DIR_|escape:'html':'UTF-8'}payzen/views/img/{$key|lower|escape:'html':'UTF-8'}.png"
               alt="{$label|escape:'html':'UTF-8'}"
               title="{$label|escape:'html':'UTF-8'}">
          {else}
            <span>{$label|escape:'html':'UTF-8'}</span>
          {/if}
        </label>

        {assign var=first value=false}
      </div>
    {/foreach}

    <div style="margin-bottom: 15px;"></div>
  {/if}

  <p style="margin-bottom: .4rem;">{if $payzen_multi_options|@count == 1}{l s='Payment option' mod='payzen'}{else}{l s='Choose your payment option' mod='payzen'}{/if}</p>
  {assign var=first value=true}
  {foreach from=$payzen_multi_options key="key" item="option"}
    {if {$payzen_multi_options|@count} == 1}
      <input type="hidden" name="payzen_opt" value="{$key|escape:'html':'UTF-8'}" id="payzen_opt_{$key|escape:'html':'UTF-8'}">
    {else}
      <input type="radio" name="payzen_opt" value="{$key|escape:'html':'UTF-8'}" id="payzen_opt_{$key|escape:'html':'UTF-8'}" {if $first == true} checked="checked"{/if}>
      &nbsp;
    {/if}

    <label for="payzen_opt_{$key|escape:'html':'UTF-8'}" style="font-weight: bold; display: inline;">{$option.localized_label|escape:'html':'UTF-8'}</label>
    <br />

    {assign var=first value=false}
  {/foreach}
</form>