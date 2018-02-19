/**
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
 */

/**
 * Card data validation.
 */
function payzenCheckFields() {
    $('#payzen_standard input, #payzen_standard select').each(function() {
        $(this).removeClass('invalid');
    });

    var cardNumber = $('#payzen_card_number').val();
    if(cardNumber.length <= 0 || !(/^\d{13,19}$/.test(cardNumber))){
        $('#payzen_card_number').addClass('invalid');
    }

    var cvv = $('#payzen_cvv').val();
    if(cvv.length <= 0 || !(/^\d{3,4}$/.test(cvv))) {
        $('#payzen_cvv').addClass('invalid');
    }

    var currentTime  = new Date();
    var currentMonth = currentTime.getMonth() + 1;
    var currentYear  = currentTime.getFullYear();

    var expiryYear = $('select[name="payzen_expiry_year"] option:selected').val();
    if(expiryYear.length <= 0 || !(/^\d{4,4}$/.test(expiryYear)) || expiryYear < currentYear) {
        $('#payzen_expiry_year').addClass('invalid');
    }

    var expiryMonth = $('select[name="payzen_expiry_month"] option:selected').val();
    if(expiryMonth.length <= 0 || !(/^\d{1,2}$/.test(expiryMonth)) || (expiryYear == currentYear && expiryMonth < currentMonth)) {
        $('#payzen_expiry_month').addClass('invalid');
    }

    return ($('#payzen_standard').find('.invalid').length > 0) ? false : true;
}
