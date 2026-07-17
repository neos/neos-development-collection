const esbuild = require("esbuild");
const path = require("path");
const fs = require("fs");
const { sassPlugin } = require("esbuild-sass-plugin");

const projectRoot = __dirname;
const isWatch = process.argv.includes("--watch");

const moveMainCssPlugin = {
	name: "move-main-css",
	setup(build) {
		build.onEnd(() => {
			const fromPath = path.join(
				projectRoot,
				"Resources/Public/JavaScript/Main.css",
			);
			const fromMapPath = path.join(
				projectRoot,
				"Resources/Public/JavaScript/Main.css.map",
			);
			const toPath = path.join(projectRoot, "Resources/Public/Styles/Main.css");
			const toMapPath = path.join(
				projectRoot,
				"Resources/Public/Styles/Main.css.map",
			);

			if (fs.existsSync(fromPath)) {
				fs.mkdirSync(path.dirname(toPath), { recursive: true });
				fs.renameSync(fromPath, toPath);
			}

			if (fs.existsSync(fromMapPath)) {
				fs.mkdirSync(path.dirname(toMapPath), { recursive: true });
				fs.renameSync(fromMapPath, toMapPath);
			}
		});
	},
};

const jsConfig = {
	entryPoints: ["packages/neos-media-browser/src/index.js"],
	bundle: true,
	outfile: path.join(projectRoot, "Resources/Public/JavaScript/Main.js"),
	format: "iife",
	platform: "browser",
	target: ["es2018"],
	sourcemap: true,
	minify: !isWatch,
	loader: {
		".js": "jsx",
		".vanilla-css": "css",
	},
	plugins: [moveMainCssPlugin],
};

const cssConfig = {
	entryPoints: ["Resources/Private/Styles/MediaBrowser.scss"],
	bundle: true,
	outfile: path.join(projectRoot, "Resources/Public/Styles/MediaBrowser.css"),
	platform: "browser",
	target: ["es2018"],
	sourcemap: false,
	minify: !isWatch,
	external: [
		"../Fonts/*",
		"../../Neos.Neos/*",
		"*.eot",
		"*.woff",
		"*.woff2",
		"*.ttf",
		"*.svg",
		"*.gif",
	],
	plugins: [
		sassPlugin({
			loadPaths: [
				path.join(projectRoot, "node_modules"),
				path.join(projectRoot, "node_modules/compass-mixins/lib"),
			],
		}),
	],
};

async function build() {
	if (isWatch) {
		const [jsContext, cssContext] = await Promise.all([
			esbuild.context(jsConfig),
			esbuild.context(cssConfig),
		]);

		await Promise.all([jsContext.watch(), cssContext.watch()]);

		console.log("Watching JS and SCSS assets with esbuild...");
		return;
	}

	await Promise.all([esbuild.build(jsConfig), esbuild.build(cssConfig)]);
}

build().catch((error) => {
	console.error(error);
	process.exit(1);
});
