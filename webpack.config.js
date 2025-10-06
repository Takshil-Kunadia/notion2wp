import path from 'path';

export default {
	entry: './src/index.js',
	output: {
		filename: 'index.js',
		path: path.resolve(__dirname, 'dist'),
	},
	mode: 'development',
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: 'babel-loader',
			}
		]
	},
	resolve: {
		extensions: ['.js']
	},
	externals: {
		'@wordpress/element': ['wp', 'element']
	}
};
