1.21.0, 2025-05-28:
- Added possibility to display payment mean as order payment method on PrestaShop Back Office.
- Added possibility to select standard payment method by default on checkout page.

1.20.0, 2025-04-01:
- Bug fix: Use delivery address to retrieve country when rebuilding context.
- Bug fix: Fix payment method name displayed in orders list.
- Bug fix: Fix error related to display/hide payment method title on checkout page.
- Display the wallet used for payment if any in order details.
- Remove the iframe payment mode.
- Remove the payment by embedded fields legacy mode.
- Update list of supported payment means.
- Update list of supported currencies.

1.19.3, 2025-02-27:
- Bug fix: Fix error related to IPN management on uncompleted transaction cancellation.
- Bug fix: Fix error related to payment form token refresh when cart not modified.

1.19.2, 2025-02-18:
- Bug fix: Fix error related to undefined function str_starts_with with PHP 7.x versions and older.

1.19.1, 2025-02-12:
- Bug fix: Fix amount incoherence when paying with embedded fields and Smartform.
- Bug fix: Fix error related to customized status identifier verification.

1.19.0, 2024-11-20:
- [SEPA] Enable payment with SEPA for some plugin variants.
- Bug fix: Fix error related to hook actionEmailSendBefore.
- Update list of supported payment means.
- Update list of supported currencies.

1.18.0, 2024-09-02:
- [embedded] Use customer wallet functionality to manage payment by alias with embedded fields and Smartform.

1.17.4, 2024-07-01:
- [technical] Enabled some features by plugin variant.

1.17.3, 2024-06-21:
- [smartform] Bug fix: Fix error related to countries restriction for Smartform payment means filter.
- Bug fix: Fix error related to order status update on IPN when order is in final status.

1.17.2, 2024-05-30:
- Rollback using 500 http code on error for IPN calls.
- Bug fix: Fix error related to hook actionEmailSendBefore.

1.17.1, 2024-04-26:
- Bug fix: Fix contact support component link display in order details for PrestaShop 1.6.x.
- Bug Fix: Fix error on null id_order in hook actionEmailSendBefore.
- Added new transaction status REFUND_TO_RETRY.

1.17.0, 2024-02-27:
- [smartform] Display Smartform payment means as smart buttons.
- Bug fix: Fix messages generated at the end of the payment when customer service option is disabled.
- Bug fix: Skip the use of the class Media to add JS for PrestaShop 1.5.x.

1.16.6, 2024-02-14:
- Bug fix: Fix module custom order statuses creation during installation.

1.16.5, 2024-02-06:
- Bug fix: Fix payment in installments invoice for PrestaShop 1.7.x and higher.

1.16.4, 2024-01-25:
- Bug fix: ignore abandoned payments in IPN calls for already saved orders.
- Improve IPN errors management.
- Improve order statuses management.

1.16.3, 2023-12-26:
- [technical] Improve features management by plugin variant.

1.16.2, 2023-11-28:
- Bug fix: Fix order slip amount.
- [smartform] Send capture delay parameter for Smartform modes.
- [smartform] Added possibility to display/hide payment method title on checkout page for Smartform modes.
- Compatibility with PrestaShop 8.1.x and higher.
- Update list of supported payment means.
- Update list of supported currencies.

1.16.1, 2023-10-03:
- Bug fix: Fix PHP error related to undefined index.
- Bug fix: Fix order status upon refund when it is already refunded or canceled.
- Handle refund of a split payment.
- Update list of supported payment means.
- Update list of supported currencies.

1.16.0, 2023-07-06:
- [smartform] Smartform integration.
- [embedded] Send shopping cart content for payment with embedded fields.
- [embedded] Bug fix: Fix error related to validation mode.
- [oney] Added 10x 12x Oney and Paylater means of payment.
- [ancv] Bug fix: Fix error related to split payments with ANCV card.
- Compatibility of contact support component with PrestaShop 1.7.x and higher.
- Added Portuguese translation.
- Update list of supported payment means.
- Update list of supported currencies.

