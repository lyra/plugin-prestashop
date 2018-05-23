{*
 * PayZen V2-Payment Module version 1.10.0 for PrestaShop 1.5-1.7. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 *
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @category  payment
 * @package   payzen
 *}

{extends file='checkout/checkout.tpl'}
{block name='content'}
  <section id="content">
    <div class="row">
      <div class="col-md-8">
        <section id="payzen_content" class="checkout-step -current">
          <h1 class="step-title h3">
            <span class="step-number"></span>
            {if isset($payzen_params) && $payzen_params.vads_action_mode == 'SILENT'}
              {l s='Payment processing' mod='payzen'}
            {else}
              {l s='Redirection to payment gateway' mod='payzen'}
            {/if}
          </h1>

          <div class="content">
            <form action="{$payzen_url|escape:'html':'UTF-8'}" method="post" id="payzen_form" name="payzen_form" onsubmit="payzenDisablePayment();">
              {foreach from=$payzen_params key='key' item='value'}
                <input type="hidden" name="{$key|escape:'html':'UTF-8'}" value="{$value|escape:'html':'UTF-8'}" />
              {/foreach}

              <p>
                <img src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen" style="margin-bottom: 5px" />
                <br />

                {if $payzen_params.vads_action_mode == 'SILENT'}
                  {l s='Please wait a moment. Your order payment is now processing.' mod='payzen'}
                {else}
                  {l s='Please wait, you will be redirected to the payment gateway.' mod='payzen'}
                {/if}

                <br /> <br />
                {l s='If nothing happens in 10 seconds, please click the button below.' mod='payzen'}
                <br /><br />
              </p>

              <p class="cart_navigation clearfix">
                <button type="submit" id="payzen_submit_payment" class="button btn btn-default standard-checkout button-medium" >
                  <span>{l s='Pay' mod='payzen'}</span>
                </button>
              </p>
            </form>
          </div>
        </section>
      </div>

       <div class="col-md-4">
      </div>
    </div>

    <script type="text/javascript">
      function payzenDisablePayment() {
        document.getElementById('payzen_submit_payment').disabled = true;
      }

      function payzenSubmitForm() {
        document.getElementById('payzen_submit_payment').click();
      }

      if (window.addEventListener) { // for most browsers
        window.addEventListener('load', payzenSubmitForm, false);
      } else if (window.attachEvent) { // for IE 8 and earlier versions
        window.attachEvent('onload', payzenSubmitForm);
      }
    </script>
  </section>
{/block}