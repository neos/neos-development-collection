/**
 * Node tree
 */
define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'./AbstractNodeTree',
		'../Application',
		'Shared/Configuration',
		'Shared/ResourceCache',
		'Shared/EventDispatcher',
		'Shared/Notification',
		'Shared/NodeTypeService',
		'../Model/NodeSelection',
		'../Model/PublishableNodes',
		'./NavigatePanelController',
		'../Inspector/InspectorController',
		'text!./NodeTree.html',
		'Shared/Endpoint/NodeEndpoint',
		'Shared/I18n'
	], function(
		Ember,
		$,
		AbstractNodeTree,
		ContentModule,
		Configuration,
		ResourceCache,
		EventDispatcher,
		Notification,
		NodeTypeService,
		NodeSelection,
		PublishableNodes,
		NavigatePanelController,
		InspectorController,
		template,
		NodeEndpoint,
		I18n
	) {
		var documentMetadata = $('#neos-document-metadata');

		return AbstractNodeTree.extend({
			elementId: ['neos-node-tree'],
			classNameBindings: ['filtering:neos-node-tree-filtering'],
			template: Ember.Handlebars.compile(template),
			treeSelector: '#neos-node-tree-tree',

			controller: NavigatePanelController,

			publishableNodes: PublishableNodes,

			editNodeTitleMode: false,
			searchTerm: '',
			nodeType: '',
			filtering: false,
			latestFilterQuery: null,

			markDirtyNodes: function() {
				$('.neos-dynatree-dirty', this.$nodeTree).removeClass('neos-dynatree-dirty');

				var that = this;
				PublishableNodes.get('workspaceWidePublishableEntitySubjects').forEach(function(node) {
					var treeNode = that.$nodeTree.dynatree('getTree').getNodeByKey(node.documentNodeContextPath);
					if (treeNode) {
						$(treeNode.span).addClass('neos-dynatree-dirty');
					}
				});
			}.observes('publishableNodes.workspaceWidePublishableEntitySubjects.@each'),

			searchTermIsEmpty: function() {
				return this.get('searchTerm') === '';
			}.property('searchTerm'),

			clearSearchTerm: function() {
				var searchIsEmpty = this.get('searchTermIsEmpty');
				this.set('searchTerm', '');
				if (!searchIsEmpty) {
					this.filterTree();
				}
			},

			searchField: Ember.TextField.extend({
				_delay: 300,
				_value: '',
				_timeout: null,
				attributeBindings: ['placeholder'],
				placeholder: function() {
					return I18n.translate('TYPO3.Neos:Main:search', 'Search');
				}.property(),
				keyUp: function() {
					var that = this,
						value = this.get('value');
					if (this.get('_timeout') !== null) {
						clearTimeout(this.get('_timeout'));
					}
					this.set('_timeout', setTimeout(function() {
						if (value !== that.get('_value')) {
							that.set('_value', value);
							that.get('parentView').filterTree();
						}
					}, this._delay));
				}
			}),

			init: function() {
				this._super();
				this.set('loadingDepth', Configuration.get('UserInterface.navigateComponent.nodeTree.loadingDepth'));
				this.set('baseNodeType', Configuration.get('UserInterface.navigateComponent.nodeTree.presets.default.baseNodeType'));

				this.on('afterPageLoaded', function(){
					this._initializePropertyObservers($('#neos-document-metadata'));
				});

				var that = this;
				EventDispatcher.on('nodesInvalidated', function() {
					that.refresh();
				});
			},

			didInsertElement: function() {
				this._super();

				var that = this,
					$neosNodeTypeSelect = that.$().find('#neos-node-tree-filter select'),
					availableNodeTypes = NodeTypeService.getSubNodeTypes(this.get('baseNodeType'));
				$neosNodeTypeSelect.chosen({disable_search_threshold: 10, allow_single_deselect: true});

				$.each(availableNodeTypes, function(nodeTypeName, nodeTypeInfo) {
					$neosNodeTypeSelect.append('<option value="' + nodeTypeName + '">' + I18n.translate(nodeTypeInfo.ui.label) + '</option>');
				});
				$neosNodeTypeSelect.trigger('chosen:updated.chosen');

				// Type filter
				$neosNodeTypeSelect.change(function() {
					that.set('nodeType', $neosNodeTypeSelect.val());
					that.filterTree();
				});

				EventDispatcher.on('contentDimensionsSelectionChanged', function() {
					that.refresh();
				});
			},

			onContextStructureModeChanged: function() {
				this.scrollToCurrentNode();
			}.observes('controller.contextStructureMode'),

			/**
			 * Initialize the dynatree instance stored at the DOM node
			 * this.$nodeTree
			 */
			_initializeTree: function() {
				if (this.$nodeTree) {
					return;
				}

				var siteName = documentMetadata.data('neos-site-name'),
					nodeType = documentMetadata.attr('typeof').substr(6),
					nodeTypeConfiguration = NodeTypeService.getNodeTypeDefinition(nodeType);

				this.set('treeConfiguration', $.extend(true, this.get('treeConfiguration'), {
					autoFocus: true,
					parent: this,
					children: [
						{
							title: siteName,
							tooltip: siteName + (nodeTypeConfiguration ? ' (' + I18n.translate(nodeTypeConfiguration.label) + ')' : ''),
							href: $('link[rel="neos-site"]').attr('href'),
							key: this.get('siteNodeContextPath'),
							isFolder: true,
							expand: false,
							isLazy: true,
							select: false,
							active: false,
							changed: false,
							unselectable: false,
							nodeType: nodeType,
							addClass: 'neos-matched',
							iconClass: 'icon-globe'
						}
					],

					onClick: function(node, event) {
						if (this.options.parent.get('editNodeTitleMode') === true) {
							return false;
						}
						// only if the node title was clicked
						// and it was not active at this time
						// it should be navigated to the target node
						if (node.getEventTargetType(event) === 'title' || node.getEventTargetType(event) === null) {
							var that = this;
							setTimeout(function() {
								if (!that.isDblClick) {
									ContentModule.loadPage(node.data.href);
								}
							}, 300);
						}

						event.preventDefault();
						return true;
					},

					onDblClick: function(node, event) {
						if (node.getEventTargetType(event) === 'title' && node.getLevel() !== 1) {
							this.isDblClick = true;
							var that = this;
							setTimeout(function() {
								that.isDblClick = false;
								that.options.parent.editNode(node);
							}, 300);
						}
					},

					onKeydown: function(node, event) {
						switch (event.which) {
							case 113: // [F2]
								this.options.parent.editNode(node);
								return false;
							case 69: // [e]
								this.options.parent.editNode(node);
								return false;
						}
					},

					onRender: function(node, nodeSpan) {
						if (PublishableNodes.get('workspaceWidePublishableEntitySubjects').findBy('documentNodeContextPath', node.data.key)) {
							$(nodeSpan).addClass('neos-dynatree-dirty');
						}
						$('a[title]', nodeSpan).tooltip({container: '#neos-application'});
					}
				}));

				this._super();

				this._initializePropertyObservers(documentMetadata);

				// Activate the current node in the tree if possible
				var pageTreeNode = this.getPageTreeNode();
				if (pageTreeNode) {
					pageTreeNode.activate();
					pageTreeNode.select();
				}

				// Handles click events when a page title is in edit mode so clicks on other pages leads not to reloads
				var that = this;
				this.$nodeTree.click(function() {
					return that.get('editNodeTitleMode') !== true;
				});
			},

			_onPageNodePathChanged: function() {
				var pageNode = this.getPageTreeNode();
				if (pageNode) {
					pageNode.activate();
					pageNode.select();
					this.scrollToCurrentNode();
				}
			}.observes('pageNodePath'),

			getPageTreeNode: function() {
				if (this.$nodeTree && this.$nodeTree.children().length > 0) {
					return this.$nodeTree.dynatree('getTree').getNodeByKey(this.get('pageNodePath'));
				}
				return null;
			},

			afterDeleteNode: function(node) {
				var isCurrentNode = node.data.key === this.get('pageNodePath');
				if (isCurrentNode) {
					ContentModule.loadPage(node.getParent().data.href);
				}
				EventDispatcher.trigger('nodeDeleted', node.getParent());
			},

			afterMove: function(node) {
				var isCurrentNode = node.data.key === this.get('pageNodePath');
				if (isCurrentNode) {
					ContentModule.loadPage(node.data.href);
				} else {
					// if the current viewed page is a children of the moved page load it
					var explodedPath = node.data.key.split('@');
					if (this.get('pageNodePath').indexOf(explodedPath[0]) === 0) {
						ContentModule.loadPage(node.data.href);
					}
				}

				EventDispatcher.trigger('nodeMoved', node);
			},

			editNode: function(node) {
				if (typeof node === 'undefined') {
					if (this.get('editNodeTitleMode') === true) {
						this.$().find('input#editNode').blur();
						return;
					}
					node = this.get('activeNode');
				}

				if (!node) {
					Notification.info('You have to select a node');
				}

				// Skip editing site nodes
				if (node.getLevel() < 2) {
					Notification.info('The Root node cannot be deleted.');
					return;
				}

				var croppedTitle = node.data.title,
					prevTitle = node.data.fullTitle,
					tree = node.tree,
					that = this;

				that.set('editNodeTitleMode', true);
				tree.$widget.unbind();

				var input = $('<input />').attr({id: 'editNode', value: prevTitle});
				$('.neos-dynatree-title', node.span).html(input);

				// Focus <input> and bind keyboard handler
				input.focus().keydown(function(event) {
					switch (event.which) {
						case 27: // [esc]
							// discard changes on [esc]
							$('input#editNode').val(prevTitle);
							$(this).blur();
							break;
						case 13: // [enter]
							// simulate blur to accept new value
							$(this).blur();
							break;
					}
				}).blur(function() {
					//TODO please don't touch this part it is really fragile so this works in FF and chrome
					// Accept new value, when user leaves <input>
					var newTitle = input.val(),
						title;
					// Re-enable mouse and keyboard handling
					tree.$widget.bind();

					if (prevTitle === newTitle || newTitle === '') {
						node.render();
					} else {
						title = newTitle;
						node.data.title = title;
						node.data.fullTitle = title;
						node.data.addClass += ' neos-dynatree-dirty';
						node.render();
						node.setLazyNodeStatus(that.statusCodes.loading);
						NodeEndpoint.update(
							{
								__contextNodePath: node.data.key,
								title: title
							}
						).then(
							function(result) {
								if (result !== null && result.success === true) {
									node.data.href = result.data.nextUri;
									node.setLazyNodeStatus(that.statusCodes.ok);
									var nodeEntity = NodeSelection.getNode(node.data.key),
										selectedNodeEntity = NodeSelection.get('selectedNode');
									if (nodeEntity) {
										nodeEntity.setAttribute('title', title);
										nodeEntity.setAttribute('__workspaceName', result.data.workspaceNameOfNode, {silent: true});
										if (nodeEntity === selectedNodeEntity) {
											InspectorController.set('cleanProperties.title', title);
											InspectorController.set('nodeProperties.title', title);
										}
										ContentModule.loadPage(node.data.href);
									}
									EventDispatcher.trigger('nodeUpdated');
								} else {
									Notification.error('Unexpected error while updating node: ' + JSON.stringify(result));
									node.setLazyNodeStatus(that.statusCodes.error);
								}
							}
						);
					}
					node.activate();
					setTimeout(function() {
						that.set('editNodeTitleMode', false);
					}, 50);
				});
			},

			createNode: function(activeNode, title, nodeType, iconClass, position) {
				var that = this,
					nodeTypeConfiguration = NodeTypeService.getNodeTypeDefinition(nodeType),
					data = {
						title: title,
						nodeType: nodeType,
						nodeTypeLabel: nodeTypeConfiguration ? nodeTypeConfiguration.label : '',
						addClass: 'typo3_neos-page neos-matched',
						iconClass: iconClass,
						expand: false
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
				var prevTitle = newNode.data.fullTitle,
					tree = newNode.tree;

				if (position === 'into') {
					activeNode.expand(true);
				}

				that.set('editNodeTitleMode', true);
				tree.$widget.unbind();

				$('> .neos-dynatree-title', newNode.span).html($('<input />').attr({id: 'editCreatedNode', value: prevTitle}));
				// Focus <input> and bind keyboard handler
				$('input#editCreatedNode').focus().select().keydown(function(event) {
					switch (event.which) {
						case 27: // [esc]
							// discard changes on [esc]
							$('input#editNode').val(prevTitle);
							$(this).blur();
							break;
						case 13: // [enter]
							// simulate blur to accept new value
							$(this).blur();
							break;
					}
				}).blur(function(event) {
					//TODO please don't touch this part it is really fragile so this works in FF and chrome
					var newTitle = $('input#editCreatedNode').val(),
						title;

					// Accept new value, when user leaves <input>
					if (prevTitle === newTitle || newTitle === '') {
						title = prevTitle;
					} else {
						title = newTitle;
					}
					title = title || 'unnamed';
					// Hack for Chrome and Safari, otherwise two pages will be created, because .blur fires one request with two datasets
					if (that.get('editNodeTitleMode') === true) {
						that.set('editNodeTitleMode', false);
						newNode.activate();
						newNode.setTitle(title);
						newNode.data.fullTitle = title;
						that.persistNode(activeNode, newNode, nodeType, title, position);
					}
				});
			},

			afterPersistNode: function(node) {
				EventDispatcher.trigger('nodeCreated');

				var treeNode = this.$nodeTree.dynatree('getTree').getNodeByKey(node.data.key);
				if (treeNode) {
					$(treeNode.span).addClass('neos-dynatree-dirty');
				}

				ContentModule.loadPage(node.data.href);
			},

			refresh: function() {
				this._updateMetaInformation();
				this.filterTree();
			},

			/**
			 * Filter the tree
			 */
			filterTree: function() {
				var that = this,
					node = this.$nodeTree.dynatree('getRoot').getChildren()[0];
				node.removeChildren();
				node.setLazyNodeStatus(this.statusCodes.loading);

				if (this.get('searchTerm') === '' && this.get('nodeType') === '') {
					this.set('filtering', false);
					this.$nodeTree.dynatree('option', 'autoFocus', true);
					node._currentlySendingServerRequest = false;
					this.loadNode(node, this.get('loadingDepth'));
				} else {
					var filterQuery = Ember.generateGuid();
					this.set('latestFilterQuery', filterQuery);
					this.set('filtering', true);
					this.$nodeTree.dynatree('option', 'autoFocus', false);
					node._currentlySendingServerRequest = true;
					NodeEndpoint.filterChildNodesForTree(
						this.get('siteNodeContextPath'),
						this.get('searchTerm'),
						this.get('nodeType')
					).then(
						function(result) {
							if (that.get('latestFilterQuery') === filterQuery) {
								node.removeChildren();
								node._currentlySendingServerRequest = false;
								if (result !== null && result.success === true) {
									node.setLazyNodeStatus(that.statusCodes.ok);
									node.addChild(result.data);
								} else {
									node.setLazyNodeStatus(that.statusCodes.error);
									Notification.error('Node Tree loading error.');
								}
							}
						}
					);
				}
			},

			afterLoadNode: function(node) {
				if (node.getLevel() === 1) {
					var tree = this.$nodeTree.dynatree('getTree'),
						currentNode = tree.getNodeByKey(this.get('pageNodePath'));
					if (currentNode) {
						currentNode.activate();
						currentNode.select();
						this.scrollToCurrentNode();
					}
				}
			}
		});
	}
);
