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

{if {$payzen_fullcb_options|@count} == 0}
  <div class="payment_module payzen {$payzen_tag|escape:'html':'UTF-8'}">
    <a href="javascript: $('#payzen_fullcb').submit();" title="{l s='Click here to pay with Full CB' mod='payzen'}">
      <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" />{$payzen_title|escape:'html':'UTF-8'}

      <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" id="payzen_fullcb">
        <input type="hidden" name="payzen_payment_type" value="fullcb" />
      </form>
    </a>
  </div>
{else}
  <div class="payment_module payzen {$payzen_tag|escape:'html':'UTF-8'}">
    <a class="unclickable" title="{l s='Choose a payment option and click « Pay » button' mod='payzen'}" href="javascript: void(0);">
      <img class="logo" src="{$payzen_logo|escape:'html':'UTF-8'}" />{$payzen_title|escape:'html':'UTF-8'}

      <form action="{$link->getModuleLink('payzen', 'redirect', array(), true)|escape:'html':'UTF-8'}" method="post" id="payzen_fullcb">
        <input type="hidden" name="payzen_payment_type" value="fullcb" />

        <br />
        {assign var=first value=true}
        {foreach from=$payzen_fullcb_options key="key" item="option"}
          <div style="padding-bottom: 5px;">
            {if $payzen_fullcb_options|@count == 1}
              <input type="hidden" id="payzen_fullcb_option_{$key|escape:'html':'UTF-8'}" name="payzen_card_type" value="{$key|escape:'html':'UTF-8'}" >
            {else}
              <input type="radio"
                     id="payzen_fullcb_option_{$key|escape:'html':'UTF-8'}"
                     name="payzen_card_type"
                     value="{$key|escape:'html':'UTF-8'}"
                     style="vertical-align: middle;"
                     {if $first == true} checked="checked"{/if}
                     onclick="javascript: $('.payzen_fullcb_review').hide(); $('#payzen_fullcb_review_{$key|escape:'html':'UTF-8'}').show();">
            {/if}

            <label for="payzen_fullcb_option_{$key|escape:'html':'UTF-8'}" style="display: inline;">
              <span style="vertical-align: middle;">{$option.localized_label|escape:'html':'UTF-8'}</span>
            </label>

            <table class="payzen_fullcb_review payzen_review" id="payzen_fullcb_review_{$key|escape:'html':'UTF-8'}" {if $first != true} style="display: none;"{/if}>
              <tr>
                <td>
                  <table>
                    <tbody>
                      <tr>
                        <td>{l s='Order amount :' mod='payzen'}</td>
                        <td class="amount">{$option.order_amount|escape:'html':'UTF-8'}</td>
                      </tr>
                      <tr>
                        <td>{l s='Fees :' mod='payzen'}</td>
                        <td class="amount">{$option.fees|escape:'html':'UTF-8'}</td>
                      </tr>
                      <tr>
                        <td colspan="2"><hr style="margin: 0px;" /></td>
                      </tr>
                      <tr>
                        <td>{l s='Total amount :' mod='payzen'}</td>
                        <td class="amount">{$option.total_amount|escape:'html':'UTF-8'}</td>
                      </tr>
                    </tbody>
                  </table>
                </td>
                <td>
                  <table>
                    <tbody>
                      <tr>
                        <th colspan="2">{l s='Installments' mod='payzen'}</th>
                      </tr>

                      <tr>
                        <td>{$smarty.now|date_format:'%d/%m/%Y'|escape:'html':'UTF-8'}</td>
                        <td class="amount">{$option.first_payment|escape:'html':'UTF-8'}</td>
                      </tr>
                      {section name=row start=1 loop=$option.count step=1}
                        {assign var=i value={$smarty.section.row.index|intval}}
                        <tr>
                          <td>{"+{$i|escape:'html':'UTF-8'} months"|date_format:'%d/%m/%Y'}</td>
                          <td class="amount">{$option.monthly_payment|escape:'html':'UTF-8'}</td>
                        </tr>
                      {/section}
                    </tbody>
                  </table>
                </td>
              </tr>
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