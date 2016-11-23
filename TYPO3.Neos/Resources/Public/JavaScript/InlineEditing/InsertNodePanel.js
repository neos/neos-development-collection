define(
[
	'emberjs',
	'vie',
	'Content/Components/AbstractInsertNodePanel',
	'Content/Application',
	'Shared/Configuration',
	'Content/Model/NodeActions',
	'Shared/NodeTypeService',
	'InlineEditing/ContentCommands',
	'Shared/I18n',
	'LibraryExtensions/Mousetrap',
	'Content/InputEvents/KeyboardEvents'
],
function(
	Ember,
	vie,
	AbstractInsertNodePanel,
	ContentModule,
	Configuration,
	NodeActions,
	NodeTypeService,
	ContentCommands,
	I18n,
	Mousetrap,
	KeyboardEvents
) {
	return AbstractInsertNodePanel.extend({
		_node: null,
		_position: null,
		_preselectedNodeType: null,
		nodeTypes: null,

		init: function() {
			this._super();
			var that = this;
			var groups = {},
				node = this.get('_node'),
				currentNodeTypeName = node.$element.data('node-_nodeType'),
				position = this.get('_position'),
				types;

			this._initializeMoustrap();

			console.log(position);
			if (position === 'into') {
				types = ContentCommands.getAllowedChildNodeTypesForNode(node);
			} else {
				types = ContentCommands.getAllowedSiblingNodeTypesForNode(node);
			}
			if (types.length === 0) {
				this.destroy();
				return;
			}
			types = types.map(function(nodeType) {
				return 'typo3:' + nodeType;
			});

			var contentTypes = NodeTypeService.getSubNodeTypes('TYPO3.Neos:Content'),
				nodeTypeGroups = this.get('nodeTypeGroups'),
				vieTypes = this.get('_node._vieEntity._enclosingCollectionWidget').options.vie.types,
				nodeTypes = Ember.A(),
				globalPosition = 0;

			types.forEach(function(nodeType) {
				var type = vieTypes.get(nodeType);
				if (!type || !type.metadata || type.metadata.abstract === true) {
					return;
				}

				var nodeTypeName = type.id.slice(1, -1).replace(type.metadata.url, '');
				if (!contentTypes.hasOwnProperty(nodeTypeName)) {
					return;
				}

				var helpMessage = '';
				if (type.metadata.ui.help && type.metadata.ui.help.message) {
					helpMessage = type.metadata.ui.help.message;
				}

				var groupName = 'group' in type.metadata.ui ? type.metadata.ui.group : 'general';
				if (groupName) {
					var group = nodeTypeGroups.findBy('name', groupName);
					if (group) {
						var nodeTypeData = Ember.Object.extend({
							_activeClassName: 'neos-content-new-selecttype-button neos-content-new-selecttype-button--current',
							_defaultClassName: 'neos-content-new-selecttype-button',

							nodeType: nodeTypeName,
							label: I18n.translate(type.metadata.ui.label),
							helpMessage: helpMessage,
							active: false,
							icon: 'icon' in type.metadata.ui ? type.metadata.ui.icon : 'icon-file',
							position: type.metadata.ui.position,
							globalPosition: globalPosition,
							groupName: groupName,
							group: group,

							nodeTypeClassName: function () {
								return 'neos-content-new-nodetype--' + this.nodeType.toLowerCase().replace(/[\.:]/g, '-');
							}.property('nodeType'),

							className: function () {
								var className = this.active ? this._activeClassName : this._defaultClassName;
								return className += ' ' + this.get('nodeTypeClassName');
							}.property('active'),

							setActive: function() {
								this.set('active', true);
							},
							setInactive: function() {
								this.set('active', false);
							},
						}).create();
						if (nodeTypeName === currentNodeTypeName) {
							nodeTypeData.setActive();
							that.set('_preselectedNodeType', nodeTypeData);
						}
						group.get('nodeTypes').pushObject(nodeTypeData);
						nodeTypes.pushObject(nodeTypeData);
					} else {
						window.console.warn('Node type group "' + groupName + '" not found for node type "' + nodeTypeName + '", defined in "Settings" configuration "TYPO3.Neos.nodeTypes.groups"');
					}
				}
			});
			this.set('nodeTypes', nodeTypes);
		},

		flattenNodeTypes: function() {
			return [].concat.apply([], this.get('nodeTypeGroups').map(function(group) {return group.nodeTypes}));
		}.property('nodeTypes.@each'),

		_initializeMoustrap: function () {
			var that = this;

			Mousetrap.bind(['return'], function () {
				that.insertCurrentNodeType();
				return false;
			});

			Mousetrap.bind(['esc'], function () {
				that.destroy();
				return false;
			});

			Mousetrap.bind(['left'], function () {
				that.selectPreviousNodeType();
				return false;
			});

			Mousetrap.bind(['right'], function () {
				that.selectNextNodeType();
				return false;
			});

			Mousetrap.bind(['mod+shift+a'], function () {
				// Don't allow to open th insert node panel an other time
				return false;
			});
		},

		selectNodeType: function(nodeType) {
			if (this.get('_preselectedNodeType') !== null && nodeType === this.get('_preselectedNodeType').nodeType) {
				return;
			}
			var nodeTypeData = this.get('nodeTypes').findBy('nodeType', nodeType);
			nodeTypeData.setActive();

			this.get('nodeTypes').findBy('nodeType', this.get('_preselectedNodeType').nodeType).setInactive();

			this.set('_preselectedNodeType', nodeTypeData);
		},

		selectPreviousNodeType: function() {
			var that = this, previous,
				current = this.get('nodeTypes').findBy('nodeType', this.get('_preselectedNodeType').nodeType),
				nodeTypes = this.get('flattenNodeTypes');

			var i = nodeTypes.indexOf(current);

			previous = nodeTypes[i - 1];
			if (previous === undefined) {
				previous = nodeTypes[nodeTypes.length - 1];
			}
			that._toggleActive(current, previous);
		},

		selectNextNodeType: function() {
			var that = this, next,
				current = this.get('nodeTypes').findBy('nodeType', this.get('_preselectedNodeType').nodeType),
				nodeTypes = this.get('flattenNodeTypes');

			var i = nodeTypes.indexOf(current);

			if (i < nodeTypes.length - 1) {
				next = nodeTypes[i + 1];
			} else {
				next = nodeTypes[0];
			}
			that._toggleActive(current, next);
		},

		_toggleActive(previous, next) {
			var that = this,
				modalBody = this.$().find('.neos-modal-body'),
				bodyOffsetTop = modalBody.offset().top;

			// Toggle
			previous.setInactive();
			next.setActive();

			// Open the current group if needed
			next.group.set('collapsed', false);

			this.set('_preselectedNodeType', next);

			// Scroll the modal top position to have the selected type in the visible area
			// var nextTop = that.$().find('.' + next.get('nodeTypeClassName')).position().top;
			// console.log(nextTop);
			// modalBody.scrollTop(nextTop - bodyOffsetTop);
		},

		insertCurrentNodeType: function() {
			nodeTypeData = this.get('_preselectedNodeType');
			if (nodeTypeData === null) {
				return;
			}
			this.insertNode(nodeTypeData.nodeType);
		},

		/**
		 * @param {string} nodeType
		 */
		insertNode: function(nodeType) {
			var referenceNode = this.get('_node');
			switch (this.get('_position')) {
				case 'before':
					NodeActions.addAbove(nodeType, referenceNode);
				break;
				case 'after':
					NodeActions.addBelow(nodeType, referenceNode);
				break;
				case 'into':
					NodeActions.addInside(nodeType, referenceNode);
				break;
			}
			this.destroy();
		},

		destroy: function() {
			this._super();
			Mousetrap.unbind(['left', 'right', 'return', 'esc']);
			KeyboardEvents.initializeContentModuleEvents();
		},
	});
});
