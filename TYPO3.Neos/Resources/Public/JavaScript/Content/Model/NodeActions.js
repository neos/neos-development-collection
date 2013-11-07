/**
 * The NodeActions is a container for numerous actions which can happen with blocks.
 * They are normally triggered when clicking Block UI handles.
 * Examples include:
 * - deletion of content
 * - creation of content
 *
 * @singleton
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/LocalStorage',
	'Shared/Notification',
	'Shared/EventDispatcher',
	'Content/Model/NodeSelection',
	'vie/instance',
	'Shared/Endpoint/NodeEndpoint'
], function(
	Ember,
	$,
	LocalStorage,
	Notification,
	EventDispatcher,
	NodeSelection,
	vieInstance,
	NodeEndpoint
) {
	return Ember.Object.extend({
		// TODO: Move this to a separate controller
		_clipboard: null,
		_elementIsAddingNewContent: null,

		clipboardContainsContent: function() {
			return this.get('_clipboard') !== null;
		}.property('_clipboard'),

		/**
		 * Initialization lifecycle method. Here, we re-fill the clipboard as needed
		 */
		init: function() {
			if (LocalStorage.getItem('clipboard')) {
				this.set('_clipboard', LocalStorage.getItem('clipboard'));
			}
		},

		/**
		 * Cut a node and put it on the clipboard
		 * TODO: Decide if we move cut copy paste to another controller
		 * @return {void}
		 */
		cut: function(nodePath) {
			if (this.get('_clipboard.type') === 'cut' && this.get('_clipboard.nodePath') === nodePath) {
				this.set('_clipboard', null);
			} else {
				this.set('_clipboard', {
					type: 'cut',
					nodePath: nodePath
				});
			}
		},

		/**
		 * Copy a node and put it on the clipboard
		 * @return {void}
		 */
		copy: function(nodePath) {
			if (this.get('_clipboard.type') === 'copy' && this.get('_clipboard.nodePath') === nodePath) {
				this.set('_clipboard', null);
			} else {
				this.set('_clipboard', {
					type: 'copy',
					nodePath: nodePath
				});
			}
		},

		/**
		 * Paste the current node on the clipboard after another node
		 * @param {String} nodePath the nodePath of the target node
		 * @return {boolean}
		 */
		pasteAfter: function(nodePath) {
			return this._paste(nodePath, 'after');
		},

		/**
		 * Paste a node on a certain location, relative to another node
		 * @param {String} nodePath the nodePath of the target node
		 * @param {String} position
		 * @return {boolean}
		 */
		_paste: function(nodePath, position) {
			var that = this,
				clipboard = this.get('_clipboard');

			if (!clipboard.nodePath) {
				Notification.info('No node found on the clipboard');
				return false;
			}
			if (clipboard.nodePath === nodePath && clipboard.type === 'cut') {
				Notification.info('It is not possible to paste a node ' + position + ' itself.');
				return false;
			}

			var action = clipboard.type === 'cut' ? 'move' : 'copy';
			NodeEndpoint[action].call(
				that,
				clipboard.nodePath,
				nodePath,
				position,
				''
			).then(
				function (result) {
					if (result.success) {
						that.set('_clipboard', null);
						require(
							{context: 'neos'},
							[
								'Content/Application'
							],
							function(ContentModule) {
								if ('data' in result && 'nextUri' in result.data) {
									ContentModule.loadPage(result.data.nextUri);
								} else {
									ContentModule.reloadPage();
								}
							}
						);
					}
				}
			);

			return true;
		},

		remove: function(model) {
			model.set('typo3:_removed', true);
			model.save(null);
			NodeSelection.updateSelection();
			EventDispatcher.trigger('contentChanged');
		},

		addAbove: function(nodeType, referenceEntity, callBack) {
			this._add(nodeType, referenceEntity, 'before', callBack);
		},

		addBelow: function(nodeType, referenceEntity, callBack) {
			this._add(nodeType, referenceEntity, 'after', callBack);
		},

		addInside: function(nodeType, referenceEntity, callBack) {
			this._add(nodeType, referenceEntity, 'into', callBack);
		},

		/**
		 * Creates a node on the server. When the result is received the callback function is called.
		 * The first argument passed to the callback is the node path of the new node, second argument
		 * is the $ object containing the rendered HTML of the new node.
		 *
		 * @param {String} nodeType
		 * @param {Object} referenceEntity
		 * @param {String} position
		 * @param {Function} callBack This function is called after element creation and receives the $ DOM element as arguments
		 * @private
		 */
		_add: function(nodeType, referenceEntity, position, callBack) {
			var that = this;
			var nodePath = referenceEntity.getSubject().substring(1, referenceEntity.getSubject().length - 1);
			var $entityElement = vieInstance.service('rdfa').getElementBySubject(referenceEntity.getSubject(), $(document)),
				$closestCollection = $entityElement.closest('[rel="typo3:content-collection"]'),
				closestCollectionEntity = vieInstance.entities.get(vieInstance.service('rdfa').getElementSubject($closestCollection));
			var typoScriptPath = position === 'into' ? referenceEntity.get('typo3:_typoscriptPath') : closestCollectionEntity.get('typo3:_typoscriptPath');
			NodeEndpoint.createAndRender(
				nodePath,
				typoScriptPath,
				{
					nodeType: nodeType,
					properties: {}
				},
				position
			).then(
				function(result) {
					result = JSON.parse(result);
					var template = $(result.collectionContent).find('[about="' + result.nodePath + '"]').first();
					callBack(result.nodePath, template);

					// Remove the loading icon from the parent content element where current element was created from.
					that.set('_elementIsAddingNewContent', null);

					EventDispatcher.trigger('contentChanged');
				}
			);
		},

		/**
		 * Paste the current node on the clipboard before another node
		 *
		 * @param {String} nodePath the nodePath of the target node
		 * @param {$} $handle the clicked handle
		 * @return {boolean}
		 */
		pasteBefore: function(nodePath, $handle) {
			return this._paste(nodePath, $handle, 'before');
		},

		/**
		 * Observes the _clipboard property and processes changes
		 * @return {void}
		 */
		onClipboardChange: function() {
			var clipboard = this.get('_clipboard');
			LocalStorage.setItem('clipboard', clipboard);
		}.observes('_clipboard')
	}).create();
});