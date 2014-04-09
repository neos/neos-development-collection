/**
 */
define(
[
	'Library/jquery-with-dependencies',
	'Library/underscore',
	'emberjs',
	'vie/instance',
	'text!InlineEditing/InlineEditingHandles/ContentElementHandle.html',
	'Content/Application',
	'Content/Inspector/InspectorController',
	'Content/Model/NodeSelection',
	'Content/Model/NodeActions',
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
	ContentCommands,
	DeleteNodeDialog,
	InsertNodePanel
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),

		_entity: null,
		_nodePath: null,
		_selectedNode: null,

		$newAfterPopoverContent: null,

		nodeSelection: NodeSelection,
		nodeActions: NodeActions,

		_onNodeSelectionChange: function() {
			this.$().find('.action-new').trigger('hidePopover');
			var selectedNode = NodeSelection.get('selectedNode');

			this.set('_selectedNode', selectedNode);
			if (selectedNode) {
				var entity = vieInstance.entities.get(vieInstance.service('rdfa').getElementSubject(selectedNode.$element));
				this.set('_entity', entity);

				entity.on('change', this._entityChanged, this);

				if (entity.has('typo3:_hidden') === true) {
					this.set('_showHide', true);
					this.set('_hidden', entity.get('typo3:_hidden'));
				} else {
					this.set('_showHide', false);
					this.set('_hidden', false);
				}

				this.set('_nodePath', this.get('_entity').getSubjectUri());
			}
		}.observes('nodeSelection.selectedNode'),

		// Button visibility flags
		_showHide: false,
		_showRemove: true,
		_showCut: true,
		_showCopy: true,

		_popoverPosition: 'right',

		_pasteTitle: 'Paste after',

		/**
		 * Returns the index of the content element in the current section
		 */
		_collectionIndex: function() {
			var entity = this.get('_entity'),
				enclosingCollectionWidget = entity._enclosingCollectionWidget,
				entityIndex = enclosingCollectionWidget.options.collection.indexOf(entity);

			if (entityIndex === -1) {
				entityIndex = enclosingCollectionWidget.options.collection.length;
			} else {
				entityIndex++;
			}
			return entityIndex;
		}.property('_entity'),

		_entityChanged: function() {
			this.set('_hidden', this.get('_entity').get('typo3:_hidden'));
		},

		/** Content element actions **/
		remove: function() {
			DeleteNodeDialog.create({
				_entity: this.get('_entity'),
				_node: this.get('_selectedNode')
			}).appendTo($('#neos-application'));
		},

		newAfter: function() {
			InsertNodePanel.create({
				_entity: this.get('_entity'),
				_node: this.get('_selectedNode'),
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

			return (clipboard.type === 'cut' && clipboard.nodePath === this.get('_nodePath'));
		}.property('nodeActions._clipboard', '_nodePath'),

		_thisElementStartedCopy: function() {
			var clipboard = NodeActions.get('_clipboard');
			if (!clipboard) {
				return false;
			}

			return (clipboard.type === 'copy' && clipboard.nodePath === this.get('_nodePath'));
		}.property('nodeActions._clipboard', '_nodePath'),

		_thisElementIsAddingNewContent: function() {
			var elementIsAddingNewContent = NodeActions.get('_elementIsAddingNewContent');
			if (!elementIsAddingNewContent) {
				return false;
			}

			return (elementIsAddingNewContent === this.get('_nodePath'));
		}.property('nodeActions._elementIsAddingNewContent', '_nodePath'),

		_elementIsPastingContent: function() {
			var elementIsPastingContent = NodeActions.get('_elementIsPastingContent');
			if (!elementIsPastingContent) {
				return false;
			}

			return (elementIsPastingContent === this.get('_nodePath'));
		}.property('nodeActions._elementIsPastingContent', '_nodePath'),

		toggleHidden: function() {
			var entity = this.get('_entity'),
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
				NodeActions.set('_elementIsPastingContent', this.get('_entity').getSubjectUri());
			}
		}
	});
});
