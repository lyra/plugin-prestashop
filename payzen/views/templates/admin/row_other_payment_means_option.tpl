{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra-network.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<tr id="payzen_other_payment_means_option_{$key|escape:'html':'UTF-8'}">
  <td>
    {include file="./input_text_lang.tpl"
      languages=$prestashop_languages
      current_lang=$prestashop_lang
      input_name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][title]"
      field_id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_title"
      input_value=$option.title
      style="width: 150px;"
    }
  </td>
  <td>
    <select id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_code" name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][code]">
       {foreach from=$payment_means_cards key="card_key" item="card_name"}
         {if $card_key != ''}<option value="{$card_key|escape:'html':'UTF-8'}" {if $option.code === $card_key} selected="selected"{/if}>{$card_name|escape:'html':'UTF-8'}</option>{/if}
       {/foreach}
    </select>
  </td>
  <td>
    <input id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_min_amount"
        name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][min_amount]"
        value="{$option.min_amount|escape:'html':'UTF-8'}"
        style="width: 75px;"
        type="text">
  </td>
  <td>
    <input id="PAYZEN_OTHER_PAYMENT_MEANS_{$key|escape:'html':'UTF-8'}_max_amount"
        name="PAYZEN_OTHER_PAYMENT_MEANS[{$key|escape:'html':'UTF-8'}][max_amount]"
        value="{$option.max_amount|escape:'html':'UTF-8'}"
        style="width: 75px;"
        type="text">
  </td>
  <td>
    <button type="button" style="width: 75px;" onclick="javascript: payzenDeleteOtherPaymentMeansOption({$key|escape:'html':'UTF-8'});">{l s='Delete' mod='payzen'}</button>
  </td>
</tr>
