require(
	{
		baseUrl: window.phoenixJavascriptBasePath,
		urlArgs: window.localStorage.showDevelopmentFeatures ? 'bust=' +  (new Date()).getTime() : '',
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
		shim: {
			'emberjs': {
				deps: ['jquery'],
				exports: 'Ember'
			},
			'phoenix/contentmodule': ['emberjs'],
			'jquery.lionbars': ['jquery'],
			'jquery.ui': ['jquery'],
			'jquery.cookie': ['jquery'],
			'jquery.dynatree': ['jquery.ui'],
			'jquery.plupload.html5': ['jquery.plupload']
		},
		locale: 'en'
	},
	[
		'jquery',
		'aloha',
		'jquery.ui',
		'emberjs',
		'jquery.lionbars',
		'phoenix/contentmodule'
	],
	function() {
		var T3 = window.T3;
		T3.Configuration = window.T3Configuration;
		delete window.T3Configuration;

		Ember.$(document).ready(function() {
			Aloha.ready(function() {
				T3.ContentModule.bootstrap();
			});

			Ext.Direct.on('exception', function(error) {
				T3.Common.Notification.error('ExtDirect error: ' + error.message);
			});

			// Because our ExtJS styles work only locally and not globally,
			// this breaks the extjs quicktip styling. Thus, we disable them
			// (affects Aloha)
			Ext.QuickTips.disable();

			ExtDirectInitialization();
		});
	}
);
