/**
 * Main Ember.Application for the Content Module.
 *
 * Entry point which initializes the Content Module UI
 */

define(
[
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'Shared/ResourceCache',
	'Shared/LocalStorage',
	'Shared/Configuration',
	'Shared/EventDispatcher',
	'Content/LoadingIndicator',
	'Content/Model/NodeSelection',
	'Content/Model/NodeActions',
	'Content/EditPreviewPanel/EditPreviewPanelController',
	'vie',
	'emberjs',
	'Content/InputEvents/KeyboardEvents',
	'create',
	'Shared/Notification',
	'Shared/HttpClient',
	'Shared/HttpRestClient',
	'Content/Components/StorageManager'
],
function(
	$,
	_,
	ResourceCache,
	LocalStorage,
	Configuration,
	EventDispatcher,
	LoadingIndicator,
	NodeSelection,
	NodeActions,
	EditPreviewPanelController,
	vie,
	Ember,
	KeyboardEvents,
	CreateJS,
	Notification,
	HttpClient,
	HttpRestClient,
	StorageManager
) {
	var ContentModule = Ember.Application.extend(Ember.Evented, {
		rootElement: '#neos-application',
		router: null,

		/**
		 * The following setting is set to "true" when unfinished features should be shown.
		 *
		 * You can use it in the UI as following:
		 *
		 * Ember.View.extend({
		 *    template: Ember.Handlebars.compile('<span style="color:white">!!! Development mode !!!</span>'),
		 *    isVisibleBinding: 'ContentModule.showDevelopmentFeatures'
		 * })
		 *
		 * OR
		 *
		 * {{view T3.Content.UI.Button label="Inspect" isVisibleBinding="ContentModule.showDevelopmentFeatures"}}
		 *
		 * OR
		 * {{#boundIf ContentModule.showDevelopmentFeatures}}
		 *   Display only in development mode
		 * {{/boundif}}
		 */
		showDevelopmentFeatures: false,

		getCurrentUri: function() {
			return window.location.href;
		},

		_isLoadingPage : null,

		vie: null,

		_activeEntity: null,
		_loadPageRequest: null,

		_vieOptions: {
			stanbolUrl: null,
			dbPediaUrl: null
		},

		$loader: null,
		spinner: null,
		httpClientFailureHandling: true,

		bootstrap: function() {
			var that = this,
				httpClientFailureHandler = function(xhr, status, statusMessage) {
					if (that.get('httpClientFailureHandling') === false) {
						return;
					}
					if (status === 'abort' || xhr.status === 401) {
						return;
					}
					if (xhr === undefined || xhr.status !== 404) {
						console.error(statusMessage, xhr);
						var errorMessage = '';
						if (xhr.responseJSON && xhr.responseJSON.error.message) {
							errorMessage = xhr.responseJSON.error.message;
						} else {
							errorMessage = $(xhr.responseText).find('.ExceptionSubject').text();
						}
						Notification.error('Server communication ' + status + ': ' + xhr.status + ' ' + statusMessage, errorMessage);
					} else {
						console.log('_handlePageNotFoundError');
						// that._handlePageNotFoundError(that.getCurrentUri());
					}
					LoadingIndicator.done();
				};
			HttpClient.on('failure', httpClientFailureHandler);
			HttpRestClient.on('failure', httpClientFailureHandler);

			this.set('vie', vie);
			if (window.T3.isContentModule) {
				this._initializeAjaxPageReload();
				this._initializeVie();
			}

			this._initializeDevelopmentFeatures();

			this._initializeNotifications();

			if (window.T3.isContentModule) {
				$('body').addClass('neos-backend');
				this._setPagePosition();
			}

			this._initializeTwitterBootstrap();

			if (window.T3.isContentModule) {
				this._initializeHistoryManagement();

				KeyboardEvents.initializeContentModuleEvents();
			}
		},

		/**
		 * Find a valid URI up in the document tree
		 *
		 * A valid URI in Neos backend for the root page is domain.com/@user-john and for a subpage,
		 * it's domain.com/page@user-john, so the script try the URL with / first and without on the
		 * second try. This slows down the process for deep tree discarding, but work more reliable.
		 *
		 * The method only reload the page with the new URL if the URL return a 20x HTTP code.
		 *
		 * @private
		 * @param currentUri
		 * @return {void}
		 */
		_handlePageNotFoundError: function(currentUri) {
			var that = this, retries = 1, retry = true, options = {};

			while (retry === true && retries <= 100 ) {
				currentUri = currentUri.replace(/\/([^\/]*)@/g, retries % 2 === 1 ? '/@' : '@');

				options = {
					type: 'GET',
					async: false,
					url: currentUri
				};

				$.ajax(options).done(function () {
					that.loadPage(currentUri);
					retry = false;
				});

				if (retries === 100) {
					Notification.error('Unable to find a valid document up in the document tree');
				}
				++retries;
			}
		},

		_initializeNotifications: function() {
				// Initialize notifications
			$(this.rootElement).append('<div class="neos-notification-container"></div>');
				// TODO: Remove with resolving #45049
			$('body').midgardNotifications();
		},

		_initializeDevelopmentFeatures: function() {
			var that = this;
			window.addEventListener('hashchange', function() {
				that._enableDevelopmentFeaturesIfNeeded();
			}, false);
			this._enableDevelopmentFeaturesIfNeeded();
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

			ResourceCache.getItem(Configuration.get('VieSchemaUri')).then(
				function(vieSchema) {
					ResourceCache.getItem(Configuration.get('NodeTypeSchemaUri')).then(
						function(nodeTypeSchema) {
							vie.Util.loadSchemaOrg(vie, vieSchema, null);
							Configuration.set('Schema', nodeTypeSchema.nodeTypes);
							that._initializeVieAfterSchemaIsLoaded(vie);
						}
					);
				}
			);
		},

		_initializeVieAfterSchemaIsLoaded: function() {
			NodeSelection.initialize();
			this.trigger('pageLoaded');

			this._initializeCreateJs();
		},

		_initializeCreateJs: function() {
				// Midgard Storage
			$('body').midgardStorage({
				vie: vie,
				url: function () { /* empty function to prevent Midgard error */ },
				localStorage: true,
				autoSave: true,
				autoSaveInterval: 2500
			});

			StorageManager.set('changes', $('body').data('Midgard-midgardStorage').changedModels);
			StorageManager.start();
			this.on('beforePageLoad', StorageManager, 'persist');

			CreateJS.initialize();
		},

		_initializeTwitterBootstrap: function() {
			$('.dropdown-toggle', this.rootElement).dropdown();
			$('html').click(function(e) {
				if ($(e.target).parents('.neos-popover').length === 0) {
					$('.neos-popover-toggle').popover('hide');
				}
			});
		},

		_initializeHistoryManagement: function() {
			var that = this;
			if (window.history && _.isFunction(window.history.replaceState)) {
				window.history.replaceState({uri: window.location.href}, document.title, window.location.href);
			}
			window.addEventListener('popstate', function(event) {
				if (event.state && event.state.uri) {
					that.loadPage(event.state.uri, true);
				}
			});
		},

		_enableDevelopmentFeaturesIfNeeded: function() {
			if (window.location.hash === '#dev') {
				this.set('showDevelopmentFeatures', true);
				LocalStorage.setItem('showDevelopmentFeatures', true);
			} else if (window.location.hash === '#nodev') {
				this.set('showDevelopmentFeatures', false);
				LocalStorage.removeItem('showDevelopmentFeatures');
			} else if(LocalStorage.getItem('showDevelopmentFeatures')) {
				this.set('showDevelopmentFeatures', true);
			}
		},

		/**
		 * Intercept all links, and instead use AJAX for reloading the page.
		 */
		_initializeAjaxPageReload: function() {
			this._linkInterceptionHandler($('a:not(' + this.rootElement + ' a)'));
			this._linkInterceptionHandler('a.neos-link-ajax', true);
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
			this.loadPage(this.getCurrentUri());
		},

		_linkInterceptionHandler: function(selector, constant) {
			var that = this;
			function clickHandler(e, link) {
				var $this = $(link),
					href = $this.attr('href'),
					protocolAndHost = location.protocol + '//' + location.host;
				// Check if the link is external by checking for a protocol
				if (href.match(/[a-z]*:\/\//) && href.substr(0, protocolAndHost.length) !== protocolAndHost) {
					return;
				}
				// Check if the link only points to a hash
				if (href[0] === '#') {
					return;
				}
				// Check if the link contains a hash and points to the current page
				if (href.indexOf('#') !== -1 && href.replace(protocolAndHost, '').split('#')[0] === location.pathname + location.search) {
					return;
				}

				// Check if the link is link to a static resource
				if (href.match(/_Resources\/Persistent/)) {
					return;
				}

				// Check if the parent content element is selected if so don't trigger the link
				if ($this.parents('.neos-contentelement-active').length !== 0) {
					e.preventDefault();
					return;
				}
				// Check if the the link is inside a inline editable container and not in preview mode if so don't trigger the link
				if ($this.parents('.neos-inline-editable').length !== 0 && EditPreviewPanelController.get('currentlyActiveMode.isPreviewMode') !== true) {
					e.preventDefault();
					return;
				}
				e.preventDefault();
				that.loadPage($this.attr('href'));
			}
			if (constant === true) {
				$(document).on('click', selector, function(e) {
					clickHandler(e, this);
				});
			} else {
				$(selector).on('click', function(e) {
					clickHandler(e, this);
				});
			}
		},

		loadPage: function(uri, ignorePushToHistory, callback) {
			var that = this;
			if (uri === '#') {
					// Often, pages use an URI of "#" to go to the homepage. In this case,
					// we get the current workspace name and redirect to this workspace instead.
				var workspaceName = $('#neos-document-metadata').data('neos-context-workspace-name');
				uri = '@' + workspaceName;
			}

			LoadingIndicator.start();
			this.set('_isLoadingPage', true);
			this.trigger('beforePageLoad');

			function pushUriToHistory() {
				if (window.history && !ignorePushToHistory && _.isFunction(window.history.pushState)) {
					window.history.pushState({uri: uri}, document.title, uri);
				}
			}

			var currentlyActiveContentElementNodePath = $('.neos-contentelement-active').attr('about');
			if (this.get('_loadPageRequest')) {
				this.get('_loadPageRequest').abort();
			}
			this.set('_loadPageRequest', HttpClient.getResource(
				uri,
				{
					xhr: function() {
						var xhr = $.ajaxSettings.xhr();
						xhr.onreadystatechange = function() {
							if (xhr.readyState === 1) {
								LoadingIndicator.set(0.1, 200);
							}
							if (xhr.readyState === 2) {
								LoadingIndicator.set(0.9, 100);
							}
							if (xhr.readyState === 3) {
								LoadingIndicator.set(0.99, 50);
							}
						};
						return xhr;
					}
				}
			));
			this.get('_loadPageRequest').then(
				function(htmlString) {
					var $htmlDom = $($.parseHTML(htmlString)),
						$documentMetadata = $htmlDom.filter('#neos-document-metadata');
					if ($documentMetadata.length === 0) {
						Notification.error('Could not read document metadata from response. Please open the location ' + uri + ' outside the Neos backend.');
						that.set('_isLoadingPage', false);
						LoadingIndicator.done();
						return;
					}

					pushUriToHistory();

					// Extract the HTML from the page, starting at (including) #neos-document-metadata until #neos-application.
					var $newContent = $htmlDom.filter('#neos-document-metadata').nextUntil('#neos-application').andSelf();

					// remove the current HTML content
					var $neosApplication = $('#neos-application');
					$neosApplication.prevAll().remove();
					$('body').prepend($newContent);
					that.set('_isLoadingPage', false);

					var $insertedContent = $neosApplication.prevAll();
					var $links = $insertedContent.find('a').add($insertedContent.filter('a'));
					that._linkInterceptionHandler($links);
					LoadingIndicator.done();

					$('title').html($htmlDom.filter('title').html());
					$('link[rel="neos-site"]').attr('href', $htmlDom.filter('link[rel="neos-site"]').attr('href'));

					// TODO: transfer body classes and other possibly important tags from the head section

					that._setPagePosition();

					// Update node selection (will update VIE)
					NodeSelection.initialize();

					if (EditPreviewPanelController.get('currentlyActiveMode.isPreviewMode') !== true) {
						// Refresh CreateJS, renders the button bars f.e.
						CreateJS.enableEdit();
					}

					// If doing a reload, we highlight the currently active content element again
					var $currentlyActiveContentElement = $('[about="' + currentlyActiveContentElementNodePath + '"]');
					if ($currentlyActiveContentElement.length === 1) {
						NodeSelection.updateSelection($currentlyActiveContentElement, {scrollToElement: true});
					}

					that.set('_isLoadingPage', false);
					LoadingIndicator.done();

					that.trigger('pageLoaded');
					// Send external event so site JS can act on it
					EventDispatcher.triggerExternalEvent('Neos.PageLoaded', 'Page is refreshed.');

					if (typeof callback === 'function') {
						callback();
					}
				},
				function() {
					Notification.error('An error occurred.');
					that.set('_isLoadingPage', false);
					LoadingIndicator.done();
				}
			).fail(function(error) {
				Notification.error('An error occurred.');
				console.error('An error occurred:', error);
			});
		}

	}).create();
	ContentModule.deferReadiness();

	return ContentModule;
});
