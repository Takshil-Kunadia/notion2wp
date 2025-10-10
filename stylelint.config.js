module.exports = {
	customSyntax: 'postcss-scss',
	extends: [
		'stylelint-config-standard-scss',
		'stylelint-config-prettier-scss',
	],
	plugins: [
		'@stylistic/stylelint-plugin',
	],
	rules: {
		'selector-class-pattern': null,
		'at-rule-no-unknown': null,
		'@stylistic/indentation': 'tab',
		'no-empty-source': null,
	},
	ignoreFiles: [
		'dist/**',
		'node_modules/**',
		'vendor/**',
	],
};
