{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
  <div class="row"><div class="col-xs-12{if version_compare($smarty.const._PS_VERSION_, '1.6.0.11', '<')} col-md-6{/if}">
{/if}

{if {$payzen_multi_options|@count} == 1 AND ($payzen_multi_card_mode == 1)}
  <div class="payment_module payzen {$payzen_tag|escape:'html':'UTF-8'} multi">
    {foreach from=$payzen_multi_options key="key" item="option"}
      <a href="javascript: $('#payzen_opt').val('{$key|escape:'html':'UTF-8'}'); $('#payzen_multi').submit();"
         title="{l s='Click to pay in installments' mod='payzen'}">

        <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" />{$payzen_title|escape:'html':'UTF-8'}
        ({$option.localized_label|escape:'html':'UTF-8'})

        <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" id="payzen_multi">
          <input type="hidden" name="payzen_payment_type" value="multi" />
          <input type="hidden" name="payzen_opt" value="" id="payzen_opt" />
        </form>
      </a>
    {/foreach}
  </div>
{else}
  <div class="payment_module payzen {$payzen_tag|escape:'html':'UTF-8'} multi">
    <a class="unclickable" title="{l s='Click on a payment option to pay in installments' mod='payzen'}" href="javascript: void(0);">
      <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" />{$payzen_title|escape:'html':'UTF-8'}

      <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" id="payzen_multi">
        <input type="hidden" name="payzen_payment_type" value="multi" />
        <input type="hidden" name="payzen_opt" value="" id="payzen_opt" />

        <br />
        {if $payzen_multi_card_mode == 2}
          <p class="tip">{if $payzen_avail_cards|@count == 1}{l s='Payment Mean' mod='payzen'}{else}{l s='Choose your payment mean' mod='payzen'}{/if}</p>

          {assign var=first value=true}
          {foreach from=$payzen_avail_cards key="key" item="card"}
            <div class="payzen-pm">
              {if $payzen_avail_cards|@count == 1}
                <input type="hidden" id="payzen_multi_card_type_{$key|escape:'html':'UTF-8'}" name="payzen_card_type" value="{$key|escape:'html':'UTF-8'}" >
              {else}
                <input type="radio" id="payzen_multi_card_type_{$key|escape:'html':'UTF-8'}" name="payzen_card_type" value="{$key|escape:'html':'UTF-8'}" style="vertical-align: middle;"{if $first == true} checked="checked"{/if} >
              {/if}

              <label for="payzen_multi_card_type_{$key|escape:'html':'UTF-8'}">
                <img src="{$card['logo']}"
                     alt="{$card['label']|escape:'html':'UTF-8'}"
                     title="{$card['label']|escape:'html':'UTF-8'}">
              </label>

              {assign var=first value=false}
            </div>
          {/foreach}
          <div style="margin-bottom: 12px;"></div>
        {/if}

        <p class="tip">{l s='Choose your payment option' mod='payzen'}</p>
        <ul>
          {foreach from=$payzen_multi_options key="key" item="option"}
            <li onclick="javascript: $('#payzen_opt').val('{$key|escape:'html':'UTF-8'}'); $('#payzen_multi').submit();">
              {$option.localized_label|escape:'html':'UTF-8'}
            </li>
          {/foreach}
        </ul>
      </form>
    </a>
  </div>
{/if}

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
  </div></div>
{/if}