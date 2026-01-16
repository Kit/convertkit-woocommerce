/**
 * Abandoned Cart Script
 *
 * @author ConvertKit
 */

jQuery(document).ready(function ($) {
	// Set timeout flag to prevent multiple requests.
	let ckwcAbandonedCartTimeout = null;

	// Check and store initial email, if valid.
	const initialEmail = $('#billing_email').val();
	if (ckwcIsValidEmail(initialEmail)) {
		ckwcStoreEmailInSession(initialEmail);
	}

	// Listen for changes / input to the email field at checkout.
	$(document).on('input change', '#billing_email', function () {
		const email = $(this).val();

		// If email is valid, store in session.
		if (ckwcIsValidEmail(email)) {
			clearTimeout(ckwcAbandonedCartTimeout);
			ckwcAbandonedCartTimeout = setTimeout(function () {
				ckwcStoreEmailInSession(email);
			}, 600);
		}
	});
});

/**
 * Store the email in the session.
 *
 * @since   2.0.5
 *
 * @param {string} email
 */
function ckwcStoreEmailInSession(email) {
	(function ($) {
		$.post(ckwc_abandoned_cart.ajax_url, {
			action: 'ckwc_abandoned_cart_email',
			nonce: ckwc_abandoned_cart.nonce,
			email,
		});
	})(jQuery);
}

/**
 * Performs basic email validation.
 *
 * @since   2.0.5
 *
 * @param {string} email
 */
function ckwcIsValidEmail(email) {
	// Email is not valid if empty.
	if (!email) {
		return false;
	}

	// Test if email is a valid formed address.
	const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return regex.test(email);
}
