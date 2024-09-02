<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

class PayzenWalletModuleFrontController extends ModuleFrontController
{
    /**
     * @var Payzen
     */
    public $module;

    /**
     * @var bool
     */
    public $display_column_right;

    /**
     * @var bool
     */
    public $display_column_left;

    public function initContent()
    {
        $this->display_column_right = false;
        $this->display_column_left = false;

        $context = Context::getContext();
        if (empty($context->customer->id)) {
            Tools::redirect('index.php');
        }

        parent::initContent();

        $showWallet = false;
        $showTokensOnly = false;

        $standard = new PayzenStandardPayment();

        if ($standard->isAvailable($context->cart) && $standard->isOneClickActive()) {
            $vars = $standard->getTplVars($context->cart, true);

            if (isset($vars['payzen_rest_form_token']) && ! empty($vars['payzen_rest_form_token'])) {
                $this->context->smarty->assign($vars);
                $showWallet = true;

                if (! $standard->isEmbedded() ||(Configuration::get('PAYZEN_STD_USE_WALLET') != 'True')) {
                    $showTokensOnly = true;
                }
            }
        }

        if (isset($this->context->cookie->payzenIdentifierOperationSuccess)) {
            $this->success[] = $this->context->cookie->payzenIdentifierOperationSuccess;
            $this->context->smarty->assign('payzen_confirm_msg', $this->context->cookie->payzenIdentifierOperationSuccess);

            unset($this->context->cookie->payzenIdentifierOperationSuccess);
        } elseif ($this->context->cookie->payzenCreateIdentifierError) {
            $this->errors[] = $this->context->cookie->payzenCreateIdentifierError;
            $this->context->smarty->assign('payzen_error_msg', $this->context->cookie->payzenCreateIdentifierError);

            unset($this->context->cookie->payzenCreateIdentifierError);
        }

        $this->context->smarty->tpl_vars['page']->value['body_classes']['page-customer-account'] = true;
        $this->context->smarty->assign('payzen_show_wallet', $showWallet);
        $this->context->smarty->assign('payzen_show_tokens_only', $showTokensOnly);

        $template = (version_compare(_PS_VERSION_, '1.7', '>=')) ? 'module:payzen/views/templates/front/customer_wallet.tpl' : 'customer_wallet_bc.tpl';

        return $this->setTemplate($template);
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        $breadcrumb['links'][] = [
            'title' => $this->trans('My payment means', [], 'Modules.Payzen.Customer_wallet'),
            'url' => $this->context->link->getModuleLink($this->module->name, 'wallet', [], true)
        ];

        return $breadcrumb;
    }
}
