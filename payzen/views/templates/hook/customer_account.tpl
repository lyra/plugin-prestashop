{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

{if version_compare($smarty.const._PS_VERSION_, '1.7', '>=')}
  <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="payzen-wallet-link" href="{$front_controller|escape:'html':'UTF-8'}">
      <span class="link-item">
        <i class="material-icons">account_box</i> {l s='My payment means' mod='payzen'}
      </span>
  </a>
{else}
   <li><a href="{$front_controller|escape:'html':'UTF-8'}" title="{l s='My payment means' mod='payzen'}"><i class="icon-user"></i><span>{l s='My payment means' mod='payzen'}</span></a></li>
{/if}