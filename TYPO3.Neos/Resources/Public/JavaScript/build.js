/**
 * WARNING: if changing any of the statements below, make sure to also
 * update them inside contentmodule-main.js!
 *
 * To start a build, run "r.js -o build.js" from within the current directory.
 */
({
	baseUrl: ".",
	paths: {
		'Library': '../Library/',
		'spinjs': '../Library/spinjs/spin.min',
		'chosen': '../Library/chosen/chosen/chosen.jquery.min',
		'jquery.lionbars': '../Library/jquery-lionbars/jQuery.lionbars.0.2.1',
		'jquery.hotkeys': '../Library/jquery-hotkeys/jquery.hotkeys',
		'jquery.popover': '../Library/jquery-popover/jquery.popover',
		'jquery.jcrop': '../Library/jcrop/js/jquery.Jcrop.min',
		'jquery.plupload': '../Library/plupload/js/plupload',
		'jquery.plupload.html5': '../Library/plupload/js/plupload.html5',
		'bootstrap.dropdown': '../Library/twitter-bootstrap/js/bootstrap-dropdown',
		'bootstrap.alert': '../Library/twitter-bootstrap/js/bootstrap-alert',
		'bootstrap.notify': '../Library/bootstrap-notify/js/bootstrap-notify',
		'codemirror': '../Library/codemirror2/lib/codemirror',
		'codemirror.xml': '../Library/codemirror2/mode/xml/xml',
		'codemirror.css': '../Library/codemirror2/mode/css/css',
		'codemirror.javascript': '../Library/codemirror2/mode/javascript/javascript',
		'codemirror.htmlmixed': '../Library/codemirror2/mode/htmlmixed/htmlmixed',
		'jquery.cookie': '../Library/jquery-cookie/jquery.cookie',
		'jquery.dynatree': '../Library/jquery-dynatree/js/jquery.dynatree',
		'hallo': '../Library/hallo/hallo',
		'createjs': '../Library/createjs/create',
		'backbone': '../Library/vie/lib/backboneJS/backbone.min',
		'underscorejs': '../Library/vie/lib/underscoreJS/underscore.min'
	},
	shim: {
		'emberjs': {
			deps: ['jquery'],
			exports: 'Ember'
		},
		'neos/contentmodule': ['emberjs'],
		'jquery.lionbars': ['jquery'],
		'jquery-ui': ['jquery'],
		'jquery.cookie': ['jquery'],
		'jquery.dynatree': ['jquery-ui'],
		'jquery.plupload.html5': ['jquery.plupload'],
		'bootstrap.dropdown': ['jquery'],
		'underscorejs': {
			'exports': '_'
		},
		'createjs': ['jquery-ui'],
		'hallo': ['jquery-ui'],
		'backbone': ['underscorejs'],
		'Library/vie/lib/rdfquery/latest/jquery.rdfquery.min': ['jquery'],
		'halloplugins/linkplugin': ['hallo']
	},
	locale: 'en',

	name: "contentmodule-main",
	out: "contentmodule-main-built.js"

	// if you un-comment the line below, you get an un-optimized version.
	//optimize: "none"
})
