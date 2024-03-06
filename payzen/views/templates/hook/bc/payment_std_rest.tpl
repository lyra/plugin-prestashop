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
  <a class="unclickable"
    {if $payzen_is_valid_std_identifier}
        title="{l s='Choose pay with registred means of payment or enter payment information and click « Pay » button' mod='payzen'}"
    {else}
        {if $payzen_std_rest_popin_mode == 'True'}
            title="{l s='Click on « Pay » button to enter payment information in a popin mode' mod='payzen'}"
        {else}
            title="{l s='Enter payment information and click « Pay » button' mod='payzen'}"
        {/if}
    {/if}
  >
    <div id="payzen_std_title"><img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" />{$payzen_title|escape:'html':'UTF-8'}</div>

    {if $payzen_std_card_data_mode == '5'}
        <div id="payzen_standard_rest_wrapper" style="padding-top: 10px; padding-left: 40px;">
            <div class="kr-embedded"{if $payzen_std_rest_popin_mode == 'True'} kr-popin {/if}
                kr-form-token="{$payzen_rest_identifier_token|escape:'html':'UTF-8'}"
                kr-language="{$payzen_set_std_rest_language|escape:'html':'UTF-8'}"
                {if isset($payzen_set_std_rest_kr_public_key)}kr-public-key="{$payzen_set_std_rest_kr_public_key|escape:'html':'UTF-8'}"{/if}
                {if isset($payzen_set_std_rest_return_url)}kr-post-url-success="{$payzen_set_std_rest_return_url|escape:'html':'UTF-8'}"{/if}
                {if isset($payzen_set_std_rest_return_url)}kr-post-url-refused="{$payzen_set_std_rest_return_url|escape:'html':'UTF-8'}"{/if}
                {if isset($payzen_set_std_rest_kr_placeholder_pan)}kr-placeholder-pan="{$payzen_set_std_rest_kr_placeholder_pan|escape:'html':'UTF-8'}"{/if}
                {if isset($payzen_set_std_rest_kr_placeholder_expiry)}kr-placeholder-expiry="{$payzen_set_std_rest_kr_placeholder_expiry|escape:'html':'UTF-8'}"{/if}
                {if isset($payzen_set_std_rest_kr_placeholder_security_code)}kr-placeholder-security-code="{$payzen_set_std_rest_kr_placeholder_security_code|escape:'html':'UTF-8'}"{/if}
                {if isset($payzen_set_std_rest_kr_label_do_register)}kr-label-do-register="{$payzen_set_std_rest_kr_label_do_register|escape:'html':'UTF-8'}"{/if}
            >
                <div class="kr-pan"></div>
                <div class="kr-expiry"></div>
                <div class="kr-security-code"></div>
                <button type="button" class="kr-payment-button"></button>
                <div class="kr-form-error"></div>
            </div>
        </div>
    {elseif $payzen_std_card_data_mode == '7' || $payzen_std_card_data_mode === '8' || $payzen_std_card_data_mode === '9'}
        <div class="kr-smart-form"{if $payzen_std_rest_popin_mode == 'True'} kr-popin {/if} {if $payzen_std_card_data_mode === '8' || $payzen_std_card_data_mode === '9'} kr-card-form-expanded {/if} {if $payzen_std_card_data_mode === '9'} kr-no-card-logo-header {/if} kr-form-token="{$payzen_rest_identifier_token|escape:'html':'UTF-8'}"
          {if isset($payzen_set_std_rest_kr_public_key)}kr-public-key="{$payzen_set_std_rest_kr_public_key|escape:'html':'UTF-8'}"{/if}
          {if isset($payzen_set_std_rest_return_url)}kr-post-url-success="{$payzen_set_std_rest_return_url|escape:'html':'UTF-8'}"{/if}
          {if isset($payzen_set_std_rest_return_url)}kr-post-url-refused="{$payzen_set_std_rest_return_url|escape:'html':'UTF-8'}"{/if}></div>
    {/if}

    <script type="text/javascript">
        $(document).ready(function(){
            {if $payzen_std_display_title != 'True'}
                $paymentOptions = $('.payment_module');
                if ($paymentOptions && $paymentOptions.length == 1) {
                    $('#payzen_std_title').hide();
                }
            {/if}
        });

        var whenDefined = function(context, variableName, cb, interval) {
            if (interval === null) {
                interval = 150;
            }

            var checkVariable = function() {
                if (context[variableName]) {
                    cb();
                } else {
                    setTimeout(checkVariable, interval);
                }
            }

            setTimeout(checkVariable, 0);
        };

        whenDefined(window, 'KR', function() {
            KR.setFormConfig({ formToken: "{$payzen_rest_identifier_token|escape:'html':'UTF-8'}", language: "{$payzen_set_std_rest_language|escape:'html':'UTF-8'}" });

            {if $payzen_std_smartform_compact_mode == 'True'}
              KR.setFormConfig({ cardForm: { layout: 'compact' }, smartForm: { layout: 'compact'} });
            {/if}

            {if $payzen_std_smartform_payment_means_grouping_threshold != 'False'}
              KR.setFormConfig({ smartForm: { groupingThreshold: "{$payzen_std_smartform_payment_means_grouping_threshold|escape:'html':'UTF-8'}" } });
            {/if}

            KR.onFormCreated(payzenInitRestEvents);
        });
    </script>

    {if $payzen_is_valid_std_identifier}
      {include file="./payment_std_oneclick.tpl"}
      <input id="payzen_payment_by_identifier" type="hidden" name="payzen_payment_by_identifier" value="1" />
    {/if}

    {if $payzen_std_card_data_mode == '5' && $payzen_std_rest_popin_mode == 'True'}
        {if version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
            <input id="payzen_standard_link" value="{l s='Pay' mod='payzen'}" class="button" />
        {else}
            <button id="payzen_standard_link" class="button btn btn-default standard-checkout button-medium">
              <span>{l s='Pay' mod='payzen'}</span>
            </button>
        {/if}
        <script type="text/javascript">
            $('#payzen_standard_link').click(function() {
                KR.openPopin();
            });
        </script>
    {/if}
  </a>
</div>

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
  </div></div>
{/if}