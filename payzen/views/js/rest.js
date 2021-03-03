/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

/**
 * REST API JS tools.
 */

$(function() {
    $('#total_price').on('DOMSubtreeModified', function() {
        // If it's one-page checkout, do nothing.
        if (payzen.pageType === 'order-opc') {
            return;
        }

        var refreshData = 'refreshToken=1';
        if (typeof $('#payzen_payment_by_identifier') !== 'undefined') {
            refreshData += '&refreshIdentifierToken=1';
        }

        $.ajax({
            type: 'POST',
            url: decodeURIComponent(payzen.restUrl),
            async: false,
            cache: false,
            data: refreshData,
            success: function(json) {
                var response = JSON.parse(json);

                if (response.token) {
                    var token = response.token;
                    sessionStorage.setItem('payzenToken', response.token);

                    if (response.identifierToken) {
                        sessionStorage.setItem('payzenIdentifierToken', response.identifierToken);

                        if ($('#payzen_payment_by_identifier').val() == '1') {
                            token = response.identifierToken;
                        }
                    }

                    KR.setFormConfig({ formToken: token,  language: PAYZEN_LANGUAGE });
                }
            }
        });
    });

    setTimeout(function() {
        if ($('#cgv').length) {
            if ($('#cgv').is(':checked')) {
                $('.payzen .kr-payment-button').removeAttr('disabled');
            } else {
                // Unchecked CVG, disable payment button.
                $('.payzen .kr-payment-button').attr('disabled', 'disabled');
            }
        }

        $('.payzen .kr-payment-button').click(function(e) {
            $('.payzen .kr-form-error').html('');
        });

        payzenInitRestEvents();
    }, 0);
});

var PAYZEN_DEFAULT_MESSAGES = ['CLIENT_300', 'CLIENT_304', 'CLIENT_502', 'PSP_539']; // Use default messages for these errors.
var PAYZEN_RECOVERABLE_ERRORS = [
    'CLIENT_300', 'CLIENT_304', 'CLIENT_502',
    'PSP_539', 'CLIENT_001', 'CLIENT_101',
    'CLIENT_301', 'CLIENT_302', 'CLIENT_303',
    'PSP_003', 'PSP_108', 'ACQ_001'
];

var payzenInitRestEvents = function() {
    KR.onError(function(e) {
        $('.payzen .processing').css('display', 'none');
        $('#payzen_oneclick_payment_description').show();

        if ($('#payzen_standard').length && $('#payzen_standard').data('submitted')) {
            // PrestaShop 1.7 template.
            $('#payment-confirmation button').removeAttr('disabled');
            $('#payzen_standard').data('submitted', false);
        }

        // Not recoverable error, reload page after a while.
        if (PAYZEN_RECOVERABLE_ERRORS.indexOf(e.errorCode) === -1) {
            setTimeout(function() {
                window.location.reload();
            }, 4000);
        }

        var msg = '';
        if (PAYZEN_DEFAULT_MESSAGES.indexOf(e.errorCode) > -1) {
            msg = e.errorMessage;
            var endsWithDot = (msg.lastIndexOf('.') == (msg.length - 1) && msg.lastIndexOf('.') >= 0);

            msg += (endsWithDot ? '' : '.');
        } else {
            msg = payzenTranslate(e.errorCode);
        }

        $('.payzen .kr-form-error').html('<span style="color: red;"><span>' + msg + '</span></span>');
    });

    KR.onFocus(function(e) {
        $('.payzen .kr-form-error').html('');
    });

    KR.button.onClick(function() {
        // Hide oneclick description if it is present and is not popin mode.
        if ($('#payzen_oneclick_payment_description').length && ! $('.payzen .kr-popin-button').length) {
            $('#payzen_oneclick_payment_description').hide();
        }
    });
};

// Translate error message.
var payzenTranslate = function(code) {
    var lang = PAYZEN_LANGUAGE; // Global variable that contains current language.
    var messages = PAYZEN_ERROR_MESSAGES.hasOwnProperty(lang) ? PAYZEN_ERROR_MESSAGES[lang] : PAYZEN_ERROR_MESSAGES['en'];

    if (!messages.hasOwnProperty(code)) {
        var index = code.lastIndexOf('_');
        code = code.substring(0, index + 1) + '999';
    }

    return messages[code];
};