1.15.11, 2023-06-28:
- Bug fix: Fix refund on of an order with a discount voucher.

1.15.10, 2023-06-02:
- Bug fix: Fix error related to split payment.

1.15.9, 2023-04-21:
- Bug fix: Fix error related to products special price in iframe payment mode.

1.15.8, 2023-03-06:
- Compatibility with PrestaShop 8.x and PHP 8.

1.15.7, 2023-02-10:
- Added field for online module documentation.
- Added new transaction statuses PENDING and PARTIALLY_AUTHORISED.

1.15.6, 2022-12-12:
- [oney] Compatibility with SoColissimo Liberté relay points.
- Improve refund errors management.
- Improve refund button display for PrestaShop 1.5.x.

1.15.5, 2022-11-03:
- [embedded] Bug fix: Fix embedded payment fields displaying on PrestaShop 1.6.x versions and older.
- Minor fix.

1.15.4, 2022-09-23:
- [embedded] Bug fix: ignore abandoned payments in IPN calls for payment with embedded fields.
- Minor fix.

1.15.3, 2022-05-05:
- Update list of supported payment means.

1.15.2, 2022-04-22:
- [embedded] Bug fix: Improve the compatibility of embedded payment fields with the option "Move JavaScript to the end" in 1.6.x PrestaShop versions.
- Bug fix: Fix duplication of refund checkbox when returning articles.

1.15.1, 2021-11-16:
- Bug fix: Fix refund of orders with a discount voucher.
- [embedded] Bug fix: compatibility of embedded payment fields with the option "Move JavaScript to the end" in 1.6.x PrestaShop versions.

1.15.0, 2021-10-17:
- [embedded] Bug fix: Do not refresh payment page automatically after an unrecoverable error.
- Bug fix: Consider the "Refunded with PayZen" order status as a final status.
- Bug fix: Manage refund captured transactions in 1.7.x PrestaShop versions.
- Bug fix: Fix wrong PrestaShop order status for partially paid orders after a total refund or cancellation from gateway Back Office.
- Bug fix: Fix wrong PrestaShop order status after refund cancelling from gateway Back Office.
- [fullcb] Bug fix: Fix smarty error "Undefined index: FR" when country "France" is disabled.
- Added option to enable/disable customer service messages.
- [oney] Consider Chronopost Relay delivery method by sending selected relay point address to Oney 3x/4x.
- [oney] Deleted FacilyPay Oney submodule.
- [franfinance] Send information about shipping method for Franfinance payment method.
- Set conversion rate value in order payments.
- Manage currency conversion in refund process.

1.14.2, 2021-07-15:
- [embedded] Bug fix: Fix order status after a payment in installments with interests.
- Possibility to open support issue from command details in PrestaShop backend.
- Improve refund management.
- [SEPA] Fix 1-click payment with SEPA.
- Display authorized amount in order details when it is available.
- Display installments number in order details when it is available.

1.14.1, 2021-04-01:
- Bug fix: Do not refund payments when vouchers are genereated in PrestShop 1.6.x.
- Bug fix: Do not save payments with negative amount in PrestaShop 1.7.7.x.
- Update 3DS management option description.
- Improve REST API keys configuration display.
- Possibility to disable web services for order operations in PrestaShop Back Office.

