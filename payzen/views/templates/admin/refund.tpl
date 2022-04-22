{**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for PrestaShop. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *}

<script>
    // Add refund checkboxes for PrestaShop >= 1.7.7.
    $(function() {
        var payzenRefund = "{l s='Refund the buyer by Web Services with %s' sprintf='PayZen' mod='payzen'}";

        // Create PayZen partial refund checkbox.
        if ($('#doPartialRefundPayzen').length === 0) {
            var newCheckbox = '\
                    <div class="cancel-product-element payzen-refund payzen-partial-refund form-group" style="display: block;">\
                        <div class="checkbox">\
                            <div class="md-checkbox md-checkbox-inline">\
                                <label>\
                                    <input type="checkbox" id="doPartialRefundPayzen" name="doPartialRefundPayzen" material_design="material_design" value="1">\
                                      <i class="md-checkbox-control"></i>' +
                                        payzenRefund + '\
                                </label>\
                            </div>\
                        </div>\
                    </div>';

                $(newCheckbox).insertAfter('.refund-checkboxes-container .refund-voucher');
            }

            // Create PayZen standard refund checkbox.
            if ($('#doStandardRefundPayzen').length === 0) {
                var newCheckbox = '\
                    <div class="cancel-product-element payzen-refund payzen-standard-refund form-group" style="display: block;">\
                        <div class="checkbox">\
                            <div class="md-checkbox md-checkbox-inline">\
                                <label>\
                                    <input type="checkbox" id="doStandardRefundPayzen" name="doStandardRefundPayzen" material_design="material_design" value="1">\
                                      <i class="md-checkbox-control"></i>' +
                                        payzenRefund + '\
                                </label>\
                            </div>\
                        </div>\
                    </div>';

                $(newCheckbox).insertAfter('.refund-checkboxes-container .refund-voucher');
            }
        });

        $(document).on('click', '.partial-refund-display', function() {
            $('.payzen-standard-refund').hide();
        });

        $(document).on('click', '.standard-refund-display', function() {
            $('.payzen-partial-refund').hide();
        });

        $(document).on('click', '.return-product-display', function() {
            $('.payzen-partial-refund').hide();
        });

        // Click on credit slip creation checkbox.
        $(document).on('click', '#cancel_product_credit_slip', function() {
            toggleCheckboxDisplay();
        });

        // Click on voucher creation checkbox.
        $(document).on('click', '#cancel_product_voucher', function() {
            toggleCheckboxDisplay();
        });

        // Do not allow refund if no credit slip is generated or if a voucher is generated.
        function toggleCheckboxDisplay() {
            $('.payzen-refund input').attr('disabled', 'disabled');
            $('.payzen-refund').hide();

            if ($('#cancel_product_credit_slip').is(':checked')
                && ! $('#cancel_product_voucher').is(':checked')) {
                if ($('.shipping-refund').is(":visible") == true) {
                    $('#doStandardRefundPayzen').removeAttr('disabled');
                    $('.payzen-standard-refund').show();
                } else {
                    $('#doPartialRefundPayzen').removeAttr('disabled');
                    $('.payzen-partial-refund').show();
                }
            }
        }
</script>