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
- Disbale payment submodules for unsupported currencies.
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
