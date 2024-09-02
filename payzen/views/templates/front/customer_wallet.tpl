{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

{extends file='customer/page.tpl'}

{block name='page_title'}
    {l s='My payment means' mod='payzen'}
{/block}

{block name='page_content'}
  <div class="container">
    <section class="page_content">
      <div id="payzen-no-tokens-warning" {if $payzen_show_wallet == true} style="display: none;" {/if} class="alert alert-info" role="alert" data-alert="info">{l s='You have no stored payment means.' mod='payzen'}</div>

    {if $payzen_show_wallet == true}
      <div class="col-md-4">
        {include file="module:payzen/views/templates/hook/payment_std_rest.tpl"}
      </div>
    {/if}
    </section>
  </div>

  <script type="text/javascript">
    function payzenManageWalletDisplay() {
        if ($('.kr-smart-form-wallet').length == 0) {
            $('#payzen_standard_rest_wrapper').addClass('payzen_hide-wallet-elements');
            $('#payzen-no-tokens-warning').show();

            return;
        }

        $('div.kr-methods-list-options-item.kr-cards').addClass('payzen_hide-wallet-elements');
        $('div.kr-smart-form-list-section-name--other').addClass('payzen_hide-wallet-elements');
        $('div.kr-smart-form-list-section-name').addClass('payzen_hide-wallet-elements');

        $('.kr-methods-list-options--wallet').each(function() {
          if (! $(this).hasClass('kr-methods-list-options--extra')) {
              $(this).addClass('payzen_hide-wallet-elements');
          }
        });
    }

    {if $payzen_show_tokens_only == true}
      KR.onLoaded(() => {
        payzenManageWalletDisplay();
      })
    {/if}
  </script>
{/block}

