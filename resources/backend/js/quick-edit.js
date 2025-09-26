/**
 * Quick Edit
 *
 * @package
 * @author ConvertKit
 */

/**
 * Populates Quick Edit fields for this Plugin with the values output
 * in the `add_inline_data` WordPress action when the user clicks
 * a Quick Edit link in a Product WP_List_Table.
 *
 * WordPress' built in Quick Edit functionality does not do this automatically
 * for Plugins that register settings, which is why we have this code here.
 *
 * @since 	1.4.8
 */
jQuery(document).ready(function ($) {
	const ckwcInlineEditPost = inlineEditPost.edit;

	// Extend WordPress' quick edit function.
	inlineEditPost.edit = function (id) {
		// Merge arguments from original function.
		ckwcInlineEditPost.apply(this, arguments);

		// Get Post ID.
		if (typeof id === 'object') {
			id = parseInt(this.getId(id));
		}

		// Move Plugin's Quick Edit fields container into the inline editor, if they don't yet exist.
		// This only needs to be done once.
		if (
			$(
				'.quick-edit-row .inline-edit-wrapper fieldset.inline-edit-col-left:first-child #ckwc-quick-edit'
			).length === 0
		) {
			$('#ckwc-quick-edit')
				.appendTo(
					'.quick-edit-row .inline-edit-wrapper fieldset.inline-edit-col-left:first-child'
				)
				.show();
		}

		// Iterate through any ConvertKit inline data, assigning values to Quick Edit fields.
		$('.ckwc', $('#inline_' + id)).each(function () {
			// Assign the setting's value to the setting's Quick Edit field.
			$(
				'#ckwc-quick-edit select[name="' +
					$(this).data('setting') +
					'"]'
			).val($(this).data('value'));
		});
	};
});