1.14.0, 2021-03-03:
- Bug fix: Update order status after multiple payment tries or on cancellation from gateway Back Office.
- [franfinance] Added new FranFinance submodule.
- [sepa] Possibility to enable payment by alias with SEPA submodule.
- [ancv] Consider the new ANCV means of payment (CVCO - Chèque-Vacances Connect).
- [embedded] Add the pop-in choice to card data entry mode setting.
- [embedded] Possibility to customize the "Register my card" checkbox label for embedded payment mode.
- Possibility to configure REST API URLs.
- [alias] Check alias validity before proceeding to payment.
- Possibility to refund payments in installments.
- Possibility to refund/cancel payment online when the order is cancelled in PrestaShop Back Office.
- Possibility to add payment means dynamically in "Other payment means" section.
- Do not use vads_order_info\* gateway parameter (use vads_ext_info_\* instead).
- Possibility to open a support issue from the plugin configuration interface.
- Use the online payment means logos.
- Identify MOTO payments for orders from PrestaShop Back Office.
- Improve installation process (do not stop installation if PrestaShop errors are thrown).
- Possibility to upgrade the module from the PrestaShop backend.
- [technical] Load plugin classes dynamically.

1.13.8, 2020-12-10:
- Bug fix: Incorrectly formatted amount in order confirmation page.
- Bug fix: Error 500 due to obsolete function (get_magic_quotes_gpc) in PHP 7.4.
- Consider case of chargedbacks when refunding.
- Display warning message on payment in iframe mode enabling.

1.13.7, 2020-11-24:
- [embedded] Bug fix: Embedded payment fields not correctly displayed since the last gateway JS library delivery on PrestaShop 1.6.
- [embedded] Bug fix: Update token on minicart change on PrestaShop 1.6.
- Minor fix.

1.13.6, 2020-10-27:
- [embedded] Bug fix: Display 3DS result for REST API payments.
- Display warning message when only offline refund is possible.

1.13.5, 2020-10-05:
- Bug fix: Fix IPN management in multistore environment.
- Bug fix: Fix Order->total_real_paid value on payment cancellation.
- Bug fix: Possibility to refund orders offline if merchant did not configure REST API keys.
- [oney] Do not display payment installments for buyer (to avoid inconsistencies).

1.13.4, 2020-08-18:
- [embedded] Bug fix: Error due to strongAuthenticationState field renaming in REST token creation.
- [embedded] Minor code improve: use KR.openPopin() and KR.submit().
- [embedded] Improve payment with embedded fields button display in PrestaShop 1.6.x versions.
- Update payment means logos.

1.13.3, 2020-06-19:
- [embedded] Bug fix: Compatibility of payment with embedded fields with Internet Explorer 11.
- Bug fix: Possibility to make refunds for a payment with many attempts.
- [embedded] Bug fix: Fix JS error if payment token not created.
- Bug fix: Delete double invoice entry in ps_order_invoice_payment table.
- Improve refund payments feature.
- [oney] Phone numbers are mandatory for Oney payment method.

1.13.2, 2020-05-20:
- [embedded] Manage new metadata field format returned in REST API IPN.
- Bug fix: Fix sent data according to new Transaction/Update REST WS.
- Send PrestaShop username and IP as a comment on refund WS calls.
- Improve some plugin translations.
- Improve redirection to gateway page.

1.13.1, 2020-04-07:
- Restore compatibility with PHP v5.3.
- [embedded] Bug fix: Payment fields error relative to new JavaScript client library.

1.13.0, 2020-03-04:
- Bug fix: Fix amount issue relative to multiple partial refunds.
- Bug fix: Shipping costs not included in the refunded amount through the PrestaShop backend.
- [oney] Adding 3x 4x Oney means of payment as submodule.
- Improve payment statuses management.

1.12.1, 2020-02-04:
- [alias] Bug fix: card data was requested even if the buyer chose to use his registered means of payment.

1.12.0, 2020-01-30:
- Bug fix: 3DS result is not correctly saved in backend order details when using embedded payment fields.
- Bug fix: Fix theme config setting for iframe mode.
- [embedded] Added possibility to display REST API fields in pop-in mode.
- Possibility to make refunds for payments.
- Possibility to cancel payment in iframe mode.
- [alias] Added payment by token.
- [sepa] Save SEPA aliases separately from CB payment aliases.
- [sepa] Added possibility to configure SEPA submodule payment.
- [technical] Do not use vads\_order\_info2 gateway parameter.
- [oney] Added warning when delivery methods are updated.
- Removed feature data acquisition on merchant website.
- Possibility to not send shopping cart content when not mandatory.
- Restrict payment submodules to specific countries.

