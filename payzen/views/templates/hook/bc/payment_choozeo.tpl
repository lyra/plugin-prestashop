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

  <div class="payment_module payzen payzen_choozeo {$payzen_tag|escape:'html':'UTF-8'}">
    {if {$payzen_choozeo_options|@count} == 1}
      <a href="javascript: $('#payzen_choozeo').submit();" title="{l s='Click here to pay with Choozeo' mod='payzen'}">
    {else}
      <a class="unclickable" title="{l s='Click on a payment option to pay with Choozeo' mod='payzen'}" href="javascript: void(0);">
    {/if}
        <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen" />{$payzen_title|escape:'html':'UTF-8'}

        <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" id="payzen_choozeo">
          <input type="hidden" name="payzen_payment_type" value="choozeo" />
          <br />

          {assign var=first value=true}
          {foreach from=$payzen_choozeo_options key="key" item="option"}
            <label class="payzen_card_click" for="payzen_card_type_{$key|escape:'html':'UTF-8'}">
              <input type="radio"
                     name="payzen_card_type"
                     id="payzen_card_type_{$key|escape:'html':'UTF-8'}"
                     value="{$key|escape:'html':'UTF-8'}"
                     {if $first == true} checked="checked"{/if}
                     onclick="javascript: $('#payzen_choozeo').submit();" />
              <img src="{$smarty.const._MODULE_DIR_|escape:'html':'UTF-8'}payzen/views/img/{$key|lower|escape:'html':'UTF-8'}.png"
                   alt="{$option|escape:'html':'UTF-8'}"
                   title="{$option|escape:'html':'UTF-8'}" />
            </label>

            {assign var=first value=false}
          {/foreach}
        </form>
      </a>
  </div>

  <![if IE]>
    <script type="text/javascript">
    // <![CDATA[
      $('div.payment_module.payzen_choozeo a img').on('click', function(e) {
        $(this).parent().click();
      });
    // ]]>
    </script>
  <![endif]>

  {if {$payzen_choozeo_options|@count} == 1}
    <script type="text/javascript">
    // <![CDATA[
      $('div.payment_module.payzen_choozeo a').on('hover', function(e) {
        $('div.payment_module.payzen_choozeo a form .payzen_card_click img').toggleClass('hover');
      });
    // ]]>
    </script>
  {/if}

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
</div></div>
{/if}