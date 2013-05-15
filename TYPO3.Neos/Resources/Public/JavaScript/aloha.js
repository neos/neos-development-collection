define(['Library/jquery-with-dependencies'], function(jQuery, jQueryUi) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('aloha');
	var Aloha = window.Aloha = window.Aloha || {};

	Aloha.settings = {
		logLevels: {'error': true, 'warn': true, 'info': false, 'debug': false},
		errorhandling : false,
		sidebar: {
			disabled: true
		},
		plugins: {
			load: [
				'common/ui',
				'common/link',
				'common/table',
				'common/format',
				'common/list',
				//'image/image-plugin',
				//'highlighteditables/highlighteditables-plugin',
				'common/dom-to-xhtml',
				'common/contenthandler',
				'common/characterpicker',
				'common/commands',
				'common/block',
				'common/align',
				'common/abbr',
				'common/horizontalruler',
				'common/paste',
				// some extra plugins
				//'toc/toc-plugin',
				'extra/cite',
				//'flag-icons/flag-icons-plugin',
				//'numerated-headers/numerated-headers-plugin',
				'extra/formatlesspaste',
				//'linkbrowser/linkbrowser-plugin',
				//'imagebrowser/imagebrowser-plugin',
				'extra/ribbon',
				//'extra/wai-lang',
				'extra/headerids',
				//'metaview/metaview-plugin',
				'extra/listenforcer'

				//  'neosAloha/neosintegration',
				//                'neosAloha/neos-links'
			].join(','),
			block: {
				sidebarAttributeEditor: false
			},
			format: {
				config : [ 'b', 'i', 'p', 'h1', 'h2', 'h3', 'pre', 'removeFormat' ]
			}
		},

			// Fine-tune some Aloha-SmartContentChange settings, making the whole system feel more responsive.
		smartContentChange: {
			idle: 500,
			delay: 150
		},
		bundles: {
				// Path for custom bundle relative from require.js path
			neosAloha: '/_Resources/Static/Packages/TYPO3.Neos/JavaScript/alohaplugins/'
		},

		baseUrl: alohaBaseUrl,

			// Pass on our jQuery instance to Aloha to prevent double loading of jQuery
		jQuery: jQuery,

		predefinedModules: {
			'jqueryui': jQueryUi
		}
	};

		// because this method is called during bootstrap, it might happen that T3.Configuration is not yet available.
		// Thus, we need to check the configuration in window.T3Configuration as well.
	if ((window.T3Configuration && window.T3Configuration.enableAloha) || (window.T3 && window.T3.Configuration && window.T3.Configuration.enableAloha)) {
		require(
			{
				context: 'aloha',
				baseUrl: alohaBaseUrl
			},
			['aloha'],
			function(Aloha) {
				if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('aloha (inner require)');
			}
		);
	}
});