1.11.4, 2019-11-28:
- Bug fix: duplicate entry error on table ps\_message\_readed at the end of the payment.

1.11.3, 2019-11-12:
- Bug fix: currency and effective currency fields are inverted in REST API response.
- Bug fix: redirection form loaded from cache in some cases in iframe mode.
- Bug fix: URL error in iframe mode relative to slash at end of base URL.

1.11.2, 2019-07-31:
- Bug fix: JavaScript loaded but not executed in iframe mode (on some PrestaShop 1.7 themes).
- Bug fix: Minimum and maximum amounts are not considered if equal to zero in customer group amount restriction.
- Compatibility with PrestaShop 1.7.6 (fix fatal error on IPN call).
- Possibility to disable payment result display on order details using a flag within payzen.php file (on PrestaShop > 1.7.1.1).

1.11.1, 2019-06-21:
- Bug fix: compatibility of iframe mode with new 1.7.5.x PrestaShop versions.
- Bug fix: filter HTML special characters in REST API placeholders settings.
- Bug fix: Do not display an amount error for multi-carrier orders.
- Improve some configuration fields validation messages.
- Improve amount errors management.
- Added transaction UUID on order details.
- [fullcb] Added possibility to enable/disable Full CB payment options.
- Send products tax rate to payment gateway.
- Fix some plugin translations.
- Display the payment result as a private message on order details (on PrestaShop > 1.7.1.1).

1.11.0, 2019-01-21:
- [embedded] Added payment with embedded fields option using REST API.
- Possibility to propose other payment means by redirection.
- [conecs] Added CONECS payment means logos.
- Improve payment buttons interface.
- Display payment submodules logos in checkout page on PrestaShop 1.7.
- Optimize payment cancellation in iframe mode.

1.10.2, 2018-12-24:
- Fix new signature algorithm name (HMAC-SHA-256).
- Compatibility with PrestaShop 1.7.4.x versions (fix logs directory).
- Update payment means logos.
- [prodfaq] Fix notice about shifting the shop to production mode.
- Added Spanish translation.
- Improve iframe mode interface.

1.10.1, 2018-07-06:
- Bug fix: Fixed negative amount for order "total_paid_real" field on out of stock orders (PrestaShop 1.5 only).
- Bug fix: Deleted payment error message shown for buyer on out of stock orders (PrestaShop < 1.6.1 only).
- [shatwo] Enable HMAC-SHA-256 signature algorithm by default.
- Ignore spaces at the beginning and the end of certificates on return signature processing.

1.10.0, 2018-05-23:
- Bug fix: relative to JavaScript action of payment button on order validation page (with one page checkout only).
- Bug fix: fatal error when creating order from PrestaShop backend with Colissimo carrier enabled.
- Bug fix: use frontend shop name available under "Preferences > Store contacts".
- Bug fix: do not update order state from "Accepted payment" to "Payment error" when replaying IPN URL for orders with many attempts.
- Enable signature algorithm selection (SHA-1 or HMAC-SHA-256).
- Improve JS code redirecting to payment gateway to avoid possible conflicts with other modules.
- Re-order configuration options in submodules backend.
- Display all links to multilingual documentation files in module backend.
- Possibility to cancel payment in iframe mode.
- Possibility to configure 3D Secure by customer group.
- [technical] Manage enabled/disabled features by plugin variant.

