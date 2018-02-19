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

<table class="table" cellpadding="10" cellspacing="0">
<thead>
  <tr>
    <th>{l s='Customer group' mod='payzen'}</th>
    <th>{l s='Minimum amount' mod='payzen'}</th>
    <th>{l s='Maximum amount' mod='payzen'}</th>
  </tr>
</thead>

<tbody>
  <tr style="background-color: #bebebe;">
    <td>{l s='ALL GROUPS' mod='payzen'}</td>
    <td>
      <input id="{$input_name|escape:'html':'UTF-8'}_0_min_amount"
          name="{$input_name|escape:'html':'UTF-8'}[0][min_amount]"
          value="{if isset($input_value[0])}{$input_value[0]['min_amount']|escape:'html':'UTF-8'}{/if}"
          style="width: 200px;"
          type="text">
    </td>
    <td>
      <input id="{$input_name|escape:'html':'UTF-8'}_0_max_amount"
          name="{$input_name|escape:'html':'UTF-8'}[0][max_amount]"
          value="{if isset($input_value[0])}{$input_value[0]['max_amount']|escape:'html':'UTF-8'}{/if}"
          style="width: 200px;"
          type="text">
    </td>
  </tr>

  {foreach from=$groups item=group}
    {assign var="group_id" value=$group.id_group}

    <tr>
      <td>{$group.name|escape:'html':'UTF-8'}</td>
      <td>
        <input id="{$input_name|escape:'html':'UTF-8'}_{$group_id|escape:'html':'UTF-8'}_min_amount"
            name="{$input_name|escape:'html':'UTF-8'}[{$group_id|escape:'html':'UTF-8'}][min_amount]"
            value="{if isset($input_value[$group_id])}{$input_value[$group_id]['min_amount']|escape:'html':'UTF-8'}{/if}"
            style="width: 200px;"
            type="text">
      </td>
      <td>
        <input id="{$input_name|escape:'html':'UTF-8'}_{$group_id|escape:'html':'UTF-8'}_max_amount"
            name="{$input_name|escape:'html':'UTF-8'}[{$group_id|escape:'html':'UTF-8'}][max_amount]"
            value="{if isset($input_value[$group_id])}{$input_value[$group_id]['max_amount']|escape:'html':'UTF-8'}{/if}"
            style="width: 200px;"
            type="text">
      </td>
    </tr>
  {/foreach}
</tbody>
</table>
