/**
 * T3.ContentModule
 *
 * Entry point which initializes the Content Module UI
 */

define(
[
	'jquery',
	'vie/instance',
	'emberjs',
	'create',
	'phoenix/common',
	'phoenix/content/model',
	'phoenix/content/ui',
	'phoenix/content/controller',
	'jquery.hotkeys'
],
function($, vie, Ember, CreateJS) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('phoenix/contentmodule');

	return Ember.Application.create({

		TYPO3_NAMESPACE: 'http://www.typo3.org/ns/2011/FLOW3/Packages/TYPO3/Content/',

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

		vie: null,

		_activeEntity: null,

		_vieOptions: {
			stanbolUrl: null,
			dbPediaUrl: null
		},

		bootstrap: function() {
			this.set('vie', vie);
			this._initializeInspector();
			this._initializeToolbar();
			this._initializeFooter();
			this._initializeLauncher();
			this._initializeAjaxPageReload();
			this._initializeVie();

			this._initializeDevelopmentFeatures();

			this._initializeNotifications();

			// this._initializeDoubleClickEdit();

			$('body').toggleClass('t3-ui-controls t3-backend');

			this._setPagePosition();

			this._initializeShortcuts();
			this._initializeHistoryManagement();

				// Remove the Aloha sidebar completely from DOM, as there is
				// currently no other way to deactivate it.
			$('.aloha-sidebar-bar').remove();
		},

		_initializeNotifications: function() {
				// Initialize notifications
			$('body').midgardNotifications();
		},

		_initializeDevelopmentFeatures: function() {
			var that = this;
			window.addEventListener('hashchange', function() {
				that._enableDevelopmentFeaturesIfNeeded();
			}, false);
			this._enableDevelopmentFeaturesIfNeeded();
		},

		_initializeDoubleClickEdit: function() {
			$('.t3-contentelement').live('dblclick', function(event) {
				if ($('.t3-primary-editor-action').length > 0) {
					$('.t3-primary-editor-action').click();
				}
				event.preventDefault();
			});
		},

		_initializeVie: function() {
			var that = this;

			if (this.get('_vieOptions').stanbolUrl) {
				vie.use(new vie.StanbolService({
					proxyDisabled: true,
					url: this.get('_vieOptions').stanbolUrl
				}));
			}

			if (this.get('_vieOptions').dbPediaUrl) {
				vie.use(new vie.DBPediaService({
					proxyDisabled: true,
					url: this.get('_vieOptions').dbPediaUrl
				}));
			}

				// Cache schema in session storage
			var schema = (window.sessionStorage ? window.sessionStorage.getItem('vie-schema') : null);
			if (T3.Configuration.phoenixShouldCacheSchema && schema) {
				VIE.Util.loadSchemaOrg(vie, JSON.parse(schema), null);
				that._initializeVieAfterSchemaIsLoaded(vie);
			} else {
				$.getJSON(phoenixContentTypeSchema).success(function(data, textStatus, jqXHR) {
					VIE.Util.loadSchemaOrg(vie, data, null);
					if (window.sessionStorage) window.sessionStorage.setItem('vie-schema', JSON.stringify(data));
					that._initializeVieAfterSchemaIsLoaded(vie);
				}).error(function(data, textStatus, jqXHR) {
					console.warn('Error loading the VIE schema', data, textStatus, jqXHR);
				});
			}
		},

		_initializeVieAfterSchemaIsLoaded: function() {
			T3.Content.Model.NodeSelection.initialize();
			T3.Content.Model.PublishableNodes.initialize();
			this._registerVieContentTypeTemplateCallbacks();
			this._initializeCreateJs();
		},

		/**
		 * Register template generation callbacks.
		 *
		 * For adding new content elements VIE needs an HTML template. This method registers callback methods
		 * for generating those templates. The template itself is rendered on the server, and contains the
		 * rendered output of the requested content type, rendered within the current typoscript path.
		 *
		 * @return {Void}
		 */
		_registerVieContentTypeTemplateCallbacks: function() {
			_.each(vie.types.toArray(), function(type) {
				var contentType = type.id.substring(1, type.id.length - 1).replace(T3.ContentModule.TYPO3_NAMESPACE, '');
				var prefix = vie.namespaces.getPrefix(type.id);

				if (prefix === 'typo3') {
					vie.service('rdfa').setTemplate('typo3:' + contentType, 'typo3:content-collection', function(entity, callBack, collectionView) {
							// This callback function is called whenever we create a content element
						var type = entity.get('@type'),
							contentType = type.id.substring(1, type.id.length - 1).replace(T3.ContentModule.TYPO3_NAMESPACE, ''),
							referenceEntity = null,
							lastMatchedEntity = null;

						var afterCreationCallback = function(nodePath, template) {
							entity.set('@subject', nodePath);
							callBack(template);
						}

						_.each(collectionView.collection.models, function(matchEntity) {
							if (entity === matchEntity && lastMatchedEntity) {
								referenceEntity = lastMatchedEntity;
								T3.Content.Controller.BlockActions.addBelow(
									contentType,
									referenceEntity,
									afterCreationCallback
								);
							} else {
								lastMatchedEntity = matchEntity;
							}
						});

						if (referenceEntity === null) {
							// No reference node found, use the section
							T3.Content.Controller.BlockActions.addInside(
								contentType,
								vie.entities.get($(collectionView.el).attr('about')),
								afterCreationCallback
							);
						}
					});
				}
			});
		},

		_initializeCreateJs: function() {
				// Midgard Storage
			$('body').midgardStorage({
				vie: vie,
				url: function () { /* empty function to prevent Midgard error */ },
				localStorage: true,
				autoSave: true
			});

			CreateJS.initialize();
		},

		_initializeShortcuts: function() {
			var that = this;
			$(document).bind('keydown', 'alt+p', function() {
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
				classNames: ['t3-ui', 't3-inspector']
			});

			inspector.appendTo($('body'));
		},

		_initializeToolbar: function() {
			var toolbar = T3.Content.UI.Toolbar.create({
				elementId: 't3-toolbar',
				classNames: ['t3-ui'],
				left: [
					T3.Content.UI.ToggleButton.extend({
						pressedBinding: 'T3.Content.Controller.Preview.previewMode',
						template: Ember.Handlebars.compile('<i class="icon-fullscreen"></i>'),
						attributeBindings: ['disabled', 'title'],
						icon: 'preview',
						title: 'Preview',
						elementAttributes: ['title']
					}),
					T3.Content.UI.PageTreeButton.extend({
						label: 'Pages'
					}),
					T3.Content.UI.ToggleButton.extend({
						pressedBinding: 'T3.Content.Controller.Wireframe.wireframeMode',
						label: 'Wireframe',
						icon: 'wireframe'
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
						target: 'T3.Content.Model.PublishableNodes',
						action: 'publishAll',
						_connectionFailedBinding: 'T3.Content.Controller.ServerConnection._failedRequest',
						_saveRunningBinding: 'T3.Content.Controller.ServerConnection._saveRunning',
						_noChangesBinding: 'T3.Content.Model.PublishableNodes.noChanges',
						classNameBindings: ['connectionStatusClass'],
						classNames: ['btn-publish'],

						connectionStatusClass: function() {
							var className = 't3-connection-status-';
							className += this.get('_connectionFailed') ? 'down' : 'up';
							return className;
						}.property('_connectionFailed')
					})
				]
			});
			toolbar.appendTo($('body'));
		},

		_initializeLauncher: function() {
			this._launcher = T3.Common.Launcher.create({
				searchItemsBinding: 'T3.Common.Launcher.SearchController.searchItems'
			});
			this._launcher.appendTo($('#t3-launcher'));
		},

		_initializeFooter: function() {
			var breadcrumb = T3.Content.UI.Breadcrumb.extend({
				contentBinding: 'T3.Content.Model.NodeSelection.nodes'
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
			this._linkInterceptionHandler($('a:not(.t3-ui a, .aloha-floatingmenu a)'));
			this._linkInterceptionHandler('a.t3-link-ajax', true);
		},

		_setPagePosition: function() {
			var hash = location.hash;
			if (hash.length > 0) {
				var contentElement = $('#' + hash.substring(1));
				if (contentElement.length > 0) {
					window.scroll(0, contentElement.position().top - $('body').offset().top);
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
				var $this = $(link);
				if (!$this.attr('href').match(/[a-z]*:\/\//)) {
						// We only load the page if the link is a non-external link.
					that.loadPage($this.attr('href'));
				}
			}
			if (constant === true) {
				$(document).delegate(selector, 'click', function(e) {
					clickHandler(e, this);
				});
			} else {
				$(selector).click(function(e) {
					clickHandler(e, this);
				});
			}
		},

		loadPage: function(uri, ignorePushToHistory) {
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

			this._showPageLoader();
			this.set('_isLoadingPage', true);

			if (window.history && !ignorePushToHistory) {
				window.history.pushState({uri: uri}, document.title, uri);
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
						T3.ContentModule.set('currentUri', uri)
					}
					$('#t3-page-metainformation').replaceWith($newMetaInformation);
					$('title').html($htmlDom.filter('title').html());

					that._setPagePosition();

						// TODO Update VIE and create here
					T3.Content.Model.NodeSelection.initialize();
					T3.Content.Model.PublishableNodes.initialize();
				} else {
						// FALLBACK: AJAX error occured,
						// so we reload the whole backend.
					window.location.href = uri;
				}
				that.set('_isLoadingPage', false);
				$('.t3-pageloader-wrapper').fadeOut('fast', function() {
					$(this).remove();
				});
			});
		},

		_showPageLoader: function() {
			require([
				'canvas.indicator'
			], function() {
				var body = $('body'),
					loader = $('<canvas class="t3-pageloader" />'),
					indicator;
				body.append($('<div />').addClass('t3-pageloader-wrapper').append(loader).fadeTo('fast', .8));

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
});
