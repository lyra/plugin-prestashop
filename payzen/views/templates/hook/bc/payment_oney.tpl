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

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
<div class="row"><div class="col-xs-12{if version_compare($smarty.const._PS_VERSION_, '1.6.0.11', '<')} col-md-6{/if}">
{/if}

{if {$payzen_oney_options|@count} == 0}
  <div class="payment_module payzen {$payzen_tag|escape:'html':'UTF-8'}">
    <a onclick="javascript: $('#payzen_oney').submit();" title="{l s='Click here to pay with FacilyPay Oney' mod='payzen'}" href="javascript: void(0);">
      <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen"/>{$payzen_title|escape:'html':'UTF-8'}

      <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" id="payzen_oney">
        <input type="hidden" name="payzen_payment_type" value="oney" />
      </form>
    </a>
  </div>
{else}
  <div class="payment_module payzen {$payzen_tag|escape:'html':'UTF-8'}">
    <a class="unclickable" title="{l s='Choose a payment option and click « Pay » button' mod='payzen'}" href="javascript: void(0);">
      <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" alt="PayZen"/>{$payzen_title|escape:'html':'UTF-8'}

      <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" id="payzen_oney">
        <input type="hidden" name="payzen_payment_type" value="oney" />

        <br />
        {assign var=first value=true}
        {foreach from=$payzen_oney_options key="key" item="option"}
          <div style="padding-bottom: 5px;">
            {if $payzen_oney_options|@count == 1}
              <input type="hidden" id="payzen_oney_option_{$key|escape:'html':'UTF-8'}" name="payzen_oney_option" value="{$key|escape:'html':'UTF-8'}" >
            {else}
              <input type="radio"
                     id="payzen_oney_option_{$key|escape:'html':'UTF-8'}"
                     name="payzen_oney_option"
                     value="{$key|escape:'html':'UTF-8'}"
                     style="vertical-align: middle;"
                     {if $first == true} checked="checked"{/if}
                     onclick="javascript: $('.payzen_oney_review').hide(); $('#payzen_oney_review_{$key|escape:'html':'UTF-8'}').show();">
            {/if}

            <label for="payzen_oney_option_{$key|escape:'html':'UTF-8'}" style="display: inline;">
              <span style="vertical-align: middle;">{$option.localized_label|escape:'html':'UTF-8'}</span>
            </label>

            <table class="payzen_oney_review payzen_review" id="payzen_oney_review_{$key|escape:'html':'UTF-8'}" {if $first != true} style="display: none;"{/if}>
              <thead>
                <tr>
                  <th>{l s='Your order total :' mod='payzen'} {$option.order_total|escape:'html':'UTF-8'}</th>
                  <th>{l s='Debit dates' mod='payzen'}</th>
                </tr>
              </thead>

              <tbody>
                <tr>
                  <td>{l s='Contribution :' mod='payzen'} {$option.first_payment|escape:'html':'UTF-8'}</td>
                  <td><strong>{$smarty.now|date_format:'%d/%m/%Y'|escape:'html':'UTF-8'}</strong></td>
                </tr>
                <tr>
                  <td>{{l s='Followed by %s payments' mod='payzen'}|sprintf:{$option.funding_count|escape:'html':'UTF-8'}}</td>
                  <td></td>
                </tr>
                {section name=row start=1 loop=$option.count step=1}
                  {assign var=i value={$smarty.section.row.index|intval}}
                  <tr>
                    <td>&nbsp;- {{l s='Payment %s :' mod='payzen'}|sprintf:$i} {$option.monthly_payment|escape:'html':'UTF-8'}</td>
                    <td><strong>{"+{$i|escape:'html':'UTF-8'} months"|date_format:'%d/%m/%Y'}</strong></td>
                  </tr>
                {/section}
                <tr>
                  <td colspan="2">{l s='Total cost of credit :' mod='payzen'} {$option.funding_fees|escape:'html':'UTF-8'}</td>
                </tr>
                <!-- <tr>
                  <td colspan="2" class="small">{{l s='Funding of %s with a fixed APR of %s %%.' mod='payzen'}|sprintf:{$option.funding_total|escape:'html':'UTF-8'}:{$option.taeg|escape:'html':'UTF-8'}}</td>
                </tr> -->
              </tbody>
          </table>

          {assign var=first value=false}
          </div>
        {/foreach}

        {if version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
          <input type="submit" name="submit" value="{l s='Pay' mod='payzen'}" class="button" />
        {else}
          <button type="submit" name="submit" class="button btn btn-default standard-checkout button-medium" >
            <span>{l s='Pay' mod='payzen'}</span>
          </button>
        {/if}
      </form>
    </a>
  </div>
{/if}

{if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
</div></div>
{/if}