1.9.0, 2017-10-16:
- Bug fix: send selected cards in payment in installments submodule.
- [oney] Bug fix: correct simulated FacilyPay Oney funding fees calculation.
- [oney] Bug fix: save failed and cancelled orders to avoid sending same order ID for FacilyPay Oney payments.
- Bug fix: error relative to missed checkout header and footer templates (PrestaShop 1.7 only).
- Bug fix: set negative tax amount to 0.
- Fix authentication error when shopping cart is shared between more than one shop.
- Added payment in pop-in using iframe mode.
- No longer use jQuery in redirection to gateway page to avoid compatibility errors.
- Disable payment button after redirect starts.
- Display payment in installments option label if only one option is available (PrestaShop 1.5 & 1.6).
- [oney] Consider Mondial Relay delivery method by sending selected relay point address to FacilyPay Oney.
- [oney] Consider DPD France Relais delivery method by sending selected relay point address to FacilyPay Oney.
- [oney] Consider SoColissimo delivery method by sending selected relay point address to FacilyPay Oney.
- Display card brand user choice if any in backend order details.
- [fullcb] Added Full CB submodule.
- Disable payment submodules for unsupported currencies.
- Add new "To validate payment" order state.
- Manage extended IPN calls (transaction validation, refund, modification and cancellation).
- Add "Delay" field and update "Rapidity" field in shipping options configuration.

1.8.2, 2017-05-01:
- [oney] Bug fix: order total really paid doubled for validated FacilyPay Oney payments in some PrestaShop versions.
- Use merchant server timezone to display payment times.

1.8.1, 2016-03-27:
- Bug fix: relative to default language for multilingual fields.
- [oney] Bug fix: relative to sending shipping data when using reclaim in shop carrier with FacilyPay Oney payments.
- Bug fix: default values lost for disabled fields after saving configuration.
- Bug fix: minor graphic bug in one page checkout mode (PrestaShop < 1.7).
- Bug fix: fix the potential error "Cannot redeclare class FileLoggerCore" when file logger is used by other installed modules.
- Update supported cards for payment in installments submodule.
- Use PHP 5.2 syntax to remain compatible with PrestaShop installations.
- Improve CSS and templates management.
- [oney] Possibility to configure FacilyPay Oney options inside merchant website.
- [oney] Do not create order if cancelled or failed FacilyPay Oney payment unless merchant enables "Order creation on failure" option.
- Sending customer cellular phone number if any.
- Possibility to manage empty cart before redirection to gateway in admin interface.

1.8.0, 2016-12-30:
- Bug fix: empty cart before redirection to gateway to avoid cart modification after submitting order. The cart is restored after cancelled / failed payment.
- Bug fix: relative to order total precision when displaying prices without taxes option is enabled for a customer group.
- Bug fix: in some themes, module interface in frontend is not correctly displayed.
- Displaying of payment installments in order details on PrestaShop backend.
- Show warning saying that online refund is not supported yet if merchant clicks on refund button in PrestaShop backend.
- Adding support of "Advanced EU compliance" module by implementing displayPaymentEU hook. In this case, only standard payment is available.
- Use of AFL license (instead of OSL) as other PrestaShop modules and themes.
- Save presentation date in payment order table (displayed in order view on PrestaShop backend).
- [oney] Send delivery address in vads_ship_to_street for FacilyPay Oney payments.
- Do not send cart data if too big cart (more than 85 products) unless it is mandatory.
- Take in account theme left and right column configurations.
- Module code refacting to improve performance and pass PrestaShop Addons validator.
- Improve management of regular expressions with UTF-8 special characters.
- Remove control over certificate format modified on the gateway.
- Compatibility with PrestaShop 1.7.x versions (imlementing paymentOptions hook).
- Perform data validation (customer address and card data) if necessary after payment mean selection.
- [oney] Give user the choice to enable / disable FacilyPay Oney payment in standard submodule.
- Make payment in installments option labels translatable.
- Possibility to choose payment types to allow in payment in installments submodule.
- Possibility to enable payment card selection on merchant website.

1.7.1, 2016-06-02:
- Improve of german and english translations.
- Adding german translations of default order states and submodules default titles.

