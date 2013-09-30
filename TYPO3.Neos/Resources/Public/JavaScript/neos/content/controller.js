/**
 * Controllers which are not model- but appearance-related
 */

define(
[
	'Content/Application',
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'Library/backbone',
	'create',
	'emberjs',
	'Shared/LocalStorage',
	'Shared/Notification',
	'vie/entity'
],
function(ContentModule, $, _, Backbone, CreateJS, Ember, LocalStorage, Notification) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/controller');

	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};

	/**
	 * This controller toggles the preview mode on and off.
	 */
	var Preview = Ember.Object.extend({
		previewMode: false,

		init: function() {
			if (LocalStorage.getItem('previewMode') === true) {
				this.set('previewMode', true);
				this._togglePreviewMode();
			}
		},

		togglePreview: function() {
			this.set('previewMode', !this.get('previewMode'));
		},

		_togglePreviewMode: function() {
			var that = this,
				isPreviewEnabled = this.get('previewMode'),
				previewCloseClass = 'neos-preview-close';
			if (isPreviewEnabled) {
				$('body')
					.append($('<div class="neos" />').addClass(previewCloseClass).append($('<button class="neos-button neos-pressed"><i class="icon-resize-small"></i></button>'))
					.on('click', function() {
						that.set('previewMode', false);
					}));
				$(document).on('keyup.wireframe', function(e) {
					if (e.keyCode === 27) {
						that.set('previewMode', false);
					}
				});
				CreateJS.disableEdit();
			} else {
				$('body > .' + previewCloseClass).remove();
				$(document).off('keyup.wireframe');
				CreateJS.enableEdit();
			}
			$('body').toggleClass('neos-previewmode neos-controls');
		},

		onPreviewModeChanged: function() {
			LocalStorage.setItem('previewMode', this.get('previewMode'));
			this._togglePreviewMode();
		}.observes('previewMode')
	}).create();

	/**
	 * This controller toggles the wireframe mode on and off.
	 */
	var Wireframe = Ember.Object.extend({
		wireframeMode: false,

		init: function() {
			if (LocalStorage.getItem('wireframeMode') === true) {
				this.toggleWireframeMode();
				$('#neos-createcontentcollection-input').keypress(function(e) {
					if ((e.keyCode || e.which) === 13) {
						$('#neos-createcontentcollection-button').click();
					}
				});
				$('#neos-createcontentcollection-button').click(function() {
					var newContentCollectionName = $('#neos-createcontentcollection-input').val();
					if (newContentCollectionName === '') {
						Notification.error('You need to give a name for the new content collection.');
					} else {
						T3.Content.Controller.Wireframe.createContentCollection(newContentCollectionName);
					}
				});
			}
		},

		toggleWireframeMode: function() {
			this.set('wireframeMode', !this.get('wireframeMode'));
		},

		onWireframeModeChange: function () {
			var wireframeMode;
			wireframeMode = this.get('wireframeMode');
			if (typeof TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController === 'object') {
				ContentModule.showPageLoader();
				TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController.updatePreferences({'contentEditing.wireframeMode': wireframeMode}, function() {
					LocalStorage.setItem('wireframeMode', wireframeMode);
					window.location.reload(false);
				});
			}
		}.observes('wireframeMode'),

		createContentCollection: function(contentCollectionName) {
			var pageNodePath = $('#neos-page-metainformation').attr('about');
			ContentModule.showPageLoader();
			TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.create(pageNodePath, {
				nodeType: 'TYPO3.Neos:ContentCollection',
				nodeName: contentCollectionName
			}, 'into',
			function (result) {
				if (result.success == true) {
					$('#neos-createcontentcollection-input').val('');
					ContentModule.reloadPage();
				}
			});
		}
	}).create();

	/**
	 * This controller toggles the page tree visibility on and off.
	 */
	var PageTree = Ember.Object.extend({
		pageTreeMode: false,

		init: function() {
			if (window.T3.isContentModule) {
				if (LocalStorage.getItem('pageTreeMode') === true) {
					$('body').addClass('neos-tree-panel-open');
					this.togglePageTreeMode();
				}
			}
		},

		togglePageTreeMode: function() {
			this.set('pageTreeMode', !this.get('pageTreeMode'));
		},

		onPageTreeModeChange: function() {
			var pageTreeMode = this.get('pageTreeMode');
			if (typeof TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController === 'object') {
				if (pageTreeMode === true) {
					$('body').addClass('neos-tree-panel-open');
				} else {
					$('body').removeClass('neos-tree-panel-open');
				}
				TYPO3_Neos_Service_ExtDirect_V1_Controller_UserController.updatePreferences({'contentEditing.pageTreeMode': pageTreeMode}, function() {
					LocalStorage.setItem('pageTreeMode', pageTreeMode);
				});
			}
		}.observes('pageTreeMode').on('init')
	}).create();

	var ServerConnection = Ember.Object.extend({
		_lastSuccessfulTransfer: null,
		_failedRequest: false,
		_pendingSave: false,
		_saveRunning: false,

		sendAllToServer: function(collection, transformFn, extDirectFn, callback, elementCallback) {
			var that = this,
				numberOfUnsavedRecords = collection.get('length'),
				responseCallback = function(element) {
					return function(provider, response) {
						if (!response.result || response.result.success !== true) {
								// TODO: Find a way to avoid this notice
							Notification.error('Server communication error, reload the page to return to a safe state if another publish does not work');
							that.set('_failedRequest', true);
							return;
						} else {
							that.set('_failedRequest', false);
							that.set('_lastSuccessfulTransfer', new Date());
						}

						if (elementCallback) {
							elementCallback(element, response);
						}
						numberOfUnsavedRecords--;
						if (numberOfUnsavedRecords <= 0) {
							that.set('_saveRunning', false);
							if (callback) {
								callback();
							}
						}
					};
				};
			collection.forEach(function(element) {
					// Force copy of array
				var args = transformFn(element).slice();
				args.push(responseCallback(element));
				that.set('_saveRunning', true);
				extDirectFn.apply(window, args);
			})
		},

		statusClass: function() {
			this.set('_saveRunning', false);
			return 'neos-connection-status-' + this.get('_failedRequest') ? 'down' : 'up';
		}.observes('_failedRequest')

	}).create();

	T3.Content.Controller = {
		Preview: Preview,
		PageTree: PageTree,
		Wireframe: Wireframe,
		ServerConnection: ServerConnection
	}
	window.T3 = T3;
});
