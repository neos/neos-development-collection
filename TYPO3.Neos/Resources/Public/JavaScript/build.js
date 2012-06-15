// Build with "r.js -o build.js"
({
	baseUrl: ".",
	paths: {
		'Library': '../Library/',
		'emberjs': '../Library/emberjs/ember-0.9.7',
		'canvas.indicator': '../Library/canvas-indicator/canvas.indicator',
		'chosen': '../Library/chosen/chosen/chosen.jquery.min',
		'jquery.lionbars': '../Library/jquery-lionbars/jQuery.lionbars.0.2.1',
		'jquery.ui': '../Library/jquery-ui/js/jquery-ui-1.9m6',
		'jquery.hotkeys': '../Library/jquery-hotkeys/jquery.hotkeys',
		'jquery.popover': '../Library/jquery-popover/jquery.popover',
		'jquery.notice': '../Library/jquery-notice/jquery.notice',
		'jquery.jcrop': '../Library/jcrop/js/jquery.Jcrop.min',
		'jquery.plupload': '../Library/plupload/js/plupload',
		'jquery.plupload.html5': '../Library/plupload/js/plupload.html5',
		'codemirror': '../Library/codemirror2/lib/codemirror',
		'codemirror.xml': '../Library/codemirror2/mode/xml/xml',
		'codemirror.css': '../Library/codemirror2/mode/css/css',
		'codemirror.javascript': '../Library/codemirror2/mode/javascript/javascript',
		'codemirror.htmlmixed': '../Library/codemirror2/mode/htmlmixed/htmlmixed',
		//dynatree
		'jquery.cookie': '../Library/jquery-cookie/jquery.cookie',
		'jquery.dynatree': '../Library/jquery-dynatree/js/jquery.dynatree'
	},
	name: "contentmodule-main",
	out: "contentmodule-main-built.js"
})
