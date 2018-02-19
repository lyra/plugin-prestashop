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

{foreach from=$languages item=language}
  {if version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
    {assign var="v5" value=true}
  {else}
    {assign var="v5" value=false}
  {/if}

  {if !isset($field_id)}
    {assign var="field_id" value={$input_name|escape:'html':'UTF-8'}}
  {/if}

  {if is_array($input_value)}
    {if isset($input_value[$language.id_lang])}
      {assign var="value" value={$input_value[$language.id_lang]|escape:'html':'UTF-8'}}
    {else}
      {assign var="value" value=""}
    {/if}
  {else}
    {assign var="value" value={$input_value|escape:'html':'UTF-8'}}
  {/if}

  <div class="translatable-field lang-{$language.id_lang|escape:'html':'UTF-8'}"
     id="{$field_id|escape:'html':'UTF-8'}_{$language.id_lang|escape:'html':'UTF-8'}"
     style="{if $v5}float: left;{/if}{if $language.id_lang != $current_lang.id_lang} display: none;{elseif !$v5} display: inline;{/if}">
    <input type="text"
        name="{$input_name|escape:'html':'UTF-8'}[{$language.id_lang|escape:'html':'UTF-8'}]"
        value="{$value|escape:'html':'UTF-8'}"
        {if isset($style)}style="{$style|escape:'html':'UTF-8'}"{/if} >
  </div>
{/foreach}

{if count($languages) > 1}
  {if version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
    <div class="displayed_flag">
      <img src="../img/l/{$current_lang.id_lang|escape:'html':'UTF-8'}.jpg" class="pointer" id="language_current_{$field_id|escape:'html':'UTF-8'}" onclick="toggleLanguageFlags(this);" alt="" />
    </div>
    <div id="languages_{$field_id|escape:'html':'UTF-8'}" class="language_flags">
      {foreach from=$languages item=language}
        <img src="../img/l/{$language.id_lang|escape:'html':'UTF-8'}.jpg"
           class="pointer" alt="{$language.name|escape:'html':'UTF-8'}"
           title="{$language.name|escape:'html':'UTF-8'}"
           onclick="changeLanguage('{$field_id|escape:'html':'UTF-8'}', '{$field_id|escape:'html':'UTF-8'}', '{$language.id_lang|escape:'html':'UTF-8'}', '{$language.iso_code|escape:'html':'UTF-8'}');" />
      {/foreach}
    </div>
    <br class="clear">
  {else}
    <div class="bootstrap translation-btn" style="vertical-align: middle; display: inline-block;">
      <div class="col-lg-2">
        <button type="button" class="btn btn-default dropdown-toggle" tabindex="-1" data-toggle="dropdown">
          <span>{$current_lang.iso_code|escape:'html':'UTF-8'}</span>
          <i class="icon-caret-down"></i>
        </button>

        <ul class="dropdown-menu">
          {foreach from=$languages item=language}
            <li><a href="javascript: payzenHideOtherLanguage({$language.id_lang|escape:'html':'UTF-8'}, '{$language.iso_code|escape:'html':'UTF-8'}');" tabindex="-1">{$language.name|escape:'html':'UTF-8'}</a></li>
          {/foreach}
        </ul>
      </div>
    </div>
  {/if}
{/if}
