{if $check_url_warn == true}
	<p class="warning">
		{l s='The automatic notification (peer to peer connection between the payment platform and your shopping cart solution) hasn\'t worked. Have you correctly set up the server URL in your store backoffice ?' mod='payzen'}
		<br />
		{l s='For understanding the problem, please read the documentation of the module : ' mod='payzen'}<br />
		&nbsp;&nbsp;&nbsp;{l s='- Chapter "To read carefully before going further"' mod='payzen'}<br />
		&nbsp;&nbsp;&nbsp;{l s='- Chapter "Server URL settings"' mod='payzen'}<br />

		{l s='If you think this is an error, you can contact our' mod='payzen'}
		<a href="{$base_dir_ssl}contact-form.php">{l s='customer support' mod='payzen'}</a>.
	</p>
	
	<br/><br/>
{/if}

{if $prod_info == true}
	<p class="warning">
		<u><b>{l s='GOING INTO PRODUCTION' mod='payzen'}</b></u><br />{l s='You want to know how to put your shop into production mode, please go to this URL : ' mod='payzen'}<a href="https://secure.payzen.eu/html/faq/prod" target="_blank">https://secure.payzen.eu/html/faq/prod</a>
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