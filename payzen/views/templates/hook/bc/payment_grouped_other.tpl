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
      <a class="unclickable" href="javascript: void(0);">
        <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen" />{$payzen_title|escape:'html':'UTF-8'}

        <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" id="payzen_grouped_other">
          <input type="hidden" name="payzen_payment_type" value="grouped_other" />
          <br />

          {assign var=first value=true}
          {foreach from=$payzen_other_options key="key" item="option"}
            <label class="payzen_card_click">
              <input type="radio"
                     name="payzen_card_type"
                     value="{$key|escape:'html':'UTF-8'}"
                     {if $first == true} checked="checked"{/if}
                     onclick="javascript: $('#payzen_grouped_other').submit();" />
              <img src="{$smarty.const._MODULE_DIR_|escape:'html':'UTF-8'}payzen/views/img/{$key|lower|escape:'html':'UTF-8'}.png"
                   alt="{$option|escape:'html':'UTF-8'}"
                   title="{$option|escape:'html':'UTF-8'}" />
            </label>

            {assign var=first value=false}
          {/foreach}
        </form>
      </a>
  </div>

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
</div></div>
{/if}