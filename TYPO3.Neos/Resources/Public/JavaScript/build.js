/**
 * WARNING: if changing any of the statements below, make sure to also
 * update them inside contentmodule-main.js!
 *
 * To start a build, run "r.js -o build.js" from within the current directory.
 */
({
	baseUrl: ".",
	paths: {
		'Library': '../Library/'
	},
	locale: 'en',

	name: "ContentModuleBootstrap",
	include: [
		// The editors below are lazily loaded through Require.js, so we need to include them in the build manually.
		"Content/Inspector/Editors/TextFieldEditor",
		"Content/Inspector/Editors/BooleanEditor",
		"Content/Inspector/Editors/DateEditor",
		"Content/Inspector/Editors/ImageEditor",
		"Content/Inspector/Editors/SelectBoxEditor"
	],
	out: "ContentModule-built.js"

	// if you un-comment the line below, you get an un-optimized version.
	//optimize: "none"
});