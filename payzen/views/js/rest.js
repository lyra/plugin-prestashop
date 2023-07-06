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

// Use default messages for these errors.
const PAYZEN_DEFAULT_MESSAGES = [
    'CLIENT_300', 'CLIENT_304', 'CLIENT_502', 'PSP_539'
];

// Errors requiring page reloading.
const PAYZEN_EXPIRY_ERRORS = [
    'PSP_108', 'PSP_136', 'PSP_649'
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

        // Reset PrestaShop confirmation button style on error.
        var conditions = $('#conditions_to_approve\\[terms-and-conditions\\]');
        if (conditions.is(":checked")) {
            $("#payment-confirmation button.btn").removeClass('disabled');
        }

        var msg = '';
        if (PAYZEN_DEFAULT_MESSAGES.indexOf(e.errorCode) > -1) {
            msg = e.errorMessage;
            var endsWithDot = (msg.lastIndexOf('.') == (msg.length - 1) && msg.lastIndexOf('.') >= 0);

            msg += (endsWithDot ? '' : '.');
        } else {
            msg = payzenTranslate(e.errorCode);
        }

        // Non recoverable errors, display a link to refresh the page.
        if (PAYZEN_EXPIRY_ERRORS.indexOf(e.errorCode) > -1) {
            msg += ' <a href="#" onclick="window.location.reload(); return false;">' + payzenTranslate('RELOAD_LINK') + '</a>';
        }

        $('.payzen .kr-form-error').html('<span style="color: red;"><span>' + msg + '</span></span>');
    });

    KR.onFocus(function(e) {
        $('.payzen .kr-form-error').html('');
    });

    KR.button.onClick(function() {
        // Hide oneclick description if it is present and is not popin mode.
        if ($('#payzen_oneclick_payment_description').length && ! $('.payzen .kr-popin-button').length && ! $('.payzen .kr-type-popin').length) {
            $('#payzen_oneclick_payment_description').hide();
        }
    });

    KR.onPopinClosed(function() {
        // Reset PrestaShop confirmation button style on popin close.
        var conditions = $('#conditions_to_approve\\[terms-and-conditions\\]');
        if (conditions.is(":checked")) {
            $("#payment-confirmation button.btn").removeClass('disabled');
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
        RELOAD_LINK: 'Veuillez rafraîchir la page.',
        CLIENT_001: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
        CLIENT_101: 'Le paiement est annulé.',
        CLIENT_301: 'Le numéro de carte est invalide. Vérifiez le numéro et essayez à nouveau.',
        CLIENT_302: 'La date d\'expiration est invalide. Vérifiez la date et essayez à nouveau.',
        CLIENT_303: 'Le code de sécurité CVV est invalide. Vérifiez le code et essayez à nouveau.',
        CLIENT_999: 'Une erreur technique est survenue. Merci de réessayer plus tard.',

        INT_999: 'Une erreur technique est survenue. Merci de réessayer plus tard.',

        PSP_003: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
        PSP_099: 'Trop de tentatives ont été effectuées. Merci de réessayer plus tard.',
        PSP_108: 'Le formulaire a expiré.',
        PSP_999: 'Une erreur est survenue durant le processus de paiement.',

        ACQ_001: 'Le paiement est refusé. Essayez de payer avec une autre carte.',
        ACQ_999: 'Une erreur est survenue durant le processus de paiement.'
    },

    en: {
        RELOAD_LINK: 'Please refresh the page.',
        CLIENT_001: 'Payment is refused. Try to pay with another card.',
        CLIENT_101: 'Payment is cancelled.',
        CLIENT_301: 'The card number is invalid. Please check the number and try again.',
        CLIENT_302: 'The expiration date is invalid. Please check the date and try again.',
        CLIENT_303: 'The card security code (CVV) is invalid. Please check the code and try again.',
        CLIENT_999: 'A technical error has occurred. Please try again later.',

        INT_999: 'A technical error has occurred. Please try again later.',

        PSP_003: 'Payment is refused. Try to pay with another card.',
        PSP_099: 'Too many attempts. Please try again later.',
        PSP_108: 'The form has expired.',
        PSP_999: 'An error has occurred during the payment process.',

        ACQ_001: 'Payment is refused. Try to pay with another card.',
        ACQ_999: 'An error has occurred during the payment process.'
    },

    de: {
        RELOAD_LINK: 'Bitte aktualisieren Sie die Seite.',
        CLIENT_001: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
        CLIENT_101: 'Die Zahlung wird storniert.',
        CLIENT_301: 'Die Kartennummer ist ungültig. Bitte überprüfen Sie die Nummer und versuchen Sie es erneut.',
        CLIENT_302: 'Das Verfallsdatum ist ungültig. Bitte überprüfen Sie das Datum und versuchen Sie es erneut.',
        CLIENT_303: 'Der Kartenprüfnummer (CVC) ist ungültig. Bitte überprüfen Sie den Nummer und versuchen Sie es erneut.',
        CLIENT_999: 'Ein technischer Fehler ist aufgetreten. Bitte Versuchen Sie es später erneut.',

        INT_999: 'Ein technischer Fehler ist aufgetreten. Bitte Versuchen Sie es später erneut.',

        PSP_003: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
        PSP_099: 'Zu viele Versuche. Bitte Versuchen Sie es später erneut.',
        PSP_108: 'Das Formular ist abgelaufen.',
        PSP_999: 'Ein Fehler ist während dem Zahlungsvorgang unterlaufen.',

        ACQ_001: 'Die Zahlung wird abgelehnt. Versuchen Sie, mit einer anderen Karte zu bezahlen.',
        ACQ_999: 'Ein Fehler ist während dem Zahlungsvorgang unterlaufen.'
    },

    es: {
        RELOAD_LINK: 'Por favor, actualice la página.',
        CLIENT_001: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
        CLIENT_101: 'Se cancela el pago.',
        CLIENT_301: 'El número de tarjeta no es válido. Por favor, compruebe el número y vuelva a intentarlo.',
        CLIENT_302: 'La fecha de caducidad no es válida. Por favor, compruebe la fecha y vuelva a intentarlo.',
        CLIENT_303: 'El código de seguridad de la tarjeta (CVV) no es válido. Por favor revise el código y vuelva a intentarlo.',
        CLIENT_999: 'Ha ocurrido un error técnico. Por favor, inténtelo de nuevo más tarde.',

        INT_999: 'Ha ocurrido un error técnico. Por favor, inténtelo de nuevo más tarde.',

        PSP_003: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
        PSP_099: 'Demasiados intentos. Por favor, inténtelo de nuevo más tarde.',
        PSP_108: 'El formulario ha expirado.',
        PSP_999: 'Ocurrió un error en el proceso de pago.',

        ACQ_001: 'El pago es rechazado. Intenta pagar con otra tarjeta.',
        ACQ_999: 'Ocurrió un error en el proceso de pago.'
    },

    br: {
        RELOAD_LINK: 'Por favor, atualize a página.',
        CLIENT_001: 'O pagamento é rejeitado. Tente pagar com outro cartão.',
        CLIENT_101: 'O pagamento é cancelado.',
        CLIENT_301: 'O número do cartão é inválido. Por favor, cheque o número e tente novamente.',
        CLIENT_302: 'A data de expiração é inválida. Verifique a data e tente novamente.',
        CLIENT_303: 'O código de segurança do cartão (CVV) é inválido. Verifique o código e tente novamente.',
        CLIENT_999: 'Ocorreu um erro técnico. Por favor, tente novamente mais tarde.',

        INT_999: 'Ocorreu um erro técnico. Por favor, tente novamente mais tarde.',

        PSP_003: 'O pagamento é rejeitado. Tente pagar com outro cartão.',
        PSP_099: 'Muitas tentativas. Por favor, tente novamente mais tarde.',
        PSP_108: 'O formulário expirou.',
        PSP_999: 'Ocorreu um erro no processo de pagamento.',

        ACQ_001: 'O pagamento é rejeitado. Tente pagar com outro cartão.',
        ACQ_999: 'Ocorreu um erro no processo de pagamento.'
    },

    pt: {
        RELOAD_LINK: 'Por favor, atualize a página.',
        CLIENT_001: 'O pagamento é rejeitado. Tente pagar com outro cartão.',
        CLIENT_101: 'O pagamento é cancelado.',
        CLIENT_301: 'O número do cartão é inválido. Por favor, cheque o número e tente novamente.',
        CLIENT_302: 'A data de expiração é inválida. Verifique a data e tente novamente.',
        CLIENT_303: 'O código de segurança do cartão (CVV) é inválido. Verifique o código e tente novamente.',
        CLIENT_999: 'Ocorreu um erro técnico. Por favor, tente novamente mais tarde.',

        INT_999: 'Ocorreu um erro técnico. Por favor, tente novamente mais tarde.',

        PSP_003: 'O pagamento é rejeitado. Tente pagar com outro cartão.',
        PSP_099: 'Muitas tentativas. Por favor, tente novamente mais tarde.',
        PSP_108: 'O formulário expirou.',
        PSP_999: 'Ocorreu um erro no processo de pagamento.',

        ACQ_001: 'O pagamento é rejeitado. Tente pagar com outro cartão.',
        ACQ_999: 'Ocorreu um erro no processo de pagamento.'
    }
};
