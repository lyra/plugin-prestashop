{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<tr id="payzen_oney{$suffix|escape:'html':'UTF-8'}_option_{$key|escape:'html':'UTF-8'}">
  <td>
    {include file="./input_text_lang.tpl"
      languages=$prestashop_languages
      current_lang=$prestashop_lang
      input_name="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS[{$key|escape:'html':'UTF-8'}][label]"
      field_id="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS_{$key|escape:'html':'UTF-8'}_label"
      input_value=$option.label
      style="width: 140px;"
    }
  </td>
  <td>
    <input id="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS_{$key|escape:'html':'UTF-8'}_code"
        name="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS[{$key|escape:'html':'UTF-8'}][code]"
        value="{$option.code|escape:'html':'UTF-8'}"
        style="width: 65px;"
        type="text">
  </td>
  <td>
    <input id="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS_{$key|escape:'html':'UTF-8'}_min_amount"
        name="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS[{$key|escape:'html':'UTF-8'}][min_amount]"
        value="{$option.min_amount|escape:'html':'UTF-8'}"
        style="width: 75px;"
        type="text">
  </td>
  <td>
    <input id="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS_{$key|escape:'html':'UTF-8'}_max_amount"
        name="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS[{$key|escape:'html':'UTF-8'}][max_amount]"
        value="{$option.max_amount|escape:'html':'UTF-8'}"
        style="width: 75px;"
        type="text">
  </td>
  <td>
    <input id="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS_{$key|escape:'html':'UTF-8'}_count"
        name="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS[{$key|escape:'html':'UTF-8'}][count]"
        value="{$option.count|escape:'html':'UTF-8'}"
        style="width: 55px;"
        type="text">
  </td>
  <td>
    <input id="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS_{$key|escape:'html':'UTF-8'}_rate"
        name="PAYZEN_ONEY{$suffix|escape:'html':'UTF-8'}_OPTIONS[{$key|escape:'html':'UTF-8'}][rate]"
        value="{$option.rate|escape:'html':'UTF-8'}"
        style="width: 55px;"
        type="text">
  </td>
  <td>
    <button type="button" style="width: 75px;" onclick="javascript: payzenDeleteOneyOption({$key|escape:'html':'UTF-8'}, {$suffix|escape:'html':'UTF-8'});">{l s='Delete' mod='payzen'}</button>
  </td>
</tr>
