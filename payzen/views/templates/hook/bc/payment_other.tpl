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
  <a href="javascript: $('#payzen_other_{$payzen_other_payment_code|escape:'html':'UTF-8'}').submit();" title="{l s='Click here to pay with %s' sprintf=$payzen_other_payment_label mod='payzen'}">
    <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" />{$payzen_title|escape:'html':'UTF-8'}

    <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" id="payzen_other_{$payzen_other_payment_code|escape:'html':'UTF-8'}">
        <input type="hidden" name="payzen_payment_type" value="other">
        <input type="hidden" name="payzen_payment_code" value="{$payzen_other_payment_code|escape:'html':'UTF-8'}">
        <input type="hidden" name="payzen_payment_title" value="{$payzen_title|escape:'html':'UTF-8'}">
    </form>
  </a>
</div>

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
  </div></div>
{/if}