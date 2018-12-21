{*
 * PayZen V2-Payment Module version 1.10.2 for PrestaShop 1.5-1.7. Support contact : support@payzen.eu.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2018 Lyra Network and contributors
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<script type="text/javascript">
  $(function() {
    $('#accordion').accordion({
      active: false,
      collapsible: true,
      autoHeight: false,
      heightStyle: 'content',
      header: 'h4',
      animated: false
    });
  });
</script>

<form method="POST" action="{$payzen_request_uri|escape:'html':'UTF-8'}" class="defaultForm form-horizontal">
  <div style="width: 100%;">
    <fieldset>
      <legend>
        <img style="width: 20px; vertical-align: middle;" src="../modules/payzen/logo.png" alt="PayZen">PayZen
      </legend>

      {l s='Developed by' mod='payzen'} : <b><a href="http://www.lyra-network.com/" target="_blank">Lyra Network</a></b><br />
      {l s='Contact us' mod='payzen'} : <b><a href="mailto:support@payzen.eu">support@payzen.eu</a></b><br />
      {l s='Module version' mod='payzen'} : <b>{if $smarty.const._PS_HOST_MODE_|defined}Cloud{/if}1.10.2</b><br />
      {l s='Gateway version' mod='payzen'} : <b>V2</b><br />

      {if !empty($payzen_doc_files)}
        <span style="color: red; font-weight: bold; text-transform: uppercase;">{l s='Click to view the module configuration documentation :' mod='payzen'}</span>
        {foreach from=$payzen_doc_files key="file" item="lang"}
          <a style="margin-left: 10px; font-weight: bold; text-transform: uppercase;" href="../modules/payzen/installation_doc/{$file|escape:'html':'UTF-8'}" target="_blank">{$lang|escape:'html':'UTF-8'}</a>
        {/foreach}
      {/if}
    </fieldset>
  </div>

  <br /><br />

  <div id="accordion" style="width: 100%; float: none;">
    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='GENERAL CONFIGURATION' mod='payzen'}</a>
    </h4>
    <div>
      <fieldset>
        <legend>{l s='BASE SETTINGS' mod='payzen'}</legend>

        <label for="PAYZEN_ENABLE_LOGS">{l s='Logs' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_ENABLE_LOGS" name="PAYZEN_ENABLE_LOGS">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ENABLE_LOGS === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enable / disbale module logs' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT GATEWAY ACCESS' mod='payzen'}</legend>

        <label for="PAYZEN_SITE_ID">{l s='Site ID' mod='payzen'}</label>
        <div class="margin-form">
          <input type="text" id="PAYZEN_SITE_ID" name="PAYZEN_SITE_ID" value="{$PAYZEN_SITE_ID|escape:'html':'UTF-8'}" autocomplete="off">
          <p>{l s='The identifier provided by your bank.' mod='payzen'}</p>
        </div>

        {if !$payzen_plugin_features['qualif']}
        <label for="PAYZEN_KEY_TEST">{l s='Certificate in test mode' mod='payzen'}</label>
        <div class="margin-form">
          <input type="text" id="PAYZEN_KEY_TEST" name="PAYZEN_KEY_TEST" value="{$PAYZEN_KEY_TEST|escape:'html':'UTF-8'}" autocomplete="off">
          <p>{l s='Certificate provided by your bank for test mode (available in your store Back Office).' mod='payzen'}</p>
        </div>
        {/if}

        <label for="PAYZEN_KEY_PROD">{l s='Certificate in production mode' mod='payzen'}</label>
        <div class="margin-form">
          <input type="text" id="PAYZEN_KEY_PROD" name="PAYZEN_KEY_PROD" value="{$PAYZEN_KEY_PROD|escape:'html':'UTF-8'}" autocomplete="off">
          <p>{l s='Certificate provided by your bank (available in your store Back Office after enabling production mode).' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_MODE">{l s='Mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_MODE" name="PAYZEN_MODE" {if $payzen_plugin_features['qualif']} disabled="disabled"{/if}>
            {foreach from=$payzen_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_MODE === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='The context mode of this module.' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_SIGN_ALGO">{l s='Signature algorithm' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_SIGN_ALGO" name="PAYZEN_SIGN_ALGO">
            <option value="SHA-1"{if $PAYZEN_SIGN_ALGO === 'SHA-1'} selected="selected"{/if}>SHA-1</option>
            <option value="SHA-256"{if $PAYZEN_SIGN_ALGO === 'SHA-256'} selected="selected"{/if}>HMAC-SHA-256</option>
          </select>
          <p>
            {l s='Algorithm used to compute the payment form signature. Selected algorithm must be the same as one configured in your store Back Office.' mod='payzen'}<br />
            {if !$payzen_plugin_features['shatwo']}
              <b>{l s='The HMAC-SHA-256 algorithm should not be activated if it is not yet available in your store Back Office, the feature will be available soon.' mod='payzen'}</b>
            {/if}
          </p>
        </div>

        <label>{l s='Instant Payment Notification URL' mod='payzen'}</label>
        <div class="margin-form">
          <span style="font-weight: bold;">{$PAYZEN_NOTIFY_URL|escape:'html':'UTF-8'}</span><br />
          <p>
            <img src="{$smarty.const._MODULE_DIR_|escape:'html':'UTF-8'}payzen/views/img/warn.png">
            <span style="color: red; display: inline-block;">
              {l s='URL to copy into your bank Back Office > Settings > Notification rules.' mod='payzen'}<br />
              {l s='In multistore mode, notification URL is the same for all the stores.' mod='payzen'}
            </span>
          </p>
        </div>

        <label for="PAYZEN_PLATFORM_URL">{l s='Payment page URL' mod='payzen'}</label>
        <div class="margin-form">
          <input type="text" id="PAYZEN_PLATFORM_URL" name="PAYZEN_PLATFORM_URL" value="{$PAYZEN_PLATFORM_URL|escape:'html':'UTF-8'}" style="width: 470px;">
          <p>{l s='Link to the payment page.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT PAGE' mod='payzen'}</legend>

        <label for="PAYZEN_DEFAULT_LANGUAGE">{l s='Default language' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_DEFAULT_LANGUAGE" name="PAYZEN_DEFAULT_LANGUAGE">
            {foreach from=$payzen_language_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_DEFAULT_LANGUAGE === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Default language on the payment page.' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_AVAILABLE_LANGUAGES">{l s='Available languages' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_AVAILABLE_LANGUAGES" name="PAYZEN_AVAILABLE_LANGUAGES[]" multiple="multiple" size="8">
            {foreach from=$payzen_language_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_AVAILABLE_LANGUAGES)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Languages available on the payment page. If you do not select any, all the supported languages will be available.' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_DELAY">{l s='Capture delay' mod='payzen'}</label>
        <div class="margin-form">
          <input type="text" id="PAYZEN_DELAY" name="PAYZEN_DELAY" value="{$PAYZEN_DELAY|escape:'html':'UTF-8'}">
          <p>{l s='The number of days before the bank capture (adjustable in your store Back Office).' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_VALIDATION_MODE">{l s='Validation mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_VALIDATION_MODE" name="PAYZEN_VALIDATION_MODE">
            {foreach from=$payzen_validation_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_VALIDATION_MODE === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='If manual is selected, you will have to confirm payments manually in your bank Back Office.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT PAGE CUSTOMIZE' mod='payzen'}</legend>

        <label for="PAYZEN_THEME_CONFIG">{l s='Theme configuration' mod='payzen'}</label>
        <div class="margin-form">
          <input type="text" id="PAYZEN_THEME_CONFIG" name="PAYZEN_THEME_CONFIG" value="{$PAYZEN_THEME_CONFIG|escape:'html':'UTF-8'}" style="width: 470px;">
          <p>{l s='The theme configuration to customize the payment page.' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_SHOP_NAME">{l s='Shop name' mod='payzen'}</label>
        <div class="margin-form">
          <input type="text" id="PAYZEN_SHOP_NAME" name="PAYZEN_SHOP_NAME" value="{$PAYZEN_SHOP_NAME|escape:'html':'UTF-8'}">
          <p>{l s='Shop name to display on the payment page. Leave blank to use gateway configuration.' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_SHOP_URL">{l s='Shop URL' mod='payzen'}</label>
        <div class="margin-form">
          <input type="text" id="PAYZEN_SHOP_URL" name="PAYZEN_SHOP_URL" value="{$PAYZEN_SHOP_URL|escape:'html':'UTF-8'}" style="width: 470px;">
          <p>{l s='Shop URL to display on the payment page. Leave blank to use gateway configuration.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='SELECTIVE 3DS' mod='payzen'}</legend>

        <label for="PAYZEN_3DS_MIN_AMOUNT">{l s='Disable 3DS by customer group' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_3DS_MIN_AMOUNT"
            input_value=$PAYZEN_3DS_MIN_AMOUNT
            min_only=true
          }
          <p>{l s='Amount below which 3DS will be disabled for each customer group. Needs subscription to selective 3DS option. For more information, refer to the module documentation.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='RETURN TO SHOP' mod='payzen'}</legend>

        <label for="PAYZEN_REDIRECT_ENABLED">{l s='Automatic redirection' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_REDIRECT_ENABLED" name="PAYZEN_REDIRECT_ENABLED" onchange="javascript: payzenRedirectChanged();">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_REDIRECT_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='If enabled, the buyer is automatically redirected to your site at the end of the payment.' mod='payzen'}</p>
        </div>

        <section id="payzen_redirect_settings">
          <label for="PAYZEN_REDIRECT_SUCCESS_T">{l s='Redirection timeout on success' mod='payzen'}</label>
          <div class="margin-form">
            <input type="text" id="PAYZEN_REDIRECT_SUCCESS_T" name="PAYZEN_REDIRECT_SUCCESS_T" value="{$PAYZEN_REDIRECT_SUCCESS_T|escape:'html':'UTF-8'}">
            <p>{l s='Time in seconds (0-300) before the buyer is automatically redirected to your website after a successful payment.' mod='payzen'}</p>
          </div>

          <label>{l s='Redirection message on success' mod='payzen'}</label>
          <div class="margin-form">
            {include file="./input_text_lang.tpl"
              languages=$prestashop_languages
              current_lang=$prestashop_lang
              input_name="PAYZEN_REDIRECT_SUCCESS_M"
              input_value=$PAYZEN_REDIRECT_SUCCESS_M
              style="width: 470px;"
            }
            <p>{l s='Message displayed on the payment page prior to redirection after a successful payment.' mod='payzen'}</p>
          </div>

          <label for="PAYZEN_REDIRECT_ERROR_T">{l s='Redirection timeout on failure' mod='payzen'}</label>
          <div class="margin-form">
            <input type="text" id="PAYZEN_REDIRECT_ERROR_T" name="PAYZEN_REDIRECT_ERROR_T" value="{$PAYZEN_REDIRECT_ERROR_T|escape:'html':'UTF-8'}">
            <p>{l s='Time in seconds (0-300) before the buyer is automatically redirected to your website after a declined payment.' mod='payzen'}</p>
          </div>

          <label>{l s='Redirection message on failure' mod='payzen'}</label>
          <div class="margin-form">
            {include file="./input_text_lang.tpl"
              languages=$prestashop_languages
              current_lang=$prestashop_lang
              input_name="PAYZEN_REDIRECT_ERROR_M"
              input_value=$PAYZEN_REDIRECT_ERROR_M
              style="width: 470px;"
            }
            <p>{l s='Message displayed on the payment page prior to redirection after a declined payment.' mod='payzen'}</p>
          </div>
        </section>

        <script type="text/javascript">
          payzenRedirectChanged();
        </script>

        <label for="PAYZEN_RETURN_MODE">{l s='Return mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_RETURN_MODE" name="PAYZEN_RETURN_MODE">
            <option value="GET"{if $PAYZEN_RETURN_MODE === 'GET'} selected="selected"{/if}>GET</option>
            <option value="POST"{if $PAYZEN_RETURN_MODE === 'POST'} selected="selected"{/if}>POST</option>
          </select>
          <p>{l s='Method that will be used for transmitting the payment result from the payment page to your shop.' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_FAILURE_MANAGEMENT">{l s='Payment failed management' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_FAILURE_MANAGEMENT" name="PAYZEN_FAILURE_MANAGEMENT">
            {foreach from=$payzen_failure_management_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_FAILURE_MANAGEMENT === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='How to manage the buyer return to shop when the payment is failed.' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_CART_MANAGEMENT">{l s='Cart management' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_CART_MANAGEMENT" name="PAYZEN_CART_MANAGEMENT">
            {foreach from=$payzen_cart_management_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_CART_MANAGEMENT === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='We recommend to choose the option « Empty cart » in order to avoid amount inconsistencies. In case of return back from the browser button the cart will be emptied. However in case of cancelled or refused payment, the cart will be recovered. If you do not want to have this behavior but the default PrestaShop one which is to keep the cart, choose the second option.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend onclick="javascript: payzenAdditionalOptionsToggle(this);" style="cursor: pointer;">
          <span class="ui-icon ui-icon-triangle-1-e" style="display: inline-block; vertical-align: middle;"></span>
          {l s='ADDITIONAL OPTIONS' mod='payzen'}
        </legend>
        <p style="font-size: .85em; color: #7F7F7F;">{l s='Configure this section if you use advanced risk assessment module or if you have a FacilyPay Oney contract.' mod='payzen'}</p>

        <section style="display: none; padding-top: 15px;">
          <label for="PAYZEN_COMMON_CATEGORY">{l s='Category mapping' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_COMMON_CATEGORY" name="PAYZEN_COMMON_CATEGORY" style="width: 220px;" onchange="javascript: payzenCategoryTableVisibility();">
              <option value="CUSTOM_MAPPING"{if $PAYZEN_COMMON_CATEGORY === 'CUSTOM_MAPPING'} selected="selected"{/if}>{l s='(Use category mapping below)' mod='payzen'}</option>
              {foreach from=$payzen_category_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_COMMON_CATEGORY === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='Use the same category for all products.' mod='payzen'}</p>

            <table cellpadding="10" cellspacing="0" class="table payzen_category_mapping" style="margin-top: 15px;{if $PAYZEN_COMMON_CATEGORY != 'CUSTOM_MAPPING'} display: none;{/if}">
            <thead>
              <tr>
                <th>{l s='Product category' mod='payzen'}</th>
                <th>{l s='Bank product category' mod='payzen'}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$prestashop_categories item="category"}
                {if $category.id_parent === 0}
                  {continue}
                {/if}

                {assign var="category_id" value=$category.id_category}

                {if isset($PAYZEN_CATEGORY_MAPPING[$category_id])}
                  {assign var="exists" value=true}
                {else}
                  {assign var="exists" value=false}
                {/if}

                {if $exists}
                  {assign var="payzen_category" value=$PAYZEN_CATEGORY_MAPPING[$category_id]}
                {else}
                  {assign var="payzen_category" value="FOOD_AND_GROCERY"}
                {/if}

                <tr id="payzen_category_mapping_{$category_id|escape:'html':'UTF-8'}">
                  <td>{$category.name|escape:'html':'UTF-8'}{if $exists === false}<span style="color: red;">*</span>{/if}</td>
                  <td>
                    <select id="PAYZEN_CATEGORY_MAPPING_{$category_id|escape:'html':'UTF-8'}" name="PAYZEN_CATEGORY_MAPPING[{$category_id|escape:'html':'UTF-8'}]"
                        style="width: 220px;"{if $PAYZEN_COMMON_CATEGORY != 'CUSTOM_MAPPING'} disabled="disabled"{/if}>
                      {foreach from=$payzen_category_options key="key" item="option"}
                        <option value="{$key|escape:'html':'UTF-8'}"{if $payzen_category === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                      {/foreach}
                    </select>
                  </td>
                </tr>
              {/foreach}
            </tbody>
            </table>
            <p class="payzen_category_mapping"{if $PAYZEN_COMMON_CATEGORY != 'CUSTOM_MAPPING'} style="display: none;"{/if}>{l s='Match each product category with a bank product category.' mod='payzen'} <b>{l s='Entries marked with * are newly added and must be configured.' mod='payzen'}</b></p>
          </div>

          <label for="PAYZEN_SEND_SHIP_DATA">{l s='Always send advanced shipping data' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_SEND_SHIP_DATA" name="PAYZEN_SEND_SHIP_DATA">
              {foreach from=$payzen_yes_no_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_SEND_SHIP_DATA === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='Select « Yes » to send advanced shipping data for all payments (carrier name, delivery type and delivery rapidity).' mod='payzen'}</p>
          </div>

          <label>{l s='Shipping options' mod='payzen'}</label>
          <div class="margin-form">
            <table class="table" cellpadding="10" cellspacing="0">
            <thead>
              <tr>
                <th>{l s='Method title' mod='payzen'}</th>
                <th>{l s='Name' mod='payzen'}</th>
                <th>{l s='Type' mod='payzen'}</th>
                <th>{l s='Rapidity' mod='payzen'}</th>
                <th>{l s='Delay' mod='payzen'}</th>
                <th style="width: 270px;" colspan="3">{l s='Address' mod='payzen'}</th>
              </tr>
            </thead>
            <tbody>
              {foreach from=$prestashop_carriers item="carrier"}
                {assign var="carrier_id" value=$carrier.id_carrier}

                {if isset($PAYZEN_ONEY_SHIP_OPTIONS[$carrier_id])}
                  {assign var="exists" value=true}
                {else}
                  {assign var="exists" value=false}
                {/if}

                {if $exists}
                  {assign var="ship_option" value=$PAYZEN_ONEY_SHIP_OPTIONS[$carrier_id]}
                {/if}

                <tr>
                  <td>{$carrier.name|escape:'html':'UTF-8'}{if $exists === false}<span style="color: red;">*</span>{/if}</td>
                  <td>
                    <input id="PAYZEN_ONEY_SHIP_OPTIONS_{$carrier_id|escape:'html':'UTF-8'}_label"
                        name="PAYZEN_ONEY_SHIP_OPTIONS[{$carrier_id|escape:'html':'UTF-8'}][label]"
                        value="{if isset($ship_option)}{$ship_option.label|escape:'html':'UTF-8'}{else}{$carrier.name|regex_replace:"#[^A-Z0-9ÁÀÂÄÉÈÊËÍÌÎÏÓÒÔÖÚÙÛÜÇ /'-]#ui":" "|substr:0:55}{/if}"
                        type="text">
                  </td>
                  <td>
                    <select id="PAYZEN_ONEY_SHIP_OPTIONS_{$carrier_id|escape:'html':'UTF-8'}_type" name="PAYZEN_ONEY_SHIP_OPTIONS[{$carrier_id|escape:'html':'UTF-8'}][type]" onchange="javascript: payzenDeliveryTypeChanged({$carrier_id|escape:'html':'UTF-8'});" style="width: 150px;">
                      {foreach from=$payzen_delivery_type_options key="key" item="option"}
                        <option value="{$key|escape:'html':'UTF-8'}"{if (isset($ship_option) && $ship_option.type === $key) || ('PACKAGE_DELIVERY_COMPANY' === $key)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                      {/foreach}
                    </select>
                  </td>
                  <td>
                    <select id="PAYZEN_ONEY_SHIP_OPTIONS_{$carrier_id|escape:'html':'UTF-8'}_speed" name="PAYZEN_ONEY_SHIP_OPTIONS[{$carrier_id|escape:'html':'UTF-8'}][speed]" onchange="javascript: payzenDeliverySpeedChanged({$carrier_id|escape:'html':'UTF-8'});">
                      {foreach from=$payzen_delivery_speed_options key="key" item="option"}
                        <option value="{$key|escape:'html':'UTF-8'}"{if (isset($ship_option) && $ship_option.speed === $key) || ('STANDARD' === $key)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                      {/foreach}
                    </select>
                  </td>
                  <td>
                    <select
                        id="PAYZEN_ONEY_SHIP_OPTIONS_{$carrier_id|escape:'html':'UTF-8'}_delay"
                        name="PAYZEN_ONEY_SHIP_OPTIONS[{$carrier_id|escape:'html':'UTF-8'}][delay]"
                        style="{if !isset($ship_option) || ($ship_option.type != 'RECLAIM_IN_SHOP') || ($ship_option.speed != 'PRIORITY')} display: none;{/if}">
                      {foreach from=$payzen_delivery_delay_options key="key" item="option"}
                        <option value="{$key|escape:'html':'UTF-8'}"{if (isset($ship_option) && isset($ship_option.delay) && ($ship_option.delay === $key)) || 'INFERIOR_EQUALS' === $key} selected="selected"{/if}>{$option|escape:'quotes':'UTF-8'}</option>
                      {/foreach}
                    </select>
                  </td>
                  <td>
                    <input
                        id="PAYZEN_ONEY_SHIP_OPTIONS_{$carrier_id|escape:'html':'UTF-8'}_address"
                        name="PAYZEN_ONEY_SHIP_OPTIONS[{$carrier_id|escape:'html':'UTF-8'}][address]"
                        placeholder="{l s='Address' mod='payzen'}"
                        value="{if isset($ship_option)}{$ship_option.address|escape:'html':'UTF-8'}{/if}"
                        style="width: 160px;{if !isset($ship_option) || $ship_option.type != 'RECLAIM_IN_SHOP'} display: none;{/if}"
                        type="text">
                  </td>
                  <td>
                    <input
                        id="PAYZEN_ONEY_SHIP_OPTIONS_{$carrier_id|escape:'html':'UTF-8'}_zip"
                        name="PAYZEN_ONEY_SHIP_OPTIONS[{$carrier_id|escape:'html':'UTF-8'}][zip]"
                        placeholder="{l s='Zip code' mod='payzen'}"
                        value="{if isset($ship_option)}{$ship_option.zip|escape:'html':'UTF-8'}{/if}"
                        style="width: 50px;{if !isset($ship_option) || $ship_option.type != 'RECLAIM_IN_SHOP'} display: none;{/if}"
                        type="text">
                  </td>
                  <td>
                    <input
                        id="PAYZEN_ONEY_SHIP_OPTIONS_{$carrier_id|escape:'html':'UTF-8'}_city"
                        name="PAYZEN_ONEY_SHIP_OPTIONS[{$carrier_id|escape:'html':'UTF-8'}][city]"
                        placeholder="{l s='City' mod='payzen'}"
                        value="{if isset($ship_option)}{$ship_option.city|escape:'html':'UTF-8'}{/if}"
                        style="width: 160px;{if !isset($ship_option) || $ship_option.type != 'RECLAIM_IN_SHOP'} display: none;{/if}"
                        type="text">
                  </td>
                </tr>
              {/foreach}
            </tbody>
            </table>
            <p>
              {l s='Define the information about all shipping methods.' mod='payzen'}<br />
              <b>{l s='Name' mod='payzen'} : </b>{l s='The label of the shipping method (use 55 alphanumeric characters, accentuated characters and these special characters: space, slash, hyphen, apostrophe).' mod='payzen'}<br />
              <b>{l s='Type' mod='payzen'} : </b>{l s='The delivery type of shipping method.' mod='payzen'}<br />
              <b>{l s='Rapidity' mod='payzen'} : </b>{l s='Select the delivery rapidity.' mod='payzen'}<br />
              <b>{l s='Delay' mod='payzen'} : </b>{l s='Select the delivery delay if speed is « Priority ».' mod='payzen'}<br />
              <b>{l s='Address' mod='payzen'} : </b>{l s='Enter address if it is a reclaim in shop.' mod='payzen'}<br />
              <b>{l s='Entries marked with * are newly added and must be configured.' mod='payzen'}</b>
            </p>
          </div>
        </section>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>

    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='ONE-TIME PAYMENT' mod='payzen'}</a>
    </h4>
    <div>
      <fieldset>
        <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

       <label for="PAYZEN_STD_ENABLED">{l s='Activation' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_STD_ENABLED" name="PAYZEN_STD_ENABLED">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enables / disables %s payment.' sprintf='standard' mod='payzen'}</p>
        </div>

        <label>{l s='Payment method title' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
            languages=$prestashop_languages
            current_lang=$prestashop_lang
            input_name="PAYZEN_STD_TITLE"
            input_value=$PAYZEN_STD_TITLE
            style="width: 330px;"
          }
          <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='AMOUNT RESTRICTIONS' mod='payzen'}</legend>

        <label>{l s='Customer group amount restriction' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_STD_AMOUNTS"
            input_value=$PAYZEN_STD_AMOUNTS
          }
          <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT PAGE' mod='payzen'}</legend>

        <label for="PAYZEN_STD_DELAY">{l s='Capture delay' mod='payzen'}</label>
        <div class="margin-form">
          <input id="PAYZEN_STD_DELAY" name="PAYZEN_STD_DELAY" value="{$PAYZEN_STD_DELAY|escape:'html':'UTF-8'}" type="text">
          <p>{l s='The number of days before the bank capture. Enter value only if different from « Base settings ».' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_STD_VALIDATION">{l s='Validation mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_STD_VALIDATION" name="PAYZEN_STD_VALIDATION">
            <option value="-1"{if $PAYZEN_STD_VALIDATION === '-1'} selected="selected"{/if}>{l s='Base settings configuration' mod='payzen'}</option>
            {foreach from=$payzen_validation_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_VALIDATION === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='If manual is selected, you will have to confirm payments manually in your bank Back Office.' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_STD_PAYMENT_CARDS">{l s='Card Types' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_STD_PAYMENT_CARDS" name="PAYZEN_STD_PAYMENT_CARDS[]" multiple="multiple" size="7">
            {foreach from=$payzen_payment_cards_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_STD_PAYMENT_CARDS)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='The card type(s) that can be used for the payment. Select none to use gateway configuration.' mod='payzen'}</p>
        </div>

        {if $payzen_plugin_features['oney']}
        <label for="PAYZEN_STD_PROPOSE_ONEY">{l s='Propose FacilyPay Oney' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_STD_PROPOSE_ONEY" name="PAYZEN_STD_PROPOSE_ONEY">
            {foreach from=$payzen_yes_no_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_PROPOSE_ONEY === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Select « Yes » if you want to propose FacilyPay Oney in one-time payment. Attention, you must ensure that you have a FacilyPay Oney contract.' mod='payzen'}</p>
        </div>
        {/if}
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='ADVANCED OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_STD_CARD_DATA_MODE">{l s='Card data entry mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_STD_CARD_DATA_MODE" name="PAYZEN_STD_CARD_DATA_MODE">
            {foreach from=$payzen_card_data_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_CARD_DATA_MODE === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Select how the card data will be entered. Attention, to use bank data acquisition on the merchant site, you must ensure that you have subscribed to this option with your bank.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>

    {if $payzen_plugin_features['multi']}
    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='PAYMENT IN INSTALLMENTS' mod='payzen'}</a>
    </h4>
    <div>

      {if $payzen_plugin_features['restrictmulti']}
      <p style="background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; font-size: 13px; margin: 0 0 20px; padding: 10px;">
        {l s='ATTENTION: The payment in installments feature activation is subject to the prior agreement of Société Générale.' mod='payzen'}<br />
        {l s='If you enable this feature while you have not the associated option, an error 10000 – INSTALLMENTS_NOT_ALLOWED or 07 - PAYMENT_CONFIG will occur and the buyer will not be able to pay.' mod='payzen'}
      </p>
      {/if}

      <fieldset>
        <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_MULTI_ENABLED">{l s='Activation' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_MULTI_ENABLED" name="PAYZEN_MULTI_ENABLED">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_MULTI_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enables / disables %s payment.' sprintf='multiple' mod='payzen'}</p>
        </div>

        <label>{l s='Payment method title' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
            languages=$prestashop_languages
            current_lang=$prestashop_lang
            input_name="PAYZEN_MULTI_TITLE"
            input_value=$PAYZEN_MULTI_TITLE
            style="width: 330px;"
          }
          <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='AMOUNT RESTRICTIONS' mod='payzen'}</legend>

        <label>{l s='Customer group amount restriction' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_MULTI_AMOUNTS"
            input_value=$PAYZEN_MULTI_AMOUNTS
          }
          <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT PAGE' mod='payzen'}</legend>

        <label for="PAYZEN_MULTI_DELAY">{l s='Capture delay' mod='payzen'}</label>
        <div class="margin-form">
          <input id="PAYZEN_MULTI_DELAY" name="PAYZEN_MULTI_DELAY" value="{$PAYZEN_MULTI_DELAY|escape:'html':'UTF-8'}" type="text">
          <p>{l s='The number of days before the bank capture. Enter value only if different from « Base settings ».' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_MULTI_VALIDATION">{l s='Validation mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_MULTI_VALIDATION" name="PAYZEN_MULTI_VALIDATION">
            <option value="-1"{if $PAYZEN_MULTI_VALIDATION === '-1'} selected="selected"{/if}>{l s='Base settings configuration' mod='payzen'}</option>
            {foreach from=$payzen_validation_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_MULTI_VALIDATION === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='If manual is selected, you will have to confirm payments manually in your bank Back Office.' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_MULTI_PAYMENT_CARDS">{l s='Card Types' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_MULTI_PAYMENT_CARDS" name="PAYZEN_MULTI_PAYMENT_CARDS[]" multiple="multiple" size="7">
            {foreach from=$payzen_multi_payment_cards_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_MULTI_PAYMENT_CARDS)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='The card type(s) that can be used for the payment. Select none to use gateway configuration.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='ADVANCED OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_MULTI_CARD_MODE">{l s='Card type selection' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_MULTI_CARD_MODE" name="PAYZEN_MULTI_CARD_MODE">
            {foreach from=$payzen_card_selection_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_MULTI_CARD_MODE === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Select where the card type will be selected by the buyer.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT OPTIONS' mod='payzen'}</legend>

        <label>{l s='Payment options' mod='payzen'}</label>
        <div class="margin-form">
          <script type="text/html" id="payzen_multi_row_option">
            {include file="./row_multi_option.tpl"
              languages=$prestashop_languages
              current_lang=$prestashop_lang
              key="PAYZEN_MULTI_KEY"
              option=$payzen_default_multi_option
            }
          </script>

          <button type="button" id="payzen_multi_options_btn"{if !empty($PAYZEN_MULTI_OPTIONS)} style="display: none;"{/if} onclick="javascript: payzenAddMultiOption(true, '{l s='Delete' mod='payzen'}');">{l s='Add' mod='payzen'}</button>

          <table id="payzen_multi_options_table"{if empty($PAYZEN_MULTI_OPTIONS)} style="display: none;"{/if} class="table" cellpadding="10" cellspacing="0">
          <thead>
            <tr>
              <th style="font-size: 10px;">{l s='Label' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Min amount' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Max amount' mod='payzen'}</th>
              {if in_array('CB', $payzen_multi_payment_cards_options)}
              <th style="font-size: 10px;">{l s='Contract' mod='payzen'}</th>
              {/if}
              <th style="font-size: 10px;">{l s='Count' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Period' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='1st payment' mod='payzen'}</th>
              <th style="font-size: 10px;"></th>
            </tr>
          </thead>

          <tbody>
            {foreach from=$PAYZEN_MULTI_OPTIONS key="key" item="option"}
              {include file="./row_multi_option.tpl"
                languages=$prestashop_languages
                current_lang=$prestashop_lang
                key=$key
                option=$option
              }
            {/foreach}

            <tr id="payzen_multi_option_add">
              <td colspan="{if in_array('CB', $payzen_multi_payment_cards_options)}7{else}6{/if}"></td>
              <td>
                <button type="button" onclick="javascript: payzenAddMultiOption(false, '{l s='Delete' mod='payzen'}');">{l s='Add' mod='payzen'}</button>
              </td>
            </tr>
          </tbody>
          </table>
          <p>
            {l s='Click on « Add » button to configure one or more payment options.' mod='payzen'}<br />
            <b>{l s='Label' mod='payzen'} : </b>{l s='The option label to display on the frontend.' mod='payzen'}<br />
            <b>{l s='Min amount' mod='payzen'} : </b>{l s='Minimum amount to enable the payment option.' mod='payzen'}<br />
            <b>{l s='Max amount' mod='payzen'} : </b>{l s='Maximum amount to enable the payment option.' mod='payzen'}<br />
            {if in_array('CB', $payzen_multi_payment_cards_options)}
            <b>{l s='Contract' mod='payzen'} : </b>{l s='ID of the contract to use with the option (Leave blank preferably).' mod='payzen'}<br />
            {/if}
            <b>{l s='Count' mod='payzen'} : </b>{l s='Total number of payments.' mod='payzen'}<br />
            <b>{l s='Period' mod='payzen'} : </b>{l s='Delay (in days) between payments.' mod='payzen'}<br />
            <b>{l s='1st payment' mod='payzen'} : </b>{l s='Amount of first payment, in percentage of total amount. If empty, all payments will have the same amount.' mod='payzen'}<br />
            <b>{l s='Do not forget to clik on « Save » button to save your modifications.' mod='payzen'}</b>
          </p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>
    {/if}

    {if $payzen_plugin_features['choozeo']}
    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='CHOOZEO PAYMENT' mod='payzen'}</a>
    </h4>
    <div>
      <fieldset>
        <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_CHOOZEO_ENABLED">{l s='Activation' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_CHOOZEO_ENABLED" name="PAYZEN_CHOOZEO_ENABLED">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_CHOOZEO_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enables / disables %s payment.' sprintf='Choozeo' mod='payzen'}</p>
        </div>

        <label>{l s='Payment method title' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
            languages=$prestashop_languages
            current_lang=$prestashop_lang
            input_name="PAYZEN_CHOOZEO_TITLE"
            input_value=$PAYZEN_CHOOZEO_TITLE
            style="width: 330px;"
          }
          <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='AMOUNT RESTRICTIONS' mod='payzen'}</legend>

        <label>{l s='Customer group amount restriction' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_CHOOZEO_AMOUNTS"
            input_value=$PAYZEN_CHOOZEO_AMOUNTS
          }
          <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT PAGE' mod='payzen'}</legend>

        <label for="PAYZEN_CHOOZEO_DELAY">{l s='Capture delay' mod='payzen'}</label>
        <div class="margin-form">
          <input id="PAYZEN_CHOOZEO_DELAY" name="PAYZEN_CHOOZEO_DELAY" value="{$PAYZEN_CHOOZEO_DELAY|escape:'html':'UTF-8'}" type="text">
          <p>{l s='The number of days before the bank capture. Enter value only if different from « Base settings ».' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT OPTIONS' mod='payzen'}</legend>

        <label>{l s='Payment options' mod='payzen'}</label>
        <div class="margin-form">
         <table class="table" cellpadding="10" cellspacing="0">
            <thead>
              <tr>
                <th>{l s='Label' mod='payzen'}</th>
                <th>{l s='Min amount' mod='payzen'}</th>
                <th>{l s='Max amount' mod='payzen'}</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td>Choozeo 3X CB</td>
                <td>
                  <input name="PAYZEN_CHOOZEO_OPTIONS[EPNF_3X][min_amount]"
                      value="{if isset($PAYZEN_CHOOZEO_OPTIONS['EPNF_3X'])}{$PAYZEN_CHOOZEO_OPTIONS['EPNF_3X']['min_amount']|escape:'html':'UTF-8'}{/if}"
                      style="width: 200px;"
                      type="text">
                </td>
                <td>
                  <input name="PAYZEN_CHOOZEO_OPTIONS[EPNF_3X][max_amount]"
                      value="{if isset($PAYZEN_CHOOZEO_OPTIONS['EPNF_3X'])}{$PAYZEN_CHOOZEO_OPTIONS['EPNF_3X']['max_amount']|escape:'html':'UTF-8'}{/if}"
                      style="width: 200px;"
                      type="text">
                </td>
              </tr>

              <tr>
                <td>Choozeo 4X CB</td>
                <td>
                  <input name="PAYZEN_CHOOZEO_OPTIONS[EPNF_4X][min_amount]"
                      value="{if isset($PAYZEN_CHOOZEO_OPTIONS['EPNF_4X'])}{$PAYZEN_CHOOZEO_OPTIONS['EPNF_4X']['min_amount']|escape:'html':'UTF-8'}{/if}"
                      style="width: 200px;"
                      type="text">
                </td>
                <td>
                  <input name="PAYZEN_CHOOZEO_OPTIONS[EPNF_4X][max_amount]"
                      value="{if isset($PAYZEN_CHOOZEO_OPTIONS['EPNF_4X'])}{$PAYZEN_CHOOZEO_OPTIONS['EPNF_4X']['max_amount']|escape:'html':'UTF-8'}{/if}"
                      style="width: 200px;"
                      type="text">
                </td>
              </tr>
            </tbody>
          </table>
          <p>{l s='Define amount restriction for each card.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>
    {/if}

    {if $payzen_plugin_features['oney']}
    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='FACILYPAY ONEY PAYMENT' mod='payzen'}</a>
    </h4>
    <div>
      <fieldset>
        <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_ONEY_ENABLED">{l s='Activation' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_ONEY_ENABLED" name="PAYZEN_ONEY_ENABLED">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ONEY_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enables / disables %s payment.' sprintf='FacilyPay Oney' mod='payzen'}</p>
        </div>

        <label>{l s='Payment method title' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
            languages=$prestashop_languages
            current_lang=$prestashop_lang
            input_name="PAYZEN_ONEY_TITLE"
            input_value=$PAYZEN_ONEY_TITLE
            style="width: 330px;"
          }
          <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='AMOUNT RESTRICTIONS' mod='payzen'}</legend>

        <label>{l s='Customer group amount restriction' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_ONEY_AMOUNTS"
            input_value=$PAYZEN_ONEY_AMOUNTS
          }
          <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT PAGE' mod='payzen'}</legend>

        <label for="PAYZEN_ONEY_DELAY">{l s='Capture delay' mod='payzen'}</label>
        <div class="margin-form">
          <input id="PAYZEN_ONEY_DELAY" name="PAYZEN_ONEY_DELAY" value="{$PAYZEN_ONEY_DELAY|escape:'html':'UTF-8'}" type="text">
          <p>{l s='The number of days before the bank capture. Enter value only if different from « Base settings ».' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_ONEY_VALIDATION">{l s='Validation mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_ONEY_VALIDATION" name="PAYZEN_ONEY_VALIDATION">
            <option value="-1"{if $PAYZEN_ONEY_VALIDATION === '-1'} selected="selected"{/if}>{l s='Base settings configuration' mod='payzen'}</option>
            {foreach from=$payzen_validation_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ONEY_VALIDATION === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='If manual is selected, you will have to confirm payments manually in your bank Back Office.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_ONEY_ENABLE_OPTIONS">{l s='Enable options selection' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_ONEY_ENABLE_OPTIONS" name="PAYZEN_ONEY_ENABLE_OPTIONS" onchange="javascript: payzenOneyEnableOptionsChanged();">
            {foreach from=$payzen_yes_no_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ONEY_ENABLE_OPTIONS === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enable payment options selection on merchant site.' mod='payzen'}</p>
        </div>

        <section id="payzen_oney_options_settings">
          <label>{l s='Payment options' mod='payzen'}</label>
          <div class="margin-form">
            <script type="text/html" id="payzen_oney_row_option">
              {include file="./row_oney_option.tpl"
                languages=$prestashop_languages
                current_lang=$prestashop_lang
                key="PAYZEN_ONEY_KEY"
                option=$payzen_default_oney_option
              }
            </script>

            <button type="button" id="payzen_oney_options_btn"{if !empty($PAYZEN_ONEY_OPTIONS)} style="display: none;"{/if} onclick="javascript: payzenAddOneyOption(true, '{l s='Delete' mod='payzen'}');">{l s='Add' mod='payzen'}</button>

            <table id="payzen_oney_options_table"{if empty($PAYZEN_ONEY_OPTIONS)} style="display: none;"{/if} class="table" cellpadding="10" cellspacing="0">
            <thead>
              <tr>
                <th style="font-size: 10px;">{l s='Label' mod='payzen'}</th>
                <th style="font-size: 10px;">{l s='Code' mod='payzen'}</th>
                <th style="font-size: 10px;">{l s='Min amount' mod='payzen'}</th>
                <th style="font-size: 10px;">{l s='Max amount' mod='payzen'}</th>
                <th style="font-size: 10px;">{l s='Count' mod='payzen'}</th>
                <th style="font-size: 10px;">{l s='Rate' mod='payzen'}</th>
                <th style="font-size: 10px;"></th>
              </tr>
            </thead>

            <tbody>
              {foreach from=$PAYZEN_ONEY_OPTIONS key="key" item="option"}
                {include file="./row_oney_option.tpl"
                  languages=$prestashop_languages
                  current_lang=$prestashop_lang
                  key=$key
                  option=$option
                }
              {/foreach}

              <tr id="payzen_oney_option_add">
                <td colspan="6"></td>
                <td>
                  <button type="button" onclick="javascript: payzenAddOneyOption(false, '{l s='Delete' mod='payzen'}');">{l s='Add' mod='payzen'}</button>
                </td>
              </tr>
            </tbody>
            </table>
            <p>
              {l s='Click on « Add » button to configure one or more payment options.' mod='payzen'}<br />
              <b>{l s='Label' mod='payzen'} : </b>{l s='The option label to display on the frontend.' mod='payzen'}<br />
              <b>{l s='Code' mod='payzen'} : </b>{l s='The option code as defined in your FacilyPay Oney contract.' mod='payzen'}<br />
              <b>{l s='Min amount' mod='payzen'} : </b>{l s='Minimum amount to enable the payment option.' mod='payzen'}<br />
              <b>{l s='Max amount' mod='payzen'} : </b>{l s='Maximum amount to enable the payment option.' mod='payzen'}<br />
              <b>{l s='Count' mod='payzen'} : </b>{l s='Total number of payments.' mod='payzen'}<br />
              <b>{l s='Rate' mod='payzen'} : </b>{l s='The interest rate in percentage.' mod='payzen'}<br />
              <b>{l s='Do not forget to clik on « Save » button to save your modifications.' mod='payzen'}</b>
            </p>
          </div>
        </section>

        <script type="text/javascript">
          payzenOneyEnableOptionsChanged();
        </script>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>
    {/if}

    {if $payzen_plugin_features['fullcb']}
    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='FULLCB PAYMENT' mod='payzen'}</a>
    </h4>
    <div>
      <fieldset>
        <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_FULLCB_ENABLED">{l s='Activation' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_FULLCB_ENABLED" name="PAYZEN_FULLCB_ENABLED">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_FULLCB_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enables / disables %s payment.' sprintf='FullCB' mod='payzen'}</p>
        </div>

        <label>{l s='Payment method title' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
            languages=$prestashop_languages
            current_lang=$prestashop_lang
            input_name="PAYZEN_FULLCB_TITLE"
            input_value=$PAYZEN_FULLCB_TITLE
            style="width: 330px;"
          }
          <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='AMOUNT RESTRICTIONS' mod='payzen'}</legend>

        <label>{l s='Customer group amount restriction' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_FULLCB_AMOUNTS"
            input_value=$PAYZEN_FULLCB_AMOUNTS
          }
          <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_FULLCB_ENABLE_OPTS">{l s='Enable options selection' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_FULLCB_ENABLE_OPTS" name="PAYZEN_FULLCB_ENABLE_OPTS" onchange="javascript: payzenFullcbEnableOptionsChanged();">
            {foreach from=$payzen_yes_no_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_FULLCB_ENABLE_OPTS === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enable payment options selection on merchant site.' mod='payzen'}</p>
        </div>

        <section id="payzen_fullcb_options_settings">
          <label>{l s='Payment options' mod='payzen'}</label>
          <div class="margin-form">
            <table class="table" cellpadding="10" cellspacing="0">
              <thead>
                <tr>
                  <th style="font-size: 10px;">{l s='Label' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Min amount' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Max amount' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Rate' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Cap' mod='payzen'}</th>
                </tr>
              </thead>

              <tbody>
                {foreach from=$PAYZEN_FULLCB_OPTIONS key="key" item="option"}
                <tr>
                  <td>
                    {include file="./input_text_lang.tpl"
                      languages=$prestashop_languages
                      current_lang=$prestashop_lang
                      input_name="PAYZEN_FULLCB_OPTIONS[{$key|escape:'html':'UTF-8'}][label]"
                      field_id="PAYZEN_FULLCB_OPTIONS_{$key|escape:'html':'UTF-8'}_label"
                      input_value=$option['label']
                      style="width: 140px;"
                    }
                    <input name="PAYZEN_FULLCB_OPTIONS[{$key|escape:'html':'UTF-8'}][count]" value="{$option['count']|escape:'html':'UTF-8'}" type="text" style="display: none; width: 0px;">
                  </td>
                  <td>
                    <input name="PAYZEN_FULLCB_OPTIONS[{$key|escape:'html':'UTF-8'}][min_amount]"
                        value="{if isset($option)}{$option['min_amount']|escape:'html':'UTF-8'}{/if}"
                        style="width: 75px;"
                        type="text">
                  </td>
                  <td>
                    <input name="PAYZEN_FULLCB_OPTIONS[{$key|escape:'html':'UTF-8'}][max_amount]"
                        value="{if isset($option)}{$option['max_amount']|escape:'html':'UTF-8'}{/if}"
                        style="width: 75px;"
                        type="text">
                  </td>
                  <td>
                    <input name="PAYZEN_FULLCB_OPTIONS[{$key|escape:'html':'UTF-8'}][rate]"
                        value="{if isset($option)}{$option['rate']|escape:'html':'UTF-8'}{/if}"
                        style="width: 70px;"
                        type="text">
                  </td>
                  <td>
                    <input name="PAYZEN_FULLCB_OPTIONS[{$key|escape:'html':'UTF-8'}][cap]"
                        value="{if isset($option)}{$option['cap']|escape:'html':'UTF-8'}{/if}"
                        style="width: 70px;"
                        type="text">
                  </td>
                </tr>
                {/foreach}
              </tbody>
            </table>
            <p>
              {l s='Configure FullCB payment options.' mod='payzen'}<br />
              <b>{l s='Min amount' mod='payzen'} : </b>{l s='Minimum amount to enable the payment option.' mod='payzen'}<br />
              <b>{l s='Max amount' mod='payzen'} : </b>{l s='Maximum amount to enable the payment option.' mod='payzen'}<br />
              <b>{l s='Rate' mod='payzen'} : </b>{l s='The interest rate in percentage.' mod='payzen'}<br />
              <b>{l s='Cap' mod='payzen'} : </b>{l s='Maximum fees amount of payment option.' mod='payzen'}<br />
              <b>{l s='Do not forget to clik on « Save » button to save your modifications.' mod='payzen'}</b>
            </p>
          </div>
        </section>

        <script type="text/javascript">
          payzenFullcbEnableOptionsChanged();
        </script>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>
    {/if}

    {if $payzen_plugin_features['ancv']}
    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='ANCV PAYMENT' mod='payzen'}</a>
    </h4>
    <div>
      <fieldset>
        <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_ANCV_ENABLED">{l s='Activation' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_ANCV_ENABLED" name="PAYZEN_ANCV_ENABLED">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ANCV_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enables / disables %s payment.' sprintf='ANCV' mod='payzen'}</p>
        </div>

        <label>{l s='Payment method title' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
            languages=$prestashop_languages
            current_lang=$prestashop_lang
            input_name="PAYZEN_ANCV_TITLE"
            input_value=$PAYZEN_ANCV_TITLE
            style="width: 330px;"
          }
          <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='AMOUNT RESTRICTIONS' mod='payzen'}</legend>

        <label>{l s='Customer group amount restriction' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_ANCV_AMOUNTS"
            input_value=$PAYZEN_ANCV_AMOUNTS
          }
          <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT PAGE' mod='payzen'}</legend>

        <label for="PAYZEN_ANCV_DELAY">{l s='Capture delay' mod='payzen'}</label>
        <div class="margin-form">
          <input id="PAYZEN_ANCV_DELAY" name="PAYZEN_ANCV_DELAY" value="{$PAYZEN_ANCV_DELAY|escape:'html':'UTF-8'}" type="text">
          <p>{l s='The number of days before the bank capture. Enter value only if different from « Base settings ».' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_ANCV_VALIDATION">{l s='Validation mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_ANCV_VALIDATION" name="PAYZEN_ANCV_VALIDATION">
            <option value="-1"{if $PAYZEN_ANCV_VALIDATION === '-1'} selected="selected"{/if}>{l s='Base settings configuration' mod='payzen'}</option>
            {foreach from=$payzen_validation_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ANCV_VALIDATION === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='If manual is selected, you will have to confirm payments manually in your bank Back Office.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>
    {/if}

    {if $payzen_plugin_features['sepa']}
    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='SEPA PAYMENT' mod='payzen'}</a>
    </h4>
    <div>
      <fieldset>
        <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_SEPA_ENABLED">{l s='Activation' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_SEPA_ENABLED" name="PAYZEN_SEPA_ENABLED">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_SEPA_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enables / disables %s payment.' sprintf='SEPA' mod='payzen'}</p>
        </div>

        <label>{l s='Payment method title' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
            languages=$prestashop_languages
            current_lang=$prestashop_lang
            input_name="PAYZEN_SEPA_TITLE"
            input_value=$PAYZEN_SEPA_TITLE
            style="width: 330px;"
          }
          <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='AMOUNT RESTRICTIONS' mod='payzen'}</legend>

        <label>{l s='Customer group amount restriction' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_SEPA_AMOUNTS"
            input_value=$PAYZEN_SEPA_AMOUNTS
          }
          <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT PAGE' mod='payzen'}</legend>

        <label for="PAYZEN_SEPA_DELAY">{l s='Capture delay' mod='payzen'}</label>
        <div class="margin-form">
          <input id="PAYZEN_SEPA_DELAY" name="PAYZEN_SEPA_DELAY" value="{$PAYZEN_SEPA_DELAY|escape:'html':'UTF-8'}" type="text">
          <p>{l s='The number of days before the bank capture. Enter value only if different from « Base settings ».' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_SEPA_VALIDATION">{l s='Validation mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_SEPA_VALIDATION" name="PAYZEN_SEPA_VALIDATION">
            <option value="-1"{if $PAYZEN_SEPA_VALIDATION === '-1'} selected="selected"{/if}>{l s='Base settings configuration' mod='payzen'}</option>
            {foreach from=$payzen_validation_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_SEPA_VALIDATION === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='If manual is selected, you will have to confirm payments manually in your bank Back Office.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>
    {/if}

    {if $payzen_plugin_features['paypal']}
    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='PAYPAL PAYMENT' mod='payzen'}</a>
    </h4>
    <div>
      <fieldset>
        <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_PAYPAL_ENABLED">{l s='Activation' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_PAYPAL_ENABLED" name="PAYZEN_PAYPAL_ENABLED">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_PAYPAL_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enables / disables %s payment.' sprintf='PayPal' mod='payzen'}</p>
        </div>

        <label>{l s='Payment method title' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
            languages=$prestashop_languages
            current_lang=$prestashop_lang
            input_name="PAYZEN_PAYPAL_TITLE"
            input_value=$PAYZEN_PAYPAL_TITLE
            style="width: 330px;"
          }
          <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='AMOUNT RESTRICTIONS' mod='payzen'}</legend>

        <label>{l s='Customer group amount restriction' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_PAYPAL_AMOUNTS"
            input_value=$PAYZEN_PAYPAL_AMOUNTS
          }
          <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT PAGE' mod='payzen'}</legend>

        <label for="PAYZEN_PAYPAL_DELAY">{l s='Capture delay' mod='payzen'}</label>
        <div class="margin-form">
          <input id="PAYZEN_PAYPAL_DELAY" name="PAYZEN_PAYPAL_DELAY" value="{$PAYZEN_PAYPAL_DELAY|escape:'html':'UTF-8'}" type="text">
          <p>{l s='The number of days before the bank capture. Enter value only if different from « Base settings ».' mod='payzen'}</p>
        </div>

        <label for="PAYZEN_PAYPAL_VALIDATION">{l s='Validation mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_PAYPAL_VALIDATION" name="PAYZEN_PAYPAL_VALIDATION">
            <option value="-1"{if $PAYZEN_PAYPAL_VALIDATION === '-1'} selected="selected"{/if}>{l s='Base settings configuration' mod='payzen'}</option>
            {foreach from=$payzen_validation_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_PAYPAL_VALIDATION === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='If manual is selected, you will have to confirm payments manually in your bank Back Office.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>
    {/if}

    {if $payzen_plugin_features['sofort']}
    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='SOFORT BANKING PAYMENT' mod='payzen'}</a>
    </h4>
    <div>
      <fieldset>
        <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_SOFORT_ENABLED">{l s='Activation' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_SOFORT_ENABLED" name="PAYZEN_SOFORT_ENABLED">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_SOFORT_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enables / disables %s payment.' sprintf='SOFORT Banking' mod='payzen'}</p>
        </div>

        <label>{l s='Payment method title' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
            languages=$prestashop_languages
            current_lang=$prestashop_lang
            input_name="PAYZEN_SOFORT_TITLE"
            input_value=$PAYZEN_SOFORT_TITLE
            style="width: 330px;"
          }
          <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='AMOUNT RESTRICTIONS' mod='payzen'}</legend>

        <label>{l s='Customer group amount restriction' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_SOFORT_AMOUNTS"
            input_value=$PAYZEN_SOFORT_AMOUNTS
          }
          <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>
    {/if}
   </div>

  {if version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
    <div class="clear" style="width: 100%;">
      <input type="submit" class="button" name="payzen_submit_admin_form" value="{l s='Save' mod='payzen'}" style="float: right;">
    </div>
  {else}
    <div class="panel-footer" style="width: 100%;">
      <button type="submit" value="1" name="payzen_submit_admin_form" class="btn btn-default pull-right" style="float: right !important;">
        <i class="process-icon-save"></i>
        {l s='Save' mod='payzen'}
      </button>
    </div>
  {/if}
</form>

<br />
<br />
