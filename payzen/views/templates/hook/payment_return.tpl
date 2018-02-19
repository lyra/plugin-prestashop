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

{assign var='warn_style' value='background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; font-size: 13px; margin: 0 0 10px; padding: 10px;'}

{if $check_url_warn == true}
  <p style="{$warn_style|escape:'html':'UTF-8'}">
    {if $maintenance_mode == true}
      {l s='The shop is in maintenance mode. The automatic notification (peer to peer connection between the payment platform and your shopping cart solution) cannot work.' mod='payzen'}
    {else}
      {l s='The automatic notification (peer to peer connection between the payment platform and your shopping cart solution) hasn\'t worked. Have you correctly set up the server URL in your store backoffice ?' mod='payzen'}
      <br />
      {l s='For understanding the problem, please read the documentation of the module : ' mod='payzen'}<br />
      &nbsp;&nbsp;&nbsp;- {l s='Chapter «To read carefully before going further»' mod='payzen'}<br />
      &nbsp;&nbsp;&nbsp;- {l s='Chapter «Server URL settings»' mod='payzen'}
    {/if}

    <br />
    {l s='If you think this is an error, you can contact our' mod='payzen'}
    <a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='payzen'}</a>.
  </p>

  <br/><br/>
{/if}

{if $prod_info == true}
  <p style="{$warn_style|escape:'html':'UTF-8'}">
    <u><b>{l s='GOING INTO PRODUCTION' mod='payzen'}</b></u><br />
    {l s='You want to know how to put your shop into production mode, please go to this URL : ' mod='payzen'}
    <a href="https://secure.payzen.eu/html/faq/prod" target="_blank">https://secure.payzen.eu/html/faq/prod</a>
  </p>

  <br/><br/>
{/if}

{if $error_msg == true}
  <p style="{$warn_style|escape:'html':'UTF-8'}">
    {l s='Your order has been registered with a payment error.' mod='payzen'}

    {l s='Please contact our' mod='payzen'}&nbsp;<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='payzen'}</a>.
  </p>
{else}
  <p>
    {l s='Your order on' mod='payzen'}&nbsp;<span class="bold">{$shop_name|escape:'html':'UTF-8'}</span> {l s='is complete.' mod='payzen'}
    <br /><br />
    {l s='We registered your payment of ' mod='payzen'}&nbsp;<span class="price">{$total_to_pay|escape:'html':'UTF-8'}</span>
    <br /><br />{l s='For any questions or for further information, please contact our' mod='payzen'}&nbsp;<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='payzen'}</a>.
  </p>
{/if}
