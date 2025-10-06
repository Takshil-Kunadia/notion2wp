import js from '@eslint/js';
import pluginReact from 'eslint-plugin-react';
import pluginReactHooks from 'eslint-plugin-react-hooks';

export default [
	{
		ignores: [ 'dist/**', 'vendor/**', 'node_modules/**' ],
	},
	js.configs.recommended,
	{
		files: [ '**/*.js', '**/*.jsx' ],
		languageOptions: {
			ecmaVersion: 2020,
			sourceType: 'module',
			globals: {
				window: 'readonly',
				document: 'readonly',
				console: 'readonly',
				fetch: 'readonly',
				wp: 'readonly',
			},
			parserOptions: {
				ecmaFeatures: {
					jsx: true,
				},
			},
		},
		plugins: {
			react: pluginReact,
			'react-hooks': pluginReactHooks,
		},
		rules: {
			// General JS rules
			'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
			'no-console': 'off',
			'no-debugger': 'warn',

			// React-specific rules
			'react/prop-types': 'off',
			'react/react-in-jsx-scope': 'off',
			'react/jsx-uses-react': 'off',
			'react/jsx-uses-vars': 'error',

			// React hooks
			'react-hooks/rules-of-hooks': 'error',
			'react-hooks/exhaustive-deps': 'warn',

			// Style
			semi: ['error', 'always'],
			quotes: ['error', 'single'],
			indent: ['error', 'tab'],
			'comma-dangle': ['error', 'always-multiline'],
		},
		settings: {
			react: {
				version: 'detect',
			},
		},
	},

	// Node environment (for webpack, config scripts, etc.)
	{
		files: ['webpack.config.js', 'scripts/**/*.js'],
		languageOptions: {
			sourceType: 'module',
			globals: {
				require: 'readonly',
				module: 'readonly',
				__dirname: 'readonly',
				process: 'readonly',
			},
		},
		rules: {
			'no-undef': 'off',
			'comma-dangle': 'off',
		},
	},
];
