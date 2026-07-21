const esbuild = require("esbuild");
const path = require("path");
const fs = require("fs");
const { sassPlugin } = require("esbuild-sass-plugin");

const projectRoot = __dirname;
const isWatch = process.argv.includes("--watch");
const tempOutDir = path.join(projectRoot, "Resources/Public/.esbuild-tmp");

function getAdjustedOutputPath(fileName) {
	if (fileName.endsWith(".css") || fileName.endsWith(".css.map")) {
		return path.join(projectRoot, "Resources/Public/Styles", fileName);
	}

	return path.join(projectRoot, "Resources/Public/JavaScript", fileName);
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

const buildConfig = {
	entryPoints: {
		Main: "packages/neos-media-browser/src/index.js",
		MediaBrowser: "Resources/Private/Styles/MediaBrowser.scss",
	},
	bundle: true,
	outdir: tempOutDir,
	entryNames: "[name]",
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

async function build() {
	if (isWatch) {
		const context = await esbuild.context(buildConfig);
		await context.watch();

		console.log("Watching JS and SCSS assets with a single esbuild context...");
		return;
	}

	await esbuild.build(buildConfig);
}

build().catch((error) => {
	console.error(error);
	process.exit(1);
});
