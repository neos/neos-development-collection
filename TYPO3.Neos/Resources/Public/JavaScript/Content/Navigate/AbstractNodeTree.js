/**
 * Abstract node tree
 */
define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'../Application',
		'Content/Model/Node',
		'vie',
		'../Model/NodeSelection',
		'Shared/Configuration',
		'Shared/NodeTypeService',
		'Shared/Notification',
		'../Inspector/InspectorController',
		'./DeleteNodeDialog',
		'./InsertNodePanel',
		'LibraryExtensions/Mousetrap',
		'Shared/Endpoint/NodeEndpoint'
	], function(
		Ember,
		$,
		ContentModule,
		EntityWrapper,
		vieInstance,
		NodeSelection,
		Configuration,
		NodeTypeService,
		Notification,
		InspectorController,
		DeleteNodeDialog,
		InsertNodePanel,
		Mousetrap,
		NodeEndpoint
	) {
		var _getAllowedChildNodeTypesForNode = function(node) {
			if (node.data.isAutoCreated) {
				return NodeTypeService.getAllowedChildNodeTypesForAutocreatedNode(node.parent.data.nodeType, node.data.name);
			} else {
				return NodeTypeService.getAllowedChildNodeTypes(node.data.nodeType);
			}
		};

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
			unmodifiableLevels: 1,

			statusCodes: {
				error: -1,
				loading: 1,
				ok: 0
			},

			desiredNewPosition: 'after',
			newPosition: function() {
				var allowedNewPositions = this.get('allowedNewPositions'),
					desiredNewPosition = this.get('desiredNewPosition');
				return allowedNewPositions.indexOf(desiredNewPosition) !== -1 ? desiredNewPosition : allowedNewPositions[allowedNewPositions.length - 1];
			}.property('allowedNewPositions', 'desiredNewPosition'),
			allowedNewPositions: function() {
				var positions = [''],
					activeNode = this.get('activeNode');
				if (!activeNode) {
					return positions;
				}

				var level = activeNode.getLevel(),
					unmodifiableLevels = this.get('unmodifiableLevels');
				if (level >= unmodifiableLevels) {
					var possibleChildNodeTypes = _getAllowedChildNodeTypesForNode(activeNode);
					if (possibleChildNodeTypes.length > 0) {
						positions.push('into');
					}

					if (level > unmodifiableLevels) {
						var possibleSiblingNodeTypes = _getAllowedChildNodeTypesForNode(activeNode.parent);
						if (possibleSiblingNodeTypes.length > 0) {
							positions.push('before');
							positions.push('after');
						}
					}
				}
				return positions;
			}.property('activeNode'),

			desiredPastePosition: 'after',
			pastePosition: function() {
				var allowedPastePositions = this.get('allowedPastePositions'),
					desiredPastePosition = this.get('desiredPastePosition');
				return allowedPastePositions.indexOf(desiredPastePosition) !== -1 ? desiredPastePosition : allowedPastePositions[allowedPastePositions.length - 1];
			}.property('allowedPastePositions', 'desiredPastePosition'),
			allowedPastePositions: function() {
				var positions = [''],
					activeNode = this.get('activeNode'),
					sourceNode = this.get('cutNode') || this.get('copiedNode');
				if (!activeNode || !sourceNode) {
					return positions;
				}

				var level = activeNode.getLevel(),
					unmodifiableLevels = this.get('unmodifiableLevels');
				if (level >= unmodifiableLevels) {
					var sourceNodeType = sourceNode.data.nodeType,
						possibleChildNodeTypes = _getAllowedChildNodeTypesForNode(activeNode);
					if (possibleChildNodeTypes.length > 0 && possibleChildNodeTypes.contains(sourceNodeType)) {
						positions.push('into');
					}

					if (activeNode.getLevel() > unmodifiableLevels) {
						var possibleSiblingNodeTypes = _getAllowedChildNodeTypesForNode(activeNode.parent);
						if (possibleSiblingNodeTypes.length > 0 && possibleSiblingNodeTypes.contains(sourceNodeType)) {
							positions.push('before');
							positions.push('after');
						}
					}
				}
				return positions;
			}.property('activeNode', 'cutNode', 'copiedNode'),

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

			pasteIsActive: function() {
				return this.get('cutNode') !== null || this.get('copiedNode') !== null;
			}.property('cutNode', 'copiedNode'),

			searchTermIsEmpty: function() {
				return this.get('searchTerm') === '';
			}.property('searchTerm'),

			newButton: Ember.View.extend({
				active: true,
				expand: false,
				attributeBindings: ['title'],
				classNameBindings: [
					':neos-node-tree-new-node',
					':icon-plus',
					'active::neos-disabled',
					'inactive:neos-disabled',
					'expand:neos-expanded',
					'newBefore:node-node-tree-new-node-before',
					'newInto:node-node-tree-new-node-into',
					'newAfter:node-node-tree-new-node-after',
					'insertNodePanelShown:neos-pressed'
				],
				downTimer: null,

				newBefore: function() {
					return this.get('newPosition') === 'before';
				}.property('newPosition'),

				newInto: function() {
					return this.get('newPosition') === 'into';
				}.property('newPosition'),

				newAfter: function() {
					return this.get('newPosition') === 'after';
				}.property('newPosition'),

				newBeforeDisabled: function() {
					return this.get('allowedNewPositions').indexOf('before') === -1;
				}.property('allowedNewPositions'),

				newIntoDisabled: function() {
					return this.get('allowedNewPositions').indexOf('into') === -1;
				}.property('allowedNewPositions'),

				newAfterDisabled: function() {
					return this.get('allowedNewPositions').indexOf('after') === -1;
				}.property('allowedNewPositions'),

				inactive: function() {
					return this.get('newBeforeDisabled') && this.get('newIntoDisabled') && this.get('newAfterDisabled');
				}.property('newBeforeDisabled', 'newIntoDisabled', 'newAfterDisabled'),

				mouseDown: function() {
					if (this.get('expand') === true) {
						if ($(event.target).closest('.neos-node-tree-new-node-position').length === 0) {
							this.set('expand', false);
						}
					} else {
						var that = this;
						clearTimeout(this.get('downTimer'));
						this.set('downTimer', setTimeout(function() {
							that.set('expand', true);
						}, 300));
					}
				},

				mouseUp: function(event) {
					clearTimeout(this.get('downTimer'));
					this.set('downTimer', null);
					if ((this.get('active') && !this.get('inactive')) && this.get('expand') === false) {
						this.get('parentView').create();
					}
					$(event.target).filter('button').click();
				},

				mouseLeave: function() {
					this.set('expand', false);
				},

				toggleNewBefore: function() {
					this.set('desiredNewPosition', 'before');
					this.set('expand', false);
				},

				toggleNewInto: function() {
					this.set('desiredNewPosition', 'into');
					this.set('expand', false);
				},

				toggleNewAfter: function() {
					this.set('desiredNewPosition', 'after');
					this.set('expand', false);
				}
			}),

			pasteButton: Ember.View.extend({
				active: true,
				expand: false,
				attributeBindings: ['title'],
				classNameBindings: [
					':neos-node-tree-paste-node',
					':icon-paste',
					'active::neos-disabled',
					'inactive:neos-disabled',
					'expand:neos-expanded',
					'pastingBefore:node-node-tree-paste-node-before',
					'pastingInto:node-node-tree-paste-node-into',
					'pastingAfter:node-node-tree-paste-node-after'
				],
				downTimer: null,

				pastingBefore: function() {
					return this.get('pastePosition') === 'before';
				}.property('pastePosition'),

				pastingInto: function() {
					return this.get('pastePosition') === 'into';
				}.property('pastePosition'),

				pastingAfter: function() {
					return this.get('pastePosition') === 'after';
				}.property('pastePosition'),

				pasteBeforeDisabled: function() {
					return this.get('allowedPastePositions').indexOf('before') === -1;
				}.property('allowedPastePositions'),

				pasteIntoDisabled: function() {
					return this.get('allowedPastePositions').indexOf('into') === -1;
				}.property('allowedPastePositions'),

				pasteAfterDisabled: function() {
					return this.get('allowedPastePositions').indexOf('after') === -1;
				}.property('allowedPastePositions'),

				inactive: function() {
					return this.get('pasteBeforeDisabled') && this.get('pasteIntoDisabled') && this.get('pasteAfterDisabled');
				}.property('pasteBeforeDisabled', 'pasteIntoDisabled', 'pasteAfterDisabled'),

				mouseDown: function() {
					if (this.get('expand') === true) {
						if ($(event.target).closest('.neos-node-tree-paste-node-position').length === 0) {
							this.set('expand', false);
						}
					} else {
						var that = this;
						clearTimeout(this.get('downTimer'));
						this.set('downTimer', setTimeout(function() {
							that.set('expand', true);
						}, 300));
					}
				},

				mouseUp: function(event) {
					clearTimeout(this.get('downTimer'));
					this.set('downTimer', null);
					if ((this.get('active') && !this.get('inactive')) && this.get('expand') === false) {
						this.get('parentView').paste();
					}
					$(event.target).filter('button').click();
				},

				mouseLeave: function() {
					this.set('expand', false);
				},

				togglePasteBefore: function() {
					this.set('desiredPastePosition', 'before');
					this.set('expand', false);
				},

				togglePasteInto: function() {
					this.set('desiredPastePosition', 'into');
					this.set('expand', false);
				},

				togglePasteAfter: function() {
					this.set('desiredPastePosition', 'after');
					this.set('expand', false);
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

			currentFocusedNodeCanBeModified: function() {
				return this.get('activeNode') && this.get('activeNode').getLevel() <= this.get('unmodifiableLevels');
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
				strings: {
					loading: 'Loading...',
					loadError: 'Load error!'
				},
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
							level = node.getLevel(),
							unmodifiableLevels = node.tree.options.parent.get('unmodifiableLevels');

						if (level >= unmodifiableLevels) {
							var possibleChildrenNodeTypes = _getAllowedChildNodeTypesForNode(node);
							if (possibleChildrenNodeTypes.contains(sourceNodeType)) {
								positions.push('over');
							}

							if (level > unmodifiableLevels) {
								var possibleSiblingNodeTypes = _getAllowedChildNodeTypesForNode(node.parent);
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

				this.$nodeTree = this.$(this.treeSelector).dynatree(this.get('treeConfiguration'));

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
					entityWrapper = NodeSelection._createEntityWrapper(this);
					if (!entityWrapper) {
						// element might not have be existing; so we directly return
						return;
					}

					var vieEntity = entityWrapper._vieEntity;
					if (vieEntity.isof('typo3:TYPO3.Neos:Document')) {
						entityWrapper.addObserver('typo3:title', function() {
							that.synchronizeNodeTitle(vieEntity);
						});
						entityWrapper.addObserver('typo3:_name', function() {
							that.synchronizeNodeName(vieEntity);
						});
						entityWrapper.addObserver('typo3:uriPathSegment', function() {
							that.synchronizeUriPathSegment(vieEntity);
						});
					}
					entityWrapper.addObserver('typo3:_hidden', function() {
						that.synchronizeNodeVisibility(vieEntity);
					});
					entityWrapper.addObserver('typo3:_hiddenInIndex', function() {
						that.synchronizeNodeVisibility(vieEntity);
					});
					entityWrapper.addObserver('typo3:_hiddenBeforeDateTime', function() {
						that.synchronizeNodeVisibility(vieEntity);
					});
					entityWrapper.addObserver('typo3:_hiddenAfterDateTime', function() {
						that.synchronizeNodeVisibility(vieEntity);
					});
				});
			},

			synchronizeNodeVisibility: function(vieEntity) {
				var now = new Date().getTime(),
					node = this.getNodeByEntity(vieEntity);

				if (node) {
					var attributes = EntityWrapper.extractAttributesFromVieEntity(vieEntity),
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
					node.data.addClass = classes;
					node.render();
				}
			},

			synchronizeNodeTitle: function(vieEntity) {
				var node = this.getNodeByEntity(vieEntity);
				if (node) {
					var attributes = EntityWrapper.extractAttributesFromVieEntity(vieEntity);
					node.setTitle(attributes.title);
				}
			},

			synchronizeNodeName: function(vieEntity) {
				var node = this.getNodeByEntity(vieEntity);
				if (node) {
					var attributes = EntityWrapper.extractAttributesFromVieEntity(vieEntity);
					var previousNodeName = vieEntity.previousAttributes()['<' + Configuration.get('TYPO3_NAMESPACE') + '_name' + '>'];
					var newNodeName = attributes._name;
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

			synchronizeUriPathSegment: function(vieEntity) {
				var node = this.getNodeByEntity(vieEntity);
				if (node) {
					var uriPathSegmentAttributeUri = '<' + Configuration.get('TYPO3_NAMESPACE') + 'uriPathSegment' + '>',
						previousUriPathSegment = vieEntity.previousAttributes()[uriPathSegmentAttributeUri],
						newUriPathSegment = vieEntity.attributes['<' + Configuration.get('TYPO3_NAMESPACE') + 'uriPathSegment' + '>'];

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

			getNodeByEntity: function(vieEntity) {
				if (this.$nodeTree && this.$nodeTree.children().length > 0) {
					return this.$nodeTree.dynatree('getTree').getNodeByKey(vieEntity.getSubjectUri());
				}
				return null;
			},

			create: function() {
				var activeNode = this.get('activeNode');
				if (activeNode === null) {
					Notification.info('You have to select a node');
					return;
				}

				var nodeType = this.get('nodeType'),
					nodeTypeDefiniton;

				if (nodeType !== '') {
					nodeTypeDefiniton = NodeTypeService.getNodeTypeDefinition(nodeType);
					this.createNode(activeNode, null, nodeType, nodeTypeDefiniton.ui.icon);
				} else {
					this.showCreateNodeDialog(activeNode);
				}
			},

			showCreateNodeDialog: function(activeNode) {
				var that = this,
					parentNode = activeNode.parent,
					allowedNodeTypes,
					baseNodeType = this.get('baseNodeType');

				this.set('insertNodePanelShown', true);

				if (this.get('newPosition') === 'into') {
					parentNode = activeNode;
				}

				allowedNodeTypes = _getAllowedChildNodeTypesForNode(parentNode);

				// Only show node types which inherit from the base node type(s).
				// If the base node type is prefixed with "!", it is seen as negated.
				allowedNodeTypes = allowedNodeTypes.filter(function (nodeTypeName) {
					var allowed = false,
						splittedBaseNodeType = baseNodeType.split(',');

					// First find if the nodetype is allowed
					splittedBaseNodeType.forEach(function(nodeTypeFilter) {
						nodeTypeFilter = nodeTypeFilter.trim();

						if (nodeTypeFilter[0] !== '!' && allowed === false) {
							allowed = NodeTypeService.isOfType(nodeTypeName, nodeTypeFilter);
						}
					});

					if (allowed === true) {
						// Find if a negative filter is applied, and if so make sure it overrules the permission
						splittedBaseNodeType.forEach(function (nodeTypeFilter) {
							nodeTypeFilter = nodeTypeFilter.trim();

							if (allowed === true && nodeTypeFilter[0] === '!' && NodeTypeService.isOfType(nodeTypeName, nodeTypeFilter.substring(1))) {
								allowed = false;
							}
						});
					}

					return allowed;
				});

				InsertNodePanel.create({
					allowedNodeTypes: allowedNodeTypes,
					insertNode: function(nodeType, icon) {
						that.set('insertNodePanelShown', false);
						that.createNode(activeNode, null, nodeType, icon);
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
					Notification.info('You have to select a node');
					return;
				}
				if (activeNode.getLevel() === 1) {
					Notification.info('The Root node cannot be deleted.');
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

			createNode: function(activeNode, title, nodeType, iconClass) {
				var newPosition = this.get('newPosition'),
					data = {
						title: title ? title : 'Loading ...',
						nodeType: nodeType,
						addClass: 'neos-matched',
						iconClass: iconClass,
						expand: true
					},
					newNode;

				switch (newPosition) {
					case 'before':
						newNode = activeNode.getParent().addChild(data, activeNode);
					break;
					case 'after':
						newNode = activeNode.getParent().addChild(data, activeNode.getNextSibling());
					break;
					case 'into':
						newNode = activeNode.addChild(data);
				}
				this.persistNode(activeNode, newNode, nodeType, title, newPosition);
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
						Notification.error('Unexpected error while creating node: ' + JSON.stringify(error));
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
						Notification.error('Unexpected error while deleting node: ' + JSON.stringify(error));
					}
				);
			},

			afterDeleteNode: Ember.K,

			toggleHidden: function() {
				var node = this.get('activeNode');
				if (!node) {
					Notification.info('You have to select a node');
				}
				var value = !node.data.isHidden;
				this.set('currentFocusedNodeIsHidden', value);
				node.data.isHidden = value;
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
						var selectedNode = NodeSelection.get('selectedNode'),
							entity = vieInstance.entities.get(vieInstance.service('rdfa').getElementSubject(selectedNode.$element));
						if (entity) {
							entity.set('typo3:_hidden', value);
						}
						if (node.data.key === selectedNode.$element.attr('about')) {
							InspectorController.set('cleanProperties._hidden', value);
							InspectorController.set('nodeProperties._hidden', value);
						}
						node.setLazyNodeStatus(that.statusCodes.ok);
						that.afterToggleHidden(node);
					},
					function(error) {
						node.setLazyNodeStatus(that.statusCodes.error);
						Notification.error('Unexpected error while updating node: ' + JSON.stringify(error));
					}
				);
			},

			afterToggleHidden: Ember.K,

			copy: function() {
				var node = this.get('activeNode');
				if (!node) {
					Notification.info('You have to select a node');
					return;
				}
				if (node.data.unselectable) {
					Notification.info('You cannot copy this node');
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
					Notification.info('You have to select a node');
					return;
				}
				if (node.data.unselectable) {
					Notification.info('You cannot cut this node');
					return;
				}
				if (this.get('cutNode') === node) {
					this.set('cutNode', null);
				} else {
					this.set('cutNode', node);
					this.set('copiedNode', null);
				}
			},

			paste: function() {
				var targetNode = this.get('activeNode'),
					cutNode = this.get('cutNode'),
					copiedNode = this.get('copiedNode');
				if (!targetNode) {
					Notification.info('You have to select a node');
				}
				var pastePosition = this.get('pastePosition');
				if (cutNode) {
					this.set('cutNode', null);
					this.move(cutNode, targetNode, pastePosition);
				}
				if (copiedNode) {
					this.set('copiedNode', null);
					var that = this,
						newNode;
					switch (pastePosition) {
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
						pastePosition,
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
							Notification.error('Unexpected error while moving node: ' + JSON.stringify(error));
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
							Notification.error('Unexpected error while moving node: ' + JSON.stringify(error));
						}
					);
				} catch(e) {
					Notification.error('Unexpected error while moving node: ' + e.toString());
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
						Notification.error('Node Tree loading error.');
					}
				);
			},

			afterLoadNode: Ember.K
		});
	}
);
