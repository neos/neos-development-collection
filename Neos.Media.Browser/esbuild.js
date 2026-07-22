const esbuild = require("esbuild");
const path = require("path");
const fs = require("fs");
const { sassPlugin } = require("esbuild-sass-plugin");

const projectRoot = __dirname;
const isWatch = process.argv.includes("--watch");

function getAdjustedOutputPath(fileName) {
	if (fileName.endsWith(".css") || fileName.endsWith(".css.map")) {
		return path.join(projectRoot, "Resources/Public/Styles", fileName);
	}

	return path.join(projectRoot, "Resources/Public/JavaScript", fileName);
}

/**
 * @todo: This will copy all NotoSans fonts from Neos.Neos to the public folder of the Media.Browser package.
 * This is a temporary solution until we have a better way to handle font files in Neos packages.
 * @fixme
 */
function copyFonts() {
	const fontsSrc = path.join(
		projectRoot,
		"../Neos.Neos/Resources/Private/Fonts/NotoSans",
	);
	const fontsDest = path.join(projectRoot, "Resources/Public/Fonts/NotoSans");
	fs.mkdirSync(fontsDest, { recursive: true });
	for (const file of fs.readdirSync(fontsSrc)) {
		fs.copyFileSync(path.join(fontsSrc, file), path.join(fontsDest, file));
	}
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

			copyFonts();
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
