{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<tr id="payzen_other_payment_means_option_{$key|escape:'html':'UTF-8'}">
  <td>
  <div style="width: 185px;">
    {include file="./input_text_lang.tpl"
      languages=$prestashop_languages
      current_lang=$prestashop_lang
      input_name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][title]"
      field_id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_title"
      input_value=$option.title
      style="width: 115px;"
    }
    </div>
  </td>
  <td>
    <select id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_code" name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][code]" style="width: 200px;">
       {foreach from=$payment_means_cards key="card_key" item="card_name"}
         {if $card_key != ''}<option value="{$card_key|escape:'html':'UTF-8'}" {if $option.code === $card_key} selected="selected"{/if}>{$card_key|escape:'html':'UTF-8'} - {$card_name|escape:'html':'UTF-8'}</option>{/if}
       {/foreach}
    </select>
  </td>
  <td>
    <select id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_countries"
        name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][countries][]"
        multiple="multiple"
        size="7"
        style="display: none;"
        onblur="payzenDisplayLabel('PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_countries', '{l s='Click to edit' mod='payzen'}');">

        {assign var="label_value" value=""}

        {foreach from=$countries_list key="countries_key" item="countries_option"}
          {if isset($option.countries) && is_array($option.countries) && in_array((string)$countries_key, $option.countries)}
            {if $label_value === ''}
              {assign var="label_value" value=$countries_option}
            {else}
              {assign var="label_value" value={$label_value|cat:', '|cat:$countries_option|escape:'html':'UTF-8'}}
            {/if}

            {assign var="selected" value=true}
          {else}
            {assign var="selected" value=false}
          {/if}

          <option value="{$countries_key|escape:'html':'UTF-8'}"{if $selected} selected="selected"{/if}>
            {$countries_option|escape:'html':'UTF-8'}
          </option>
        {/foreach}
    </select>

    {if $label_value === ''}
      {assign var="label_value" value="{l s='Click to edit' mod='payzen'}"}
    {/if}

    <span id="LABEL_PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_countries"
        onclick="javascript:payzenDisplayMultiSelect('PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_countries');"
        style="width: 100%; display: block; cursor: pointer;">
        {$label_value|escape:'html':'UTF-8'}
    </span>
  </td>
  <td>
    <input id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_min_amount"
        name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][min_amount]"
        value="{$option.min_amount|escape:'html':'UTF-8'}"
        style="width: 70px;"
        type="text">
  </td>
  <td>
    <input id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_max_amount"
        name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][max_amount]"
        value="{$option.max_amount|escape:'html':'UTF-8'}"
        style="width: 70px;"
        type="text">
  </td>
  <td>
    <input id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_capture"
        name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][capture]"
        value="{$option.capture|default:''|escape:'html':'UTF-8'}"
        style="width: 50px;"
        type="text">
  </td>
  <td>
    <select id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_validation" name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][validation]" style="width: 165px;">
        <option value="-1"{if isset($option.validation) && $option.validation === '-1'} selected="selected"{/if}>{l s='Module general configuration' mod='payzen'}</option>
        {foreach from=$validation_mode_options key="validation_key" item="validation_option"}
            <option value="{$validation_key|escape:'html':'UTF-8'}"{if isset($option.validation) && $option.validation === (string)$validation_key} selected="selected"{/if}>{$validation_option|escape:'html':'UTF-8'}</option>
        {/foreach}
    </select>
  </td>
  <td>
    <input id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_cart" name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][cart]"
        style="width: 100%;"
        type="checkbox"
        {if isset($option.cart) && $option.cart === 'True'}checked{/if}
        value="True">
  </td>
  <td>
    <button type="button" onclick="javascript: payzenDeleteOtherPaymentMeansOption({$key|escape:'html':'UTF-8'});">{l s='Delete' mod='payzen'}</button>
  </td>
</tr>
