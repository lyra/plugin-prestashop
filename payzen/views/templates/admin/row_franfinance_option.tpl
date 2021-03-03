{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<tr id="payzen_ffin_option_{$key|escape:'html':'UTF-8'}">
  <td>
    {include file="./input_text_lang.tpl"
      languages=$prestashop_languages
      current_lang=$prestashop_lang
      input_name="PAYZEN_FFIN_OPTIONS[{$key|escape:'html':'UTF-8'}][label]"
      field_id="PAYZEN_FFIN_OPTIONS_{$key|escape:'html':'UTF-8'}_label"
      input_value=$option.label
      style="width: 140px;"
    }
  </td>
  <td>
    <select id="PAYZEN_FFIN_OPTIONS_{$key|escape:'html':'UTF-8'}_count"
        name="PAYZEN_FFIN_OPTIONS[{$key|escape:'html':'UTF-8'}][count]">
        {foreach from=$franfinance_count key="count_key" item="count_option"}
            <option value="{$count_key|escape:'html':'UTF-8'}"{if isset($option.count) && $option.count === (string)$count_key} selected="selected"{/if}>{$count_option|escape:'html':'UTF-8'}</option>
        {/foreach}
    </select>
  </td>
  <td>
    <select id="PAYZEN_FFIN_OPTIONS_{$key|escape:'html':'UTF-8'}_fees"
        name="PAYZEN_FFIN_OPTIONS[{$key|escape:'html':'UTF-8'}][fees]">
        {foreach from=$fees_options key="fee_key" item="fee_option"}
            <option value="{$fee_key|escape:'html':'UTF-8'}"{if isset($option.fees) && $option.fees === (string)$fee_key} selected="selected"{/if}>{$fee_option|escape:'html':'UTF-8'}</option>
        {/foreach}
     </select>
  </td>
  <td>
    <input id="PAYZEN_FFIN_OPTIONS_{$key|escape:'html':'UTF-8'}_min_amount"
        name="PAYZEN_FFIN_OPTIONS[{$key|escape:'html':'UTF-8'}][min_amount]"
        value="{$option.min_amount|escape:'html':'UTF-8'}"
        style="width: 75px;"
        type="text">
  </td>
  <td>
    <input id="PAYZEN_FFIN_OPTIONS_{$key|escape:'html':'UTF-8'}_max_amount"
        name="PAYZEN_FFIN_OPTIONS[{$key|escape:'html':'UTF-8'}][max_amount]"
        value="{$option.max_amount|escape:'html':'UTF-8'}"
        style="width: 75px;"
        type="text">
  </td>
  <td>
    <button type="button" style="width: 75px;" onclick="javascript: payzenDeleteFranfinanceOption({$key|escape:'html':'UTF-8'});">{l s='Delete' mod='payzen'}</button>
  </td>
</tr>
