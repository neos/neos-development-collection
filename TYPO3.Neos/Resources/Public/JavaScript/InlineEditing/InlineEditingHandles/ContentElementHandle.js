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
		'neos/content/ui/elements/new-contentelement-popover-content',
		'Model/NodeCollection',
		'InlineEditing/Dialogs/DeleteNodeDialog'
	],
	function ($, _, Ember, vieInstance, template, Application, ContentElementPopoverContent, NodeCollection, DeleteNodeDialog) {

		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),

			_entity: null,
			_nodePath: null,
			_selectedNode: null,

			$newAfterPopoverContent: null,

			_onNodeSelectionChange: function() {
				this.$().find('.action-new').trigger('hidePopover');

				var selectedNode = T3.Content.Model.NodeSelection.get('selectedNode');

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
			}.observes('T3.Content.Model.NodeSelection.selectedNode'),

			// Button visibility flags
			_showHide: false,
			_showRemove: true,
			_showCut: true,
			_showCopy: true,

			_popoverPosition: 'right',

			/**
			 * Returns the index of the content element in the current section
			 */
			_collectionIndex: function() {
				var index = 0;
				_.each(this.get('_selectedNode').$element.parent().children().filter('.neos-contentelement'), function(element, iterator) {
					if (element === this.get('_selectedNode').$element.get(0)) {
						index = iterator;
					}
				}, this);
				if (index === 0) {
					return this.get('_selectedNode').$element.parent().children().filter('.neos-contentelement').length + 1;
				}
				return index + 1;
			}.property('_selectedNode'),

			didInsertElement: function() {
				this.$newAfterPopoverContent = $('<div />', {id: this.get(Ember.GUID_KEY)});
			},

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
				var that = this;

				this.$().find('.action-new').popover({
					additionalClasses: 'neos-new-contentelement-popover',
					content: this.$newAfterPopoverContent,
					preventLeft: this.get('_popoverPosition') === 'left' ? false : true,
					preventRight: this.get('_popoverPosition') === 'right' ? false : true,
					preventTop: this.get('_popoverPosition') === 'top' ? false : true,
					preventBottom: this.get('_popoverPosition') === 'bottom' ? false : true,
					positioning: 'absolute',
					zindex: 10090,
					closeEvent: function() {
						that.set('pressed', false);
					},
					openEvent: function() {
						that.onPopoverOpen.call(that);
					}
				});
				this.$().find('.action-new').trigger('showPopover');
			},

			onPopoverOpen: function() {
				var groups = {};
				_.each(NodeCollection.get('content').options.definition.range, function(nodeType) {
					var type = NodeCollection.get('content').options.vie.types.get(nodeType);
					type.metadata.nodeType = type.id.substring(1, type.id.length - 1).replace(Application.TYPO3_NAMESPACE, '');

					if (type.metadata.ui && type.metadata.ui.group) {
						if (!groups[type.metadata.ui.group]) {
							groups[type.metadata.ui.group] = {
								name: type.metadata.ui.group,
								children: []
							};
						}
						groups[type.metadata.ui.group].children.push(type.metadata);
					}
				}, this);

					// Make the data object an array for usage in #each helper
				var data = [];
				T3.Configuration.nodeTypeGroups.forEach(function(groupName) {
					if (groups[groupName]) {
						data.push(groups[groupName]);
					}
				});

				ContentElementPopoverContent.create({
					_options: NodeCollection.get('content').options,
					_index: this.get('_collectionIndex'),
					_clickedButton: this,
					data: data
				}).replaceIn(this.$newAfterPopoverContent);
			},

			_hideToggleTitle: function() {
				return this.get('_hidden') === true ? 'Unhide' : 'Hide';
			}.property('_hidden'),

			_thisElementStartedCut: function() {
				var clipboard = T3.Content.Controller.NodeActions.get('_clipboard');
				if (!clipboard) {
					return false;
				}

				return (clipboard.type === 'cut' && clipboard.nodePath === this.get('_nodePath'));
			}.property('T3.Content.Controller.NodeActions._clipboard', '_nodePath'),

			_thisElementStartedCopy: function() {
				var clipboard = T3.Content.Controller.NodeActions.get('_clipboard');
				if (!clipboard) {
					return false;
				}

				return (clipboard.type === 'copy' && clipboard.nodePath === this.get('_nodePath'));
			}.property('T3.Content.Controller.NodeActions._clipboard', '_nodePath'),

			_thisElementIsAddingNewContent: function() {
				var elementIsAddingNewContent = T3.Content.Controller.NodeActions.get('_elementIsAddingNewContent');
				if (!elementIsAddingNewContent) {
					return false;
				}

				return (elementIsAddingNewContent === this.get('_nodePath'));
			}.property('T3.Content.Controller.NodeActions._elementIsAddingNewContent', '_nodePath'),

			_pasteInProgress: false,

			toggleHidden: function() {
				var entity = this.get('_entity'),
					value = !entity.get('typo3:_hidden');
				this.set('_hidden', value);
				entity.set('typo3:_hidden', value);
				T3.Content.Controller.Inspector.nodeProperties.set('_hidden', value);
				T3.Content.Controller.Inspector.apply();
			},

			cut: function() {
				T3.Content.Controller.NodeActions.cut(this.get('_nodePath'));
			},

			copy: function() {
				T3.Content.Controller.NodeActions.copy(this.get('_nodePath'));
			},

			pasteAfter: function() {
				if (T3.Content.Controller.NodeActions.pasteAfter(this.get('_nodePath')) === true) {
					this.set('_pasteInProgress', true);
				}
			}
		});
	}
);
