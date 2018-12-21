<?php
/**
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
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Class that renders PayZen payment module administration interface.
 */
class PayzenHelperForm
{
    private function __construct()
    {
        // do not instantiate this class
    }

    public static function getAdminFormContext()
    {
        $context = Context::getContext();

        /* @var Payzen */
        $payzen = Module::getInstanceByName('payzen');

        $languages = array();
        foreach (PayzenApi::getSupportedLanguages() as $code => $label) {
            $languages[$code] = $payzen->l($label, 'payzenhelperform');
        }
        asort($languages);

        $category_options = array(
            'FOOD_AND_GROCERY' => $payzen->l('Food and grocery', 'payzenhelperform'),
            'AUTOMOTIVE' => $payzen->l('Automotive', 'payzenhelperform'),
            'ENTERTAINMENT' => $payzen->l('Entertainment', 'payzenhelperform'),
            'HOME_AND_GARDEN' => $payzen->l('Home and garden', 'payzenhelperform'),
            'HOME_APPLIANCE' => $payzen->l('Home appliance', 'payzenhelperform'),
            'AUCTION_AND_GROUP_BUYING' => $payzen->l('Auction and group buying', 'payzenhelperform'),
            'FLOWERS_AND_GIFTS' => $payzen->l('Flowers and gifts', 'payzenhelperform'),
            'COMPUTER_AND_SOFTWARE' => $payzen->l('Computer and software', 'payzenhelperform'),
            'HEALTH_AND_BEAUTY' => $payzen->l('Health and beauty', 'payzenhelperform'),
            'SERVICE_FOR_INDIVIDUAL' => $payzen->l('Service for individual', 'payzenhelperform'),
            'SERVICE_FOR_BUSINESS' => $payzen->l('Service for business', 'payzenhelperform'),
            'SPORTS' => $payzen->l('Sports', 'payzenhelperform'),
            'CLOTHING_AND_ACCESSORIES' => $payzen->l('Clothing and accessories', 'payzenhelperform'),
            'TRAVEL' => $payzen->l('Travel', 'payzenhelperform'),
            'HOME_AUDIO_PHOTO_VIDEO' => $payzen->l('Home audio, photo, video', 'payzenhelperform'),
            'TELEPHONY' => $payzen->l('Telephony', 'payzenhelperform')
        );

        // get documentation links
        $doc_files = array();
        $filenames = glob(_PS_MODULE_DIR_.'payzen/installation_doc/PayZen_PrestaShop_1.5-1.7_v1.10.2*.pdf');

        $doc_languages = array(
            'fr' => 'Français',
            'en' => 'English',
            'es' => 'Español'
            // complete when other languages are managed
        );

        foreach ($filenames as $filename) {
            $base_filename = basename($filename, '.pdf');
            $lang = Tools::substr($base_filename, -2); // extract language code

            $doc_files[$base_filename.'.pdf'] = $doc_languages[$lang];
        }

        $tpl_vars = array(
            'payzen_plugin_features' => PayzenTools::$plugin_features,
            'payzen_request_uri' => $_SERVER['REQUEST_URI'],

            'payzen_doc_files' => $doc_files,
            'payzen_enable_disable_options' => array(
                'False' => $payzen->l('Disabled', 'payzenhelperform'),
                'True' => $payzen->l('Enabled', 'payzenhelperform')
            ),
            'payzen_mode_options' => array(
                'TEST' => $payzen->l('TEST', 'payzenhelperform'),
                'PRODUCTION' => $payzen->l('PRODUCTION', 'payzenhelperform')
            ),
            'payzen_language_options' => $languages,
            'payzen_validation_mode_options' => array(
                '' => $payzen->l('Bank Back Office configuration', 'payzenhelperform'),
                '0' => $payzen->l('Automatic', 'payzenhelperform'),
                '1' => $payzen->l('Manual', 'payzenhelperform')
            ),
            'payzen_payment_cards_options' => array('' => $payzen->l('ALL', 'payzenhelperform')) + PayzenTools::getSupportedCardTypes(),
            'payzen_multi_payment_cards_options' => array('' => $payzen->l('ALL', 'payzenhelperform')) + PayzenTools::getSupportedMultiCardTypes(),
            'payzen_category_options' => $category_options,
            'payzen_yes_no_options' => array(
                'False' => $payzen->l('No', 'payzenhelperform'),
                'True' => $payzen->l('Yes', 'payzenhelperform')
            ),
            'payzen_delivery_type_options' => array(
                'PACKAGE_DELIVERY_COMPANY' => $payzen->l('Delivery company', 'payzenhelperform'),
                'RECLAIM_IN_SHOP' => $payzen->l('Reclaim in shop', 'payzenhelperform'),
                'RELAY_POINT' => $payzen->l('Relay point', 'payzenhelperform'),
                'RECLAIM_IN_STATION' => $payzen->l('Reclaim in station', 'payzenhelperform')
            ),
            'payzen_delivery_speed_options' => array(
                'STANDARD' => $payzen->l('Standard', 'payzenhelperform'),
                'EXPRESS' => $payzen->l('Express', 'payzenhelperform'),
                'PRIORITY' => $payzen->l('Priority', 'payzenhelperform')
            ),
            'payzen_delivery_delay_options' => array(
                'INFERIOR_EQUALS' => $payzen->l('<= 1 hour', 'payzenhelperform'),
                'SUPERIOR' => $payzen->l('> 1 hour', 'payzenhelperform'),
                'IMMEDIATE' => $payzen->l('Immediate', 'payzenhelperform'),
                'ALWAYS' => $payzen->l('24/7', 'payzenhelperform')
            ),
            'payzen_failure_management_options' => array(
                PayzenTools::ON_FAILURE_RETRY => $payzen->l('Go back to checkout', 'payzenhelperform'),
                PayzenTools::ON_FAILURE_SAVE => $payzen->l('Save order and go back to order history', 'payzenhelperform')
            ),
            'payzen_cart_management_options' => array(
                PayzenTools::EMPTY_CART => $payzen->l('Empty cart to avoid amount errors', 'payzenhelperform'),
                PayzenTools::KEEP_CART => $payzen->l('Keep cart (PrestaShop default behavior)', 'payzenhelperform')
            ),
            'payzen_card_data_mode_options' => array(
                '1' => $payzen->l('Bank data acquisition on payment gateway', 'payzenhelperform'),
                '2' => $payzen->l('Card type selection on merchant site', 'payzenhelperform'),
                '3' => $payzen->l('Bank data acquisition on merchant site', 'payzenhelperform'),
                '4' => $payzen->l('Payment page integrated to checkout process (iframe mode)', 'payzenhelperform')
            ),
            'payzen_card_selection_mode_options' => array(
                '1' => $payzen->l('On payment gateway', 'payzenhelperform'),
                '2' => $payzen->l('On merchant site', 'payzenhelperform')
            ),
            'payzen_default_multi_option' => array(
                'label' => '',
                'min_amount' => '',
                'max_amount' => '',
                'contract' => '',
                'count' => '',
                'period' => '',
                'first' => ''
            ),
            'payzen_default_oney_option' => array(
                'label' => '',
                'code' => '',
                'min_amount' => '',
                'max_amount' => '',
                'count' => '',
                'rate' => ''
            ),

            'prestashop_categories' => Category::getCategories((int)$context->language->id, true, false),
            'prestashop_languages' => Language::getLanguages(false),
            'prestashop_lang' => Language::getLanguage((int)$context->language->id),
            'prestashop_carriers' => Carrier::getCarriers(
                (int)$context->language->id,
                true,
                false,
                false,
                null,
                Carrier::ALL_CARRIERS
            ),
            'prestashop_groups' => self::getAuthorizedGroups(),

            'PAYZEN_ENABLE_LOGS' => Configuration::get('PAYZEN_ENABLE_LOGS'),

            'PAYZEN_SITE_ID' => Configuration::get('PAYZEN_SITE_ID'),
            'PAYZEN_KEY_TEST' => Configuration::get('PAYZEN_KEY_TEST'),
            'PAYZEN_KEY_PROD' => Configuration::get('PAYZEN_KEY_PROD'),
            'PAYZEN_MODE' => Configuration::get('PAYZEN_MODE'),
            'PAYZEN_SIGN_ALGO' => Configuration::get('PAYZEN_SIGN_ALGO'),
            'PAYZEN_PLATFORM_URL' => Configuration::get('PAYZEN_PLATFORM_URL'),
            'PAYZEN_NOTIFY_URL' => self::getIpnUrl(),

            'PAYZEN_DEFAULT_LANGUAGE' => Configuration::get('PAYZEN_DEFAULT_LANGUAGE'),
            'PAYZEN_AVAILABLE_LANGUAGES' => !Configuration::get('PAYZEN_AVAILABLE_LANGUAGES') ?
                                            array('') :
                                            explode(';', Configuration::get('PAYZEN_AVAILABLE_LANGUAGES')),
            'PAYZEN_DELAY' => Configuration::get('PAYZEN_DELAY'),
            'PAYZEN_VALIDATION_MODE' => Configuration::get('PAYZEN_VALIDATION_MODE'),

            'PAYZEN_THEME_CONFIG' => Configuration::get('PAYZEN_THEME_CONFIG'),
            'PAYZEN_SHOP_NAME' => Configuration::get('PAYZEN_SHOP_NAME'),
            'PAYZEN_SHOP_URL' => Configuration::get('PAYZEN_SHOP_URL'),

            'PAYZEN_3DS_MIN_AMOUNT' => self::getArrayConfig('PAYZEN_3DS_MIN_AMOUNT'),

            'PAYZEN_REDIRECT_ENABLED' => Configuration::get('PAYZEN_REDIRECT_ENABLED'),
            'PAYZEN_REDIRECT_SUCCESS_T' => Configuration::get('PAYZEN_REDIRECT_SUCCESS_T'),
            'PAYZEN_REDIRECT_SUCCESS_M' => self::getLangConfig('PAYZEN_REDIRECT_SUCCESS_M'),
            'PAYZEN_REDIRECT_ERROR_T' => Configuration::get('PAYZEN_REDIRECT_ERROR_T'),
            'PAYZEN_REDIRECT_ERROR_M' => self::getLangConfig('PAYZEN_REDIRECT_ERROR_M'),
            'PAYZEN_RETURN_MODE' => Configuration::get('PAYZEN_RETURN_MODE'),
            'PAYZEN_FAILURE_MANAGEMENT' => Configuration::get('PAYZEN_FAILURE_MANAGEMENT'),
            'PAYZEN_CART_MANAGEMENT' => Configuration::get('PAYZEN_CART_MANAGEMENT'),

            'PAYZEN_COMMON_CATEGORY' => Configuration::get('PAYZEN_COMMON_CATEGORY'),
            'PAYZEN_CATEGORY_MAPPING' => self::getArrayConfig('PAYZEN_CATEGORY_MAPPING'),
            'PAYZEN_SEND_SHIP_DATA' => Configuration::get('PAYZEN_SEND_SHIP_DATA'),
            'PAYZEN_ONEY_SHIP_OPTIONS' => self::getArrayConfig('PAYZEN_ONEY_SHIP_OPTIONS'),

            'PAYZEN_STD_TITLE' => self::getLangConfig('PAYZEN_STD_TITLE'),
            'PAYZEN_STD_ENABLED' => Configuration::get('PAYZEN_STD_ENABLED'),
            'PAYZEN_STD_AMOUNTS' => self::getArrayConfig('PAYZEN_STD_AMOUNTS'),
            'PAYZEN_STD_DELAY' => Configuration::get('PAYZEN_STD_DELAY'),
            'PAYZEN_STD_VALIDATION' => Configuration::get('PAYZEN_STD_VALIDATION'),
            'PAYZEN_STD_PAYMENT_CARDS' => !Configuration::get('PAYZEN_STD_PAYMENT_CARDS') ?
                                            array('') :
                                            explode(';', Configuration::get('PAYZEN_STD_PAYMENT_CARDS')),
            'PAYZEN_STD_PROPOSE_ONEY' => Configuration::get('PAYZEN_STD_PROPOSE_ONEY'),
            'PAYZEN_STD_CARD_DATA_MODE' => Configuration::get('PAYZEN_STD_CARD_DATA_MODE'),

            'PAYZEN_MULTI_TITLE' => self::getLangConfig('PAYZEN_MULTI_TITLE'),
            'PAYZEN_MULTI_ENABLED' => Configuration::get('PAYZEN_MULTI_ENABLED'),
            'PAYZEN_MULTI_AMOUNTS' => self::getArrayConfig('PAYZEN_MULTI_AMOUNTS'),
            'PAYZEN_MULTI_DELAY' => Configuration::get('PAYZEN_MULTI_DELAY'),
            'PAYZEN_MULTI_VALIDATION' => Configuration::get('PAYZEN_MULTI_VALIDATION'),
            'PAYZEN_MULTI_CARD_MODE' => Configuration::get('PAYZEN_MULTI_CARD_MODE'),
            'PAYZEN_MULTI_PAYMENT_CARDS' => !Configuration::get('PAYZEN_MULTI_PAYMENT_CARDS') ?
                                            array('') :
                                            explode(';', Configuration::get('PAYZEN_MULTI_PAYMENT_CARDS')),
            'PAYZEN_MULTI_OPTIONS' => self::getArrayConfig('PAYZEN_MULTI_OPTIONS'),

            'PAYZEN_ANCV_TITLE' => self::getLangConfig('PAYZEN_ANCV_TITLE'),
            'PAYZEN_ANCV_ENABLED' => Configuration::get('PAYZEN_ANCV_ENABLED'),
            'PAYZEN_ANCV_AMOUNTS' => self::getArrayConfig('PAYZEN_ANCV_AMOUNTS'),
            'PAYZEN_ANCV_DELAY' => Configuration::get('PAYZEN_ANCV_DELAY'),
            'PAYZEN_ANCV_VALIDATION' => Configuration::get('PAYZEN_ANCV_VALIDATION'),

            'PAYZEN_ONEY_TITLE' => self::getLangConfig('PAYZEN_ONEY_TITLE'),
            'PAYZEN_ONEY_ENABLED' => Configuration::get('PAYZEN_ONEY_ENABLED'),
            'PAYZEN_ONEY_AMOUNTS' => self::getArrayConfig('PAYZEN_ONEY_AMOUNTS'),
            'PAYZEN_ONEY_DELAY' => Configuration::get('PAYZEN_ONEY_DELAY'),
            'PAYZEN_ONEY_VALIDATION' => Configuration::get('PAYZEN_ONEY_VALIDATION'),
            'PAYZEN_ONEY_ENABLE_OPTIONS' => Configuration::get('PAYZEN_ONEY_ENABLE_OPTIONS'),
            'PAYZEN_ONEY_OPTIONS' => self::getArrayConfig('PAYZEN_ONEY_OPTIONS'),

            'PAYZEN_FULLCB_TITLE' => self::getLangConfig('PAYZEN_FULLCB_TITLE'),
            'PAYZEN_FULLCB_ENABLED' => Configuration::get('PAYZEN_FULLCB_ENABLED'),
            'PAYZEN_FULLCB_AMOUNTS' => self::getArrayConfig('PAYZEN_FULLCB_AMOUNTS'),
            'PAYZEN_FULLCB_ENABLE_OPTS' => Configuration::get('PAYZEN_FULLCB_ENABLE_OPTS'),
            'PAYZEN_FULLCB_OPTIONS' => self::getArrayConfig('PAYZEN_FULLCB_OPTIONS'),

            'PAYZEN_SEPA_TITLE' => self::getLangConfig('PAYZEN_SEPA_TITLE'),
            'PAYZEN_SEPA_ENABLED' => Configuration::get('PAYZEN_SEPA_ENABLED'),
            'PAYZEN_SEPA_AMOUNTS' => self::getArrayConfig('PAYZEN_SEPA_AMOUNTS'),
            'PAYZEN_SEPA_DELAY' => Configuration::get('PAYZEN_SEPA_DELAY'),
            'PAYZEN_SEPA_VALIDATION' => Configuration::get('PAYZEN_SEPA_VALIDATION'),

            'PAYZEN_SOFORT_TITLE' => self::getLangConfig('PAYZEN_SOFORT_TITLE'),
            'PAYZEN_SOFORT_ENABLED' => Configuration::get('PAYZEN_SOFORT_ENABLED'),
            'PAYZEN_SOFORT_AMOUNTS' => self::getArrayConfig('PAYZEN_SOFORT_AMOUNTS'),

            'PAYZEN_PAYPAL_TITLE' => self::getLangConfig('PAYZEN_PAYPAL_TITLE'),
            'PAYZEN_PAYPAL_ENABLED' => Configuration::get('PAYZEN_PAYPAL_ENABLED'),
            'PAYZEN_PAYPAL_AMOUNTS' => self::getArrayConfig('PAYZEN_PAYPAL_AMOUNTS'),
            'PAYZEN_PAYPAL_DELAY' => Configuration::get('PAYZEN_PAYPAL_DELAY'),
            'PAYZEN_PAYPAL_VALIDATION' => Configuration::get('PAYZEN_PAYPAL_VALIDATION'),

            'PAYZEN_CHOOZEO_TITLE' => self::getLangConfig('PAYZEN_CHOOZEO_TITLE'),
            'PAYZEN_CHOOZEO_ENABLED' => Configuration::get('PAYZEN_CHOOZEO_ENABLED'),
            'PAYZEN_CHOOZEO_AMOUNTS' => self::getArrayConfig('PAYZEN_CHOOZEO_AMOUNTS'),
            'PAYZEN_CHOOZEO_DELAY' => Configuration::get('PAYZEN_CHOOZEO_DELAY'),
            'PAYZEN_CHOOZEO_OPTIONS' => self::getArrayConfig('PAYZEN_CHOOZEO_OPTIONS')
        );

        if (!PayzenTools::$plugin_features['acquis']) {
            unset($tpl_vars['payzen_card_data_mode_options']['3']);
        }

        return $tpl_vars;
    }

