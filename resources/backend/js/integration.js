/**
 * Displays or hides settings in the UI, depending on which settings are enabled
 * or disabled.
 *
 * @package
 * @author ConvertKit
 */

let ckwcSettings = {
	enabled: false,
	display_opt_in: false,
	send_purchases: false,
	abandoned_cart: false,
};

/**
 * Displays or hides settings in the UI, depending on which settings are enabled
 * or disabled.
 *
 * @since 	1.4.2
 */
jQuery(document).ready(function ($) {
	// Bail if we're not viewing the Integration Settings,
	// which can be determined by WooCommerce's hidden input field 'section'.
	if ($('input[name="section"]').val() !== 'ckwc') {
		return;
	}

	// Update settings.
	ckwcSettings = {
		enabled: $('input[name="woocommerce_ckwc_enabled"]').prop('checked'),
		display_opt_in: $('input[name="woocommerce_ckwc_display_opt_in"]').prop(
			'checked'
		),
		send_purchases: $('input[name="woocommerce_ckwc_send_purchases"]').prop(
			'checked'
		),
		abandoned_cart: $('input[name="woocommerce_ckwc_abandoned_cart"]').prop(
			'checked'
		),
	};

	// Refresh UI.
	ckwcRefreshUI();

	// Update settings and refresh UI when a setting is changed.
	$('input[type=checkbox]').on('change', function () {
		ckwcSettings[$(this).attr('id').replace('woocommerce_ckwc_', '')] =
			$(this).prop('checked');

		ckwcRefreshUI();
	});

	// Sync Past Orders when button pressed and confirmed.
	$('a#ckwc_sync_past_orders').on('click', function (e) {
		// Confirm that the user wants to sync past orders.
		const result = confirm(
			ckwc_integration.sync_past_orders_confirmation_message
		);

		// Prevent clicking the link if the user cancels.
		if (!result) {
			e.preventDefault();
			return false;
		}
	});
});

/**
 * Shows all table rows on the integration settings screen, and then hides
 * table rows related to a setting, if that setting is disabled.
 *
 * @since 	1.4.2
 */
function ckwcRefreshUI() {
	(function ($) {
		// Show all rows.
		$('table.form-table tr').each(function () {
			$(this).show();
		});

		// Iterate through settings.
		for (const setting in ckwcSettings) {
			if (!ckwcSettings[setting]) {
				$('table.form-table tr').each(function () {
					// Skip if this table row is for the setting we've just checked/unchecked.
					if (
						$('[id="woocommerce_ckwc_' + setting + '"]', $(this))
							.length > 0
					) {
						return;
					}

					// Hide this row if the input, select, link or span element within the row has the CSS class of the setting name.
					if (
						$('input, select, a, span', $(this)).hasClass(setting)
					) {
						$(this).hide();
					}
				});
			}
		}
	})(jQuery);
}
