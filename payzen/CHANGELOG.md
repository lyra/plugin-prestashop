1.4.7, 2018-08-02:
- Specific message if IPN not worked and shop is in maintenance mode.
- Improve configuration interface.
- Remove obsolete parameters (Extra GET parameters, extra POST parameters, Return URL).
- Enable signature algorithm selection (SHA-1 or HMAC-SHA-256).
- Added German translations.
- [technical]Manage features by domain.

1.4f, 2013-04-22:
- Update selective 3DS feature: disable 3DS only if order amount is under entered amount.

1.4e, 2013-03-14:
- [multi]Bug fix: Signature error generated when one-time payment and payment in installments plugins do not run in the same context mode.
- Displayed notice about going into production mode (only in test mode).

1.4d, 2012-06-20:
- Bug fix: regression about taking in account characters « and » when processing signature.

1.4c, 2012-05-03:
- Delete feature "buyer disconnected when returning to shop using browser buttons".
- Displayed error message when amount is inconsistent on return to shop.
- Save order with an error status when amount is inconsistent (between order and payment).

1.4b, 2012-04-20:
- Improve compatibility with Smarty v2.

1.4a, 2012-04-18:
- Compatibility with cookies that end with a suffix in field vads\_return\_get\_params.

1.4, 2012-04-13:
- Added setting to manage 3DS authentication according to order amount.
- Disconnect buyer when returning to shop using browser buttons.

1.3b, 2012-04-06:
- Consider One Page Checkout option.
- Take in account currencies without cents.
- Use real delivery address when So Colissimo is used as carrier.
- Removed unused language parameter on IPN call.

1.3a, 2011-12-14:
- Bug fix: take in account characters « and » when processing signature.

1.3, 2011-07-27:
- Added option to select order failure management (save order an go back to history or go back to order retry page).

1.2d, 2011-06-14:
- Manage secure key when calling validateOrder() method.
- Avoid warning on order confirmation page : "Warning : the secure key is empty, check your payment account before validation."

1.2c, 2011-05-02:
- Bug fix: Manage vads_available_languages field to accept empty string.

1.2b, 2011-04-28:
- Manage magic quotes if enabled on merchant server to avoid signature errors.

1.2a, 2011-04-19:
- Bug fix: Accept dates between 8:00 PM and 8:59 PM when validating payment date.

1.2, 2011-04-18:
- [multi]Updated plugin for compatibility with payment in installments plugin.
- Some minor improvements.

1.1, 2011-02-18:
- Initial stable release of PayZen plugin.