    private static function getIpnUrl()
    {
        $shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));

        // ssl enabled on default shop ?
        $id_shop_group = isset($shop->id_shop_group) ? $shop->id_shop_group : $shop->id_group_shop;
        $ssl = Configuration::get('PS_SSL_ENABLED', null, $id_shop_group, $shop->id);

        $ipn = ($ssl ? 'https://'.$shop->domain_ssl : 'http://'.$shop->domain)
               .$shop->getBaseURI().'modules/payzen/validation.php';

        return $ipn;
    }

    private static function getArrayConfig($name)
    {
        $value = @unserialize(Configuration::get($name));

        if (!is_array($value)) {
            $value = array();
        }

        return $value;
    }

    private static function getLangConfig($name)
    {
        $languages = Language::getLanguages(false);

        $result = array();
        foreach ($languages as $language) {
            $result[$language['id_lang']] = Configuration::get($name, $language['id_lang']);
        }

        return $result;
    }

    private static function getAuthorizedGroups()
    {
        $context = Context::getContext();

        /* @var Payzen */
        $payzen = Module::getInstanceByName('payzen');

        $sql = 'SELECT DISTINCT gl.`id_group`, gl.`name` FROM `'._DB_PREFIX_.'group_lang` AS gl
            INNER JOIN `'._DB_PREFIX_.'module_group` AS mg
            ON (
                gl.`id_group` = mg.`id_group`
                AND mg.`id_module` = '.(int)$payzen->id.'
                AND mg.`id_shop` = '.(int)$context->shop->id.'
            )
            WHERE gl.`id_lang` = '.(int)$context->language->id;

        return Db::getInstance()->executeS($sql);
    }
}
