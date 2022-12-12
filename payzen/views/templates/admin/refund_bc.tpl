{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<script>
    // Add refund checkboxes for PrestaShop < 1.7.7.
    $(function() {
        var payzenRefund = "{l s='Refund the buyer by Web Services with %s' sprintf='PayZen' mod='payzen'}";

        {if version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
            // Create PayZen partial refund checkbox.
            if ($('#doPartialRefundPayzen').length === 0) {
                var newCheckbox = '<br><input type="checkbox" id="doPartialRefundPayzen" name="doPartialRefundPayzen" class="button">'
                                + '<label for="doPartialRefundPayzen" style="float:none; font-weight:normal;">&nbsp;' + payzenRefund + '</label>';

                $(newCheckbox).insertAfter($('#generateDiscountRefund').next());
            }
    
            // Create PayZen standard refund checkbox.
            if ($('#doStandardRefundPayzen').length === 0) {
                var newCheckbox = '<span style="display: none;" class="payzen-standard-refund">\
                                    <br>\
                                    <input type="checkbox" id="doStandardRefundPayzen" name="doStandardRefundPayzen" class="button">\
                                    <label for="doStandardRefundPayzen" style="float:none; font-weight:normal;">&nbsp;' + payzenRefund + '</label>\
                                    </span>';

                $(newCheckbox).insertAfter($('#generateDiscount').next());
            }
        {else}
            // Create PayZen partial refund checkbox.
            if ($('#doPartialRefundPayzen').length === 0) {
                var newCheckbox = '<p class="checkbox payzen-partial-refund">\
                                       <label for="doPartialRefundPayzen">\
                                           <input type="checkbox" id="doPartialRefundPayzen" name="doPartialRefundPayzen" value="1">' +
                                               payzenRefund + '\
                                       </label>\
                                   </p>';

                $(newCheckbox).insertAfter($('#generateDiscountRefund').parent().parent());
            }

            // Create PayZen standard refund checkbox.
            if ($('#doStandardRefundPayzen').length === 0) {
                var newCheckbox = '<p class="checkbox payzen-standard-refund" style="display: none;">\
                                       <label for="doStandardRefundPayzen">\
                                           <input type="checkbox" id="doStandardRefundPayzen" name="doStandardRefundPayzen" value="1">' +
                                               payzenRefund + '\
                                       </label>\
                                    </p>';
                $(newCheckbox).insertAfter($('#generateDiscount').parent().parent());
            }
        {/if}
    });

    // Click on credit slip creation checkbox, standard payment.
    $(document).on('click', '#generateCreditSlip', function() {
        toggleStandardCheckboxDisplay();
    });

    // Click on voucher creation checkbox, standard payment.
    $(document).on('click', '#generateDiscount', function() {
        toggleStandardCheckboxDisplay();
    });

    // Click on voucher creation checkbox, partial payment.
    $(document).on('click', '#generateDiscountRefund', function() {
        if ($('#generateDiscountRefund').is(':checked')) {
            $('.payzen-partial-refund input').attr('disabled', 'disabled');
            $('.payzen-partial-refund').hide();
        } else {
            $('.payzen-partial-refund input').removeAttr('disabled');
            $('.payzen-partial-refund').show();
        }
    });

    // Do not allow refund if no credit slip is generated or if a voucher is generated.
    function toggleStandardCheckboxDisplay() {
        if ($('#generateCreditSlip').is(':checked')
            && ! $('#generateDiscount').is(':checked')) {
            $('#doStandardRefundPayzen').removeAttr('disabled');
            $('.payzen-standard-refund').show();
        } else {
            $('#doStandardRefundPayzen').attr('disabled', 'disabled');
            $('.payzen-standard-refund').hide();
        }
    }
</script>