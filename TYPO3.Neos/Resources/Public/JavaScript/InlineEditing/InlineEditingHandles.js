define(
[
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'emberjs',
	'vie',
	'text!InlineEditing/InlineEditingHandles.html',
	'Content/Application',
	'Content/Inspector/InspectorController',
	'Content/Model/NodeSelection',
	'Content/Model/NodeActions',
	'Content/Components/NewPositionSelectorButton',
	'Content/Components/PastePositionSelectorButton',
	'Shared/NodeTypeService',
	'InlineEditing/ContentCommands',
	'InlineEditing/Dialogs/DeleteNodeDialog',
	'Shared/I18n'
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
	NewPositionSelectorButton,
	PastePositionSelectorButton,
	NodeTypeService,
	ContentCommands,
	DeleteNodeDialog,
	I18n
) {
	return Ember.View.extend({
		classNames: ['neos-handle-container'],
		template: Ember.Handlebars.compile(template),

		_node: null,

		nodeSelection: NodeSelection,
		nodeActions: NodeActions,

		// this property mirrors the _hidden property of the node (it's automatically updated)
		_hidden: false,

		NewPositionSelectorButton: NewPositionSelectorButton.extend({
			allowedPositionsBinding: 'parentView.allowedNewPositions',
			triggerAction: function(position) {
				ContentCommands.create(position);
			}
		}),

		PastePositionSelectorButton: PastePositionSelectorButton.extend({
			allowedPositionsBinding: 'parentView.allowedPastePositions',
			triggerAction: function(position) {
				ContentCommands.paste(position);
			}
		}),

		_onNodeSelectionChange: function() {
			this.$().find('.action-new').trigger('hidePopover');
			var selectedNode = NodeSelection.get('selectedNode');
			if (!selectedNode) {
				return;
			}

			if (selectedNode.get('nodeType') === 'ALOHA-CONTROL') {
				selectedNode = selectedNode.node;
			}

			this.set('_node', selectedNode);

			if (selectedNode.isHideable()) {
				this.set('_showHide', true);
				this.set('_hidden', selectedNode.isHidden());
			} else {
				this.set('_showHide', false);
				this.set('_hidden', false);
			}

			var that = this;
			selectedNode.addObserver('typo3:_hidden', function() {
				that.set('_hidden', selectedNode.isHidden());
			});
		}.observes('nodeSelection.selectedNode'),

		currentFocusedNodeCanBeModified: function() {
			return this.get('_node') && !!this.get('_node').$element.data('node-_is-autocreated');
		}.property('_node'),

		allowedNewPositions: function() {
			var positions = [],
				selectedNode = this.get('_node');
			if (!selectedNode || NodeTypeService.isOfType(selectedNode, 'TYPO3.Neos:Document')) {
				return positions;
			}

			var possibleChildNodeTypes = ContentCommands.getAllowedChildNodeTypesForNode(selectedNode);
			if (possibleChildNodeTypes.length > 0) {
				positions.push('into');
			}

			var possibleSiblingNodeTypes = ContentCommands.getAllowedSiblingNodeTypesForNode(selectedNode);
			if (possibleSiblingNodeTypes.length > 0) {
				positions.push('before');
				positions.push('after');
			}
			return positions;
		}.property('_node'),

		allowedPastePositions: function() {
			var positions = [],
				selectedNode = this.get('_node'),
				sourceNode = this.get('nodeActions.clipboard');
			if (!selectedNode || !sourceNode || NodeTypeService.isOfType(selectedNode, 'TYPO3.Neos:Document')) {
				return positions;
			}

			var sourceNodeType = sourceNode.nodeType,
				possibleChildNodeTypes = ContentCommands.getAllowedChildNodeTypesForNode(selectedNode);
			if (possibleChildNodeTypes.length > 0 && possibleChildNodeTypes.contains(sourceNodeType)) {
				positions.push('into');
			}

			var possibleSiblingNodeTypes = ContentCommands.getAllowedSiblingNodeTypesForNode(selectedNode);
			if (possibleSiblingNodeTypes.length > 0 && possibleSiblingNodeTypes.contains(sourceNodeType)) {
				positions.push('before');
				positions.push('after');
			}
			return positions;
		}.property('_node', 'nodeActions.clipboard'),

		/** Content element actions **/
		remove: function() {
			DeleteNodeDialog.create({_node: this.get('_node')});
		},

		_hideToggleTitle: function() {
			return this.get('_hidden') === true ? I18n.translate('TYPO3.Neos:Main:unhide', 'Unhide') : I18n.translate('TYPO3.Neos:Main:hide', 'Hide');
		}.property('_hidden'),

		_thisElementStartedCut: function() {
			var clipboard = NodeActions.get('clipboard');
			if (!clipboard) {
				return false;
			}

			return (clipboard.type === 'cut' && clipboard.nodePath === this.get('_node.nodePath'));
		}.property('nodeActions.clipboard', '_node'),

		_thisElementStartedCopy: function() {
			var clipboard = NodeActions.get('clipboard');
			if (!clipboard) {
				return false;
			}

			return (clipboard.type === 'copy' && clipboard.nodePath === this.get('_node.nodePath'));
		}.property('nodeActions.clipboard', '_node'),

		toggleHidden: function() {
			var node = this.get('_node'),
				value = !node.getAttribute('_hidden');
			this.set('_hidden', value);
			node.setAttribute('_hidden', value);
			InspectorController.set('nodeProperties._hidden', value);
			InspectorController.apply();
		},

		cut: function() {
			ContentCommands.cut();
		},

		copy: function() {
			ContentCommands.copy();
		},

		didInsertElement: function() {
			this.$('[neos-data-tooltip]').tooltip({container: '#neos-application'});
		}
	});
});
