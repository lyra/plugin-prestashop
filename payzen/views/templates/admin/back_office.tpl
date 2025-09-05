{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
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

    {if $payzen_plugin_features['support']}
      $('contact-support').on('sendmail', function(e){
        $.ajax({
          type: 'POST',
          url: "{$payzen_request_uri}",
          data: e.originalEvent.detail,
          success: function(res) {
            location.reload();
          },
          dataType: 'html'
        });
      });
    {/if}
  });
</script>

<script type="text/javascript">
  function payzenCardEntryChanged() {
    var cardDataMode = $('select#PAYZEN_STD_CARD_DATA_MODE option:selected').val();

    switch (cardDataMode) {
      case '7':
      case '8':
      case '9':
       $('#PAYZEN_REST_SETTINGS').show();
       $('#PAYZEN_STD_SMARTFORM_CUSTOMIZATION_SETTINGS').show();
       payzenOneClickMenuDisplay();
       toggleEmbeddedCheckbox(true);
       break;
        $('#PAYZEN_REST_SETTINGS').show();
        $('#PAYZEN_STD_SMARTFORM_CUSTOMIZATION_SETTINGS').show();
        $('#PAYZEN_STD_CANCEL_IFRAME_MENU').hide();
        payzenOneClickMenuDisplay();
        toggleEmbeddedCheckbox(true);
        break;
      default:
        $('#PAYZEN_REST_SETTINGS').hide();
        $('#PAYZEN_STD_USE_WALLET_MENU').hide();
        toggleEmbeddedCheckbox(false);
    }
  }

  function toggleEmbeddedCheckbox(show) {
    if (show) {
      $('.PAYZEN_OTHER_PAYMENT_MEANS_EMBEDDED').show();
      $('input').filter(function(){ return this.id.match(/PAYZEN_OTHER_PAYMENT_MEANS_\d*_embedded/); }).parent().show();
    } else {
      $('.PAYZEN_OTHER_PAYMENT_MEANS_EMBEDDED').hide();
      $('input').filter(function(){ return this.id.match(/PAYZEN_OTHER_PAYMENT_MEANS_\d*_embedded/); }).parent().hide();
      $('select').filter(function(){ return this.id.match(/PAYZEN_OTHER_PAYMENT_MEANS_\d*_validation/); }).prop("disabled", false);
      $('input').filter(function(){ return this.id.match(/PAYZEN_OTHER_PAYMENT_MEANS_\d*_capture/); }).prop("disabled", false);
    }
  }
</script>

<script type="text/javascript">
  function onEmbeddedCheckboxChange(checkbox) {
    var embeddedMode = checkbox.checked;
    var key = checkbox.className;
    $("#PAYZEN_OTHER_PAYMENT_MEANS_" + key + "_capture").prop("disabled", embeddedMode);
    $("#PAYZEN_OTHER_PAYMENT_MEANS_" + key + "_validation").prop("disabled", embeddedMode);
  }
</script>

