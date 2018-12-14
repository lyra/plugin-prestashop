{if isset($payzen_opc_enabled) && $payzen_opc_enabled}
	{assign var='order_page' value='order-opc.php'}
{else}
	{assign var='order_page' value='order.php'}
{/if}

{capture name=path}<a href="{$base_dir}{$order_page}">{l s='Your shopping cart' mod='payzen'}</a><span class="navigation-pipe">{$navigationPipe}</span>PayZen{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Redirection to payment gateway' mod='payzen'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($payzen_empty_cart) && $payzen_empty_cart}
	<p class="warning">{l s='Your shopping cart is empty.' mod='payzen'}</p>
{else}

	<h3>{l s='Payment by bank card' mod='payzen'}</h3>

	<form action="{$payzen_url}" method="post" id="payzen_form">
		{foreach from=$payzen_params key='key' item='value'}
			<input type="hidden" name="{$key}" value="{$value}" />
		{/foreach}

		<p>
			<img src="{$base_dir}modules/payzen/img/BannerLogo.png" alt="PayZen" style="margin-bottom: 5px" />
			<br />
			{l s='Please wait, you will be redirected to the payment platform.' mod='payzen'}
			<br /> <br />
			{l s='If you are not redirected in 10 seconds, please click the button below.' mod='payzen'}
			<br /><br />
		</p>

		<p class="cart_navigation">
			<input type="submit" name="submitPayment" value="{l s='Pay' mod='payzen'}" class="exclusive" />
		</p>
	</form>

	<script type="text/javascript">
		{literal}
			$(document).ready(function() {
				$('#payzen_form').submit();
			});
		{/literal}
	</script>
{/if}