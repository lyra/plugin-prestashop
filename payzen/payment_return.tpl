{if $check_url_warn == true}
    <p class="warning">
        {if $maintenance_mode == true}
          {l s='The shop is in maintenance mode.The automatic notification cannot work.' mod='payzen'}
        {else}
            {l s='The automatic validation has not worked. Have you correctly set up the notification URL in your gateway Back Office ?' mod='payzen'}
            <br />
            {l s='For understanding the problem, please read the documentation of the module :' mod='payzen'}<br />
            &nbsp;&nbsp;&nbsp;- {l s='Chapter « To read carefully before going further »' mod='payzen'}<br />
            &nbsp;&nbsp;&nbsp;- {l s='Chapter « Notification URL settings »' mod='payzen'}
        {/if}

        <br />
        {l s='If you think this is an error, you can contact our' mod='payzen'}
        <a href="{$base_dir_ssl}contact-form.php">{l s='customer support' mod='payzen'}</a>.
    </p>

    <br/><br/>
{/if}

{if $prod_info == true}
    <p class="warning">
        <span style="text-decoration: underline; font-weight: bold;">{l s='GOING INTO PRODUCTION' mod='payzen'}</span><br />
        {l s='You want to know how to put your shop into production mode, please read chapters « Proceeding to test phase » and « Shifting the shop to production mode » in the documentation of the module.' mod='payzen'}
    </p>

    <br/><br/>
{/if}

{if $error_msg == true}
    <p class="warning">
        {l s='Your order has been registered with a payment error.' mod='payzen'}

        {l s='Please contact our' mod='payzen'}&nbsp;<a href="{$base_dir_ssl}contact-form.php">{l s='customer support' mod='payzen'}</a>.
    </p>
{else}
    <p>{l s='Your order on' mod='payzen'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='payzen'}
        <br /><br />
        {l s='We registered your payment of ' mod='payzen'} <span class="price">{$total_to_pay}</span>
        <br /><br />{l s='For any questions or for further information, please contact our' mod='payzen'} <a href="{$base_dir_ssl}contact-form.php">{l s='customer support' mod='payzen'}</a>.
    </p>
{/if}