import js from "@eslint/js";

export default [
	{
		ignores: ["dist/**", "vendor/**", "node_modules/**"],
	},
	js.configs.recommended,
	{
		files: ["**/*.js"],
		languageOptions: {
			globals: {
				console: "readonly",
				document: "readonly",
				window: "readonly",
				process: "readonly",
			},
		},
		rules: {
			"no-unused-vars": "warn",
			"no-undef": "warn",
		},
	},
];
