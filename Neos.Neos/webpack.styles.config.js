const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const TerserPlugin = require("terser-webpack-plugin");
const IgnoreEmitPlugin = require("ignore-emit-webpack-plugin");

const stylesConfig = {
	context: __dirname,
	devtool: "source-map",
	entry: {
		Main: ["./Resources/Private/Styles/Neos.scss"],
		Lite: ["./Resources/Private/Styles/Lite.scss"],
		Minimal: ["./Resources/Private/Styles/Minimal.scss"],
		Login: ["./Resources/Private/Styles/Login.scss"],
		Error: ["./Resources/Private/Styles/Error.scss"],
		RawContentMode: ["./Resources/Private/Styles/RawContentMode.scss"],
		Welcome: ["./Resources/Private/Styles/Welcome.scss"],
	},
	output: {
		path: __dirname + "/Resources/Public/Styles",
		filename: "[name].js",
	},
	resolve: {
		extensions: [".css", ".scss", ".sass"],
	},
	module: {
		rules: [
			{
				test: /\.(png|gif|jpe?g|svg)$/i,
				include: __dirname + "/Resources/Public/Images/",
				use: [
					{
						loader: "url-loader",
						options: {
							name: "images/[name].[ext]",
							publicPath: "../",
							limit: 30000,
						},
					},
				],
			},
			{
				test: /\.(eot|ttf|woff|woff2)$/,
				use: [
					{
						loader: "file-loader",
						options: {
							name: "[name].[ext]",
							outputPath: "../Fonts/",
						},
					},
				],
			},
			{
				test: /\.scss$/,
				use: [MiniCssExtractPlugin.loader, "css-loader", "sass-loader"],
			},
		],
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: "[name].css",
		}),
		new IgnoreEmitPlugin(/([a-zA-Z0-9\s_\\.\-\(\):])+(.js|.map.js)$/),
	],
	optimization: {
		minimizer: [
			new TerserPlugin({
				terserOptions: {
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

module.exports = stylesConfig;
