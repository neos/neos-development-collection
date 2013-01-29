
window._requirejsLoadingTrace = [];
window.renderLoadingTrace = function() {
	return JSON.stringify(window._requirejsLoadingTrace);
};
/**
 * WARNING: if changing any of the require() statements below, make sure to also
 * update them inside build.js!
 */
require(
	{
		baseUrl: window.neosJavascriptBasePath,
		urlArgs: window.localStorage.showDevelopmentFeatures ? 'bust=' +  (new Date()).getTime() : '',

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
			'jquery.plupload': ['jquery'],
			'jquery.popover': ['jquery'],
			'jquery.jcrop': ['jquery'],
			'jquery.hotkeys': ['jquery'],
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
		locale: 'en'
	},
	[
		'jquery',
		'neos/contentmodule',
		'emberjs',
		'storage',
		'jquery-ui',
		'jquery.lionbars'
	],
	function($, ContentModule) {
		if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('contentmodule-main');
		var T3 = window.T3;
		T3.Configuration = window.T3Configuration;
		T3.ContentModule = ContentModule;
		delete window.T3Configuration;

		Ember.$(document).ready(function() {
			T3.ContentModule.bootstrap();

			Ext.Direct.on('exception', function(error) {
				T3.Content.Controller.ServerConnection.set('_failedRequest', true);
				T3.Common.Notification.error('ExtDirect error: ' + error.message);
			});

			ExtDirectInitialization();
		});
	}
);
