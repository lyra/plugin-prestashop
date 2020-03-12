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

<div class="payment_module payzen {$payzen_tag|escape:'html':'UTF-8'}">
  {if $payzen_std_card_data_mode == 1 && !$payzen_saved_identifier}
    <a href="javascript: $('#payzen_standard').submit();" title="{l s='Click here to pay by credit card' mod='payzen'}">
  {else}
    <a class="unclickable"
      {if $payzen_saved_identifier}
        title="{l s='Choose pay with registred means of payment or enter payment information and click « Pay » button' mod='payzen'}"
      {else}
        title="{l s='Enter payment information and click « Pay » button' mod='payzen'}"
      {/if}
    >
  {/if}
    <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen" />{$payzen_title|escape:'html':'UTF-8'}
    {if $payzen_saved_identifier}
      {include file="./payment_std_oneclick.tpl"}
    {/if}

    <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}"
          method="post" id="payzen_standard"
          {if $payzen_saved_identifier} style="display: none;"{/if}
    >

      <input type="hidden" name="payzen_payment_type" value="standard" />

      {if $payzen_saved_identifier}
        <input id="payzen_payment_by_identifier" type="hidden" name="payzen_payment_by_identifier" value="1" />
      {/if}

      {if ($payzen_std_card_data_mode == 2)}
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

        {if $payzen_saved_identifier}
            <div>
                <ul>
                    {if $payzen_std_card_data_mode == 2}
                        <li>
                            <span class="payzen_span">{l s='You will enter payment data after order confirmation.' mod='payzen'}</span>
                        </li>
                    {/if}
                    <li style="margin: 8px 0px 8px;">
                        <span class="payzen_span">{l s='OR' mod='payzen'}</span>
                    </li>
                    <li>
                        <p class="payzen_link" onclick="payzenOneclickPaymentSelect(1)">{l s='Click here to pay with your registered means of payment.' mod='payzen'}</p>
                    </li>
                </ul>
            </div>
        {/if}

        {if version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
          <input id="payzen_submit_form" type="submit" name="submit" value="{l s='Pay' mod='payzen'}" class="button"/>
        {else}
          <button id="payzen_submit_form" type="submit" name="submit" class="button btn btn-default standard-checkout button-medium">
            <span>{l s='Pay' mod='payzen'}</span>
          </button>
        {/if}
      {/if}
    </form>

    {if $payzen_saved_identifier}
      <script type="text/javascript">
        $('#payzen_standard_link').click(function(){
          {if ($payzen_std_card_data_mode == 2)}
            $('#payzen_submit_form').click();
          {else}
            $('#payzen_standard').submit();
          {/if}
        });
      </script>
    {/if}
  </a>
</div>

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
  </div></div>
{/if}