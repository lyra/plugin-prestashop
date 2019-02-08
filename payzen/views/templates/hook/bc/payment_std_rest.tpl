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
  <a class="unclickable" title="{l s='Enter payment information and click « Pay » button' mod='payzen'}">
    <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen" />{$payzen_title|escape:'html':'UTF-8'}
    <br /><br />

    <div class="kr-embedded"{if !$payzen_ajax} kr-form-token="{$payzen_rest_form_token|escape:'html':'UTF-8'}"{/if}>
      <div class="kr-pan"></div>
      <div class="kr-expiry"></div>
      <div class="kr-security-code"></div>

      <button class="kr-payment-button"></button>

      <div class="kr-form-error"></div>
    </div>
  </a>

  <script type="text/javascript">
    // for AJAX loading
    {if $payzen_ajax}
      KR.setFormConfig({
        formToken: "{$payzen_rest_form_token|escape:'html':'UTF-8'}"
      });
    {/if}
  </script>
</div>

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
</div></div>
{/if}