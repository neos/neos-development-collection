/**
 * Abstract node tree
 */
define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'../Application',
		'Content/Model/Node',
		'Content/Components/NewPositionSelectorButton',
		'Content/Components/PastePositionSelectorButton',
		'../Model/NodeSelection',
		'Shared/Configuration',
		'Shared/NodeTypeService',
		'Shared/Notification',
		'Shared/EventDispatcher',
		'../Inspector/InspectorController',
		'./DeleteNodeDialog',
		'./InsertNodePanel',
		'LibraryExtensions/Mousetrap',
		'Shared/Endpoint/NodeEndpoint',
		'Shared/I18n'
	], function(
		Ember,
		$,
		ContentModule,
		EntityWrapper,
		NewPositionSelectorButton,
		PastePositionSelectorButton,
		NodeSelection,
		Configuration,
		NodeTypeService,
		Notification,
		EventDispatcher,
		InspectorController,
		DeleteNodeDialog,
		InsertNodePanel,
		Mousetrap,
		NodeEndpoint,
		I18n
	) {

		return Ember.View.extend({
			template: Ember.required(),

			/**
			 * DOM node which is the container for the dynatree
			 */
			$nodeTree: null,
			searchTerm: '',
			nodeType: '',
			insertNodePanelShown: false,
			cutNode: null,
			copiedNode: null,
			activeNode: null,
			dragInProgress: false,
			loadingDepth: 4,

			pageNodePath: null,
			siteNodeContextPath: null,
			siteRootUri: null,

			baseNodeType: Ember.K,

			statusCodes: {
				error: -1,
				loading: 1,
				ok: 0
			},

			_getAllowedChildNodeTypesForNode: function(node) {
				var nodeType;
				if (node.data.isAutoCreated) {
					nodeType = node.parent.data.nodeType;
				} else {
					nodeType = node.data.nodeType;
				}
				if (typeof nodeType === 'undefined') {
					// Should only be in case of a parent of siteNode
					nodeType = 'unstructured';
				}
				if (node.data.isAutoCreated) {
					return NodeTypeService.getAllowedChildNodeTypesForAutocreatedNode(nodeType, node.data.name);
				} else {
					return NodeTypeService.getAllowedChildNodeTypes(nodeType);
				}
			},

			_updateMetaInformation: function() {
				var documentMetadata = $('#neos-document-metadata');
				this.set('pageNodePath', documentMetadata.attr('about'));
				this.set('siteNodeContextPath', documentMetadata.data('neos-site-node-context-path'));
				this.set('siteRootUri', $('link[rel="neos-site"]').attr('href'));

				// Make sure we update the siteNodeContextPath in case the dimensions changed
				if (this.$nodeTree) {
					var rootNode = this.$nodeTree.dynatree('getRoot').getChildren()[0];
					if (rootNode) {
						rootNode.data.key = this.get('siteNodeContextPath');
						rootNode.data.href = this.get('siteRootUri');
						rootNode.render();
					}
				}
			},

			searchTermIsEmpty: function() {
				return this.get('searchTerm') === '';
			}.property('searchTerm'),

			allowedNewPositions: function() {
				var positions = [],
					activeNode = this.get('activeNode');
				if (!activeNode) {
					return positions;
				}

				if (typeof activeNode.data.nodeType !== 'undefined') {
					var possibleChildNodeTypes = this._getAllowedChildNodeTypesForNode(activeNode);
					if (possibleChildNodeTypes.length > 0) {
						positions.push('into');
					}
					if (typeof activeNode.parent.data.nodeType !== 'undefined') {
						var possibleSiblingNodeTypes = this._getAllowedChildNodeTypesForNode(activeNode.parent);
						if (possibleSiblingNodeTypes.length > 0) {
							positions.push('before');
							positions.push('after');
						}
					}
				}

				return positions;
			}.property('activeNode'),

			allowedPastePositions: function() {
				var positions = [],
					activeNode = this.get('activeNode'),
					sourceNode = this.get('cutNode') || this.get('copiedNode');
				if (!activeNode || !sourceNode) {
					return positions;
				}

				if (typeof activeNode.data.nodeType !== 'undefined') {
					var sourceNodeType = sourceNode.data.nodeType,
						possibleChildNodeTypes = this._getAllowedChildNodeTypesForNode(activeNode);
					if (possibleChildNodeTypes.length > 0 && possibleChildNodeTypes.contains(sourceNodeType)) {
						positions.push('into');
					}
					if (typeof activeNode.parent.data.nodeType !== 'undefined') {
						var possibleSiblingNodeTypes = this._getAllowedChildNodeTypesForNode(activeNode.parent);
						if (possibleSiblingNodeTypes.length > 0 && possibleSiblingNodeTypes.contains(sourceNodeType)) {
							positions.push('before');
							positions.push('after');
						}
					}
				}
				return positions;
			}.property('activeNode', 'cutNode', 'copiedNode'),

			NewPositionSelectorButton: NewPositionSelectorButton.extend({
				allowedPositionsBinding: 'parentView.allowedNewPositions',
				triggerAction: function(position) {
					this.get('parentView').create(position);
				}
			}),

			PastePositionSelectorButton: PastePositionSelectorButton.extend({
				allowedPositionsBinding: 'parentView.allowedPastePositions',
				triggerAction: function(position) {
					this.get('parentView').paste(position);
				}
			}),

			currentFocusedNodeIsHidden: function() {
				return this.get('activeNode') ? this.get('activeNode').data.isHidden : false;
			}.property('activeNode'),

			currentFocusedNodeIsCut: function() {
				return this.get('activeNode') && this.get('activeNode') === this.get('cutNode');
			}.property('activeNode', 'cutNode'),

			currentFocusedNodeIsCopied: function() {
				return this.get('activeNode') && this.get('activeNode') === this.get('copiedNode');
			}.property('activeNode', 'copiedNode'),

			currentFocusedNodeCantBeModified: function() {
				if (this.get('activeNode')) {
					if ((this.get('activeNode').data.isAutoCreated === true) ||
						(typeof this.get('activeNode').parent.data.nodeType === 'undefined')) {
						// AutoCreated node or root site node
						return true;
					} else {
						return false;
					}
				}
			}.property('activeNode'),

			init: function() {
				this._super();
				this._updateMetaInformation();

				var that = this;

				ContentModule.on('pageLoaded', this, function() {
					that.set('pageNodePath', $('#neos-document-metadata').attr('about'));
					this.trigger('afterPageLoaded');
				});
			},

			didInsertElement: function() {
				var that = this;
				$(document).ready(function() {
					that._initializeTree();
				});
			},

			treeConfiguration: {
				keyboard: true,
				selectMode: 1,
				minExpandLevel: 2,
				classNames: {
					container: 'neos-dynatree-container',
					node: 'neos-dynatree-node',
					folder: 'neos-dynatree-folder',

					empty: 'neos-dynatree-empty',
					vline: 'neos-dynatree-vline',
					expander: 'neos-dynatree-expander',
					connector: 'neos-dynatree-connector',
					checkbox: 'neos-dynatree-checkbox',
					nodeIcon: 'neos-dynatree-icon',
					title: 'neos-dynatree-title',
					noConnector: 'neos-dynatree-no-connector',

					nodeError: 'neos-dynatree-statusnode-error',
					nodeWait: 'neos-dynatree-statusnode-wait',
					hidden: 'neos-dynatree-hidden',
					combinedExpanderPrefix: 'neos-dynatree-exp-',
					combinedIconPrefix: 'neos-dynatree-ico-',
					nodeLoading: 'neos-dynatree-loading',
					hasChildren: 'neos-dynatree-has-children',
					active: 'neos-dynatree-active',
					selected: 'neos-dynatree-selected',
					expanded: 'neos-dynatree-expanded',
					lazy: 'neos-dynatree-lazy',
					focused: 'neos-dynatree-focused',
					partsel: 'neos-dynatree-partsel',
					lastsib: 'neos-dynatree-lastsib'
				},
				autoFocus: false,
				clickFolderMode: 1,
				debugLevel: 0, // 0: quiet, 1: normal, 2: debug
				cookieId: 'nodes',
				isDblClick: false,
				dnd: {
					autoExpandMS: 1000,
					preventVoidMoves: true, // Prevent dropping nodes 'before self', etc.

					/**
					 * Executed on beginning of drag.
					 * Returns false to cancel dragging of node.
					 */
					onDragStart: function(node) {
						var parent = node.tree.options.parent;
						// the root node should not be draggable
						if (node.data.key !== parent.get('siteNodeContextPath')) {
							parent.set('dragInProgress', true);
							Mousetrap.bind('esc', function() {
								parent.$nodeTree.dynatree('getTree').cancelDrag();
							});
							return true;
						} else {
							parent.set('dragInProgress', false);
							return false;
						}
					},

					onDragStop: function(node) {
						node.tree.options.parent.set('dragInProgress', false);
						Mousetrap.unbind('esc');
					},

					/**
					 * sourceNode may be null for non-dynatree droppables.
					 * Return false to disallow dropping on node. In this case
					 * onDragOver and onDragLeave are not called.
					 * Return 'over', 'before, or 'after' to force a hitMode.
					 * Return ['before', 'after'] to restrict available hitModes.
					 * Any other return value will calc the hitMode from the cursor position.
					 */
					onDragEnter: function(node, sourceNode) {
						var sourceNodeType = sourceNode.data.nodeType,
							positions = [],
							parent = node.tree.options.parent;

						if (typeof node.data.nodeType !== 'undefined') {
							var possibleChildrenNodeTypes = parent._getAllowedChildNodeTypesForNode(node);
							if (possibleChildrenNodeTypes.contains(sourceNodeType)) {
								positions.push('over');
							}
							if (typeof node.parent.data.nodeType !== 'undefined') {
								var possibleSiblingNodeTypes = parent._getAllowedChildNodeTypesForNode(node.parent);
								if (possibleSiblingNodeTypes.contains(sourceNodeType)) {
									positions.push('before');
									positions.push('after');
								}
							}
						}

						return positions;
					},

					onDragOver: function(node, sourceNode, hitMode) {
						if (node.isDescendantOf(sourceNode)) {
							return false;
						}
						return true;
					},

					/**
					 * This function MUST be defined to enable dropping of items on
					 * the tree.
					 *
					 * hit mode over, after and before
					 * !source node = new Node
					 */
					onDrop: function(node, sourceNode, hitMode, ui, draggable) {
						node.tree.options.parent.move(sourceNode, node, hitMode === 'over' ? 'into' : hitMode);
					}
				},

				/**
				 * The following callback is executed if an lazy-loading node
				 * has not yet been loaded.
				 *
				 * It might be executed multiple times in rapid succession,
				 * and needs to take care itself that it only fires one
				 * request per node at a time. This is implemented
				 * using node._currentlySendingServerRequest.
				 */
				onLazyRead: function(node) {
					var parent = node.tree.options.parent,
						parentLoadingDepth = parent.get('loadingDepth') ? parent.get('loadingDepth') : 0;
					this.options.parent.loadNode(node, node.getLevel() === 1 ? parentLoadingDepth : 1);
				},

				onActivate: function(node) {
					this.options.parent.set('activeNode', node);
				}
			},

			/**
			 * Initialize the dynatree instance stored at the DOM node
			 * this.$nodeTree
			 */
			_initializeTree: function() {
				if (this.$nodeTree) {
					return;
				}
				var treeConfiguration = this.get('treeConfiguration');
				treeConfiguration['strings'] = {
					loading: I18n.translate('TYPO3.Neos:Main:loading', 'Loading'),
					loadError: I18n.translate('TYPO3.Neos:Main:loadError', 'Load error!')
				};
				this.$nodeTree = this.$(this.treeSelector).dynatree(treeConfiguration);

				// Automatically expand the first node when opened
				this.$nodeTree.dynatree('getRoot').getChildren()[0].expand(true);
			},

			scrollToCurrentNode: function() {
				var activeNode = this.get('activeNode');
				if (!activeNode) {
					return;
				}

				var $activeNode = $(activeNode.span),
					$activeNodeLink = $('a', $activeNode),
					offsetTopLimit = $activeNode.height() * 5,
					activeNodeOffsetTop = this.$nodeTree.parent().scrollTop() + $activeNode.position().top;
				this.$nodeTree.parent().animate({
					scrollTop: activeNodeOffsetTop - offsetTopLimit > 0 ? activeNodeOffsetTop - offsetTopLimit : 0,
					scrollLeft: $activeNodeLink.position().left + $activeNodeLink.width() > this.$nodeTree.width() ? $activeNodeLink.position().left - $('span:first', $activeNode).width() : 0
				});
			},

			/**
			 * Observe elements that are existing on the page and update tree nodes accordingly
			 *
			 * @param {object} $elements An jQuery selection of elements containing schema information
			 */
			_initializePropertyObservers: function($elements) {
				var that = this,
					entityWrapper;

				$elements.each(function() {
					entityWrapper = NodeSelection.getNode($(this).attr('about'));
					if (!entityWrapper) {
						// element might not be existing; so we directly return
						return;
					}

					if (NodeTypeService.isOfType(entityWrapper, 'TYPO3.Neos:Document')) {
						entityWrapper.addObserver('typo3:title', function() {
							that.synchronizeNodeTitle(this);
						});
						entityWrapper.addObserver('typo3:_name', function() {
							that.synchronizeNodeName(this);
						});
						entityWrapper.addObserver('typo3:uriPathSegment', function() {
							that.synchronizeUriPathSegment(this);
						});
					}
					entityWrapper.addObserver('typo3:_hidden', function() {
						that.synchronizeNodeVisibility(this);
					});
					entityWrapper.addObserver('typo3:_hiddenInIndex', function() {
						that.synchronizeNodeVisibility(this);
					});
					entityWrapper.addObserver('typo3:_hiddenBeforeDateTime', function() {
						that.synchronizeNodeVisibility(this);
					});
					entityWrapper.addObserver('typo3:_hiddenAfterDateTime', function() {
						that.synchronizeNodeVisibility(this);
					});
					entityWrapper.addObserver('typo3:__label', function() {
						that.synchronizeNodeLabel(this);
					});
				});
			},

			synchronizeNodeVisibility: function(entityWrapper) {
				var now = new Date().getTime(),
					node = this.getNodeByEntityWrapper(entityWrapper);
				if (node) {
					var attributes = entityWrapper.get('attributes'),
						classes = node.data.addClass;
					if (attributes._hidden === true) {
						classes = $.trim(classes.replace(/neos-timedVisibility/g, ''));
						classes = classes + ' neos-hidden';
					} else if (attributes._hiddenBeforeDateTime !== ''
						&& new Date(attributes._hiddenBeforeDateTime).getTime() > now
						|| attributes._hiddenAfterDateTime !== '') {
						classes = classes + ' neos-timedVisibility';
					} else {
						classes = $.trim(classes.replace(/neos-timedVisibility/g, ''));
						classes = $.trim(classes.replace(/neos-hidden/g, ''));
					}
					if (attributes._hiddenInIndex === true) {
						classes = classes + ' neos-hiddenInIndex';
					} else {
						classes = $.trim(classes.replace(/neos-hiddenInIndex/g, ''));
					}
					node.data.isHidden = attributes._hidden;
					node.data.addClass = classes;
					node.render();
					this.notifyPropertyChange('activeNode');
				}
			},

			synchronizeNodeTitle: function(entityWrapper) {
				var node = this.getNodeByEntityWrapper(entityWrapper);
				if (node) {
					var title = entityWrapper.getAttribute('title');
					node.data.title = title
					node.data.fullTitle = title;
				}
			},

			synchronizeNodeLabel: function(entityWrapper) {
				var node = this.getNodeByEntityWrapper(entityWrapper);
				if (node) {
					node.setTitle(entityWrapper.getAttribute('__label'));
				}
			},

			synchronizeNodeName: function(entityWrapper) {
				var node = this.getNodeByEntityWrapper(entityWrapper);
				if (node) {
					var previousNodeName = entityWrapper.getPreviousAttribute('_name'),
						newNodeName = entityWrapper.getAttribute('_name');
					if (node.data.key) {
						node.data.key = node.data.key.replace(previousNodeName + '@', newNodeName + '@');
					}
					node.data.name = newNodeName;
					node.render();
					if (node.hasChildren() === true) {
						node.data.isLazy = true;
						// Remove children so they can't be clicked until they are reloaded
						node.removeChildren();
						node.setLazyNodeStatus(this.statusCodes.loading);

						this.one('afterPageLoaded', function() {
							node.data.isLazy = true;
							node.reloadChildren();
						});
					}
				}
			},

			synchronizeUriPathSegment: function(entityWrapper) {
				var node = this.getNodeByEntityWrapper(entityWrapper);
				if (node) {
					var previousUriPathSegment = entityWrapper.getPreviousAttribute('uriPathSegment'),
						newUriPathSegment = entityWrapper.getAttribute('uriPathSegment');

					if (node.data.href) {
						node.data.href = node.data.href.replace(previousUriPathSegment + '@', newUriPathSegment + '@');
					}
					node.render();
					if (node.hasChildren() === true) {
						node.data.isLazy = true;
						// Remove children so they can't be clicked until they are reloaded
						node.removeChildren();
						node.setLazyNodeStatus(this.statusCodes.loading);

						this.one('afterPageLoaded', function() {
							node.data.isLazy = true;
							node.reloadChildren();
						});
					}
				}
			},

			getNodeByEntityWrapper: function(entityWrapper) {
				if (this.$nodeTree && this.$nodeTree.children().length > 0) {
					return this.$nodeTree.dynatree('getTree').getNodeByKey(entityWrapper.get('nodePath'));
				}
				return null;
			},

			create: function(position) {
				var activeNode = this.get('activeNode');
				if (activeNode === null) {
					Notification.info(I18n.translate('TYPO3.Neos:Main:aNodeMustBeSelected', 'You have to select a node'));
					return;
				}

				var nodeType = this.get('nodeType'),
					nodeTypeDefiniton;

				if (nodeType !== '') {
					nodeTypeDefiniton = NodeTypeService.getNodeTypeDefinition(nodeType);
					this.createNode(activeNode, null, nodeType, nodeTypeDefiniton.ui.icon);
				} else {
					this.showCreateNodeDialog(activeNode, position);
				}
			},

			showCreateNodeDialog: function(activeNode, position) {
				var that = this,
					parentNode = activeNode.parent,
					allowedNodeTypes;

				this.set('insertNodePanelShown', true);

				if (position === 'into') {
					parentNode = activeNode;
				}

				allowedNodeTypes = this._getAllowedChildNodeTypesForNode(parentNode);

				// Only show node types which inherit from the base node type(s).
				// If the base node type is prefixed with "!", it is seen as negated.
				this.get('baseNodeType').split(',').forEach(function(nodeTypeFilter) {
					nodeTypeFilter = nodeTypeFilter.trim();

					allowedNodeTypes = allowedNodeTypes.filter(function (nodeTypeName) {
						if (nodeTypeFilter[0] === '!') {
							return !NodeTypeService.isOfType(nodeTypeName, nodeTypeFilter.substring(1));
						} else {
							return NodeTypeService.isOfType(nodeTypeName, nodeTypeFilter);
						}
					});
				});

				InsertNodePanel.create({
					allowedNodeTypes: allowedNodeTypes,
					_position: position,
					insertNode: function(nodeType, icon) {
						that.set('insertNodePanelShown', false);
						that.createNode(activeNode, null, nodeType, icon, position);
						this.cancel();
					},
					willDestroyElement: function() {
						that.set('insertNodePanelShown', false);
					}
				});
			},

			/**
			 * When clicking the delete node, we show a dialog
			 */
			showDeleteNodeDialog: function() {
				var activeNode = this.get('activeNode');
				if (activeNode === null) {
					Notification.info(I18n.translate('TYPO3.Neos:Main:aNodeMustBeSelected', 'You have to select a node'));
					return;
				}
				if (activeNode.getLevel() === 1) {
					Notification.info(I18n.translate('TYPO3.Neos:Main:rootNodeCannotBeDeleted', 'The Root node cannot be deleted.'));
					return;
				}

				var that = this;
				DeleteNodeDialog.create({
					title: activeNode.data.title,
					numberOfChildren: activeNode.data.children ? activeNode.data.children.length : 0,
					deleteNode: function() {
						that.deleteNode(activeNode);
						this.cancel();
					}
				});
			},

			createNode: function(activeNode, title, nodeType, iconClass, position) {
				var nodeTypeConfiguration = NodeTypeService.getNodeTypeDefinition(nodeType),
					data = {
						title: title ? title : I18n.translate('TYPO3.Neos:Main:loading', 'Loading'),
						nodeType: nodeType,
						nodeTypeLabel: nodeTypeConfiguration ? nodeTypeConfiguration.label : '',
						addClass: 'neos-matched',
						iconClass: iconClass,
						expand: true
					},
					newNode;

				switch (position) {
					case 'before':
						newNode = activeNode.getParent().addChild(data, activeNode);
					break;
					case 'after':
						newNode = activeNode.getParent().addChild(data, activeNode.getNextSibling());
					break;
					case 'into':
						newNode = activeNode.addChild(data);
				}
				this.persistNode(activeNode, newNode, nodeType, title, position);
			},

			persistNode: function(activeNode, node, nodeType, title, position) {
				var that = this,
					tree = node.tree,
					parameters = {
						nodeType: nodeType
					};
				if (title) {
					parameters.properties = {
						title: title
					};
				}

				node.setLazyNodeStatus(this.statusCodes.loading);
				NodeEndpoint.createNodeForTheTree(
					activeNode.data.key,
					parameters,
					position,
					this.baseNodeType
				).then(
					function(result) {
						// Actualizing the created dynatree node
						node.data.key = result.data.key;
						node.data.name = result.data.name;
						node.data.title = result.data.title;
						node.data.tooltip = result.data.tooltip;
						node.data.href = result.data.href;
						node.data.isFolder = result.data.isFolder;
						node.data.isLazy = result.data.isLazy;
						node.data.nodeType = result.data.nodeType;
						node.data.expand = result.data.expand;
						node.data.addClass = result.data.addClass;
						node.setLazyNodeStatus(that.statusCodes.ok);
						if (result.data.children) {
							node.addChild(result.data.children);
						}
						node.render();

						// Re-enable mouse and keyboard handling
						tree.$widget.bind();
						that.afterPersistNode(node);
					},
					function(error) {
						Notification.error(I18n.translate('TYPO3.Neos:Main:error.node.create.unexpected', 'Unexpected error while creating node') + ': ' + JSON.stringify(error));
						node.setLazyNodeStatus(that.statusCodes.error);
					}
				);
			},

			afterPersistNode: Ember.K,

			deleteNode: function(node) {
				node.setLazyNodeStatus(this.statusCodes.loading);
				var that = this;
				NodeEndpoint['delete'](node.data.key).then(
					function() {
						var parentNode = node.getParent();
						parentNode.activate();
						node.remove();
						that.afterDeleteNode(node);
					},
					function(error) {
						Notification.error(I18n.translate('TYPO3.Neos:Main:error.node.delete.unexpected', 'Unexpected error while deleting node') + ': ' + JSON.stringify(error));
					}
				);
			},

			afterDeleteNode: Ember.K,

			toggleHidden: function() {
				var node = this.get('activeNode');
				if (!node) {
					Notification.info(I18n.translate('TYPO3.Neos:Main:aNodeMustBeSelected', 'You have to select a node'));
				}
				var value = !node.data.isHidden;
				node.data.isHidden = value;
				// Trigger update of ``currentFocusedNodeIsHidden``
				this.notifyPropertyChange('activeNode');
				if (value === true) {
					node.data.addClass = node.data.addClass += ' neos-hidden';
				} else {
					node.data.addClass = node.data.addClass.replace('neos-hidden', '');
				}
				node.data.addClass = node.data.addClass.replace('  ', ' ');
				node.render();
				node.setLazyNodeStatus(this.statusCodes.loading);
				var that = this;
				NodeEndpoint.update(
					{
						__contextNodePath: node.data.key,
						_hidden: value
					}
				).then(
					function(result) {
						var nodeEntity = NodeSelection.getNode(node.data.key),
							selectedNodeEntity = NodeSelection.get('selectedNode');
						if (nodeEntity) {
							nodeEntity.setAttribute('_hidden', value);
							nodeEntity.setAttribute('__workspaceName', result.data.workspaceNameOfNode, {silent: true});
							if (nodeEntity === selectedNodeEntity) {
								InspectorController.set('cleanProperties._hidden', value);
								InspectorController.set('nodeProperties._hidden', value);
							}
						}
						EventDispatcher.trigger('nodeUpdated');
						node.setLazyNodeStatus(that.statusCodes.ok);
						that.afterToggleHidden(node);
					},
					function(error) {
						node.setLazyNodeStatus(that.statusCodes.error);
						Notification.error(I18n.translate('TYPO3.Neos:Main:error.node.update.unexpected', 'Unexpected error while updating node') + ': ' + JSON.stringify(error));
					}
				);
			},

			afterToggleHidden: Ember.K,

			copy: function() {
				var node = this.get('activeNode');
				if (!node) {
					Notification.info(I18n.translate('TYPO3.Neos:Main:aNodeMustBeSelected', 'You have to select a node'));
					return;
				}
				if (node.data.unselectable) {
					Notification.info(I18n.translate('TYPO3.Neos:Main:cannotCopyNode', 'You cannot copy this node'));
					return;
				}
				if (this.get('copiedNode') === node) {
					this.set('copiedNode', null);
				} else {
					this.set('copiedNode', node);
					this.set('cutNode', null);
				}
			},

			cut: function() {
				var node = this.get('activeNode');
				if (!node) {
					Notification.info(I18n.translate('TYPO3.Neos:Main:aNodeMustBeSelected', 'You have to select a node'));
					return;
				}
				if (node.data.unselectable) {
					Notification.info(I18n.translate('TYPO3.Neos:Main:cannotCutNode', 'You cannot cut this node'));
					return;
				}
				if (this.get('cutNode') === node) {
					this.set('cutNode', null);
				} else {
					this.set('cutNode', node);
					this.set('copiedNode', null);
				}
			},

			paste: function(position) {
				var targetNode = this.get('activeNode'),
					cutNode = this.get('cutNode'),
					copiedNode = this.get('copiedNode');
				if (!targetNode) {
					Notification.info(I18n.translate('TYPO3.Neos:Main:aNodeMustBeSelected', 'You have to select a node'));
				}
				if (cutNode) {
					this.set('cutNode', null);
					this.move(cutNode, targetNode, position);
				}
				if (copiedNode) {
					var that = this,
						newNode;
					switch (position) {
						case 'before':
							newNode = targetNode.getParent().addChild(copiedNode.data, targetNode);
						break;
						case 'after':
							newNode = targetNode.getParent().addChild(copiedNode.data, targetNode.getNextSibling());
						break;
						case 'into':
							newNode = targetNode.addChild(copiedNode.data);
					}
					newNode.setLazyNodeStatus(this.statusCodes.loading);
					NodeEndpoint.copy(
						copiedNode.data.key,
						targetNode.data.key,
						position,
						copiedNode.data.name
					).then(
						function(result) {
							// after we finished moving, update the node path/url
							newNode.data.href = result.data.nodeUri;
							newNode.data.key = result.data.newNodePath;
							newNode.render();
							newNode.setLazyNodeStatus(that.statusCodes.ok);
							if (typeof newNode.data.children !== 'undefined') {
								newNode.removeChildren();
								newNode.setLazyNodeStatus(that.statusCodes.loading);
								that.loadNode(newNode, 1);
							}
							that.afterPaste(newNode);
						},
						function(error) {
							newNode.setLazyNodeStatus(that.statusCodes.error);
							Notification.error(I18n.translate('TYPO3.Neos:Main:error.node.move.unexpected', 'Unexpected error while moving node') + ': ' + JSON.stringify(error));
						}
					);
				}
			},

			afterPaste: Ember.K,

			move: function(sourceNode, targetNode, position) {
				if (sourceNode === targetNode) {
					return;
				}
				var that = this;
				try {
					sourceNode.move(targetNode, position === 'into' ? 'over' : position);
					sourceNode.activate();
					sourceNode.setLazyNodeStatus(this.statusCodes.loading);
					NodeEndpoint.move(
						sourceNode.data.key,
						targetNode.data.key,
						position
					).then(
						function(result) {
							// Update the pageNodePath if we moved the current page
							if (that.get('pageNodePath') === sourceNode.data.key) {
								that.set('pageNodePath', result.data.newNodePath);
							} else {
								// handle pageNodePath if we moved a parent node
								var explodedParentPath = sourceNode.data.key.split('@');

								if (that.get('pageNodePath').indexOf(explodedParentPath[0]) === 0) {
									var newExplodedPath = result.data.newNodePath.split('@');

									that.set('pageNodePath', that.get('pageNodePath').replace(explodedParentPath[0], newExplodedPath[0]))
								}
							}

							// after we finished moving, update the node path/url
							sourceNode.data.href = result.data.nextUri;
							sourceNode.data.key = result.data.newNodePath;
							sourceNode.render();
							sourceNode.setLazyNodeStatus(that.statusCodes.ok);
							if (typeof sourceNode.data.children !== 'undefined') {
								sourceNode.removeChildren();
								sourceNode.setLazyNodeStatus(that.statusCodes.loading);
								that.loadNode(sourceNode, 1);
							}
							that.afterMove(sourceNode);
						},
						function(error) {
							sourceNode.setLazyNodeStatus(that.statusCodes.error);
							Notification.error(I18n.translate('TYPO3.Neos:Main:error.node.move.unexpected', 'Unexpected error while moving node') + ': ' + JSON.stringify(error));
						}
					);
				} catch(e) {
					Notification.error(I18n.translate('TYPO3.Neos:Main:error.node.move.unexpected', 'Unexpected error while moving node') + ': ' + e.toString());
				}
			},

			afterMove: Ember.K,

			refresh: function() {
				var node = this.$nodeTree.dynatree('getRoot').getChildren()[0];
				node.removeChildren();
				this.loadNode(node, 0);
			},

			/**
			 * Loads the children of the given node
			 *
			 * @param {object} node
			 * @param {number} depth
			 */
			loadNode: function(node, depth) {
				if (node._currentlySendingServerRequest) {
					return;
				}

				var that = this;
				node._currentlySendingServerRequest = true;
				node.setLazyNodeStatus(this.statusCodes.loading);
				NodeEndpoint.getChildNodesForTree(
					node.data.key,
					this.baseNodeType,
					depth,
					this.get('pageNodePath')
				).then(
					function(result) {
						node._currentlySendingServerRequest = false;
						node.setLazyNodeStatus(that.statusCodes.ok);
						node.addChild(result.data);
						that.afterLoadNode(node);
					},
					function() {
						node.setLazyNodeStatus(that.statusCodes.error);
						Notification.error(I18n.translate('TYPO3.Neos:Main:error.nodeTree.load', 'Node Tree loading error.'));
					}
				);
			},

			afterLoadNode: Ember.K
		});
	}
);
