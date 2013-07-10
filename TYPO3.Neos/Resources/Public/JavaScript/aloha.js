define(['Library/jquery-with-dependencies'], function(jQuery, jQueryUi) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('aloha');

	if (!window.T3.isContentModule) {
		return;
	}
	var Aloha = window.Aloha = window.Aloha || {__shouldInit: true};

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
				//'common/characterpicker',
				'common/commands',
				'common/block',
				'common/align',
				//'common/abbr',
				//'common/horizontalruler',
				'common/paste',
				// some extra plugins
				//'toc/toc-plugin',
				//'extra/cite',
				//'flag-icons/flag-icons-plugin',
				//'numerated-headers/numerated-headers-plugin',
				'extra/formatlesspaste'
				//'linkbrowser/linkbrowser-plugin',
				//'imagebrowser/imagebrowser-plugin',
				//'extra/ribbon',
				//'extra/wai-lang',
				//'extra/headerids',
				//'metaview/metaview-plugin',
				//'extra/listenforcer'

				//  'neosAloha/neosintegration',
				//                'neosAloha/neos-links'
			].join(','),
			block: {
				sidebarAttributeEditor: false
			},
			/**
			 * FIXME:
			 * Currently the filtering of options for alignment and format don't work correctly,
			 * meaning that all options are still shown. This seems to be an Aloha bug and needs
			 * more research.
			 */
			table: { editables: { '[property="typo3:title"]': [ ] }},
			link: { editables: { '[property="typo3:title"]': [ ] }},
			list: { editables: { '[property="typo3:title"]': [ ] }},
			align: { editables: { '[property="typo3:title"]': [ ] }},
			format: {
				config : [ 'b', 'i', 'u', 'sub', 'sup', 'p', 'h1', 'h2', 'h3', 'pre', 'removeFormat' ]
				,
				editables: {
					'div[property="typo3:title"]': [ 'h1', 'h2', 'h3' ]
				}
			}
		},
		toolbar: {
			tabs: [
				{
					label: 'tab.format.label',
						// The "format" tab is shown in the top-menu, the remaining tabs are shown
						// in the inspector.
					components: [
						[ 'formatBlock' ],
						[ 'bold', 'italic', 'underline', 'subscript', 'superscript' ],
						[
							'formatLink', 'editLink', 'createTable', 'formatAbbr', 'formatNumeratedHeaders', 'toggleDragDrop',
							'toggleMetaView', 'wailang', 'toggleFormatlessPaste'
						], [
							'alignLeft', 'alignCenter', 'alignRight', 'alignJustify',
							'orderedList', 'unorderedList', 'indentList', 'outdentList', 'colorPicker'
						]
					]
				},
					// we completely disable the "insert" tab, as the needed features should reside in the "format" tab.
				{
					label: "tab.insert.label",
					showOn: function() {
						return false;
					}
				},
				// we completely disable the "link" tab, as this should all be in the "format" tab.
				{
					label: 'tab.link.label',
					showOn: function() {
						return false;
					}
				}

			]
		},

			// Fine-tune some Aloha-SmartContentChange settings, making the whole system feel more responsive.
		smartContentChange: {
			idle: 500,
			delay: 150
		},
		bundles: {
				// Path for custom bundle relative from require.js path
			//neosAloha: '/_Resources/Static/Packages/TYPO3.Neos/JavaScript/alohaplugins/'
		},

		baseUrl: alohaBaseUrl,

			// Pass on our jQuery instance to Aloha to prevent double loading of jQuery
		jQuery: jQuery,

		predefinedModules: {
			'jqueryui': jQueryUi
		},

		requireConfig: {
			map: {
				'../../TYPO3.Neos/JavaScript/LibraryExtensions/UiAlohaPlugin/button': {
					'originalButton': 'ui/button'
				},
				'*': {
					'ui/ui-plugin': '../../TYPO3.Neos/JavaScript/LibraryExtensions/UiAlohaPlugin/ui-plugin',
					'ui/multiSplit': '../../TYPO3.Neos/JavaScript/LibraryExtensions/UiAlohaPlugin/multiSplit',
					'ui/button': '../../TYPO3.Neos/JavaScript/LibraryExtensions/UiAlohaPlugin/button'
				}
			}
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
			function() {
				if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('aloha (inner require)');
			}
		);
	}
});


