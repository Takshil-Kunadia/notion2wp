const path = require('path');

module.exports = {
	entry: './src/index.js',
	output: {
		filename: 'index.js',
		path: path.resolve(__dirname, 'dist'),
	},
	mode: 'development',
	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							'@babel/preset-env',
							'@babel/preset-react'  // <-- enables JSX
						]
					}
				}
			}
		]
	},
	resolve: {
		extensions: ['.js', '.jsx', '.json']
	},
	externals: {
		'@wordpress/element': ['wp', 'element']
	}
};
