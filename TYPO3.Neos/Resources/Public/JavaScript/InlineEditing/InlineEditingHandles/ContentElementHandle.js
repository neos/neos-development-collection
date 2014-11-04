/**
 */
define(
[
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'emberjs',
	'vie',
	'text!InlineEditing/InlineEditingHandles/ContentElementHandle.html',
	'Content/Application',
	'Content/Inspector/InspectorController',
	'Content/Model/NodeSelection',
	'Content/Model/NodeActions',
	'Shared/NodeTypeService',
	'InlineEditing/ContentCommands',
	'InlineEditing/Dialogs/DeleteNodeDialog',
	'InlineEditing/InsertNodePanel'
],
function (
	$,
	_,
	Ember,
	vieInstance,
	template,
	Application,
	InspectorController,
	NodeSelection,
	NodeActions,
	NodeTypeService,
	ContentCommands,
	DeleteNodeDialog,
	InsertNodePanel
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),

		_node: null,

		$newAfterPopoverContent: null,

		nodeSelection: NodeSelection,
		nodeActions: NodeActions,

		// this property mirrors the _hidden property of the node (it's automatically updated)
		_hidden: false,

		_onNodeSelectionChange: function() {
			this.$().find('.action-new').trigger('hidePopover');
			var selectedNode = this.get('nodeSelection.selectedNode'),
				entity = selectedNode.get('_vieEntity');

			if (selectedNode && entity) {
				this.set('_node', selectedNode);

				entity.on('change', this._entityChanged, this);
				this._entityChanged();

				//this.set('_nodePath', this.get('_entity').getSubjectUri());

				if (selectedNode.isHideable()) {
					this.set('_showHide', true);
					this.set('_hidden', selectedNode.isHidden());
				} else {
					this.set('_showHide', false);
					this.set('_hidden', false);
				}
			}
		}.observes('nodeSelection.selectedNode'),

		// Button visibility flags
		_showHide: false,
		_showRemove: true,
		_showCut: true,
		_showCopy: true,

		/**
		 * @return {array}
		 */
		_getAllowedNodeTypesForSelectedNode: function() {
			var selectedNode = this.get('nodeSelection.selectedNode');
			if (!selectedNode) {
				// very early when initializing the user interface, the selectedNode is not set. Because the code below breaks
				// if node is null, we need to catch that here.
				return [];
			}

			// Collections are now always auto-created
			if (NodeTypeService.isOfType(selectedNode, 'TYPO3.Neos:ContentCollection')) {
				return NodeTypeService.getAllowedChildNodeTypesForAutocreatedNode(
					selectedNode.$element.data('node-__parent-node-type'),
					selectedNode.$element.data('node-_name')
				);
			} else if (selectedNode.$element.data('node-__grandparent-node-type') && selectedNode.$element.data('node-__parent-node-name')) {
				// The currently selected node is no collection, so we check the constraints on the parent collection
				return NodeTypeService.getAllowedChildNodeTypesForAutocreatedNode(
					selectedNode.$element.data('node-__grandparent-node-type'),
					selectedNode.$element.data('node-__parent-node-name')
				);
			}

			return [];
		},

		/**
		 * @return {boolean}
		 */
		_showCreate: function() {
			var allowedNodeTypes = this._getAllowedNodeTypesForSelectedNode();
			return allowedNodeTypes.length > 0;
		}.property('nodeSelection.selectedNode'),

		/**
		 * @return {boolean}
		 */
		_showPaste: function() {
			if (this.get('nodeActions.clipboardContainsContent') === false) {
				return false;
			}

			var nodeTypeOfNodeOnClipboard = this.get('nodeActions._clipboard.nodeType'),
				allowedNodeTypesToPaste = this._getAllowedNodeTypesForSelectedNode(),
				isAllowed = false;

			_.each(allowedNodeTypesToPaste, function(nodeType) {
				if (nodeType === nodeTypeOfNodeOnClipboard) {
					isAllowed = true;
				}
			});

			return isAllowed;
		}.property('nodeActions.clipboardContainsContent', 'nodeSelection.selectedNode'),

		_popoverPosition: 'right',

		_pasteTitle: 'Paste after',

		/**
		 * Returns the index of the content element in the current section
		 */
		_collectionIndex: function() {
			var node = this.get('_node'),
				enclosingCollectionWidget = node.get('_vieEntity')._enclosingCollectionWidget,
				entityIndex = enclosingCollectionWidget.options.collection.indexOf(node.get('_vieEntity'));

			if (entityIndex === -1) {
				entityIndex = enclosingCollectionWidget.options.collection.length;
			} else {
				entityIndex++;
			}
			return entityIndex;
		}.property('_node'),

		_entityChanged: function() {
			this.set('_hidden', this.get('_node._vieEntity').get('typo3:_hidden'));
		},

		/** Content element actions **/
		remove: function() {
			DeleteNodeDialog.create({
				_node: this.get('nodeSelection.selectedNode')
			}).appendTo($('#neos-application'));
		},

		newAfter: function() {
			InsertNodePanel.create({
				_node: this.get('nodeSelection.selectedNode'),
				_index: this.get('_collectionIndex')
			}).appendTo($('#neos-application'));
		},

		_hideToggleTitle: function() {
			return this.get('_hidden') === true ? 'Unhide' : 'Hide';
		}.property('_hidden'),

		_thisElementStartedCut: function() {
			var clipboard = NodeActions.get('_clipboard');
			if (!clipboard) {
				return false;
			}

			return (clipboard.type === 'cut' && clipboard.nodePath === this.get('_node.nodePath'));
		}.property('nodeActions._clipboard', '_node'),

		_thisElementStartedCopy: function() {
			var clipboard = NodeActions.get('_clipboard');
			if (!clipboard) {
				return false;
			}

			return (clipboard.type === 'copy' && clipboard.nodePath === this.get('_node.nodePath'));
		}.property('nodeActions._clipboard', '_node'),

		_thisElementIsAddingNewContent: function() {
			var elementIsAddingNewContent = NodeActions.get('_elementIsAddingNewContent');
			if (!elementIsAddingNewContent) {
				return false;
			}

			return (elementIsAddingNewContent === this.get('_node.nodePath'));
		}.property('nodeActions._elementIsAddingNewContent', '_node'),

		_elementIsPastingContent: function() {
			var elementIsPastingContent = NodeActions.get('_elementIsPastingContent');
			if (!elementIsPastingContent) {
				return false;
			}

			return (elementIsPastingContent === this.get('_node.nodePath'));
		}.property('nodeActions._elementIsPastingContent', '_node'),

		toggleHidden: function() {
			var entity = this.get('_node._vieEntity'),
				value = !entity.get('typo3:_hidden');
			this.set('_hidden', value);
			entity.set('typo3:_hidden', value);
			InspectorController.set('nodeProperties._hidden', value);
			InspectorController.apply();
		},

		cut: function() {
			ContentCommands.cut();
		},

		copy: function() {
			ContentCommands.copy();
		},

		paste: function() {
			if (ContentCommands.paste() === true) {
				NodeActions.set('_elementIsPastingContent', this.get('_node.nodePath'));
			}
		}
	});
});
