module.exports = {
	extends: 'plugin:@wordpress/eslint-plugin/recommended',
	rules: {
		// Turn off specific rules
		camelcase: 'off',
		'no-undef': 'off',
		'no-console': 'off',
		'no-alert': 'off',
	},
};
