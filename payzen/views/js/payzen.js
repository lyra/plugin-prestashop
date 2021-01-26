/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

/**
 * Misc JavaScript functions.
 */

function payzenAddMultiOption(first) {
    if (first) {
        $('#payzen_multi_options_btn').hide();
        $('#payzen_multi_options_table').show();
    }

    var timestamp = new Date().getTime();

    var rowTpl = $('#payzen_multi_row_option').html();
    rowTpl = rowTpl.replace(/PAYZEN_MULTI_KEY/g, '' + timestamp);

    $(rowTpl).insertBefore('#payzen_multi_option_add');
}

function payzenDeleteMultiOption(key) {
    $('#payzen_multi_option_' + key).remove();

    if ($('#payzen_multi_options_table tbody tr').length === 1) {
        $('#payzen_multi_options_btn').show();
        $('#payzen_multi_options_table').hide();
        $('#payzen_multi_options_table').append("<input type=\"hidden\" id=\"PAYZEN_MULTI_OPTIONS\" name=\"PAYZEN_MULTI_OPTIONS\" value=\"\">");
    }
}

function payzenAddOneyOption(first, suffix = '') {
    if (first) {
        $('#payzen_oney' + suffix + '_options_btn').hide();
        $('#payzen_oney' + suffix + '_options_table').show();
    }

    var timestamp = new Date().getTime();
    var key = suffix != '' ? /PAYZEN_ONEY34_KEY/g : /PAYZEN_ONEY_KEY/g;
    var rowTpl = $('#payzen_oney' + suffix + '_row_option').html();
    rowTpl = rowTpl.replace(key, '' + timestamp);

    $(rowTpl).insertBefore('#payzen_oney' + suffix + '_option_add');
}

function payzenDeleteOneyOption(key, suffix = '') {
    $('#payzen_oney' + suffix + '_option_' + key).remove();

    if ($('#payzen_oney' + suffix + '_options_table tbody tr').length === 1) {
        $('#payzen_oney' + suffix + '_options_btn').show();
        $('#payzen_oney' + suffix + '_options_table').hide();
        $('#payzen_oney' + suffix + '_options_table').append("<input type=\"hidden\" id=\"PAYZEN_ONEY" + suffix + "_OPTIONS\" name=\"PAYZEN_ONEY" + suffix + "_OPTIONS\" value=\"\">");
    }
}

function payzenAdditionalOptionsToggle(legend) {
    var fieldset = $(legend).parent();

    $(legend).children('span').toggleClass('ui-icon-triangle-1-e ui-icon-triangle-1-s');
    fieldset.find('section').slideToggle();
}

function payzenCategoryTableVisibility() {
    var category = $('select#PAYZEN_COMMON_CATEGORY option:selected').val();

    if (category === 'CUSTOM_MAPPING') {
        $('.payzen_category_mapping').show();
        $('.payzen_category_mapping select').removeAttr('disabled');
    } else {
        $('.payzen_category_mapping').hide();
        $('.payzen_category_mapping select').attr('disabled', 'disabled');
    }
}

function payzenDeliveryTypeChanged(key) {
    var type = $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_type').val();

    if (type === 'RECLAIM_IN_SHOP') {
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_address').show();
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_zip').show();
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_city').show();
    } else {
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_address').val('');
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_zip').val('');
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_city').val('');

        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_address').hide();
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_zip').hide();
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_city').hide();
    }

    var speed = $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_speed').val();
    if (speed === 'PRIORITY') {
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_delay').show();
    } else {
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_delay').hide();
    }
}

function payzenDeliverySpeedChanged(key) {
    var speed = $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_speed').val();
    var type = $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_type').val();

    if (speed === 'PRIORITY') {
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_delay').show();
    } else {
        $('#PAYZEN_ONEY_SHIP_OPTIONS_' + key + '_delay').hide();
    }
}

function payzenRedirectChanged() {
    var redirect = $('select#PAYZEN_REDIRECT_ENABLED option:selected').val();

    if (redirect === 'True') {
        $('#payzen_redirect_settings').show();
        $('#payzen_redirect_settings select, #payzen_redirect_settings input').removeAttr('disabled');
    } else {
        $('#payzen_redirect_settings').hide();
        $('#payzen_redirect_settings select, #payzen_redirect_settings input').attr('disabled', 'disabled');
    }
}

