/**
 * T3.ContentModule
 *
 * Entry point which initializes the Content Module UI
 */

define(
[
	'jquery',
	'phoenix/common',
	'phoenix/content/model',
	'phoenix/content/ui',
	'phoenix/content/controller',
	'jquery.hotkeys'
],
function(jQuery) {
	var T3 = window.T3 || {},
		$ = jQuery;
	var ContentModule = Ember.Application.create({

		/**
		 * The following setting is set to "true" when unfinished features should be shown.
		 *
		 * You can use it in the UI as following:
		 *
		 * Ember.View.extend({
		 *    template: Ember.Handlebars.compile('<span style="color:white">!!! Development mode !!!</span>'),
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

		_isLoadingPage : null,

		_launcher: null,

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

				// Aloha is already loaded, so we can directly blockify our content
			window.PhoenixAlohaPlugin.start();
			this._initializeAlohaBlocksAndUpdateUi();

			jQuery('.t3-contentelement').live('dblclick', function(event) {
				if (jQuery('.t3-primary-editor-action').length > 0) {
					jQuery('.t3-primary-editor-action').click();
				}
				event.preventDefault();
			});

			jQuery('body').toggleClass('t3-ui-controls t3-backend');

			this._setPagePosition();

			this._initializeShortcuts();
			this._initializeHistoryManagement();

				// Remove the Aloha sidebar completely from DOM, as there is
				// currently no other way to deactivate it.
			jQuery('.aloha-sidebar-bar').remove();
		},

		_initializeShortcuts: function() {
			var that = this;
			jQuery(document).bind('keydown', 'alt+p', function() {
				T3.Content.Controller.Preview.togglePreview();
				return false;
			}).bind('keydown', 'alt+l', function() {
				that._launcher.activate();
				return false;
			});
		},

		_initializeHistoryManagement: function() {
			var that = this;
			if (window.history) {
				window.history.replaceState({uri: window.location.href}, document.title, window.location.href);
			}
			window.addEventListener('popstate', function(event) {
				if (event.state) {
					that.loadPage(event.state.uri, true);
				}
			});
		},

		_initializeAlohaBlocksAndUpdateUi: function() {
			Aloha.jQuery('.t3-contentelement').alohaBlock({
				'aloha-block-type': 'TYPO3Block'
			});

			// Now we initialize the BlockManager. This triggers updates to the PublishableBlocks controller
			T3.Content.Model.BlockManager.initialize();

			// Now the ChangesController can read from local storage, and update the blocks.
			T3.Content.Model.Changes.initialize();

			// Now we need to initialize all dependencies from the BlockManager.
			T3.Content.Model.BlockSelection.initialize();

			// Initialize "Add Block" buttons
			// TODO: find a clean place where to put this...
			jQuery('button.t3-create-new-content').click(function() {
				T3.Content.Controller.BlockActions.addInside(jQuery(this).attr('data-node'), jQuery(this));
			});

			if (T3.Content.Controller.Preview.get('previewMode')) {

				// HACK around an aloha bug:
				// somehow, enabling aloha and then *directly* deactivating
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
				T3.Common.LocalStorage.setItem('showDevelopmentFeatures', true);
			} else if (window.location.hash === '#nodev') {
				this.set('showDevelopmentFeatures', false);
				T3.Common.LocalStorage.removeItem('showDevelopmentFeatures');
			} else if(T3.Common.LocalStorage.getItem('showDevelopmentFeatures')) {
				this.set('showDevelopmentFeatures', true);
			}
		},

		_initializeInspector: function() {
			var inspector = T3.Content.UI.Inspector.create({
				elementId: 't3-inspector',
				classNames: ['t3-ui', 't3-inspector', 'aloha-block-do-not-deactivate']
			});

			inspector.appendTo(jQuery('body'));
		},

		_initializeToolbar: function() {
			var toolbar = T3.Content.UI.Toolbar.create({
				elementId: 't3-toolbar',
				classNames: ['t3-ui'],
				left: [
					T3.Content.UI.PageTreeButton.extend({
						label: 'Pages'
					}),
					T3.Content.UI.ToggleButton.extend({
						pressedBinding: 'T3.Content.Controller.Preview.previewMode',
						label: 'Preview',
						icon: 'preview'
					})
				],
				right: [
					Ember.View.extend({
						template: Ember.Handlebars.compile('<span style="color:white">!!! Development mode !!!</span>'),
						isVisibleBinding: 'T3.ContentModule.showDevelopmentFeatures'
					}),
					T3.Content.UI.Button.extend({
						label: 'Publish Page',
						disabledBinding: Ember.Binding.or('_noChanges', '_saveRunning'),
						target: 'T3.Content.Model.PublishableBlocks',
						action: 'publishAll',
						_connectionFailedBinding: 'T3.Content.Controller.ServerConnection._failedRequest',
						_saveRunningBinding: 'T3.Content.Controller.ServerConnection._saveRunning',
						_noChangesBinding: 'T3.Content.Model.PublishableBlocks.noChanges',
						classNameBindings: ['connectionStatusClass'],

						connectionStatusClass: function() {
							var className = 't3-connection-status-';
							className += this.get('_connectionFailed') ? 'down' : 'up';
							return className;
						}.property('_connectionFailed')
					})
				]
			});
			toolbar.appendTo(jQuery('body'));
		},

		_initializeLauncher: function() {
			this._launcher = T3.Common.Launcher.create({
				searchItemsBinding: 'T3.Common.Launcher.SearchController.searchItems'
			});
			this._launcher.appendTo(jQuery('#t3-launcher'));
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
			footer.appendTo(jQuery('body'));
		},

		/**
		 * Intercept all links, and instead use AJAX for reloading the page.
		 */
		_initializeAjaxPageReload: function() {
			this._linkInterceptionHandler(jQuery('a:not(.t3-ui a, .aloha-floatingmenu a)'));
			this._linkInterceptionHandler('a.t3-link-ajax', true);
		},

		_onBlockSelectionChange: function(blocks) {
			T3.Content.Model.BlockSelection.updateSelection(blocks);
			this._markTopmostSelectedBlock(blocks);
		},

		_markTopmostSelectedBlock: function(blocks) {
			jQuery('.aloha-block-active-top').removeClass('aloha-block-active-top');
			if (blocks.length > 0) {
				blocks[0].$element.addClass('aloha-block-active-top');
			}
		},

		_setPagePosition: function() {
			var hash = location.hash;
			if (hash.length > 0) {
				var contentElement = jQuery('#' + hash.substring(1));
				if (contentElement.length > 0) {
					window.scroll(0, contentElement.position().top - jQuery('body').offset().top);
				}
			}
		},

		reloadPage: function() {
			this.loadPage(T3.ContentModule.currentUri);
		},
		_linkInterceptionHandler: function(selector, constant) {
			var that = this;
			function clickHandler(e, link) {
				e.preventDefault();
				var $this = jQuery(link);
				if (!$this.attr('href').match(/[a-z]*:\/\//)) {
						// We only load the page if the link is a non-external link.
					that.loadPage($this.attr('href'));
				}
			}
			if (constant === true) {
				jQuery(document).delegate(selector, 'click', function(e) {
					clickHandler(e, this);
				});
			} else {
				jQuery(selector).click(function(e) {
					clickHandler(e, this);
				});
			}
		},
		loadPage: function(uri, ignorePushToHistory) {
			var that = this;

			var selectorsToReplace = [];

			jQuery('.t3-reloadable-content').each(function() {
				var id = jQuery(this).attr('id');
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

			this._showPageLoader();
			this.set('_isLoadingPage', true);

			if (window.history && !ignorePushToHistory) {
				window.history.pushState({uri: uri}, document.title, uri);
			}

			$.get(uri, function(htmlString, status) {
				if (status === 'success') {
					var $htmlDom = jQuery(htmlString);

					$.each(selectorsToReplace, function(index, selector) {
						if ($htmlDom.find(selector).length > 0) {
							jQuery(selector).replaceWith($htmlDom.find(selector));
						} else if ($htmlDom.filter(selector).length > 0) {
							// find only looks inside the *descendants* of the result
							// set; that's why we might need to use "filter" if a top-
							// level element has the t3-reloadable-content CSS class applied
							jQuery(selector).replaceWith($htmlDom.filter(selector));
						} else {
							throw 'Target HTML selector not found. Something has gone really wrong';
						}

						that._linkInterceptionHandler(jQuery(selector).find('a'));
					});

					var $newMetaInformation = $htmlDom.filter('#t3-page-metainformation');
					if ($newMetaInformation.length === 0) {
						// FALLBACK: Something went really wrong with the fetching.
						// so we reload the whole backend.
						window.location.href = uri;
					} else {
						T3.ContentModule.currentUri = uri;
					}
					jQuery('#t3-page-metainformation').replaceWith($newMetaInformation);
					jQuery('title').html($htmlDom.filter('title').html());

					that._setPagePosition();
					that._initializeAlohaBlocksAndUpdateUi();
				} else {
					// FALLBACK: AJAX error occured,
					// so we reload the whole backend.
					window.location.href = uri;
				}
				that.set('_isLoadingPage', false);
				jQuery('.t3-pageloader-wrapper').fadeOut('fast', function() {
					$(this).remove();
				});
			});
		},

		_showPageLoader: function() {
			require([
				'canvas.indicator'
			], function() {
				var body = jQuery('body'),
					loader = jQuery('<canvas class="t3-pageloader" />'),
					indicator;
				body.append(jQuery('<div />').addClass('t3-pageloader-wrapper').append(loader).fadeTo('fast', .8));

				indicator = new CanvasIndicator(loader.get(0), {
					bars: 12,
					innerRadius: 8,
					size: [3, 15],
					rgb: [0, 0, 0],
					fps: 15
				});
			});
		}
	});

	T3.ContentModule = ContentModule;
	window.T3 = T3;
});
