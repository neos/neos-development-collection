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
	'vie',
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
		clipboard: null,
		_elementIsAddingNewContent: null,
		_elementIsPastingContent: null,

		clipboardContainsContent: function() {
			return this.get('clipboard') !== null;
		}.property('clipboard'),

		/**
		 * Initialization lifecycle method. Here, we re-fill the clipboard as needed
		 */
		init: function() {
			if (LocalStorage.getItem('clipboard')) {
				this.set('clipboard', LocalStorage.getItem('clipboard'));
			}
		},

		/**
		 * Cut a node and put it on the clipboard
		 * TODO: Decide if we move cut copy paste to another controller
		 * @param {object} node
		 * @return {void}
		 */
		cut: function(node) {
			if (this.get('clipboard.type') === 'cut' && this.get('clipboard.nodePath') === node.get('nodePath')) {
				this.set('clipboard', null);
			} else {
				this.set('clipboard', {
					type: 'cut',
					nodePath: node.get('nodePath'),
					nodeType: node.get('nodeType')
				});
			}
		},

		/**
		 * Copy a node and put it on the clipboard
		 * @param {object} node
		 * @return {void}
		 */
		copy: function(node) {
			if (this.get('clipboard.type') === 'copy' && this.get('clipboard.nodePath') === node.get('nodePath')) {
				this.set('clipboard', null);
			} else {
				this.set('clipboard', {
					type: 'copy',
					nodePath: node.get('nodePath'),
					nodeType: node.get('nodeType')
				});
			}
		},

		/**
		 * Paste the current node on the clipboard after another node
		 *
		 * @param {object} node, the target node
		 * @return {boolean}
		 */
		pasteAfter: function(node) {
			return this._paste(node, 'after');
		},

		/**
		 * Paste the current node on the clipboard before another node
		 *
		 * @param {object} node, the target node
		 * @return {boolean}
		 */
		pasteBefore: function(node) {
			return this._paste(node, 'before');
		},

		/**
		 * Paste the current node on the clipboard into another node
		 *
		 * @param {object} node, the target node
		 * @return {boolean}
		 */
		pasteInto: function(node) {
			return this._paste(node, 'into');
		},

		/**
		 * Paste a node on a certain location, relative to another node
		 * @param {object} node, the target node
		 * @param {string} position
		 * @return {boolean}
		 */
		_paste: function(node, position) {
			var that = this,
				clipboard = this.get('clipboard');

			if (!clipboard.nodePath) {
				Notification.info('No node found on the clipboard');
				return false;
			}
			if (clipboard.nodePath === node.get('nodePath') && clipboard.type === 'cut') {
				Notification.info('It is not possible to paste a node ' + position + ' itself.');
				return false;
			}

			var action = clipboard.type === 'cut' ? 'move' : 'copy';
			NodeEndpoint[action].call(
				that,
				clipboard.nodePath,
				node.get('nodePath'),
				position,
				''
			).then(
				function (result) {
					if (result.success) {
						that.set('clipboard', null);
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
					// Remove the loading icon from the parent content element where current element was pasted from.
					that.set('_elementIsPastingContent', null);

					EventDispatcher.trigger('contentChanged');
				}
			);

			return true;
		},

		remove: function(model) {
			model.get('_vieEntity').set('typo3:_removed', true);
			model.get('_vieEntity').save(null);
			NodeSelection.updateSelection();
			EventDispatcher.triggerExternalEvent('Neos.NodeRemoved', 'Node was removed.', {element: model.$element.get(0)});
			EventDispatcher.one('contentSaved', function() {
				this.trigger('contentChanged');
			});
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
			var that = this,
				nodePath = referenceEntity.getSubject().substring(1, referenceEntity.getSubject().length - 1),
				$entityElement = vieInstance.service('rdfa').getElementBySubject(referenceEntity.getSubject(), $(document)),
				$closestCollection = $entityElement.closest('[rel="typo3:content-collection"]'),
				closestCollectionEntity = vieInstance.entities.get(vieInstance.service('rdfa').getElementSubject($closestCollection)),
				typoScriptPath = position === 'into' ? referenceEntity.get('typo3:__typoscriptPath') : closestCollectionEntity.get('typo3:__typoscriptPath');

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
					var template = $(result.collectionContent).find('[about="' + result.nodePath + '"]').first();
					callBack(result.nodePath, template);

					// Remove the loading icon from the parent content element where current element was created from.
					that.set('_elementIsAddingNewContent', null);

					EventDispatcher.trigger('contentChanged');

					var $createdElement = vieInstance.service('rdfa').getElementBySubject('<' + result.nodePath + '>', $(document));
					EventDispatcher.triggerExternalEvent('Neos.NodeCreated', 'Node was created.', {element: $createdElement.get(0)});
				}
			);
		},

		/**
		 * Observes the clipboard property and processes changes
		 * @return {void}
		 */
		onClipboardChange: function() {
			var clipboard = this.get('clipboard');
			LocalStorage.setItem('clipboard', clipboard);
		}.observes('clipboard')
	}).create();
});