function payzenOneyEnableOptionsChanged() {
    var enable = $('select#PAYZEN_ONEY_ENABLE_OPTIONS option:selected').val();

    if (enable === 'True') {
        $('#payzen_oney_options_settings').show();
        $('#payzen_oney_options_settings select, #payzen_oney_options_settings input').removeAttr('disabled');
    } else {
        $('#payzen_oney_options_settings').hide();
        $('#payzen_oney_options_settings select, #payzen_oney_options_settings input').attr('disabled', 'disabled');
    }
}

function payzenFullcbEnableOptionsChanged() {
    var enable = $('select#PAYZEN_FULLCB_ENABLE_OPTS option:selected').val();

    if (enable === 'True') {
        $('#payzen_fullcb_options_settings').show();
        $('#payzen_fullcb_options_settings select, #payzen_fullcb_options_settings input').removeAttr('disabled');
    } else {
        $('#payzen_fullcb_options_settings').hide();
        $('#payzen_fullcb_options_settings select, #payzen_fullcb_options_settings input').attr('disabled', 'disabled');
    }
}

function payzenHideOtherLanguage(id, name) {
    $('.translatable-field').hide();
    $('.lang-' + id).css('display', 'inline');

    $('.translation-btn button span').text(name);

    var id_old_language = id_language;
    id_language = id;

    if (id_old_language !== id) {
        changeEmployeeLanguage();
    }
}

function payzenAddOtherPaymentMeansOption(first) {
    if (first) {
        $('#payzen_other_payment_means_options_btn').hide();
        $('#payzen_other_payment_means_options_table').show();
        $('#PAYZEN_OTHER_PAYMENT_MEANS').remove();
    }

    var timestamp = new Date().getTime();

    var rowTpl = $('#payzen_other_payment_means_row_option').html();
    rowTpl = rowTpl.replace(/PAYZEN_OTHER_PAYMENT_SCRIPT_MEANS_KEY/g, '' + timestamp);

    $(rowTpl).insertBefore('#payzen_other_payment_means_option_add');
}

function payzenDeleteOtherPaymentMeansOption(key) {
    $('#payzen_other_payment_means_option_' + key).remove();

    if ($('#payzen_other_payment_means_options_table tbody tr').length === 1) {
        $('#payzen_other_payment_means_options_btn').show();
        $('#payzen_other_payment_means_options_table').hide();
        $('#payzen_other_payment_means_options_table').append("<input type=\"hidden\" id=\"PAYZEN_OTHER_PAYMENT_MEANS\" name=\"PAYZEN_OTHER_PAYMENT_MEANS\" value=\"\">");
    }
}

function payzenCountriesRestrictMenuDisplay(retrictCountriesPaymentId) {
    var countryRestrict = $('#' + retrictCountriesPaymentId).val();
    if (countryRestrict === '2') {
        $('#' + retrictCountriesPaymentId + '_MENU').show();
    } else {
        $('#' + retrictCountriesPaymentId + '_MENU').hide();
    }
}

function payzenOneClickMenuDisplay() {
    var oneClickPayment = $('#PAYZEN_STD_1_CLICK_PAYMENT').val();
    if (oneClickPayment == 'True') {
        $('#PAYZEN_STD_1_CLICK_MENU').show();
    } else {
        $('#PAYZEN_STD_1_CLICK_MENU').hide();
    }
}

function payzenDisplayMultiSelect(selectId) {
    $('#' + selectId).show();
    $('#' + selectId).focus();
    $('#LABEL_' + selectId).hide();
}

function payzenDisplayLabel(selectId, clickMessage) {
    $('#' + selectId).hide();
    $('#LABEL_' + selectId).show();
    $('#LABEL_' + selectId).text(payzenGetLabelText(selectId, clickMessage));
}

function payzenGetLabelText(selectId, clickMessage) {
    var select = document.getElementById(selectId);
    var labelText = '', option;

    for (var i = 0, len = select.options.length; i < len; i++) {
        option = select.options[i];

        if (option.selected) {
            labelText += option.text + ', ';
        }
    }

    labelText = labelText.substring(0, labelText.length - 2);
    if (!labelText) {
        labelText = clickMessage;
    }

    return labelText;
}