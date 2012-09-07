// Build with "r.js -o build.js"
({
	baseUrl: ".",
	paths: {
		'Library': '../Library/',
		'canvas.indicator': '../Library/canvas-indicator/canvas.indicator',
		'chosen': '../Library/chosen/chosen/chosen.jquery.min',
		'jquery.lionbars': '../Library/jquery-lionbars/jQuery.lionbars.0.2.1',
		'jquery.hotkeys': '../Library/jquery-hotkeys/jquery.hotkeys',
		'jquery.popover': '../Library/jquery-popover/jquery.popover',
		'jquery.jcrop': '../Library/jcrop/js/jquery.Jcrop.min',
		'jquery.plupload': '../Library/plupload/js/plupload',
		'jquery.plupload.html5': '../Library/plupload/js/plupload.html5',
		'codemirror': '../Library/codemirror2/lib/codemirror',
		'codemirror.xml': '../Library/codemirror2/mode/xml/xml',
		'codemirror.css': '../Library/codemirror2/mode/css/css',
		'codemirror.javascript': '../Library/codemirror2/mode/javascript/javascript',
		'codemirror.htmlmixed': '../Library/codemirror2/mode/htmlmixed/htmlmixed',
		'jquery.cookie': '../Library/jquery-cookie/jquery.cookie',
		'jquery.dynatree': '../Library/jquery-dynatree/js/jquery.dynatree',
		'hallo': '../Library/hallo/hallo-min',
		'createjs': '../Library/createjs/create-min',
		'backbone': '../Library/vie/lib/backboneJS/backbone.min',
		'underscorejs': '../Library/vie/lib/underscoreJS/underscore.min'
	},
	shim: {
		'emberjs': {
			deps: ['jquery'],
			exports: 'Ember'
		},
		'phoenix/contentmodule': ['emberjs'],
		'jquery.lionbars': ['jquery'],
		'jquery-ui': ['jquery'],
		'jquery.cookie': ['jquery'],
		'jquery.dynatree': ['jquery-ui'],
		'jquery.plupload.html5': ['jquery.plupload'],
		'create/collectionsWidgets/jquery.typo3.collectionWidget': ['createjs'],
		'underscorejs': {
			'exports': '_'
		},
		'createjs': ['jquery-ui'],
		'hallo': ['jquery-ui'],
		'backbone': ['underscorejs'],
		'vie': ['underscorejs', 'backbone'],
		'Library/vie/lib/rdfquery/latest/jquery.rdfquery.min': ['jquery'],
	},
	locale: 'en',

	name: "contentmodule-main",
	out: "contentmodule-main-built.js"

	// if you un-comment the line below, you get an un-optimized version.
	//optimize: "none"
})
