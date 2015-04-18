define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'vie',
	'Content/Model/NodeActions',
	'Content/Model/NodeSelection',
	'Shared/Notification',
	'Shared/NodeTypeService'
],
function (Ember, $, vieInstance, NodeActions, NodeSelection, Notification, NodeTypeService) {
	return Ember.Object.create({

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
		 * Returns true if the selected node's closest parent is a section
		 *
		 * @param {object} node
		 * @return {boolean}
		 */
		closestParentIsCollection: function(node) {
			var parentElement = node.$element.parents('[typeof^="typo3:"]').first();
			return parentElement.length > 0 ? NodeTypeService.isOfType(parentElement.attr('typeof').substr(6), 'TYPO3.Neos:ContentCollection') : false;
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
				referenceNode = NodeSelection.get('selectedNode');
			}

			if (this.isDocument(referenceNode)) {
				Notification.info('Select a content element or section for adding content');
				return;
			}

			if (typeof index === 'undefined') {
				index = this._collectionIndex(referenceNode);
			}

			require({context: 'neos'}, ['InlineEditing/InsertNodePanel'], function(InsertNodePanel) {
				if($('.neos-modal:visible').length > 0) {
					$('.neos-modal .neos-close').trigger('click');
				}

				InsertNodePanel.create({
					_node: referenceNode,
					_index: position === 'after' ? index : 0
				});
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
				node = NodeSelection.get('selectedNode');
			}

			NodeActions.cut(node);
		},

		/**
		 * Copy node, if no node is given the currently selected node is copied
		 *
		 * @param {object} node
		 * @return {void}
		 */
		copy: function(node) {
			if (!node) {
				node = NodeSelection.get('selectedNode');
			}

			NodeActions.copy(node);
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
				referenceNode = NodeSelection.get('selectedNode');
			}

			if (this.isCollection(referenceNode)) {
				return NodeActions.pasteInto(referenceNode);
			} else {
				return NodeActions.pasteAfter(referenceNode);
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
				node = NodeSelection.get('selectedNode');
			}

			require({context: 'neos'}, ['InlineEditing/Dialogs/DeleteNodeDialog'], function(DeleteNodeDialog) {
				if ($('.neos-modal:visible').length > 0) {
					$('.neos-modal .neos-close').trigger('click');
				}

				DeleteNodeDialog.create({_node: node});
			});
		},

		/**
		 * Returns the index of the content element in the current section
		 */
		_collectionIndex: function(node) {
			if (!node) {
				return 0;
			}

			var entity = node.get('_vieEntity'),
				enclosingCollectionWidget = entity._enclosingCollectionWidget,
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
