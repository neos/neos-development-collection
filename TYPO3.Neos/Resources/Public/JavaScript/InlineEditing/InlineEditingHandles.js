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
	'Content/Components/AbstractPositionSelectorButton',
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
	AbstractPositionSelectorButton,
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

		NewPositionSelectorButton: AbstractPositionSelectorButton.extend({
			allowedPositionsBinding: 'parentView.allowedNewPositions',
			iconClass: 'icon-plus',
			init: function() {
				this._super();
				this.set('title', I18n.translate('TYPO3.Neos:Main:content.navigate.createNewHoldPosition', 'Create new (hold to select position)'));
			},

			mouseUp: function(event) {
				clearTimeout(this.get('downTimer'));
				this.set('downTimer', null);
				if (this.get('isActive') === true && this.get('isDisabled') === false && this.get('isExpanded') === false) {
					var newPosition = this.get('position');
					ContentCommands.create(newPosition);
				}
				$(event.target).filter('button').click();
			}
		}),

		PastePositionSelectorButton: AbstractPositionSelectorButton.extend({
			allowedPositionsBinding: 'parentView.allowedPastePositions',
			iconClass: 'icon-paste',
			init: function() {
				this._super();
				this.set('title', I18n.translate('TYPO3.Neos:Main:content.navigate.pasteHoldPosition', 'Paste (hold to select position)'));
			},

			mouseUp: function(event) {
				clearTimeout(this.get('downTimer'));
				this.set('downTimer', null);
				if (this.get('isActive') === true && this.get('isDisabled') === false && this.get('isExpanded') === false) {
					var pastePosition = this.get('position');
					ContentCommands.paste(pastePosition);
				}
				$(event.target).filter('button').click();
			}
		}),

		_onNodeSelectionChange: function() {
			this.$().find('.action-new').trigger('hidePopover');
			var selectedNode = this.get('nodeSelection.selectedNode'),
				entity = selectedNode.get('_vieEntity');

			if (selectedNode && entity) {
				this.set('_node', selectedNode);

				entity.on('change', this._entityChanged, this);
				this._entityChanged();

				if (selectedNode.isHideable()) {
					this.set('_showHide', true);
					this.set('_hidden', selectedNode.isHidden());
				} else {
					this.set('_showHide', false);
					this.set('_hidden', false);
				}
			}
		}.observes('nodeSelection.selectedNode'),

		currentFocusedNodeCanBeModified: function() {
			if (this.get('nodeSelection.selectedNode')) {
				if (this.get('nodeSelection.selectedNode').$element.data('node-_is-autocreated')) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}.property('nodeSelection.selectedNode'),

		allowedNewPositions: function() {
			var positions = [],
				selectedNode = this.get('nodeSelection.selectedNode');
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
		}.property('nodeSelection.selectedNode'),

		allowedPastePositions: function() {
			var positions = [],
				selectedNode = this.get('nodeSelection.selectedNode'),
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
		}.property('nodeSelection.selectedNode', 'nodeActions.clipboard'),

		_entityChanged: function() {
			this.set('_hidden', this.get('_node._vieEntity').get('typo3:_hidden'));
		},

		/** Content element actions **/
		remove: function() {
			DeleteNodeDialog.create({_node: this.get('nodeSelection.selectedNode')});
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
		}
	});
});
