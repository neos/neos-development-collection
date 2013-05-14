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

	name: "contentmodule-main",
	out: "contentmodule-main-built.js"

	// if you un-comment the line below, you get an un-optimized version.
	//optimize: "none"
})
