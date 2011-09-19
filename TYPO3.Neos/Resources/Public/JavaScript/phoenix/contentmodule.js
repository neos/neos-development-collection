/**
 * T3.ContentModule
 *
 * Entry point which initializes the Content Module UI
 */

define(
[
	'phoenix/common',
	'phoenix/content/model',
	'phoenix/content/ui',
	'phoenix/content/controller'
],
function() {

	var T3 = window.T3 || {},
		$ = window.alohaQuery || window.jQuery;
	var ContentModule = SC.Application.create({

		/**
		 * The following setting is set to "true" when unfinished features should be shown.
		 *
		 * You can use it in the UI as following:
		 *
		 * SC.View.extend({
		 *    template: SC.Handlebars.compile('<span style="color:white">!!! Development mode !!!</span>'),
		 *    isVisibleBinding: 'T3.ContentModule.showDevelopmentFeatures'
		 * })
		 *
		 * OR
		 *
		 * {{view T3.Content.UI.Button label="Inspect" isVisibleBinding="T3.ContentModule.showDevelopmentFeatures"}}
		 *
		 * OR
		 * {{#boundIf T3.ContentModule.showDevelopmentFeatures}}
		 *   Display only in development mode
		 * {{/boundif}}
		 */
		showDevelopmentFeatures: false,

		currentUri: window.location.href,

		bootstrap: function() {
			this._initializeInspector();
			this._initializeToolbar();
			this._initializeFooter();
			this._initializeLauncher();
			this._initializeAjaxPageReload();

			var that = this;
			window.addEventListener("hashchange", function() {
				that._enableDevelopmentFeaturesIfNeeded();
			}, false);
			this._enableDevelopmentFeaturesIfNeeded();

			// When aloha is loaded, blockify our content.
			Aloha.bind('aloha', this._initializeAlohaBlocksAndUpdateUi);

			$('body').addClass('t3-ui-controls-active');
			$('body').addClass('t3-backend');
		},

		_initializeAlohaBlocksAndUpdateUi: function() {
			$.each(T3.Configuration.Schema, function(key, value) {
				var cssClassName = key.toLowerCase().replace(/[.:]/g, '-');
				$('.' + cssClassName).alohaBlock({
					'block-type': 'TYPO3Block',
					'__contenttype': key
				});
			});

			T3.Content.Model.PublishableBlocks.initialize();

			// Now we initialize the BlockManager. This triggers updates to the PublishableBlocks controller
			T3.Content.Model.BlockManager.initialize();

			// Now the ChangesController can read from local storage, and update the blocks.
			T3.Content.Model.Changes.initialize();

			// Now we need to initialize all dependencies from the BlockManager.
			T3.Content.Model.BlockSelection.initialize();

			if (T3.Content.Controller.Preview.get('previewMode')) {

				// HACK around an aloha bug:
				// somehow, enabling aloha and then *directly* diactivating
				// editables breaks does not work. That's why we need a timeout.
				window.setTimeout(function() {
					Aloha.editables.forEach(function(editable) {
						editable.disable();
					});
				}, 100);
			}
		},

		_enableDevelopmentFeaturesIfNeeded: function() {
			if (window.location.hash === '#dev') {
				this.set('showDevelopmentFeatures', true);
				window.localStorage['showDevelopmentFeatures'] = true;
			} else if (window.location.hash === '#nodev') {
				this.set('showDevelopmentFeatures', false);
				delete window.localStorage['showDevelopmentFeatures']
			} else if(window.localStorage['showDevelopmentFeatures']) {
				this.set('showDevelopmentFeatures', 'true');
			}
		},

		_initializeInspector: function() {
			var inspector = T3.Content.UI.Inspector.create({
				elementId: 't3-inspector',
				classNames: ['t3-ui', 't3-inspector', 'aloha-block-do-not-deactivate']
			});

			inspector.appendTo($('body'));
		},

		_initializeToolbar: function() {
			var toolbar = T3.Content.UI.Toolbar.create({
				elementId: 't3-toolbar',
				classNames: ['t3-ui'],
				left: [
					T3.Content.UI.PageTreeButton.extend({
						label: 'Pages',
						popoverTitle: 'Page Tree',
						isVisibleBinding: 'T3.ContentModule.showDevelopmentFeatures'
					}),
					T3.Content.UI.ToggleButton.extend({
						pressedBinding: 'T3.Content.Controller.Preview.previewMode',
						label: 'Preview',
						icon: 'preview'
					})
				],
				right: [
					SC.View.extend({
						template: SC.Handlebars.compile('<span style="color:white">!!! Development mode !!!</span>'),
						isVisibleBinding: 'T3.ContentModule.showDevelopmentFeatures'
					}),
					T3.Content.UI.Button.extend({
						label: 'Cancel',
						disabledBinding: 'T3.Content.Model.Changes.noChanges',
						target: 'T3.Content.Model.Changes',
						action: 'revert'
					}),
					T3.Content.UI.Button.extend({
						label: 'Save Page',
						disabledBinding: 'T3.Content.Model.Changes.noChanges',
						target: 'T3.Content.Model.Changes',
						action: 'save'
					}),
					T3.Content.UI.Button.extend({
						label: 'Publish Page',
						disabledBinding: 'T3.Content.Model.PublishableBlocks.noChanges',
						target: 'T3.Content.Model.PublishableBlocks',
						action: 'publishAll'
					})
				]
			});
			toolbar.appendTo($('body'));
		},

		_initializeLauncher: function() {
			var launcher = T3.Common.Launcher.create({
				modulesBinding: 'T3.Common.ModulesController.filteredModules',
				valueBinding: 'T3.Common.ModulesController.filterValue'
			});
			launcher.appendTo($('#t3-ui-top'));
		},

		_initializeFooter: function() {
			var breadcrumb = T3.Content.UI.Breadcrumb.extend({
				contentBinding: 'T3.Content.Model.BlockSelection.blocks'
			});
			var footer = T3.Content.UI.Toolbar.create({
				elementId: 't3-footer',
				classNames: ['t3-ui'],
				left: [
					breadcrumb
				]
			});
			footer.appendTo($('body'));
		},

		/**
		 * Intercept all links, and instead use AJAX for reloading the page.
		 */
		_initializeAjaxPageReload: function() {
			// TODO: we might need to make this more configurable
			this._linkInterceptionHandler($('a'));
		},

		_onBlockSelectionChange: function(blocks) {
			T3.Content.Model.BlockSelection.updateSelection(blocks);
		},

		reloadPage: function() {
			this.loadPage(T3.ContentModule.currentUri);
		},
		_linkInterceptionHandler: function($selector) {
			var that = this;
			$selector.click(function(e) {
				e.preventDefault();
				var $this = $(this);
				that.loadPage($this.attr('href'));
			})
		},
		loadPage: function(uri) {
			var that = this;

			var selectorsToReplace = [];

			$('.t3-reloadable-content').each(function() {
				var id = $(this).attr('id');
				if (!id) {
					// TODO: we need cleaner developer error handling
					throw 'You have marked a DOM element with the CSS class t3-reloadable-content; but this element has no ID.';
				}
				selectorsToReplace.push('#' + id);
			});

			if (selectorsToReplace.length === 0) {
				// FALLBACK: The user did not configure reloadable content;
				// so we fall back to classical reload.
				window.location.href = uri;
				return;
			}

			$.get(uri, function(htmlString, status) {
				if (status === 'success') {
					var $htmlDom = $(htmlString);

					$.each(selectorsToReplace, function(index, selector) {
						if ($htmlDom.find(selector).length > 0) {
							$(selector).replaceWith($htmlDom.find(selector));
						} else if ($htmlDom.filter(selector).length > 0) {
							// find only looks inside the *descendants* of the result
							// set; that's why we might need to use "filter" if a top-
							// level element has the t3-reloadable-content CSS class applied
							$(selector).replaceWith($htmlDom.filter(selector));
						} else {
							throw 'Target HTML selector not found. Something has gone really wrong';
						}

						that._linkInterceptionHandler($(selector).find('a'));
					});

					var $newMetaInformation = $htmlDom.filter('#t3-page-metainformation');
					if ($newMetaInformation.length === 0) {
						// FALLBACK: Something went really wrong with the fetching.
						// so we reload the whole backend.
						window.location.href = uri;
					} else {
						T3.ContentModule.currentUri = uri;
					}
					$('#t3-page-metainformation').replaceWith($newMetaInformation);
					$('title').html($htmlDom.filter('title').html());

					that._initializeAlohaBlocksAndUpdateUi();
				} else {
					// FALLBACK: AJAX error occured,
					// so we reload the whole backend.
					window.location.href = uri;
				}
			});
		}
	});

	T3.ContentModule = ContentModule;
	window.T3 = T3;
});