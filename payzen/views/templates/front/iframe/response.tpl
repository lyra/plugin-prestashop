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

{if version_compare($smarty.const._PS_VERSION_, '1.7', '>=')}
  {include file="module:payzen/views/templates/front/iframe/loader.tpl"}
{else}
  {include file="./loader.tpl"}
{/if}

<script type="text/javascript">
  var url = decodeURIComponent("{$payzen_url|escape:'url':'UTF-8'}");

  if (window.top) {
    window.top.location = url;
  } else {
    window.location = url;
  }
</script>
