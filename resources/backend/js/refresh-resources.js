/**
 * Refresh Resources
 *
 * @author ConvertKit
 */

/**
 * Refreshes sequences, forms and tags when the Refresh button is clicked.
 *
 * @since 	1.4.8
 */
jQuery(document).ready(function ($) {
	$('button.ckwc-refresh-resources').on('click', function (e) {
		e.preventDefault();

		// Fetch some DOM elements.
		const button = this,
			field = $(button).data('field');

		// Disable button.
		$(button).prop('disabled', true).addClass('is-refreshing');

		// Perform AJAX request to refresh resource.
		$.ajax({
			type: 'POST',
			data: {
				action: 'ckwc_admin_refresh_resources',
				nonce: ckwc_admin_refresh_resources.nonce,
			},
			url: ckwc_admin_refresh_resources.ajaxurl,
			success(response) {
				if (ckwc_admin_refresh_resources.debug) {
					console.log(response);
				}

				// Remove any existing error notices that might be displayed.
				ckwcRefreshResourcesRemoveNotices();

				// Show an error if the request wasn't successful.
				if (!response.success) {
					// Show error notice.
					ckwcRefreshResourcesOutputErrorNotice(response.data);

					// Enable button.
					$(button).prop('disabled', false).removeClass('is-refreshing');

					return;
				}

				// Get currently selected option.
				const selectedOption = $(field).val();

				// Remove existing select options.
				$('option', $(field)).each(function () {
					// Skip if data-preserve-on-refresh is specified, as this means we want to keep this specific option.
					// This will be present on the 'None' and 'Default' options.
					if (
						typeof $(this).data('preserve-on-refresh') !==
						'undefined'
					) {
						return;
					}

					// Remove this option.
					$(this).remove();
				});

				// Populate each resource type with select options from response data into
				// the application option group.
				for (const [resource, resources] of Object.entries(
					response.data
				)) {
					// resource = forms, sequences, tags.
					// resoruces = array of resources.
					resources.forEach(function (item) {
						const value =
							$('optgroup#ckwc-' + resource, $(field)).data(
								'option-value-prefix'
							) + item.id;
						$('optgroup#ckwc-' + resource, $(field)).append(
							new Option(
								item.name,
								value,
								false,
								selectedOption === value ? true : false
							)
						);
					});
				}

				// Reload Select2 instances, so that they reflect the changes made.
				$('.ckwc-select2').select2();

				// Enable button.
				$(button).prop('disabled', false).removeClass('is-refreshing');
			},
		}).fail(function (response) {
			if (ckwc_admin_refresh_resources.debug) {
				console.log(response);
			}

			// Remove any existing error notices that might be displayed.
			ckwcRefreshResourcesRemoveNotices();

			// Show error notice.
			ckwcRefreshResourcesOutputErrorNotice(
				'Kit for WooCommerce: ' +
					response.status +
					' ' +
					response.statusText
			);

			// Enable button.
			$(button).prop('disabled', false).removeClass('is-refreshing');
		});
	});
});

/**
 * Removes any existing ConvertKit WordPress style error notices.
 *
 * @since 	1.4.9
 */
function ckwcRefreshResourcesRemoveNotices() {
	(function ($) {
		$('div.ckwc-error').remove();
	})(jQuery);
}

/**
 * Removes any existing ConvertKit WordPress style error notices, before outputting
 * an error notice.
 *
 * @since 	1.4.9
 *
 * @param {string} message Error message to display.
 */
function ckwcRefreshResourcesOutputErrorNotice(message) {
	(function ($) {
		// Show a WordPress style error notice.
		$('hr.wp-header-end').after(
			'<div id="message" class="error ckwc-error notice is-dismissible"><p>' +
				message +
				'</p></div>'
		);

		// Notify WordPress that a new dismissible notification exists, triggering WordPress' makeNoticesDismissible() function,
		// which adds a dismiss button and binds necessary events to hide the notification.
		// We can't directly call makeNoticesDismissible(), as its minified function name will be different.
		$(document).trigger('wp-updates-notice-added');
	})(jQuery);
}
