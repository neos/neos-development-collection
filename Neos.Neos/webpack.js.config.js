const TerserPlugin = require("terser-webpack-plugin");
const webpack = require("webpack");

const javascriptConfig = {
	context: __dirname,
	devtool: "source-map",
	entry: {
		Main: ["./Resources/Public/JavaScript/index.js"],
	},
	output: {
		path: __dirname + "/Resources/Public/JavaScript",
		filename: "[name].min.js",
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: "babel-loader",
				},
			},
			{
				test: /\.ts(x)?$/,
				use: {
					loader: "ts-loader",
				},
				exclude: /node_modules/,
			},
		],
	},
	resolve: {
		extensions: [".tsx", ".ts", ".js"],
	},
	plugins: [
		// @deprecated Will remove jQuery support with neos 8.0
		new webpack.ProvidePlugin({
			$: "jquery",
			jQuery: "jquery",
		}),
	],
	optimization: {
		minimizer: [
			new TerserPlugin({
				terserOptions: {
					sourceMap: true,
					warnings: false,
					parse: {},
					compress: {},
					mangle: true,
					keep_fnames: true,
				},
			}),
		],
	},
	performance: {
		hints: "warning",
	},
};

module.exports = javascriptConfig;
