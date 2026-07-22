const esbuild = require("esbuild");
const path = require("path");
const fs = require("fs");
const { sassPlugin } = require("esbuild-sass-plugin");

const projectRoot = __dirname;
const isWatch = process.argv.includes("--watch");

function getAdjustedOutputPath(fileName) {
	// CSS files
	if (fileName.endsWith(".css") || fileName.endsWith(".css.map")) {
		return path.join(projectRoot, "Resources/Public/Styles", fileName);
	}

	// JS files
	if (fileName.endsWith(".js") || fileName.endsWith(".js.map")) {
		return path.join(projectRoot, "Resources/Public/JavaScript", fileName);
	}

	// All other files (fonts, images, etc.) will be placed in the Assets folder.
	return path.join(projectRoot, "Resources/Public/Assets", fileName);
}

const writeOutputPlugin = {
	name: "write-output",
	setup(build) {
		build.initialOptions.write = false;

		build.onEnd((result) => {
			if (!result.outputFiles) {
				return;
			}

			for (const outputFile of result.outputFiles) {
				const fileName = path.basename(outputFile.path);
				const outputPath = getAdjustedOutputPath(fileName);

				fs.mkdirSync(path.dirname(outputPath), { recursive: true });
				fs.writeFileSync(outputPath, outputFile.contents);
			}
		});
	},
};

const options = {
	entryPoints: {
		Main: "packages/neos-media-browser/src/index.js",
		MediaBrowser: "Resources/Private/Styles/MediaBrowser.scss",
	},
	bundle: true,
	outdir: "Resources/Public",
	format: "iife",
	platform: "browser",
	target: ["es2020"],
	sourcemap: true,
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
	loader: {
		".js": "jsx",
	},
	plugins: [
		sassPlugin({
			loadPaths: [path.join(projectRoot, "node_modules")],
		}),
		writeOutputPlugin,
	],
};

if (isWatch) {
	esbuild.context(options).then((ctx) => ctx.watch());
} else {
	esbuild.build(options);
}
