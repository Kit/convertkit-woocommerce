/**
 * Opt-in Block for Gutenberg
 *
 * @author ConvertKit
 */

/**
 * @typedef {import('@wordpress/blocks')} WPBlocks
 * @typedef {import('@wordpress/block-editor')} WPBlockEditor
 * @typedef {import('@wordpress/element').WPElement} WPElement
 * @typedef {import('@wordpress/components')} WPComponents
 * @typedef {import('@woocommerce/settings')} WCSettings
 */

/**
 * Registers the opt-in block in the Gutenberg editor.
 *
 * @param {WPBlocks}      blocks
 * @param {WPBlockEditor} editor
 * @param {WPElement}     element
 * @param {WPComponents}  components
 * @param {WCSettings}    settings
 */
(function (blocks, editor, element, components, settings) {
	// Define some constants for the various items we'll use.
	const el = element.createElement;
	const { registerBlockType } = blocks;
	const { InspectorControls } = editor;
	const { Button, CardBody, CheckboxControl } = components;

	// Get settings from WooCommerce; this calls the get_script_data()
	// PHP method.
	const { getSetting } = settings;
	const {
		enabled,
		displayOptIn,
		optInLabel,
		optInStatus,
		integrationSettingsURL,
		integrationSettingsButtonLabel,
	} = getSetting('ckwc_opt_in_data');

	// Define ConvertKit Logo as an SVG icon.
	const icon = el(
		'svg',
		{
			width: 172,
			height: 160,
			fill: 'none',
			viewBox: '0 0 172 160',
		},
		el('path', {
			d: 'M82.72 126.316c29.77 0 52.78-22.622 52.78-50.526 0-26.143-21.617-42.106-35.935-42.106-19.945 0-35.93 14.084-38.198 34.988-.418 3.856-3.476 7.09-7.355 7.061-6.423-.046-15.746-.1-21.658-.08-2.555.008-4.669-2.065-4.543-4.618.89-18.123 6.914-35.07 18.402-48.087C58.976 8.488 77.561 0 99.565 0c36.969 0 71.869 33.786 71.869 75.79 0 46.508-38.312 84.21-87.927 84.21-35.384 0-71.021-23.258-83.464-55.775a.702.702 0 01-.03-.377c.165-.962.494-1.841.818-2.707.471-1.258.931-2.488.864-3.906l-.215-4.529a5.523 5.523 0 013.18-5.263l1.798-.842a6.982 6.982 0 003.912-5.075 6.993 6.993 0 016.887-5.736c5.282 0 9.875 3.515 11.59 8.512 8.307 24.212 21.511 42.014 53.873 42.014z',
			fill: '#FB6970',
		})
	);

	// Define checkbox to display, if the integration is enabled and set to display
	// the opt in checkbox, based on the integration settings for the
	// checkbox checked state and its label.
	let checkbox = null;
	if (enabled && displayOptIn) {
		checkbox = el(CheckboxControl, {
			id: 'ckwc_opt_in',
			checked: optInStatus === 'checked' ? true : false,
			label: optInLabel,

			// Required so it cannot be interacted with in the editor.
			disabled: true,
		});
	}

	// Define Inspector Controls, which displays a sidebar in the editor with a button
	// linking to the integration's settings.
	const blockInspectorControls = el(
		InspectorControls,
		{},
		el(
			CardBody,
			{},
			el(Button, {
				href: integrationSettingsURL,
				text: integrationSettingsButtonLabel,
				target: '_new',
				isSecondary: true,
			})
		)
	);

	// Register Block.
	registerBlockType('ckwc/opt-in', {
		// Icon.
		icon,

		// Editor.
		edit() {
			// Return a render of the checkbox in the block editor as it would look
			// on the frontend checkout, based on the integration settings for the
			// checkbox checked state and its label.
			return el('div', {}, [blockInspectorControls, checkbox]);
		},

		// Output.
		save() {
			// This isn't used on the frontend, as WooCommerce Checkout blocks operate a bit differently from typical blocks.
			// See resources/frontend/opt-in-block.js to define the checkout output.
			return null;
		},
	});
})(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.element,
	window.wp.components,
	window.wc.wcSettings
);
