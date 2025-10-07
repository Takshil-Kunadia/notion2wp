module.exports = {
	customSyntax: 'postcss-scss',
	extends: [
		'stylelint-config-standard-scss',
		'stylelint-config-prettier-scss',
	],
	rules: {
		'selector-class-pattern': null,
		'at-rule-no-unknown': null,
		'indentation': 'tab',
		'no-empty-source': null,
	},
	ignoreFiles: [
		'dist/**',
		'node_modules/**',
		'vendor/**',
	],
};