1.7.0, 2015-12-09:
- Updating PayPal submodule logo.
- Displaying of appropriate title (according to choosen submodule) in the redirection page.
- Ability to define amount restrictions for client groups in all submodules.

1.6.0, 2015-10-09:
- Bug fix: mark module order messages as read and not assigned to avoid after-sales service alerts (since v 1.6.1 of PrestaShop).
- Ability to override "Capture delay" and "Validation mode" options for submodules.
- Adding PayPal submodule.
- Taking into account of pending verification status for PayPal payments.
- Creation of an order state for "Pending authorisation" payments.

1.5.0, 2015-07-16:
- [sofort] Adding SOFORT Banking submodule.
- [sofort,sepa] Adding custom state for pending funds transfer payments.
- Correction of IPN URL displayed in module backend (common URL for all stores in multistore mode).

1.4.0, 2015-06-09:
- Bug fix: relative to submodules availability in frontend.
- Bug fix: relative to max version specified in PrestaShop "ps_versions_compliancy" module property.
- Bug fix: do not send shipping_amount and insurance_amount variables to avoid amount consistency bug for PayPal payment.
- Bug fix: store ID missed when IPN URL called (in multistore mode).
- [ancv] Adding ANCV submodule.
- [sepa] Adding SEPA submodule.
- Reorganization of module settings display.
- Use of language dropdown (in PrestaShop v1.6.x) instead of flags for multilingual setting fields.
- Adding PT and DE translations for IPN responses.

1.3.2, 2015-04-17:
- Bug fix: do not send cart content (except when it is mandatory) to avoid gateway consistency checks.
- Bug fix: dot not post category mappings to server when using the same category for all products (in module backend configuration).
- Bug fix: relative to order amount rounding.
- Consideration of locales (of languages) like pt_BR for Brazilian portuguese.
- Checking php.ini limits (post_max_size et max_input_vars) when displaying module configuration in backend.
- [oney] Bug fix: dot not post shipping options to server when merchant has not FacilyPay Oney contract (in module backend configuration).

1.3.1, 2015-03-19:
- Bug fix: relative to checking PrestaShop (v 1.5.0.x) supported version at module installation.
- Bug fix: relative to card data entry on the PrestaShop frontend.
- Bug fix: relative to empty / unknown order state after return to shop.
- Impoving the module integration with specific merchants' themes.
- Improving module UI display on the payment mean selection page.
- Updating "Store ID" and "IPN URL" settings labels.
- Force module templates recompilation after (re)installation.
- Adapting module folders/files structure according to the new PrestaShop structure.
- Validation of the URL entered in "Shop URL" option of the module.

1.2f, 2014-06-26:
- Redirection to payment mean selection page (instead of cart page) after cancelled/failed payment (when possible).
- Update payment transaction information after IPN URL replay.

1.2e, 2014-06-13:
- Adding german translations.

1.2d, 2014-04-16:
- Compliance with PrestaShop 1.6 "HTML code reduction" option.
- Correction of a problem relative to cart retrieving from IPN URL call.

1.2c, 2014-04-11:
- Correction of a problem relative to order state when a product is out of stock.
- Checking if SSL enabled before activating card data entry on the PrestaShop frontend option.
- Ability to enable / disable logs.
- Adding 3DS authentication and certificate as order message.

1.2b, 2014-04-02:
- Bug fix: load all contents (images, css, ...) in HTTPS (if SSL enabled) to avoid browsers warning message.
- Compatibility with PrestaShop 1.6 version.
- Adding logs to file.

1.1, 2013-08-19:
- Managing single and mutiple payments in the same module.
- Ability to propose the card type choice on the PrestaShop frontend.
- Ability to propose the card data entry on the PrestaShop frontend (if option subscribed on the gateway).
- Ability to rename the payment method title in all languages available in PretsaShop.
- Adding theme configuration to customize payment page.

1.0, 2012-10-25:
- Module creation.