<form method="POST" action="{$payzen_request_uri|escape:'html':'UTF-8'}" class="defaultForm form-horizontal">
  <div style="width: 100%;">
    <fieldset>
      <legend>
        <img style="width: 20px; vertical-align: middle;" src="{$payzen_logo}">PayZen
      </legend>

      <div style="padding: 5px;">{l s='Developed by' mod='payzen'} <b><a href="https://www.lyra.com/" target="_blank">Lyra Network</a></b></div>
      <div style="padding: 5px;">{l s='Contact us' mod='payzen'} <span style="display: inline-table;"><b>{$payzen_formatted_support_email|unescape:'html':'UTF-8'}</b></span></div>
      <div style="padding: 5px;">{l s='Module version' mod='payzen'} <b>{if $smarty.const._PS_HOST_MODE_}Cloud{/if}{$payzen_plugin_version|escape:'html':'UTF-8'}</b></div>
      <div style="padding: 5px;">{l s='Gateway version' mod='payzen'} <b>{$payzen_gateway_version|escape:'html':'UTF-8'}</b></div>

      {if !empty($payzen_doc_files)}
        <div style="padding: 5px;"><span style="color: red; font-weight: bold; text-transform: uppercase;">{l s='Click to view the module configuration documentation :' mod='payzen'}</span>
        {foreach from=$payzen_doc_files key="lang" item="url"}
          <a style="margin-left: 10px; font-weight: bold; text-transform: uppercase;" href="{$url|escape:'html':'UTF-8'}" target="_blank">{$lang|escape:'html':'UTF-8'}</a>
        {/foreach}
        </div>
      {/if}

      {if $payzen_plugin_features['support']}
        <div style="padding: 5px;"><contact-support
          shop-id="{$PAYZEN_SITE_ID|escape:'html':'UTF-8'}"
          context-mode="{$PAYZEN_MODE|escape:'html':'UTF-8'}"
          sign-algo="{$PAYZEN_SIGN_ALGO|escape:'html':'UTF-8'}"
          contrib="{$payzen_contrib|escape:'html':'UTF-8'}"
          integration-mode="{$payzen_card_data_entry_modes[$PAYZEN_STD_CARD_DATA_MODE]|escape:'html':'UTF-8'}"
          plugins="{$payzen_installed_modules|escape:'html':'UTF-8'}"
          title=""
          first-name="{$payzen_employee->firstname|escape:'html':'UTF-8'}"
          last-name="{$payzen_employee->lastname|escape:'html':'UTF-8'}"
          from-email="{$payzen_employee->email|escape:'html':'UTF-8'}"
          to-email="{$payzen_support_email|escape:'html':'UTF-8'}"
          cc-emails=""
          phone-number=""
          language="{$prestashop_lang.iso_code|escape:'html':'UTF-8'}">
        </contact-support></div>
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
          <p>{l s='Enable / disable module logs.' mod='payzen'}</p>
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
          <label for="PAYZEN_KEY_TEST">{l s='Key in test mode' mod='payzen'}</label>
          <div class="margin-form">
            <input type="text" id="PAYZEN_KEY_TEST" name="PAYZEN_KEY_TEST" value="{$PAYZEN_KEY_TEST|escape:'html':'UTF-8'}" autocomplete="off">
            <p>{l s='Key provided by your bank for test mode (available in your store Back Office).' mod='payzen'}</p>
          </div>
        {/if}

        <label for="PAYZEN_KEY_PROD">{l s='Key in production mode' mod='payzen'}</label>
        <div class="margin-form">
          <input type="text" id="PAYZEN_KEY_PROD" name="PAYZEN_KEY_PROD" value="{$PAYZEN_KEY_PROD|escape:'html':'UTF-8'}" autocomplete="off">
          <p>{l s='Key provided by your bank (available in your store Back Office after enabling production mode).' mod='payzen'}</p>
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
        <legend onclick="javascript: payzenAdditionalOptionsToggle(this);" style="cursor: pointer;">
          <span class="ui-icon ui-icon-triangle-1-e" style="display: inline-block; vertical-align: middle;"></span>
          {l s='REST API KEYS' mod='payzen'}
        </legend>

        <p style="font-size: .85em; color: #7F7F7F;">
         {l s='REST API keys are available in your store Back Office (menu: Settings > Shops > REST API keys).' mod='payzen'}
        </p>

        <section style="display: none; padding-top: 15px;">
          <p style="font-size: .85em; color: #7F7F7F;">
           {l s='Configure this section if you are using order operations from Prestashop Back Office or if you are using embedded payment fields modes.' mod='payzen'}
          </p>
          <label for="PAYZEN_PRIVKEY_TEST">{l s='Test password' mod='payzen'}</label>
          <div class="margin-form">
            <input type="password" id="PAYZEN_PRIVKEY_TEST" name="PAYZEN_PRIVKEY_TEST" value="{$PAYZEN_PRIVKEY_TEST|escape:'html':'UTF-8'}" style="width: 470px;" autocomplete="off" />
          </div>
          <p></p>

          <label for="PAYZEN_PRIVKEY_PROD">{l s='Production password' mod='payzen'}</label>
          <div style="border-bottom: 5px;" class="margin-form">
            <input type="password" id="PAYZEN_PRIVKEY_PROD" name="PAYZEN_PRIVKEY_PROD" value="{$PAYZEN_PRIVKEY_PROD|escape:'html':'UTF-8'}" style="width: 470px;" autocomplete="off">
          </div>
          <p></p>

          <label for="PAYZEN_REST_SERVER_URL">{l s='REST API server URL' mod='payzen'}</label>
          <div class="margin-form">
            <input type="text" id="PAYZEN_REST_SERVER_URL" name="PAYZEN_REST_SERVER_URL" value="{$PAYZEN_REST_SERVER_URL|escape:'html':'UTF-8'}" style="width: 470px;" autocomplete="off">
          </div>
          <p></p>

          <p style="font-size: .85em; color: #7F7F7F;">
           {l s='Configure this section only if you are using embedded payment fields modes.' mod='payzen'}
          </p>
          <p></p>

          <label for="PAYZEN_PUBKEY_TEST">{l s='Public test key' mod='payzen'}</label>
          <div class="margin-form">
            <input type="text" id="PAYZEN_PUBKEY_TEST" name="PAYZEN_PUBKEY_TEST" value="{$PAYZEN_PUBKEY_TEST|escape:'html':'UTF-8'}" style="width: 470px;" autocomplete="off">
          </div>
          <p></p>

          <label for="PAYZEN_PUBKEY_PROD">{l s='Public production key' mod='payzen'}</label>
          <div class="margin-form">
            <input type="text" id="PAYZEN_PUBKEY_PROD" name="PAYZEN_PUBKEY_PROD" value="{$PAYZEN_PUBKEY_PROD|escape:'html':'UTF-8'}" style="width: 470px;" autocomplete="off">
          </div>
          <p></p>

          <label for="PAYZEN_RETKEY_TEST">{l s='HMAC-SHA-256 test key' mod='payzen'}</label>
          <div class="margin-form">
            <input type="password" id="PAYZEN_RETKEY_TEST" name="PAYZEN_RETKEY_TEST" value="{$PAYZEN_RETKEY_TEST|escape:'html':'UTF-8'}" style="width: 470px;" autocomplete="off">
          </div>
          <p></p>

          <label for="PAYZEN_RETKEY_PROD">{l s='HMAC-SHA-256 production key' mod='payzen'}</label>
          <div class="margin-form">
            <input type="password" id="PAYZEN_RETKEY_PROD" name="PAYZEN_RETKEY_PROD" value="{$PAYZEN_RETKEY_PROD|escape:'html':'UTF-8'}" style="width: 470px;" autocomplete="off">
          </div>
          <p></p>

          <label>{l s='API REST Notification URL' mod='payzen'}</label>
          <div class="margin-form">
            {$PAYZEN_REST_NOTIFY_URL|escape:'html':'UTF-8'}<br />
            <p>
              <img src="{$smarty.const._MODULE_DIR_|escape:'html':'UTF-8'}payzen/views/img/warn.png">
              <span style="color: red; display: inline-block;">
                {l s='URL to copy into your bank Back Office > Settings > Notification rules.' mod='payzen'}<br />
                {l s='In multistore mode, notification URL is the same for all the stores.' mod='payzen'}
              </span>
            </p>
          </div>

          <label for="PAYZEN_REST_JS_CLIENT_URL">{l s='JavaScript client URL' mod='payzen'}</label>
          <div class="margin-form">
            <input type="text" id="PAYZEN_REST_JS_CLIENT_URL" name="PAYZEN_REST_JS_CLIENT_URL" value="{$PAYZEN_REST_JS_CLIENT_URL|escape:'html':'UTF-8'}" style="width: 470px;" autocomplete="off">
          </div>
        </section>
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

        <label>{l s='Theme configuration' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
              languages=$prestashop_languages
              current_lang=$prestashop_lang
              input_name="PAYZEN_THEME_CONFIG"
              input_value=$PAYZEN_THEME_CONFIG
              style="width: 470px;"
           }
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
        <legend>{l s='CUSTOM 3DS' mod='payzen'}</legend>

        <label for="PAYZEN_3DS_MIN_AMOUNT">{l s='Manage 3DS by customer group' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_3DS_MIN_AMOUNT"
            input_value=$PAYZEN_3DS_MIN_AMOUNT
            min_only=true
          }
          <p>{l s='Amount by customer group below which customer could be exempt from strong authentication. Needs subscription to « Selective 3DS1 » or « Frictionless 3DS2 » options. For more information, refer to the module documentation.' mod='payzen'}</p>
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

        <label for="PAYZEN_ENABLE_CUST_MSG">{l s='Customer service messages' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_ENABLE_CUST_MSG" name="PAYZEN_ENABLE_CUST_MSG">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ENABLE_CUST_MSG === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enable / disable the customer service messages generated at the end of the payment (concerns PrestaShop 1.7.1.2 and above).' mod='payzen'}</p>
         </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend onclick="javascript: payzenAdditionalOptionsToggle(this);" style="cursor: pointer;">
          <span class="ui-icon ui-icon-triangle-1-e" style="display: inline-block; vertical-align: middle;"></span>
          {l s='ADDITIONAL OPTIONS' mod='payzen'}
        </legend>
        <p style="font-size: .85em; color: #7F7F7F;">{l s='Configure this section if you use advanced risk assessment module or if you have an Oney contract.' mod='payzen'}</p>

        <section style="display: none; padding-top: 15px;">
          <label for="PAYZEN_SEND_CART_DETAIL">{l s='Send shopping cart details' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_SEND_CART_DETAIL" name="PAYZEN_SEND_CART_DETAIL">
              {foreach from=$payzen_enable_disable_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_SEND_CART_DETAIL === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='If you disable this option, the shopping cart details will not be sent to the gateway. Attention, in some cases, this option has to be enabled. For more information, refer to the module documentation.' mod='payzen'}</p>
          </div>

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
                        style="{if !isset($ship_option) || ($ship_option.speed != 'PRIORITY')} display: none;{/if}">
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
              <b>{l s='Type' mod='payzen'} : </b>{l s='The delivery type of shipping method.' mod='payzen'}<br />
              <b>{l s='Rapidity' mod='payzen'} : </b>{l s='Select the delivery rapidity.' mod='payzen'}<br />
              <b>{l s='Delay' mod='payzen'} : </b>{l s='Select the delivery delay if speed is « Priority ».' mod='payzen'}<br />
              <b>{l s='Address' mod='payzen'} : </b>{l s='Enter address if it is a reclaim in shop.' mod='payzen'}<br />
              <b>{l s='Entries marked with * are newly added and must be configured.' mod='payzen'}</b>
            </p>
          </div>
          {if $payzen_plugin_features['brazil']}
            <label for="PAYZEN_DOCUMENT">{l s='CPF/CNPJ field' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_DOCUMENT" name="PAYZEN_DOCUMENT">
                {foreach from=$payzen_extra_options key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_DOCUMENT === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <p>{l s='Custom field where CPF/CNPJ is saved on shop.' mod='payzen'}</p>
            </div>
            <label for="PAYZEN_NUMBER">{l s='Address number field' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_NUMBER" name="PAYZEN_NUMBER">
                {foreach from=$payzen_extra_options key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_NUMBER === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <p>{l s='Custom field where address number is saved on shop.' mod='payzen'}</p>
            </div>
            <label for="PAYZEN_NEIGHBORHOOD">{l s='Neighborhood field' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_NEIGHBORHOOD" name="PAYZEN_NEIGHBORHOOD">
                {foreach from=$payzen_extra_options key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_NEIGHBORHOOD === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <p>{l s='Custom field where address neighborhood is saved on shop.' mod='payzen'}</p>
            </div>
          {/if}
        </section>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>

    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='STANDARD PAYMENT' mod='payzen'}</a>
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
          <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
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
        <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_STD_COUNTRY">{l s='Restrict to some countries' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_STD_COUNTRY" name="PAYZEN_STD_COUNTRY" onchange="javascript: payzenCountriesRestrictMenuDisplay('PAYZEN_STD_COUNTRY')">
            {foreach from=$payzen_countries_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_COUNTRY === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Buyer\'s billing countries in which this payment method is available.' mod='payzen'}</p>
        </div>

        <div id="PAYZEN_STD_COUNTRY_MENU" {if $PAYZEN_STD_COUNTRY === '1'} style="display: none;"{/if}>
          <label for="PAYZEN_STD_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_STD_COUNTRY_LST" name="PAYZEN_STD_COUNTRY_LST[]" multiple="multiple" size="7">
              {foreach from=$payzen_countries_list['ps_countries'] key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_STD_COUNTRY_LST)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
          </div>
        </div>

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
              <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_STD_PAYMENT_CARDS)} selected="selected"{/if}>{if $key !== ""} {$key|escape:'html':'UTF-8'} - {/if}{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='The card type(s) that can be used for the payment. Select none to use gateway configuration.' mod='payzen'}</p>
        </div>

        </fieldset>
        <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='ADVANCED OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_STD_CARD_DATA_MODE">{l s='Payment data entry mode' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_STD_CARD_DATA_MODE" name="PAYZEN_STD_CARD_DATA_MODE" onchange="javascript: payzenCardEntryChanged();">
            {foreach from=$payzen_card_data_mode_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_CARD_DATA_MODE === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <input type="hidden" id="PAYZEN_STD_CARD_DATA_MODE_OLD" name="PAYZEN_STD_CARD_DATA_MODE_OLD" value="{$PAYZEN_STD_CARD_DATA_MODE|escape:'html':'UTF-8'}"/>
          <p>{l s='Select how the payment data will be entered. Attention, to use bank data acquisition on the merchant site, you must ensure that you have subscribed to this option with your bank.' mod='payzen'}</p>
        </div>

        <div id="PAYZEN_REST_SETTINGS" {if $PAYZEN_STD_CARD_DATA_MODE !== '7' && $PAYZEN_STD_CARD_DATA_MODE !== '8' && $PAYZEN_STD_CARD_DATA_MODE !== '9'} style="display: none;"{/if}>
          <p></p>
          <label for="PAYZEN_STD_REST_POPIN_MODE">{l s='Display in a pop-in' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_STD_REST_POPIN_MODE" name="PAYZEN_STD_REST_POPIN_MODE">
                {foreach from=$payzen_yes_no_options key="key" item="option"}
                    <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_REST_POPIN_MODE === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
            </select>
            <p>{l s='This option allows to display the embedded payment fields in a pop-in.' mod='payzen'}</p>
          </div>
          <p></p>
          <p></p>
          <label for="PAYZEN_STD_REST_THEME">{l s='Theme' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_STD_REST_THEME" name="PAYZEN_STD_REST_THEME">
                {foreach from=$payzen_std_rest_theme_options key="key" item="option"}
                    <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_REST_THEME === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
            </select>
            <p>{l s='Select a theme to use to display the embedded payment fields.' mod='payzen'}</p>
          </div>
          <p></p>
          <div id="PAYZEN_STD_SMARTFORM_CUSTOMIZATION_SETTINGS" {if $PAYZEN_STD_CARD_DATA_MODE !== '7' && $PAYZEN_STD_CARD_DATA_MODE !== '8' && $PAYZEN_STD_CARD_DATA_MODE !== '9'} style="display: none;"{/if}>
            <label for="PAYZEN_STD_SF_COMPACT_MODE">{l s='Compact mode' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_STD_SF_COMPACT_MODE" name="PAYZEN_STD_SF_COMPACT_MODE">
                  {foreach from=$payzen_yes_no_options key="key" item="option"}
                      <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_SF_COMPACT_MODE === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                  {/foreach}
              </select>
              <p>{l s='This option allows to display the embedded payment fields in a compact mode.' mod='payzen'}</p>
            </div>
            <p></p>
            <label for="PAYZEN_STD_SF_THRESHOLD">{l s='Payment means grouping threshold' mod='payzen'}</label>
            <div class="margin-form">
              <input type="text" id="PAYZEN_STD_SF_THRESHOLD" name="PAYZEN_STD_SF_THRESHOLD" value="{$PAYZEN_STD_SF_THRESHOLD|escape:'html':'UTF-8'}" style="width: 150px;" />
              <p>{l s='Number of means of payment from which they will be grouped.' mod='payzen'}</p>
            </div>
            <p></p>
            <label for="PAYZEN_STD_SF_DISPLAY_TITLE">{l s='Display title' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_STD_SF_DISPLAY_TITLE" name="PAYZEN_STD_SF_DISPLAY_TITLE">
                  {foreach from=$payzen_yes_no_options key="key" item="option"}
                      <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_SF_DISPLAY_TITLE === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                  {/foreach}
              </select>
              <p>{l s='Display payment method title when it is the only one activated.' mod='payzen'}</p>
            </div>
            <p></p>
          </div>
          <p></p>
          <label for="PAYZEN_STD_REST_PLACEHLDR">{l s='Custom fields placeholders' mod='payzen'}</label>
          <div class="margin-form">
            <table class="table" cellspacing="0" cellpadding="10">
              <tbody>
                <tr>
                  <td>{l s='Card number' mod='payzen'}</td>
                  <td>
                    {include file="./input_text_lang.tpl"
                      languages=$prestashop_languages
                      current_lang=$prestashop_lang
                      input_name="PAYZEN_STD_REST_PLACEHLDR[pan]"
                      field_id="PAYZEN_STD_REST_PLACEHLDR_pan"
                      input_value=$PAYZEN_STD_REST_PLACEHLDR.pan
                      style="width: 150px;"
                    }
                  </td>
                </tr>

                <tr>
                  <td>{l s='Expiry date' mod='payzen'}</td>
                  <td>
                    {include file="./input_text_lang.tpl"
                      languages=$prestashop_languages
                      current_lang=$prestashop_lang
                      input_name="PAYZEN_STD_REST_PLACEHLDR[expiry]"
                      field_id="PAYZEN_STD_REST_PLACEHLDR_expiry"
                      input_value=$PAYZEN_STD_REST_PLACEHLDR.expiry
                      style="width: 150px;"
                    }
                  </td>
                </tr>

                <tr>
                  <td>{l s='CVV' mod='payzen'}</td>
                  <td>
                    {include file="./input_text_lang.tpl"
                      languages=$prestashop_languages
                      current_lang=$prestashop_lang
                      input_name="PAYZEN_STD_REST_PLACEHLDR[cvv]"
                      field_id="PAYZEN_STD_REST_PLACEHLDR_cvv"
                      input_value=$PAYZEN_STD_REST_PLACEHLDR.cvv
                      style="width: 150px;"
                    }
                  </td>
                </tr>

              </tbody>
            </table>
            <p>{l s='Texts to use as placeholders for embedded payment fields.' mod='payzen'}</p>
          </div>
          <p></p>

          <label>{l s='Register card label' mod='payzen'}</label>
          <div class="margin-form">
            {include file="./input_text_lang.tpl"
              languages=$prestashop_languages
              current_lang=$prestashop_lang
              input_name="PAYZEN_STD_REST_LBL_REGIST"
              input_value=$PAYZEN_STD_REST_LBL_REGIST
              style="width: 330px;"
            }
            <p>{l s='Label displayed to invite buyers to register their card data.' mod='payzen'}</p>
          </div>
          <p></p>
          <label for="PAYZEN_STD_REST_ATTEMPTS">{l s='Payment attempts number for cards' mod='payzen'}</label>
          <div class="margin-form">
            <input type="text" id="PAYZEN_STD_REST_ATTEMPTS" name="PAYZEN_STD_REST_ATTEMPTS" value="{$PAYZEN_STD_REST_ATTEMPTS|escape:'html':'UTF-8'}" style="width: 150px;" />
            <p>{l s='Maximum number of payment by cards retries after a failed payment (between 0 and 2). If blank, the gateway default value is 2.' mod='payzen'}</p>
          </div>
          <p></p>
        </div>

        <div id="PAYZEN_STD_1_CLICK_PAYMENT_MENU">
          <label for="PAYZEN_STD_1_CLICK_PAYMENT">{l s='Payment by token' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_STD_1_CLICK_PAYMENT" name="PAYZEN_STD_1_CLICK_PAYMENT" onchange="javascript: payzenOneClickMenuDisplay()">
              {foreach from=$payzen_yes_no_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_1_CLICK_PAYMENT === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='This option allows to pay orders without re-entering bank data at each payment. The "payment by token" option should be enabled on your %s store to use this feature.' sprintf='PayZen' mod='payzen'}</p>
          </div>
        </div>

        <div id="PAYZEN_STD_USE_WALLET_MENU" {if ($PAYZEN_STD_CARD_DATA_MODE !== '7' && $PAYZEN_STD_CARD_DATA_MODE !== '8' && $PAYZEN_STD_CARD_DATA_MODE !== '9') || $PAYZEN_STD_1_CLICK_PAYMENT !== 'True'} style="display: none;"{/if}>
          <label for="PAYZEN_STD_USE_WALLET">{l s='Use buyer wallet to manage tokens' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_STD_USE_WALLET" name="PAYZEN_STD_USE_WALLET">
              {foreach from=$payzen_yes_no_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_USE_WALLET === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='A wallet allows a buyer to store multiple payment cards and choose which one to use at the time of purchase, without having to enter a card number. The buyer can request the deletion of a card stored in his wallet at any time.' mod='payzen'}</p>
          </div>
        </div>

        <div id="PAYZEN_STD_USE_PAYMENT_MEAN_AS_TITLE_MENU">
          <label for="PAYZEN_STD_USE_PAYMENT_MEAN_AS_TITLE">{l s='Display payment means as payment method' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_STD_USE_PAYMENT_MEAN_AS_TITLE" name="PAYZEN_STD_USE_PAYMENT_MEAN_AS_TITLE">
              {foreach from=$payzen_yes_no_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_USE_PAYMENT_MEAN_AS_TITLE === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='If you enable this option, the used means of payment will be set as the payment method in the order details in PrestaShop Back Office.' mod='payzen'}</p>
          </div>
        </div>

        {if version_compare($smarty.const._PS_VERSION_, '1.7', '>=')}
          <div id="PAYZEN_STD_SELECT_BY_DEFAULT_MENU">
            <label for="PAYZEN_STD_SELECT_BY_DEFAULT">{l s='Select by default' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_STD_SELECT_BY_DEFAULT" name="PAYZEN_STD_SELECT_BY_DEFAULT">
                {foreach from=$payzen_yes_no_options key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_STD_SELECT_BY_DEFAULT === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <p>{l s='If you enable this option, this payment method will be selected by default on the checkout page.' mod='payzen'}</p>
            </div>
          </div>
        {/if}
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
            <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
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
          <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

          <label for="PAYZEN_MULTI_COUNTRY">{l s='Restrict to some countries' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_MULTI_COUNTRY" name="PAYZEN_MULTI_COUNTRY" onchange="javascript: payzenCountriesRestrictMenuDisplay('PAYZEN_MULTI_COUNTRY')">
              {foreach from=$payzen_countries_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_MULTI_COUNTRY === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='Buyer\'s billing countries in which this payment method is available.' mod='payzen'}</p>
          </div>

          <div id="PAYZEN_MULTI_COUNTRY_MENU" {if $PAYZEN_MULTI_COUNTRY === '1'} style="display: none;"{/if}>
            <label for="PAYZEN_MULTI_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_MULTI_COUNTRY_LST" name="PAYZEN_MULTI_COUNTRY_LST[]" multiple="multiple" size="7">
                {foreach from=$payzen_countries_list['ps_countries'] key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_MULTI_COUNTRY_LST)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
            </div>
          </div>

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
                <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_MULTI_PAYMENT_CARDS)} selected="selected"{/if}>{if $key !== ""} {$key|escape:'html':'UTF-8'} - {/if}{$option|escape:'html':'UTF-8'}</option>
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
                  <th style="font-size: 10px;">{l s='1st installment' mod='payzen'}</th>
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
              <b>{l s='Count' mod='payzen'} : </b>{l s='Total number of installments.' mod='payzen'}<br />
              <b>{l s='Period' mod='payzen'} : </b>{l s='Delay (in days) between installments.' mod='payzen'}<br />
              <b>{l s='1st installment' mod='payzen'} : </b>{l s='Amount of first installment, in percentage of total amount. If empty, all installments will have the same amount.' mod='payzen'}<br />
              <b>{l s='Do not forget to click on « Save » button to save your modifications.' mod='payzen'}</b>
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
            <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
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
          <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

          {if isset ($payzen_countries_list['CHOOZEO'])}
            <label for="PAYZEN_CHOOZEO_COUNTRY">{l s='Restrict to some countries' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_CHOOZEO_COUNTRY" name="PAYZEN_CHOOZEO_COUNTRY" onchange="javascript: payzenCountriesRestrictMenuDisplay('PAYZEN_CHOOZEO_COUNTRY')">
                {foreach from=$payzen_countries_options key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_CHOOZEO_COUNTRY === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <p>{l s='Buyer\'s billing countries in which this payment method is available.' mod='payzen'}</p>
            </div>

            <div id="PAYZEN_CHOOZEO_COUNTRY_MENU" {if $PAYZEN_CHOOZEO_COUNTRY === '1'} style="display: none;"{/if}>
              <label for="PAYZEN_CHOOZEO_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
              <div class="margin-form">
                <select id="PAYZEN_CHOOZEO_COUNTRY_LST" name="PAYZEN_CHOOZEO_COUNTRY_LST[]" multiple="multiple" size="7">
                  {if isset ($payzen_countries_list['CHOOZEO'])}
                      {foreach from=$payzen_countries_list['CHOOZEO'] key="key" item="option"}
                          <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_CHOOZEO_COUNTRY_LST)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                      {/foreach}
                  {/if}
                </select>
              </div>
            </div>
          {else}
            <input type="hidden" name="PAYZEN_CHOOZEO_COUNTRY" value="1">
            <input type="hidden" name="PAYZEN_CHOOZEO_COUNTRY_LST[]" value ="">
            <p style="background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; font-size: 13px; margin: 0 0 20px; padding: 10px;">
                {l s='Payment method unavailable for the list of countries defined on your PrestaShop store.' mod='payzen'}
            </p>
          {/if}

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
                  <th>{l s='Activation' mod='payzen'}</th>
                  <th>{l s='Label' mod='payzen'}</th>
                  <th>{l s='Min amount' mod='payzen'}</th>
                  <th>{l s='Max amount' mod='payzen'}</th>
                </tr>
              </thead>

              <tbody>
                <tr>
                  <td>
                    <input name="PAYZEN_CHOOZEO_OPTIONS[EPNF_3X][enabled]"
                      style="width: 100%;"
                      type="checkbox"
                      value="True"
                      {if !isset($PAYZEN_CHOOZEO_OPTIONS.EPNF_3X.enabled) || ($PAYZEN_CHOOZEO_OPTIONS.EPNF_3X.enabled ==='True')}checked{/if}>
                  </td>
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
                  <td>
                    <input name="PAYZEN_CHOOZEO_OPTIONS[EPNF_4X][enabled]"
                      style="width: 100%;"
                      type="checkbox"
                      value="True"
                      {if !isset($PAYZEN_CHOOZEO_OPTIONS.EPNF_4X.enabled) || ($PAYZEN_CHOOZEO_OPTIONS.EPNF_4X.enabled ==='True')}checked{/if}>
                  </td>
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
        <a href="#">{l s='ONEY PAYMENT' mod='payzen'}</a>
      </h4>
      <div>
        <fieldset>
          <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

          <label for="PAYZEN_ONEY34_ENABLED">{l s='Activation' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_ONEY34_ENABLED" name="PAYZEN_ONEY34_ENABLED">
              {foreach from=$payzen_enable_disable_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ONEY34_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
          </div>

          <label>{l s='Payment method title' mod='payzen'}</label>
          <div class="margin-form">
            {include file="./input_text_lang.tpl"
              languages=$prestashop_languages
              current_lang=$prestashop_lang
              input_name="PAYZEN_ONEY34_TITLE"
              input_value=$PAYZEN_ONEY34_TITLE
              style="width: 330px;"
            }
            <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
          </div>
        </fieldset>
        <div class="clear">&nbsp;</div>

        <fieldset>
          <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

          {if isset ($payzen_countries_list['ONEY34'])}
            <label for="PAYZEN_ONEY34_COUNTRY">{l s='Restrict to some countries' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_ONEY34_COUNTRY" name="PAYZEN_ONEY34_COUNTRY" onchange="javascript: payzenCountriesRestrictMenuDisplay('PAYZEN_ONEY34_COUNTRY')">
                {foreach from=$payzen_countries_options key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ONEY34_COUNTRY === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <p>{l s='Buyer\'s billing countries in which this payment method is available.' mod='payzen'}</p>
            </div>

            <div id="PAYZEN_ONEY34_COUNTRY_MENU" {if $PAYZEN_ONEY34_COUNTRY === '1'} style="display: none;"{/if}>
              <label for="PAYZEN_ONEY34_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
              <div class="margin-form">
                <select id="PAYZEN_ONEY34_COUNTRY_LST" name="PAYZEN_ONEY34_COUNTRY_LST[]" multiple="multiple" size="7">
                  {if isset ($payzen_countries_list['ONEY34'])}
                      {foreach from=$payzen_countries_list['ONEY34'] key="key" item="option"}
                          <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_ONEY34_COUNTRY_LST)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                      {/foreach}
                  {/if}
                </select>
              </div>
            </div>
          {else}
            <input type="hidden" name="PAYZEN_ONEY34_COUNTRY" value="1">
            <input type="hidden" name="PAYZEN_ONEY34_COUNTRY_LST[]" value ="">
            <p style="background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; font-size: 13px; margin: 0 0 20px; padding: 10px;">
                {l s='Payment method unavailable for the list of countries defined on your PrestaShop store.' mod='payzen'}
            </p>
          {/if}

          <label>{l s='Customer group amount restriction' mod='payzen'}</label>
          <div class="margin-form">
            {include file="./table_amount_group.tpl"
              groups=$prestashop_groups
              input_name="PAYZEN_ONEY34_AMOUNTS"
              input_value=$PAYZEN_ONEY34_AMOUNTS
            }
            <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
          </div>
        </fieldset>
        <div class="clear">&nbsp;</div>

        <fieldset>
          <legend>{l s='PAYMENT PAGE' mod='payzen'}</legend>

          <label for="PAYZEN_ONEY34_DELAY">{l s='Capture delay' mod='payzen'}</label>
          <div class="margin-form">
            <input id="PAYZEN_ONEY34_DELAY" name="PAYZEN_ONEY34_DELAY" value="{$PAYZEN_ONEY34_DELAY|escape:'html':'UTF-8'}" type="text">
            <p>{l s='The number of days before the bank capture. Enter value only if different from « Base settings ».' mod='payzen'}</p>
          </div>

          <label for="PAYZEN_ONEY34_VALIDATION">{l s='Validation mode' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_ONEY34_VALIDATION" name="PAYZEN_ONEY34_VALIDATION">
              <option value="-1"{if $PAYZEN_ONEY34_VALIDATION === '-1'} selected="selected"{/if}>{l s='Base settings configuration' mod='payzen'}</option>
              {foreach from=$payzen_validation_mode_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ONEY34_VALIDATION === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='If manual is selected, you will have to confirm payments manually in your bank Back Office.' mod='payzen'}</p>
          </div>
        </fieldset>
        <div class="clear">&nbsp;</div>

        <fieldset>
          <legend>{l s='PAYMENT OPTIONS' mod='payzen'}</legend>

          <label>{l s='Payment options' mod='payzen'}</label>
          <div class="margin-form">
            <script type="text/html" id="payzen_oney34_row_option">
              {include file="./row_oney_option.tpl"
                languages=$prestashop_languages
                current_lang=$prestashop_lang
                key="PAYZEN_ONEY34_KEY"
                option=$payzen_default_oney_option
                suffix='34'
              }
            </script>

            <button type="button" id="payzen_oney34_options_btn"{if !empty($PAYZEN_ONEY34_OPTIONS)} style="display: none;"{/if} onclick="javascript: payzenAddOneyOption(true, '34');">{l s='Add' mod='payzen'}</button>

            <table id="payzen_oney34_options_table"{if empty($PAYZEN_ONEY34_OPTIONS)} style="display: none;"{/if} class="table" cellpadding="10" cellspacing="0">
              <thead>
                <tr>
                  <th style="font-size: 10px;">{l s='Label' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Code' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Means of payment' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Min amount' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Max amount' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Count' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Rate' mod='payzen'}</th>
                  <th style="font-size: 10px;"></th>
                </tr>
              </thead>

              <tbody>
                {foreach from=$PAYZEN_ONEY34_OPTIONS key="key" item="option"}
                  {include file="./row_oney_option.tpl"
                    languages=$prestashop_languages
                    current_lang=$prestashop_lang
                    key=$key
                    option=$option
                    suffix='34'
                  }
                {/foreach}

                <tr id="payzen_oney34_option_add">
                  <td colspan="6"></td>
                  <td>
                    <button type="button" onclick="javascript: payzenAddOneyOption(false, '34');">{l s='Add' mod='payzen'}</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <p>
              {l s='Click on « Add » button to configure one or more payment options.' mod='payzen'}<br />
              <b>{l s='Label' mod='payzen'} : </b>{l s='The option label to display on the frontend (the %c and %r patterns will be respectively replaced by payments count and option rate).' mod='payzen'}<br />
              <b>{l s='Code' mod='payzen'} : </b>{l s='The option code as defined in your Oney contract.' mod='payzen'}<br />
              <b>{l s='Means of payment' mod='payzen'} : </b>{l s='Choose the means of payment you want to propose.' mod='payzen'}<br />
              <b>{l s='Min amount' mod='payzen'} : </b>{l s='Minimum amount to enable the payment option.' mod='payzen'}<br />
              <b>{l s='Max amount' mod='payzen'} : </b>{l s='Maximum amount to enable the payment option.' mod='payzen'}<br />
              <b>{l s='Count' mod='payzen'} : </b>{l s='Total number of installments.' mod='payzen'}<br />
              <b>{l s='Rate' mod='payzen'} : </b>{l s='The interest rate in percentage.' mod='payzen'}<br />
              <b>{l s='Do not forget to click on « Save » button to save your modifications.' mod='payzen'}</b>
            </p>
          </div>
        </fieldset>
        <div class="clear">&nbsp;</div>
      </div>
    {/if}

    {if $payzen_plugin_features['franfinance']}
      <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
        <a href="#">{l s='FRANFINANCE PAYMENT' mod='payzen'}</a>
      </h4>
      <div>
        <fieldset>
          <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

          <label for="PAYZEN_FFIN_ENABLED">{l s='Activation' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_FFIN_ENABLED" name="PAYZEN_FFIN_ENABLED">
              {foreach from=$payzen_enable_disable_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_FFIN_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
          </div>

          <label>{l s='Payment method title' mod='payzen'}</label>
          <div class="margin-form">
            {include file="./input_text_lang.tpl"
              languages=$prestashop_languages
              current_lang=$prestashop_lang
              input_name="PAYZEN_FFIN_TITLE"
              input_value=$PAYZEN_FFIN_TITLE
              style="width: 330px;"
            }
            <p>{l s='Method title to display on payment means page.' mod='payzen'}</p>
          </div>
        </fieldset>
        <div class="clear">&nbsp;</div>

        <fieldset>
          <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

          {if isset ($payzen_countries_list['FFIN'])}
            <label for="PAYZEN_FFIN_COUNTRY">{l s='Restrict to some countries' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_FFIN_COUNTRY" name="PAYZEN_FFIN_COUNTRY" onchange="javascript: payzenCountriesRestrictMenuDisplay('PAYZEN_FFIN_COUNTRY')">
                {foreach from=$payzen_countries_options key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_FFIN_COUNTRY === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <p>{l s='Buyer\'s billing countries in which this payment method is available.' mod='payzen'}</p>
            </div>

            <div id="PAYZEN_FFIN_COUNTRY_MENU" {if $PAYZEN_FFIN_COUNTRY === '1'} style="display: none;"{/if}>
              <label for="PAYZEN_FFIN_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
              <div class="margin-form">
                <select id="PAYZEN_FFIN_COUNTRY_LST" name="PAYZEN_FFIN_COUNTRY_LST[]" multiple="multiple" size="7">
                  {if isset ($payzen_countries_list['FFIN'])}
                      {foreach from=$payzen_countries_list['FFIN'] key="key" item="option"}
                          <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_FFIN_COUNTRY_LST)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                      {/foreach}
                  {/if}
                </select>
              </div>
            </div>
          {else}
            <input type="hidden" name="PAYZEN_FFIN_COUNTRY" value="1">
            <input type="hidden" name="PAYZEN_FFIN_COUNTRY_LST[]" value ="">
            <p style="background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; font-size: 13px; margin: 0 0 20px; padding: 10px;">
                {l s='Payment method unavailable for the list of countries defined on your PrestaShop store.' mod='payzen'}
            </p>
          {/if}

          <label>{l s='Customer group amount restriction' mod='payzen'}</label>
          <div class="margin-form">
            {include file="./table_amount_group.tpl"
              groups=$prestashop_groups
              input_name="PAYZEN_FFIN_AMOUNTS"
              input_value=$PAYZEN_FFIN_AMOUNTS
            }
            <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
          </div>
        </fieldset>
        <div class="clear">&nbsp;</div>

        <fieldset>
          <legend>{l s='PAYMENT OPTIONS' mod='payzen'}</legend>

          <label>{l s='Payment options' mod='payzen'}</label>
          <div class="margin-form">
            <script type="text/html" id="payzen_ffin_row_option">
              {include file="./row_franfinance_option.tpl"
                languages=$prestashop_languages
                current_lang=$prestashop_lang
                key="PAYZEN_FFIN_KEY"
                option=$payzen_default_franfinance_option
              }
            </script>

            <button type="button" id="payzen_ffin_options_btn"{if !empty($PAYZEN_FFIN_OPTIONS)} style="display: none;"{/if} onclick="javascript: payzenAddFranfinanceOption(true, '{l s='Delete' mod='payzen'}');">{l s='Add' mod='payzen'}</button>

            <table id="payzen_ffin_options_table"{if empty($PAYZEN_FFIN_OPTIONS)} style="display: none;"{/if} class="table" cellpadding="10" cellspacing="0">
              <thead>
                <tr>
                  <th style="font-size: 10px;">{l s='Label' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Count' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Fees' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Min amount' mod='payzen'}</th>
                  <th style="font-size: 10px;">{l s='Max amount' mod='payzen'}</th>
                  <th style="font-size: 10px;"></th>
                </tr>
              </thead>

              <tbody>
                {foreach from=$PAYZEN_FFIN_OPTIONS key="key" item="option"}
                  {include file="./row_franfinance_option.tpl"
                    languages=$prestashop_languages
                    current_lang=$prestashop_lang
                    key=$key
                    option=$option
                  }
                {/foreach}

                <tr id="payzen_ffin_option_add">
                  <td colspan="7"></td>
                  <td>
                    <button type="button" onclick="javascript: payzenAddFranfinanceOption(false, '{l s='Delete' mod='payzen'}');">{l s='Add' mod='payzen'}</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <p>
              {l s='Click on « Add » button to configure one or more payment options.' mod='payzen'}<br />
              <b>{l s='Label' mod='payzen'} : </b>{l s='The option label to display on the frontend (the %c pattern will be replaced by payments count).' mod='payzen'}<br />
              <b>{l s='Count' mod='payzen'} : </b>{l s='Total number of installments.' mod='payzen'}<br />
              <b>{l s='Fees' mod='payzen'} : </b>{l s='Enable or disables fees application.' mod='payzen'}<br />
              <b>{l s='Min amount' mod='payzen'} : </b>{l s='Minimum amount to enable the payment option.' mod='payzen'}<br />
              <b>{l s='Max amount' mod='payzen'} : </b>{l s='Maximum amount to enable the payment option.' mod='payzen'}<br />
              <b>{l s='Do not forget to click on « Save » button to save your modifications.' mod='payzen'}</b>
            </p>
          </div>
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
            <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
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
          <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

          <div id="PAYZEN_FULLCB_COUNTRY_MENU">
            <input type="hidden" name="PAYZEN_FULLCB_COUNTRY" value="1">
            <input type="hidden" name="PAYZEN_FULLCB_COUNTRY_LST[]" value ="FR">
            <label for="PAYZEN_FULLCB_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
            <div class="margin-form">
              <span style="font-size: 13px; padding-top: 5px; vertical-align: middle;"><b>{$payzen_countries_list['FULLCB']['FR']|escape:'html':'UTF-8'}</b></span>
            </div>
          </div>

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
                    <th style="font-size: 10px;">{l s='Activation' mod='payzen'}</th>
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
                      <input name="PAYZEN_FULLCB_OPTIONS[{$key|escape:'html':'UTF-8'}][enabled]"
                        style="width: 100%;"
                        type="checkbox"
                        value="True"
                        {if !isset($option.enabled) || ($option.enabled === 'True')}checked{/if}>
                    </td>
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
                <b>{l s='Activation' mod='payzen'} : </b>{l s='Enable / disable the payment option.' mod='payzen'}<br />
                <b>{l s='Min amount' mod='payzen'} : </b>{l s='Minimum amount to enable the payment option.' mod='payzen'}<br />
                <b>{l s='Max amount' mod='payzen'} : </b>{l s='Maximum amount to enable the payment option.' mod='payzen'}<br />
                <b>{l s='Rate' mod='payzen'} : </b>{l s='The interest rate in percentage.' mod='payzen'}<br />
                <b>{l s='Cap' mod='payzen'} : </b>{l s='Maximum fees amount of payment option.' mod='payzen'}<br />
                <b>{l s='Do not forget to click on « Save » button to save your modifications.' mod='payzen'}</b>
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
            <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
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
          <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

          <label for="PAYZEN_ANCV_COUNTRY">{l s='Restrict to some countries' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_ANCV_COUNTRY" name="PAYZEN_ANCV_COUNTRY" onchange="javascript: payzenCountriesRestrictMenuDisplay('PAYZEN_ANCV_COUNTRY')">
              {foreach from=$payzen_countries_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_ANCV_COUNTRY === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='Buyer\'s billing countries in which this payment method is available.' mod='payzen'}</p>
          </div>

          <div id="PAYZEN_ANCV_COUNTRY_MENU" {if $PAYZEN_ANCV_COUNTRY === '1'} style="display: none;"{/if}>
            <label for="PAYZEN_ANCV_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_ANCV_COUNTRY_LST" name="PAYZEN_ANCV_COUNTRY_LST[]" multiple="multiple" size="7">
                {foreach from=$payzen_countries_list['ps_countries'] key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_ANCV_COUNTRY_LST)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
            </div>
          </div>

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
            <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
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
          <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

          {if isset ($payzen_countries_list['SEPA'])}
            <label for="PAYZEN_SEPA_COUNTRY">{l s='Restrict to some countries' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_SEPA_COUNTRY" name="PAYZEN_SEPA_COUNTRY" onchange="javascript: payzenCountriesRestrictMenuDisplay('PAYZEN_SEPA_COUNTRY')">
                {foreach from=$payzen_countries_options key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_SEPA_COUNTRY === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <p>{l s='Buyer\'s billing countries in which this payment method is available.' mod='payzen'}</p>
            </div>

            <div id="PAYZEN_SEPA_COUNTRY_MENU" {if $PAYZEN_SEPA_COUNTRY === '1'} style="display: none;"{/if}>
              <label for="PAYZEN_SEPA_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
              <div class="margin-form">
                <select id="PAYZEN_SEPA_COUNTRY_LST" name="PAYZEN_SEPA_COUNTRY_LST[]" multiple="multiple" size="7">
                  {if isset ($payzen_countries_list['SEPA'])}
                      {foreach from=$payzen_countries_list['SEPA'] key="key" item="option"}
                          <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_SEPA_COUNTRY_LST)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                      {/foreach}
                  {/if}
                </select>
              </div>
            </div>
          {else}
            <input type="hidden" name="PAYZEN_SEPA_COUNTRY" value="1">
            <input type="hidden" name="PAYZEN_SEPA_COUNTRY_LST[]" value ="">
            <p style="background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; font-size: 13px; margin: 0 0 20px; padding: 10px;">
                {l s='Payment method unavailable for the list of countries defined on your PrestaShop store.' mod='payzen'}
            </p>
          {/if}

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

        <fieldset>
          <legend>{l s='PAYMENT OPTIONS' mod='payzen'}</legend>

          <label for="PAYZEN_SEPA_MANDATE_MODE">{l s='SEPA direct debit mode' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_SEPA_MANDATE_MODE" name="PAYZEN_SEPA_MANDATE_MODE" onchange="javascript: payzenSepa1clickPaymentMenuDisplay('PAYZEN_SEPA_MANDATE_MODE')">
              {foreach from=$payzen_sepa_mandate_mode_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_SEPA_MANDATE_MODE === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='Select SEPA direct debit mode. Attention, the two last choices require the payment by token option on %s.' sprintf='PayZen' mod='payzen'}</p>
          </div>

          <div id="PAYZEN_SEPA_1_CLICK_PAYMNT_MENU"  {if $PAYZEN_SEPA_MANDATE_MODE !== 'REGISTER_PAY'} style="display: none;"{/if}>
            <label for="PAYZEN_SEPA_1_CLICK_PAYMNT">{l s='1-Click payment' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_SEPA_1_CLICK_PAYMNT" name="PAYZEN_SEPA_1_CLICK_PAYMNT">
                {foreach from=$payzen_yes_no_options key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_SEPA_1_CLICK_PAYMNT === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <p>{l s='This option allows to pay orders without re-entering bank data at each payment. The "payment by token" option should be enabled on your %s store to use this feature.' sprintf='PayZen' mod='payzen'}</p>
            </div>
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
            <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
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
          <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

          <label for="PAYZEN_PAYPAL_COUNTRY">{l s='Restrict to some countries' mod='payzen'}</label>
          <div class="margin-form">
            <select id="PAYZEN_PAYPAL_COUNTRY" name="PAYZEN_PAYPAL_COUNTRY" onchange="javascript: payzenCountriesRestrictMenuDisplay('PAYZEN_PAYPAL_COUNTRY')">
              {foreach from=$payzen_countries_options key="key" item="option"}
                <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_PAYPAL_COUNTRY === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
              {/foreach}
            </select>
            <p>{l s='Buyer\'s billing countries in which this payment method is available.' mod='payzen'}</p>
          </div>

          <div id="PAYZEN_PAYPAL_COUNTRY_MENU" {if $PAYZEN_PAYPAL_COUNTRY === '1'} style="display: none;"{/if}>
            <label for="PAYZEN_PAYPAL_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_PAYPAL_COUNTRY_LST" name="PAYZEN_PAYPAL_COUNTRY_LST[]" multiple="multiple" size="7">
                {foreach from=$payzen_countries_list['ps_countries'] key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_PAYPAL_COUNTRY_LST)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
            </div>
          </div>

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
            <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
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
          <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

          {if isset ($payzen_countries_list['SOFORT'])}
            <label for="PAYZEN_SOFORT_COUNTRY">{l s='Restrict to some countries' mod='payzen'}</label>
            <div class="margin-form">
              <select id="PAYZEN_SOFORT_COUNTRY" name="PAYZEN_SOFORT_COUNTRY" onchange="javascript: payzenCountriesRestrictMenuDisplay('PAYZEN_SOFORT_COUNTRY')">
                {foreach from=$payzen_countries_options key="key" item="option"}
                  <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_SOFORT_COUNTRY === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                {/foreach}
              </select>
              <p>{l s='Buyer\'s billing countries in which this payment method is available.' mod='payzen'}</p>
            </div>

            <div id="PAYZEN_SOFORT_COUNTRY_MENU" {if $PAYZEN_SOFORT_COUNTRY === '1'} style="display: none;"{/if}>
              <label for="PAYZEN_SOFORT_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
              <div class="margin-form">
                <select id="PAYZEN_SOFORT_COUNTRY_LST" name="PAYZEN_SOFORT_COUNTRY_LST[]" multiple="multiple" size="7">
                  {if isset ($payzen_countries_list['SOFORT'])}
                      {foreach from=$payzen_countries_list['SOFORT'] key="key" item="option"}
                          <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_SOFORT_COUNTRY_LST)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
                      {/foreach}
                  {/if}
                </select>
              </div>
            </div>
          {else}
            <input type="hidden" name="PAYZEN_SOFORT_COUNTRY" value="1">
            <input type="hidden" name="PAYZEN_SOFORT_COUNTRY_LST[]" value ="">
            <p style="background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; font-size: 13px; margin: 0 0 20px; padding: 10px;">
                {l s='Payment method unavailable for the list of countries defined on your PrestaShop store.' mod='payzen'}
            </p>
          {/if}

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

    <h4 style="font-weight: bold; margin-bottom: 0; overflow: hidden; line-height: unset !important;">
      <a href="#">{l s='OTHER PAYMENT MEANS' mod='payzen'}</a>
    </h4>
    <div>
      <fieldset>
        <legend>{l s='MODULE OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_OTHER_ENABLED">{l s='Activation' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_OTHER_ENABLED" name="PAYZEN_OTHER_ENABLED">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_OTHER_ENABLED === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Enables / disables this payment method.' mod='payzen'}</p>
        </div>

        <label>{l s='Payment method title' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./input_text_lang.tpl"
            languages=$prestashop_languages
            current_lang=$prestashop_lang
            input_name="PAYZEN_OTHER_TITLE"
            input_value=$PAYZEN_OTHER_TITLE
            style="width: 330px;"
          }
          <p>{l s='Method title to display on payment means page. Used only if « Regroup payment means » option is enabled.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>

      <fieldset>
        <legend>{l s='RESTRICTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_OTHER_COUNTRY">{l s='Restrict to some countries' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_OTHER_COUNTRY" name="PAYZEN_OTHER_COUNTRY" onchange="javascript: payzenCountriesRestrictMenuDisplay('PAYZEN_OTHER_COUNTRY')">
            {foreach from=$payzen_countries_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_OTHER_COUNTRY === (string)$key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='Buyer\'s billing countries in which this payment method is available.' mod='payzen'}</p>
        </div>

        <div id="PAYZEN_OTHER_COUNTRY_MENU" {if $PAYZEN_OTHER_COUNTRY === '1'} style="display: none;"{/if}>
        <label for="PAYZEN_OTHER_COUNTRY_LST">{l s='Authorized countries' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_OTHER_COUNTRY_LST" name="PAYZEN_OTHER_COUNTRY_LST[]" multiple="multiple" size="7">
            {foreach from=$payzen_countries_list['ps_countries'] key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if in_array($key, $PAYZEN_OTHER_COUNTRY_LST)} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
        </div>
        </div>

        <label>{l s='Customer group amount restriction' mod='payzen'}</label>
        <div class="margin-form">
          {include file="./table_amount_group.tpl"
            groups=$prestashop_groups
            input_name="PAYZEN_OTHER_AMOUNTS"
            input_value=$PAYZEN_OTHER_AMOUNTS
          }
          <p>{l s='Define amount restriction for each customer group.' mod='payzen'}</p>
        </div>
      </fieldset>
      <div class="clear payzen-grouped">&nbsp;</div>

      <fieldset>
        <legend>{l s='PAYMENT OPTIONS' mod='payzen'}</legend>

        <label for="PAYZEN_OTHER_GROUPED_VIEW">{l s='Regroup payment means ' mod='payzen'}</label>
        <div class="margin-form">
          <select id="PAYZEN_OTHER_GROUPED_VIEW" name="PAYZEN_OTHER_GROUPED_VIEW" onchange="javascript: payzenGroupedViewChanged();">
            {foreach from=$payzen_enable_disable_options key="key" item="option"}
              <option value="{$key|escape:'html':'UTF-8'}"{if $PAYZEN_OTHER_GROUPED_VIEW === $key} selected="selected"{/if}>{$option|escape:'html':'UTF-8'}</option>
            {/foreach}
          </select>
          <p>{l s='If this option is enabled, all the payment means added in this section will be displayed within the same payment submodule.' mod='payzen'}</p>
        </div>

        <label>{l s='Payment means' mod='payzen'}</label>
        <div class="margin-form">
          {assign var=merged_array_cards value=$payzen_payment_cards_options}
          {assign var=VALID_PAYZEN_EXTRA_PAYMENT_MEANS value=[]}
          {foreach from=$PAYZEN_EXTRA_PAYMENT_MEANS key="key_card" item="option_card"}
              {if ! isset($merged_array_cards[$option_card.code])}
                  {append var='merged_array_cards' value=$option_card.title index=$option_card.code}
                  {$VALID_PAYZEN_EXTRA_PAYMENT_MEANS.$key_card = $option_card}
              {/if}
          {/foreach}

          <script type="text/html" id="payzen_other_payment_means_row_option">
            {include file="./row_other_payment_means_option.tpl"
              payment_means_cards=$merged_array_cards
              countries_list=$payzen_countries_list['ps_countries']
              validation_mode_options=$payzen_validation_mode_options
              enable_disable_options=$payzen_enable_disable_options
              languages=$prestashop_languages
              current_lang=$prestashop_lang
              key="PAYZEN_OTHER_PAYMENT_SCRIPT_MEANS_KEY"
              option=$payzen_default_other_payment_means_option
            }
          </script>

          <button type="button" id="payzen_other_payment_means_options_btn"{if !empty($PAYZEN_OTHER_PAYMENT_MEANS)} style="display: none;"{/if} onclick="javascript: payzenAddOtherPaymentMeansOption(true, '{l s='Delete' mod='payzen'}');">{l s='Add' mod='payzen'}</button>

          <table id="payzen_other_payment_means_options_table"{if empty($PAYZEN_OTHER_PAYMENT_MEANS)} style="display: none;"{/if} class="table" cellpadding="10" cellspacing="0">
          <thead>
            <tr>
              <th style="font-size: 10px;">{l s='Label' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Means of payment' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Countries' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Min amount' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Max amount' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Capture' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Validation mode' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Cart data' mod='payzen'}</th>
              <th style="font-size: 10px; {if $PAYZEN_STD_CARD_DATA_MODE !== '7' && $PAYZEN_STD_CARD_DATA_MODE !== '8' && $PAYZEN_STD_CARD_DATA_MODE !== '9'} display: none;{/if}" class="PAYZEN_OTHER_PAYMENT_MEANS_EMBEDDED">{l s='Integrated mode' mod='payzen'}</th>
              <th style="font-size: 10px;"></th>
            </tr>
          </thead>

          <tbody>
            {foreach from=$PAYZEN_OTHER_PAYMENT_MEANS key="key" item="option"}
              {include file="./row_other_payment_means_option.tpl"
                payment_means_cards=$merged_array_cards
                countries_list=$payzen_countries_list['ps_countries']
                validation_mode_options=$payzen_validation_mode_options
                enable_disable_options=$payzen_enable_disable_options
                languages=$prestashop_languages
                current_lang=$prestashop_lang
                key=$key
                option=$option
              }
            {/foreach}

            <tr id="payzen_other_payment_means_option_add">
              <td colspan="8"></td>
              <td>
                <button type="button" onclick="javascript: payzenAddOtherPaymentMeansOption(false, '{l s='Delete' mod='payzen'}');">{l s='Add' mod='payzen'}</button>
              </td>
            </tr>
          </tbody>
          </table>

          {if empty($PAYZEN_OTHER_PAYMENT_MEANS)}
            <input type="hidden" id="PAYZEN_OTHER_PAYMENT_MEANS" name="PAYZEN_OTHER_PAYMENT_MEANS" value="">
          {/if}

          <p>
            {l s='Click on « Add » button to configure one or more payment means.' mod='payzen'}<br />
            <b>{l s='Label' mod='payzen'} : </b>{l s='The label of the means of payment to display on your site.' mod='payzen'}<br />
            <b>{l s='Means of payment' mod='payzen'} : </b>{l s='Choose the means of payment you want to propose.' mod='payzen'}<br />
            <b>{l s='Countries' mod='payzen'} : </b>{l s='Countries where the means of payment will be available. Keep blank to authorize all countries.' mod='payzen'}<br />
            <b>{l s='Min amount' mod='payzen'} : </b>{l s='Minimum amount to enable the means of payment.' mod='payzen'}<br />
            <b>{l s='Max amount' mod='payzen'} : </b>{l s='Maximum amount to enable the means of payment.' mod='payzen'}<br />
            <b>{l s='Capture' mod='payzen'} : </b>{l s='The number of days before the bank capture. Enter value only if different from « Base settings ».' mod='payzen'}<br />
            <b>{l s='Validation mode' mod='payzen'} : </b>{l s='If manual is selected, you will have to confirm payments manually in your bank Back Office.' mod='payzen'}<br />
            <b>{l s='Cart data' mod='payzen'} : </b>{l s='If you disable this option, the shopping cart details will not be sent to the gateway. Attention, in some cases, this option has to be enabled. For more information, refer to the module documentation.' mod='payzen'}<br />
            <b style="{if $PAYZEN_STD_CARD_DATA_MODE !== '7' && $PAYZEN_STD_CARD_DATA_MODE !== '8' && $PAYZEN_STD_CARD_DATA_MODE !== '9'}display: none;{/if}" class="PAYZEN_OTHER_PAYMENT_MEANS_EMBEDDED">{l s='Integrated mode' mod='payzen'} : </b><span style="{if $PAYZEN_STD_CARD_DATA_MODE !== '7' && $PAYZEN_STD_CARD_DATA_MODE !== '8' && $PAYZEN_STD_CARD_DATA_MODE !== '9'}display: none;{/if}" class="PAYZEN_OTHER_PAYMENT_MEANS_EMBEDDED">{l s='If you enable this option, the payment mean will be displayed in the embedded payment fields. Attention, not all available payment means are supported by the embedded payment fields. For more information, refer to the module documentation.' mod='payzen'}</span><br />
            <b>{l s='Do not forget to click on « Save » button to save your modifications.' mod='payzen'}</b>
          </p>
        </div>

        <label>{l s='Add payment means' mod='payzen'}</label>
        <div class="margin-form">
          <script type="text/html" id="payzen_add_payment_means_row_option">
            {include file="./row_extra_means_of_payment.tpl"
              key="PAYZEN_EXTRA_PAYMENT_MEANS_SCRIPT_KEY"
              option=$payzen_default_extra_payment_means_option
            }
          </script>

          <button type="button" id="payzen_extra_payment_means_options_btn"{if !empty($VALID_PAYZEN_EXTRA_PAYMENT_MEANS)} style="display: none;"{/if} onclick="javascript: payzenAddExtraPaymentMeansOption(true, '{l s='Delete' mod='payzen'}');">{l s='Add' mod='payzen'}</button>

          <table id="payzen_extra_payment_means_options_table"{if empty($VALID_PAYZEN_EXTRA_PAYMENT_MEANS)} style="display: none;"{/if} class="table" cellpadding="10" cellspacing="0">
          <thead>
            <tr>
              <th style="font-size: 10px;">{l s='Code' mod='payzen'}</th>
              <th style="font-size: 10px; width: 350px;">{l s='Label' mod='payzen'}</th>
              <th style="font-size: 10px;">{l s='Action' mod='payzen'}</th>
            </tr>
          </thead>

          <tbody>
            {foreach from=$VALID_PAYZEN_EXTRA_PAYMENT_MEANS key="key" item="option"}
                {include file="./row_extra_means_of_payment.tpl"
                    key=$key
                    option=$option
                }
            {/foreach}

            <tr id="payzen_extra_payment_means_option_add">
              <td colspan="2"></td>
              <td>
                <button type="button" onclick="javascript: payzenAddExtraPaymentMeansOption(false, '{l s='Delete' mod='payzen'}');">{l s='Add' mod='payzen'}</button>
              </td>
            </tr>
          </tbody>
          </table>

          {if empty($VALID_PAYZEN_EXTRA_PAYMENT_MEANS)}
            <input type="hidden" id="PAYZEN_EXTRA_PAYMENT_MEANS" name="PAYZEN_EXTRA_PAYMENT_MEANS" value="">
          {/if}

          <p>
            {l s='Click on « Add » button to add one or more new payment means.' mod='payzen'}<br />
            <b>{l s='Code' mod='payzen'} : </b>{l s='The code of the means of payment as expected by %s gateway.' sprintf='PayZen' mod='payzen'}<br />
            <b>{l s='Label' mod='payzen'} : </b>{l s='The default label of the means of payment.' mod='payzen'}<br />
            <b>{l s='Do not forget to click on « Save » button to save your modifications.' mod='payzen'}</b>
          </p>
        </div>
      </fieldset>
      <div class="clear">&nbsp;</div>
    </div>

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