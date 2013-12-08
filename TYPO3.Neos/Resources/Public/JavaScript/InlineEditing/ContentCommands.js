define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'vie/instance',
	'Content/Model/NodeActions',
	'Content/Model/NodeSelection',
	'Shared/Notification',
	'Shared/NodeTypeService'
],
function (Ember, $, vieInstance, NodeActions, NodeSelection, Notification, NodeTypeService) {
	return Ember.Object.create({

		_entity: null,
		_nodePath: null,
		_selectedNode: null,

		/**
		 * Returns true if the selected node is a document
		 *
		 * @param {object} node
		 * @return {boolean}
		 */
		isDocument: function(node) {
			return NodeTypeService.isOfType(node, 'TYPO3.Neos:Document');
		},

		/**
		 * Returns true if the selected node is a section
		 *
		 * @param {object} node
		 * @return {boolean}
		 */
		isCollection: function(node) {
			return NodeTypeService.isOfType(node, 'TYPO3.Neos:ContentCollection');
		},

		/**
		 * Opens the create new node dialog. Given position, referenceNode
		 * and index are optional.
		 *
		 * If no reference is given the reference will be based on the current
		 * node selection.
		 *
		 * @param {string} position could be after or into
		 * @param {node} referenceNode node which will be used as reference for positioning
		 * @param {integer} index the index to add the new node (overrides the position based on referenceNode)
		 * @return {void}
		 */
		create: function(position, referenceNode, index) {
			if (!referenceNode) {
				referenceNode = this._getSelectedNode();
			}

			if (this.isDocument(referenceNode)) {
				Notification.info('Select a content element or section for adding content');
				return;
			}

			var entity = this._getEntity(referenceNode);
			if (!entity) {
				return;
			}

			if (typeof index === 'undefined') {
				index = this._collectionIndex(entity);
			}

			require(['InlineEditing/InsertNodePanel'], function(InsertNodePanel) {
				if($('.neos-modal:visible').length > 0) {
					$('.neos-modal .neos-close').trigger('click');
				}

				InsertNodePanel.create({
					_entity: entity,
					_node: referenceNode,
					_index: position === 'after' ? index : 0
				}).appendTo($('#neos-application'));
			});
		},

		/**
		 * Cut node, if no node is given the currently selected node is cut
		 *
		 * @param {object} node
		 * @return {void}
		 */
		cut: function(node) {
			if (!node) {
				node = this._getSelectedNode();
			}

			var entity = this._getEntity(node);
			if (!entity) {
				return;
			}

			NodeActions.cut(entity.getSubjectUri());
		},

		/**
		 * Copy node, if no node is given the currently selected node is copied
		 *
		 * @param {object} node
		 * @return {void}
		 */
		copy: function(node) {
			if (!node) {
				node = this._getSelectedNode();
			}

			var entity = this._getEntity(node);
			if (!entity) {
				return;
			}

			NodeActions.copy(entity.getSubjectUri());
		},

		/**
		 * Paste node from clipboard
		 *
		 * @param {string} position Could be after or into
		 * @param {object} referenceNode Node used as reference to find the location to paste, if left out the currently selected node is used
		 * @return {boolean}
		 */
		paste: function(position, referenceNode) {
			if (!referenceNode) {
				referenceNode = this._getSelectedNode();
			}

			var entity = this._getEntity(referenceNode);
			if (!entity) {
				return;
			}

			if (this.isCollection(referenceNode)) {
				return NodeActions._paste(entity.getSubjectUri(), 'into');
			} else {
				return NodeActions.pasteAfter(entity.getSubjectUri());
			}
		},

		/**
		 * Show the delete node dialog.
		 *
		 * @param {object} node The node to delete, if left out the currently selected node is used
		 * @return {void}
		 */
		remove: function(node) {
			if (!node) {
				node = this._getSelectedNode();
			}

			var entity = this._getEntity(node);
			if (!entity) {
				return;
			}

			require(['InlineEditing/Dialogs/DeleteNodeDialog'], function(DeleteNodeDialog) {
				if ($('.neos-modal:visible').length > 0) {
					$('.neos-modal .neos-close').trigger('click');
				}

				DeleteNodeDialog.create({
					_entity: entity,
					_node: node
				}).appendTo($('#neos-application'));
			});
		},

		_getSelectedNode: function() {
			return NodeSelection.get('selectedNode');
		},

		_getEntity: function(selectedNode) {
			return vieInstance.entities.get(vieInstance.service('rdfa').getElementSubject(selectedNode.$element));
		},

		/**
		 * Returns the index of the content element in the current section
		 */
		_collectionIndex: function(entity) {
			if (!entity) {
				return 0;
			}

			var enclosingCollectionWidget = entity._enclosingCollectionWidget,
				entityIndex = enclosingCollectionWidget.options.collection.indexOf(entity);

			if (entityIndex === -1) {
				entityIndex = enclosingCollectionWidget.options.collection.length;
			} else {
				entityIndex++;
			}
			return entityIndex;
		}

	});
});