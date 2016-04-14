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
	return Ember.Object.extend({
		_nodePath: null,
		_selectedNode: null,

		nodeSelection: NodeSelection,

		_onNodeSelectionChange: function() {
			var selectedNode = NodeSelection.get('selectedNode');

			if (!selectedNode) {
				return;
			}

			if (selectedNode.get('nodeType') === 'ALOHA-CONTROL') {
				selectedNode = selectedNode.node;
			}

			this.set('_selectedNode', selectedNode);
		}.observes('nodeSelection.selectedNode'),

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
		 * @param {string} position could be after, before or into
		 * @return {void}
		 */
		create: function(position) {
			if (typeof position === 'undefined') {
				position = 'into';
			}

			var selectedNode = this.get('_selectedNode');
			require({context: 'neos'}, ['InlineEditing/InsertNodePanel'], function(InsertNodePanel) {
				if($('.neos-modal:visible').length > 0) {
					$('.neos-modal .neos-close').trigger('click');
				}

				InsertNodePanel.create({
					_node: selectedNode,
					_position: position
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
				node = this.get('_selectedNode');
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
				node = this.get('_selectedNode');
			}

			NodeActions.copy(node);
		},

		/**
		 * Paste node from clipboard
		 *
		 * @param {string} position Could be after, before or into
		 * @return {boolean}
		 */
		paste: function(position) {
			var referenceNode = this.get('_selectedNode');
			switch (position) {
				case 'before':
					return NodeActions.pasteBefore(referenceNode);
				case 'after':
					return NodeActions.pasteAfter(referenceNode);
				case 'into':
					return NodeActions.pasteInto(referenceNode);
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
				node = this.get('_selectedNode');
			}

			require({context: 'neos'}, ['InlineEditing/Dialogs/DeleteNodeDialog'], function(DeleteNodeDialog) {
				if ($('.neos-modal:visible').length > 0) {
					$('.neos-modal .neos-close').trigger('click');
				}

				DeleteNodeDialog.create({_node: node});
			});
		},

		_getAllowedChildNodeTypes: function(nodeName, nodeType, parentNodeType, isAutoCreated) {
			var types = null;
			if (typeof isAutoCreated !== 'undefined') {
				if ((typeof parentNodeType !== 'undefined') && (typeof nodeName !== 'undefined')) {
					types = NodeTypeService.getAllowedChildNodeTypesForAutocreatedNode(
						parentNodeType,
						nodeName
					);
				}
			} else if (typeof nodeType !== 'undefined') {
				types = NodeTypeService.getAllowedChildNodeTypes(nodeType);
			}

			if (types) {
				var contentTypes = NodeTypeService.getSubNodeTypes('TYPO3.Neos:Content'),
					contentTypesArray = Object.keys(contentTypes);
				return types.filter(function(n) {
					return contentTypesArray.indexOf(n) !== -1;
				});
			}

			return [];
		},

		/**
		 * Get allowed child node types.
		 *
		 * @param {object} node The node for which to determine allowed child node types
		 * @return {array}
		 */
		getAllowedChildNodeTypesForNode: function(node) {
			var isAutoCreated = node.$element.data('node-_is-autocreated');
			var nodeName = node.$element.data('node-_name');
			var nodeType = node.$element.data('node-_node-type');
			var parentNodeType = node.$element.data('node-__parent-node-type');
			return this._getAllowedChildNodeTypes(nodeName, nodeType, parentNodeType, isAutoCreated);
		},

		/**
		 * Get allowed sibling node types.
		 *
		 * @param {object} node The node for which to determine allowed sibling node types
		 * @return {array}
		 */
		getAllowedSiblingNodeTypesForNode: function(node) {
			var isAutoCreated = node.$element.data('node-_parent-is-autocreated');
			var nodeName = node.$element.data('node-__parent-node-name');
			var nodeType = node.$element.data('node-__parent-node-type');
			var parentNodeType = node.$element.data('node-__grandparent-node-type');
			return this._getAllowedChildNodeTypes(nodeName, nodeType, parentNodeType, isAutoCreated);
		}
	}).create();
});