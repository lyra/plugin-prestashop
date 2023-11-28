{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<!-- This meta tag is mandatory to avoid encoding problems caused by \PrestaShop\PrestaShop\Core\Payment\PaymentOptionFormDecorator -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}"
      method="post"
      id="payzen_standard"
      style="margin-left: 2.875rem; margin-top: 1.25rem; margin-bottom: 1rem;{if $payzen_is_valid_std_identifier} display: none;{/if}">

  <input type="hidden" name="payzen_payment_type" value="standard" />

  {if $payzen_is_valid_std_identifier}
    <input id="payzen_payment_by_identifier" type="hidden" name="payzen_payment_by_identifier" value="1" />
  {/if}

  {if ($payzen_std_card_data_mode == 2)}
    {assign var=first value=true}
    {foreach from=$payzen_avail_cards key="key" item="card"}
      <div class="payzen-pm">
        {if $payzen_avail_cards|@count == 1}
          <input type="hidden" id="payzen_card_type_{$key|escape:'html':'UTF-8'}" name="payzen_card_type" value="{$key|escape:'html':'UTF-8'}" >
        {else}
          <input type="radio" id="payzen_card_type_{$key|escape:'html':'UTF-8'}" name="payzen_card_type" value="{$key|escape:'html':'UTF-8'}" style="vertical-align: middle;"{if $first == true} checked="checked"{/if} >
        {/if}

        <label for="payzen_card_type_{$key|escape:'html':'UTF-8'}">
          <img src="{$card['logo']}"
               alt="{$card['label']|escape:'html':'UTF-8'}"
               title="{$card['label']|escape:'html':'UTF-8'}">
        </label>

        {assign var=first value=false}
      </div>
    {/foreach}
    <div style="margin-bottom: 12px;"></div>

    {if $payzen_is_valid_std_identifier}
      <ul>
        {if $payzen_std_card_data_mode == 2}
          <li>{l s='You will enter payment data after order confirmation.' mod='payzen'}</li>
        {/if}
        <li style="margin: 8px 0px 8px;">
          <span>{l s='OR' mod='payzen'}</span>
        </li>
        <li>
          <a href="javascript: void(0);" onclick="payzenOneclickPaymentSelect(1)">{l s='Click here to pay with your registered means of payment.' mod='payzen'}</a>
        </li>
      </ul>
    {/if}
  {/if}
</form>

<script type="text/javascript">
  window.onload = function(e) {
    options = document.getElementsByClassName('payment-option');
    if ((typeof options !== null) && (options.length == 1)) {
      document.getElementById('pay-with-payment-option-1-form').classList.add('payzen-show-options');

      let element = document.getElementById('payment-option-1-additional-information');
      if (element !== null) {
        document.getElementById('payment-option-1-additional-information').classList.add('payzen-show-options');
      }
    } else {
      document.getElementById('pay-with-payment-option-1-form').classList.remove('payzen-show-options');

      let element = document.getElementById('payment-option-1-additional-information');
      if (element !== null) {
        document.getElementById('payment-option-1-additional-information').classList.remove('payzen-show-options');
      }
    }
  };
</script>