var PAYZEN_ERROR_MESSAGES = {
    fr: {
        CLIENT_001: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
        CLIENT_101: 'Le paiement est annulé.',
        CLIENT_301: 'Le numéro de carte est invalide. Vérifiez le numéro et essayez à nouveau.',
        CLIENT_302: 'La date d\'expiration est invalide. Vérifiez la date et essayez à nouveau.',
        CLIENT_303: 'Le code de sécurité CVV est invalide. Vérifiez le code et essayez à nouveau.',
        CLIENT_999: 'Une erreur technique est survenue. Merci de réessayer plus tard.',

        INT_999: 'Une erreur technique est survenue. Merci de réessayer plus tard.',

        PSP_003: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
        PSP_099: 'Trop de tentatives ont été effectuées. Merci de réessayer plus tard.',
        PSP_108: 'Le formulaire a expiré. Veuillez rafraîchir la page.',
        PSP_999: 'Une erreur est survenue durant le processus de paiement.',

        ACQ_001: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
        ACQ_999: 'Une erreur est survenue durant le processus de paiement.'
    },

    en: {
        CLIENT_001: 'Payment is refused. Try to pay with another card.',
        CLIENT_101: 'Payment is cancelled.',
        CLIENT_301: 'The card number is invalid. Please check the number and try again.',
        CLIENT_302: 'The expiration date is invalid. Please check the date and try again.',
        CLIENT_303: 'The card security code (CVV) is invalid. Please check the code and try again.',
        CLIENT_999: 'A technical error has occurred. Please try again later.',

        INT_999: 'A technical error has occurred. Please try again later.',

        PSP_003: 'Payment is refused. Try to pay with another card.',
        PSP_099: 'Too many attempts. Please try again later.',
        PSP_108: 'The form has expired. Please refresh the page.',
        PSP_999: 'An error has occurred during the payment process.',

        ACQ_001: 'Payment is refused. Try to pay with another card.',
        ACQ_999: 'An error has occurred during the payment process.'
    },

    de: {
        CLIENT_001: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
        CLIENT_101: 'Die Zahlung wird storniert.',
        CLIENT_301: 'Die Kartennummer ist ungültig. Bitte überprüfen Sie die Nummer und versuchen Sie es erneut.',
        CLIENT_302: 'Das Verfallsdatum ist ungültig. Bitte überprüfen Sie das Datum und versuchen Sie es erneut.',
        CLIENT_303: 'Der Kartenprüfnummer (CVC) ist ungültig. Bitte überprüfen Sie den Nummer und versuchen Sie es erneut.',
        CLIENT_999: 'Ein technischer Fehler ist aufgetreten. Bitte Versuchen Sie es später erneut.',

        INT_999: 'Ein technischer Fehler ist aufgetreten. Bitte Versuchen Sie es später erneut.',

        PSP_003: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
        PSP_099: 'Zu viele Versuche. Bitte Versuchen Sie es später erneut.',
        PSP_108: 'Das Formular ist abgelaufen. Bitte aktualisieren Sie die Seite.',
        PSP_999: 'Ein Fehler ist während dem Zahlungsvorgang unterlaufen.',

        ACQ_001: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
        ACQ_999: 'Ein Fehler ist während dem Zahlungsvorgang unterlaufen.'
    },

    es: {
        CLIENT_001: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
        CLIENT_101: 'Se cancela el pago.',
        CLIENT_301: 'El número de tarjeta no es válido. Por favor, compruebe el número y vuelva a intentarlo.',
        CLIENT_302: 'La fecha de caducidad no es válida. Por favor, compruebe la fecha y vuelva a intentarlo.',
        CLIENT_303: 'El código de seguridad de la tarjeta (CVV) no es válido. Por favor revise el código y vuelva a intentarlo.',
        CLIENT_999: 'Ha ocurrido un error técnico. Por favor, inténtelo de nuevo más tarde.',

        INT_999: 'Ha ocurrido un error técnico. Por favor, inténtelo de nuevo más tarde.',

        PSP_003: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
        PSP_099: 'Demasiados intentos. Por favor, inténtelo de nuevo más tarde.',
        PSP_108: 'El formulario ha expirado. Por favor, actualice la página.',
        PSP_999: 'Ocurrió un error en el proceso de pago.',

        ACQ_001: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
        ACQ_999: 'Ocurrió un error en el proceso de pago.'
    }
};
