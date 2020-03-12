{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

{assign var='warn_style' value='background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; font-size: 13px; margin: 0 0 10px; padding: 10px;'}

{if $check_url_warn == true}
  <p style="{$warn_style|escape:'html':'UTF-8'}">
    {if $maintenance_mode == true}
      {l s='The shop is in maintenance mode.The automatic notification cannot work.' mod='payzen'}
    {else}
      {l s='The automatic validation has not worked. Have you correctly set up the notification URL in your bank Back Office ?' mod='payzen'}
      <br />
      {l s='For understanding the problem, please read the documentation of the module : ' mod='payzen'}<br />
      &nbsp;&nbsp;&nbsp;- {l s='Chapter « To read carefully before going further »' mod='payzen'}<br />
      &nbsp;&nbsp;&nbsp;- {l s='Chapter « Notification URL settings »' mod='payzen'}
    {/if}

    <br />
    {l s='If you think this is an error, you can contact our' mod='payzen'}
    <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='payzen'}</a>.
  </p>

  <br/><br/>
{/if}

{if $prod_info == true}
  <p style="{$warn_style|escape:'html':'UTF-8'}">
    <span style="font-weight: bold; text-decoration: underline;">{l s='GOING INTO PRODUCTION' mod='payzen'}</span>
    <br />
    {l s='You want to know how to put your shop into production mode, please read chapters « Proceeding to test phase » and « Shifting the shop to production mode » in the documentation of the module.' mod='payzen'}
  </p>

  <br/><br/>
{/if}

{if $error_msg == true}
  <p style="{$warn_style|escape:'html':'UTF-8'}">
    {l s='Your order has been registered with a payment error.' mod='payzen'}

    {l s='Please contact our' mod='payzen'}&nbsp;<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='payzen'}</a>.
  </p>
{else}
  {if $amount_error_msg == true}
    <p style="{$warn_style|escape:'html':'UTF-8'}">
      {l s='Your order has been registered with an amount error.' mod='payzen'}

      {l s='Please contact our' mod='payzen'}&nbsp;<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='payzen'}</a>.
    </p>
  {/if}

  <p>
    {l s='Your order on' mod='payzen'}&nbsp;<span class="bold">{$shop_name|escape:'html':'UTF-8'}</span> {l s='is complete.' mod='payzen'}
    <br /><br />
    {l s='We registered your payment of ' mod='payzen'}&nbsp;<span class="price">{$total_to_pay|escape:'html':'UTF-8'}</span>
    <br /><br />{l s='For any questions or for further information, please contact our' mod='payzen'}&nbsp;<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='payzen'}</a>.
  </p>
{/if}