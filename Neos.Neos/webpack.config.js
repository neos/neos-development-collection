const TerserPlugin = require("terser-webpack-plugin");
const debug = process.env.NODE_ENV !== "production";

const buildConfig = (env) => {
	if (!["styles", "js"].includes(env)) {
		console.error(
			"Wrong webpack build parameter. Possible choices: 'js' or 'styles'."
		);
		return false;
	}

	const webpackConfig = require("./webpack." + env + ".config.js");
	if (!debug) {
		const terserOptions = {
			terserOptions: {
				sourceMap: true,
				warnings: false,
				parse: {},
				compress: {},
				mangle: true,
				keep_fnames: true,
			},
		};

		webpackConfig.optimization.minimizer.push(new TerserPlugin(terserOptions));
		return webpackConfig;
	}
};

module.exports = buildConfig;
