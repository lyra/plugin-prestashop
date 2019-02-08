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
  {if $payzen_std_card_data_mode == 1}
    <a href="javascript: $('#payzen_standard').submit();" title="{l s='Click here to pay by credit card' mod='payzen'}">
  {else}
    <a class="unclickable" title="{l s='Enter payment information and click « Pay » button' mod='payzen'}">
  {/if}
    <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen" />{$payzen_title|escape:'html':'UTF-8'}

    <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}"
          method="post"
          {if $payzen_std_card_data_mode == 3}onsubmit="javascript: return payzenCheckFields();"{/if}
          id="payzen_standard">

      <input type="hidden" name="payzen_payment_type" value="standard" />

      {if ($payzen_std_card_data_mode == 2) OR ($payzen_std_card_data_mode == 3)}
        <br />

        {assign var=first value=true}
        {foreach from=$payzen_avail_cards key="key" item="label"}
          <div style="display: inline-block;">
            {if $payzen_avail_cards|@count == 1}
              <input type="hidden" id="payzen_card_type_{$key|escape:'html':'UTF-8'}" name="payzen_card_type" value="{$key|escape:'html':'UTF-8'}" >
            {else}
              <input type="radio" id="payzen_card_type_{$key|escape:'html':'UTF-8'}" name="payzen_card_type" value="{$key|escape:'html':'UTF-8'}" style="vertical-align: middle;"{if $first == true} checked="checked"{/if} >
            {/if}

            <label for="payzen_card_type_{$key|escape:'html':'UTF-8'}" class="payzen_card">
              {assign var=img_file value=$smarty.const._PS_MODULE_DIR_|cat:'payzen/views/img/':{$key|lower|escape:'html':'UTF-8'}:'.png'}

              {if file_exists($img_file)}
                <img src="{$base_dir_ssl|escape:'html':'UTF-8'}modules/payzen/views/img/{$key|lower}.png" alt="{$label|escape:'html':'UTF-8'}" title="{$label|escape:'html':'UTF-8'}" >
              {else}
                <span>{$label|escape:'html':'UTF-8'}</span>
              {/if}
            </label>

            {assign var=first value=false}
          </div>
        {/foreach}
        <br />
        <div style="margin-bottom: 12px;"></div>

        {if $payzen_std_card_data_mode == 3}
          <label for="payzen_card_number">{l s='Card number' mod='payzen'}</label><br />
          <input type="text" name="payzen_card_number" value="" autocomplete="off" maxlength="19" id="payzen_card_number" style="max-width: 220px;" class="data" >
          <br />

          <label for="payzen_expiry_month">{l s='Expiration date' mod='payzen'}</label><br />
          <select name="payzen_expiry_month" id="payzen_expiry_month" style="width: 90px;" class="data">
            <option value="">{l s='Month' mod='payzen'}</option>
            {section name=expiry start=1 loop=13 step=1}
            <option value="{$smarty.section.expiry.index|intval}">{$smarty.section.expiry.index|str_pad:2:"0":$smarty.const.STR_PAD_LEFT}</option>
            {/section}
          </select>

          <select name="payzen_expiry_year" id="payzen_expiry_year" style="width: 90px;" class="data">
            <option value="">{l s='Year' mod='payzen'}</option>
            {assign var=year value=$smarty.now|date_format:"%Y"}
            {section name=expiry start=$year loop=$year+9 step=1}
            <option value="{$smarty.section.expiry.index|intval}">{$smarty.section.expiry.index|intval}</option>
            {/section}
          </select>
          <br />

          <label for="payzen_cvv">{l s='CVV' mod='payzen'}</label><br />
          <input type="text" name="payzen_cvv" value="" autocomplete="off" maxlength="4" id="payzen_cvv" style="max-width: 55px;" class="data" >
          <br />
        {/if}

        {if version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
          <input type="submit" name="submit" value="{l s='Pay' mod='payzen'}" class="button" />
        {else}
          <button type="submit" name="submit" class="button btn btn-default standard-checkout button-medium" >
            <span>{l s='Pay' mod='payzen'}</span>
          </button>
        {/if}
      {/if}
    </form>
  </a>
</div>

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
</div></div>
{/if}