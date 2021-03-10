const debug = process.env.NODE_ENV !== "production";
const TerserPlugin = require("terser-webpack-plugin");
const webpack = require("webpack");

const javascriptConfig = {
	context: __dirname,
	devtool: debug ? "inline-sourcemap" : false,
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
		],
	},
	plugins: [
		new webpack.ProvidePlugin({
			$: "jquery",
			jQuery: "jquery",
		}),
	],
	optimization: {
		minimizer: [],
	},
	performance: {
		hints: debug ? "warning" : false,
	},
};

// if (!debug) {
// 	const terserOptions = {
// 		terserOptions: {
// 			sourceMap: true,
// 			warnings: false,
// 			parse: {},
// 			compress: {},
// 			mangle: true,
// 			keep_fnames: true,
// 		},
// 	};

// 	javascriptConfig.optimization.minimizer.push(new TerserPlugin(terserOptions));
// }

module.exports = javascriptConfig;
