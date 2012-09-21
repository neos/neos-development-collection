define(['jquery', 'jquery-ui'], function(jQuery, jQueryUi) {
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
				'common/format',
				'common/paste',
				'common/block',
				'common/table',
				'common/link'//,
//				'phoenixAloha/phoenixintegration',
//				'phoenixAloha/phoenix-links'
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
			phoenixAloha: '/_Resources/Static/Packages/TYPO3.TYPO3/JavaScript/alohaplugins/'
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
	if ((window.T3Configuration && window.T3Configuration.enableAloha) || (T3.Configuration && T3.Configuration.enableAloha)) {
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


