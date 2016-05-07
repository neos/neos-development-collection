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
	'create',
	'Shared/Endpoint/NodeEndpoint',
	'Content/LoadingIndicator'
], function(
	Ember,
	$,
	LocalStorage,
	Notification,
	EventDispatcher,
	NodeSelection,
	vieInstance,
	CreateJS,
	NodeEndpoint,
	LoadingIndicator
) {
	return Ember.Object.extend({
		// TODO: Move this to a separate controller
		clipboard: null,

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
		 *
		 * @param {object} node
		 * @return {void}
		 * @TODO Decide if we move cut copy paste to another controller
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
		 *
		 * @param {object} node
		 * @return {void}
		 * @TODO Decide if we move cut copy paste to another controller
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
		 * Paste the node in the clipboard after given node
		 *
		 * @param {object} referenceNode
		 * @return {boolean}
		 */
		pasteAfter: function(referenceNode) {
			return this._paste(referenceNode, 'after');
		},

		/**
		 * Paste the node in the clipboard before given node
		 *
		 * @param {object} referenceNode
		 * @return {boolean}
		 */
		pasteBefore: function(referenceNode) {
			return this._paste(referenceNode, 'before');
		},

		/**
		 * Paste the node in the clipboard into given node
		 *
		 * @param {object} referenceNode
		 * @return {boolean}
		 */
		pasteInto: function(referenceNode) {
			return this._paste(referenceNode, 'into');
		},

		/**
		 * Paste the node in the clipboard at given position, relative to given node
		 *
		 * @param {object} referenceNode
		 * @param {string} position
		 * @return {boolean}
		 */
		_paste: function(referenceNode, position) {
			var that = this,
				clipboard = this.get('clipboard');

			if (!clipboard.nodePath) {
				Notification.info('No node found on the clipboard');
				return false;
			}
			if (clipboard.nodePath === referenceNode.get('nodePath') && clipboard.type === 'cut') {
				Notification.info('It is not possible to paste a node ' + position + ' itself.');
				return false;
			}

			var referenceNodeEntity = referenceNode.get('_vieEntity'),
				collectionModel = referenceNodeEntity._enclosingCollectionWidget.options.model,
				collection = referenceNodeEntity._enclosingCollectionWidget.options.collection,
				typoScriptPath = collectionModel.get('typo3:__typoscriptPath'),
				nodeType = clipboard.nodeType,
				localXhr = this._prepareXhr(),
				action = 'moveAndRender',
				args = [
					clipboard.nodePath,
					referenceNode.get('nodePath'),
					position,
					typoScriptPath
				];

			if (clipboard.type === 'copy') {
				action = 'copyAndRender';
				// Add empty node name argument
				args.push('');
			}

			args.push({
				xhr: function() {
					return localXhr;
				}
			});

			LoadingIndicator.start();
			NodeEndpoint[action].apply(that, args).then(
				function(result) {
					if (clipboard.type === 'cut') {
						that.set('clipboard', null);
						var cutNodeEntity = vieInstance.entities.get('<' + clipboard.nodePath + '>'),
							$cutNodeElement;
						if (cutNodeEntity) {
							$cutNodeElement = vieInstance.service('rdfa').getElementBySubject(cutNodeEntity.getSubject(), $(document));
							cutNodeEntity._enclosingCollectionWidget.options.collection.remove(cutNodeEntity);
							EventDispatcher.triggerExternalEvent('Neos.NodeRemoved', 'Node was removed.', {element: $cutNodeElement.get(0)});
						}
					}

					that._insertNode(result, localXhr, nodeType, collection, position, referenceNodeEntity, clipboard.type === 'cut');
				}
			).fail(
				function(error) {
					that._reloadPage();
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

		/**
		 * Add a node of given node type before given node
		 *
		 * @param {string} nodeType
		 * @param {object} referenceNode
		 */
		addAbove: function(nodeType, referenceNode) {
			this._add(nodeType, referenceNode, 'before');
		},

		/**
		 * Add a node of given node type after given node
		 *
		 * @param {string} nodeType
		 * @param {object} referenceNode
		 */
		addBelow: function(nodeType, referenceNode) {
			this._add(nodeType, referenceNode, 'after');
		},

		/**
		 * Add a node of given node type into given node
		 *
		 * @param {string} nodeType
		 * @param {object} referenceNode
		 */
		addInside: function(nodeType, referenceNode) {
			this._add(nodeType, referenceNode, 'into');
		},

		/**
		 * Creates a node on the server. When the result is received the callback function is called.
		 *
		 * @param {string} nodeType
		 * @param {object} referenceNode
		 * @param {string} position
		 */
		_add: function(nodeType, referenceNode, position) {
			var that = this,
				referenceNodeEntity = referenceNode.get('_vieEntity'),
				collectionModel = referenceNodeEntity._enclosingCollectionWidget.options.model,
				collection = referenceNodeEntity._enclosingCollectionWidget.options.collection,
				typoScriptPath = collectionModel.get('typo3:__typoscriptPath'),
				localXhr = this._prepareXhr();

			LoadingIndicator.start();
			NodeEndpoint.createAndRender(
				referenceNode.get('nodePath'),
				typoScriptPath,
				{
					nodeType: nodeType,
					properties: {}
				},
				position,
				{
					xhr: function() {
						return localXhr;
					}
				}
			).then(
				function(result) {
					that._insertNode(result, localXhr, nodeType, collection, position, referenceNodeEntity, false);
				}
			).fail(
				function(error) {
					that._reloadPage();
				}
			);
		},

		/**
		 * Inserts a node of the result into the collection at given position.
		 *
		 * @param {object} result Result containing the content collection and node path.
		 * @param {object} xhr xhr object of the ajax request.
		 * @param {string} nodeType The node type of the node
		 * @param {object} collection The collection to insert the node into.
		 * @param {string} position The position to insert the node relative to the reference node
		 * @param {object} referenceNodeEntity
		 * @param {boolean} isMoved If the inserted node was moved
		 * @return {void}
		 */
		_insertNode: function(result, xhr, nodeType, collection, position, referenceNodeEntity, isMoved) {
			var rdfaService = vieInstance.service('rdfa'),
				affectedNodePath = xhr.getResponseHeader('X-Neos-AffectedNodePath');
			var newElement = $(result).find('[about="' + affectedNodePath + '"]').first();
			if (newElement.length === 0) {
				console.warn('Node could not be found in rendered collection.');
				this._reloadPage();
				// reload page is deferred, to fulfill the promise we should return here to avoid the rest of the code to be executed.
				return;
			}

			rdfaService.setTemplate('typo3:' + nodeType, 'typo3:content-collection', function (entity, callback) {
				if (newElement.attr('about') !== undefined) {
					// Direct match with container element
					newElement.attr('about', '');
				}
				var subject = rdfaService.findPredicateElements(subject, newElement, false).each(function () {
					var predicateElement = $(this);
					var predicate = rdfaService.getElementPredicate(predicateElement);
					if (entity.has(predicate) && entity.get(predicate).isCollection) {
						return true;
					}
					rdfaService.writeElementValue(null, predicateElement, '');
				});
				callback(newElement);
			});
			vieInstance.load({element: newElement}).from('rdfa').execute();
			var subject = rdfaService.getElementSubject(newElement),
				nodeEntity = vieInstance.entities.get(subject),
				options = {};

			if (position !== 'into') {
				var referenceIndex = collection.indexOf(referenceNodeEntity);
				if (referenceIndex !== -1) {
					options.at = position === 'after' ? referenceIndex + 1 : referenceIndex;
				}
			}
			collection.add(nodeEntity, options);
			var $newElement = rdfaService.getElementBySubject(subject, $(document));

			if ($newElement.length === 0) {
				console.warn('Node could not be found in document.');
				this._reloadPage();
				// reload page is deferred, to fulfill the promise we should return here to avoid the rest of the code to be executed.
				return;
			}
			CreateJS.refreshEdit($newElement.get(0));

			if (isMoved) {
				// Replace existing entity wrapper in case it already exists
				NodeSelection.replaceEntityWrapper($newElement, true);
			}

			// Select the inserted node
			NodeSelection.updateSelection($newElement, {scrollToElement: true, deselectEditables: true, selectFirstEditable: !isMoved});

			EventDispatcher.trigger('contentChanged');
			EventDispatcher.triggerExternalEvent('Neos.NodeCreated', 'Node was created.', {element: $newElement.get(0)});
			LoadingIndicator.done();
		},

		_reloadPage: function() {
			require(
				{context: 'neos'},
				['Content/Application'],
				function(ContentModule) {
					ContentModule.reloadPage();
				}
			);
		},

		_prepareXhr: function() {
			var xhr = $.ajaxSettings.xhr();
			xhr.onreadystatechange = function () {
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
