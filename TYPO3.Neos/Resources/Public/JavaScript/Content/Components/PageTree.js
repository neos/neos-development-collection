/**
 * Toolbar which can contain other views. Has two areas, left and right.
 */
define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Content/Application',
		'vie/entity',
		'Content/Model/NodeSelection',
		'text!./PageTree.html',
		'text!./DeletePageDialog.html',
		'Content/Components/InsertDocumentNodePanel',
		'Shared/Notification'
	], function(
		$, Ember, ContentModule, EntityWrapper, NodeSelection, pageTreeTemplate, deletePageDialogTemplate, InsertDocumentNodePanel, Notification) {
		if (window._requirejsLoadingTrace) {
			window._requirejsLoadingTrace.push('neos/content/ui/elements/page-tree');
		}

		var DTNodeStatus_Error = -1,
			DTNodeStatus_Loading = 1,
			DTNodeStatus_Ok = 0;

		return Ember.View.extend({
			classNames: ['neos-pagetree'],
			template: Ember.Handlebars.compile(pageTreeTemplate),

			/**
			 * DOM node which is the container for the dynatree
			 */
			$tree: null,
			editNodeTitleMode: false,
			isDblClick: false,

			didInsertElement: function() {
				var that = this;
				$(document).ready(function() {
					that._initializeTree();
				});
			},

			/**
			 * Initialize the dynatree instance stored at the DOM node
			 * this.$tree
			 */
			_initializeTree: function() {
				if (this.$tree) {
					return;
				}

				var that = this,
					pageMetaInformation = $('#neos-page-metainformation'),
					siteRootNodePath = pageMetaInformation.data('__siteroot'),
					pageNodePath = $('#neos-page-metainformation').attr('about');

				this.$tree = this.$('#neos-dd-pagetree').dynatree({
					keyboard: true,
					minExpandLevel: 1,
					classNames: {
						title: 'dynatree-title'
					},
					clickFolderMode: 1,
					debugLevel: 0, // 0: quiet, 1: normal, 2: debug
					strings: {
						loading: 'Loading…',
						loadError: 'Load error!'
					},
					children: [
						{
							title: pageMetaInformation.data('__sitename'),
							key: pageMetaInformation.data('__siteroot'),
							isFolder: true,
							expand: false,
							isLazy: true,
							select: false,
							active: false,
							unselectable: true,
							iconClass: 'icon-globe'
						}
					],
					dnd: {
						autoExpandMS: 1000,
						preventVoidMoves: true, // Prevent dropping nodes 'before self', etc.

						/**
						 * Executed on beginning of drag.
						 * Returns false to cancel dragging of node.
						 */
						onDragStart: function(node) {
							if (node.data.key !== siteRootNodePath) {
								return true;
							} else {
								// the root node should not be draggable
								return false;
							}
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
							return true;
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
						 * hitmode over, after and before
						 * !sourcenode = new Node
						 */
						onDrop: function(node, sourceNode, hitMode, ui, draggable) {
							var position = hitMode === 'over' ? 'into' : hitMode;
							sourceNode.move(node, hitMode);

							TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.move(
								sourceNode.data.key,
								node.data.key,
								position,
								function(result) {
									if (result !== null && result.success === true) {
										// after we finished drag/drop, update the node path/url
										sourceNode.data.href = result.data.nextUri;
										sourceNode.data.key = result.data.newNodePath;
										ContentModule.loadPage(sourceNode.data.href);
									} else {
										Notification.error('Unexpected error while moving node: ' + JSON.stringify(result));
									}
								}
							);
						}
					},

					/**
					 * The following callback is executed if an lazy-loading node
					 * has not yet been loaded.
					 *
					 * It might be executed multiple times in rapid succession,
					 * and needs to take care itself that it only fires one
					 * ExtDirect request per node at a time. This is implemented
					 * using node._currentlySendingExtDirectAjaxRequest.
					 */
					onLazyRead: function(node) {
						if (node._currentlySendingExtDirectAjaxRequest) {
							return;
						}

						node._currentlySendingExtDirectAjaxRequest = true;
						TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(
							node.data.key,
							'TYPO3.Neos:Document',
							0,
							function(result) {
								node._currentlySendingExtDirectAjaxRequest = false;
								if (result !== null && result.success === true) {
									node.setLazyNodeStatus(DTNodeStatus_Ok);
									node.addChild(result.data);
								} else {
									node.setLazyNodeStatus(DTNodeStatus_Error);
									Notification.error('Page Tree loading error.');
								}
								if (node.getLevel() === 1) {
									that.$tree.dynatree('getTree').activateKey(pageNodePath);
								}
							}
						);
					},

					onClick: function(node, event) {
						if (that.editNodeTitleMode === true) {
							return false;
						}
						// only if the node title was clicked
						// and it was not active at this time
						// it should be navigated to the target node
						if (node.data.key !== siteRootNodePath && (node.getEventTargetType(event) === 'title' || node.getEventTargetType(event) === 'icon')) {
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
							that.isDblClick = true;
							setTimeout(function() {
								that.isDblClick = false;
								that.editNode(node);
							}, 300);
						}
					},

					onKeydown: function(node, event) {
						switch( event.which ) {
							case 113: // [F2]
								that.editNode(node);
								return false;
							case 13: // [enter]
								that.editNode(node);
								return false;
						}
					}
				});

				this._initializePagePropertyObservers();

				ContentModule.on('pageLoaded', function() {
					// for in-page reloads we need to re-monitor the current page
					that._initializePagePropertyObservers();
				});

				// Automatically expand the first node when opened
				this.$tree.dynatree('getRoot').getChildren()[0].expand(true);

				// Handles click events when a page title is in edit mode so clicks on other pages leads not to reloads
				this.$tree.click(function() {
					if (that.editNodeTitleMode === true) {
						return false;
					}
					return true;
				});

				// Adding a new page by clicking on the newPage container, if a page is active
				this.$('#neos-action-newpage').click(function() {
					var activeNode = that.$tree.dynatree('getActiveNode');
					if (activeNode !== null) {
						that.showCreateDocumentNodeDialog(activeNode);
					} else {
						Notification.notice('You have to select a page');
					}
					return false;
				});

				// Deleting a page by clicking on the deletePage container, if a page is active
				this.$('#neos-action-deletepage').click(function() {
					var activeNode = that.$tree.dynatree('getActiveNode');
					if (activeNode !== null) {
						if (activeNode.data.key !== siteRootNodePath) {
							that.showDeletePageDialog(activeNode);
						} else {
							Notification.notice('The Root page cannot be deleted.');
						}
					} else {
						Notification.notice('You have to select a page');
					}
				});
			},

			_initializePagePropertyObservers: function() {
				var that = this,
					$metainformation = $('#neos-page-metainformation');
				if ($metainformation.length === 0) {
					return;
				}

				var entityWrapper = NodeSelection._createEntityWrapper($metainformation);
				if (!entityWrapper) {
					// page might not have been loaded; so we directly return
					return;
				}

				// Activate the current node in the tree if possible
				var pageTreeNode = this.getPageTreeNode();
				if (pageTreeNode) {
					pageTreeNode.activate();
				}

				entityWrapper.addObserver('typo3:title', function() {
					that.synchronizePageTreeTitle(EntityWrapper.extractAttributesFromVieEntity(entityWrapper._vieEntity));
				});
				entityWrapper.addObserver('typo3:_name', function() {
					that.synchronizePageTreeNodeName(EntityWrapper.extractAttributesFromVieEntity(entityWrapper._vieEntity));
				});
				entityWrapper.addObserver('typo3:_hidden', function() {
					that.synchronizePageTreeVisibility(EntityWrapper.extractAttributesFromVieEntity(entityWrapper._vieEntity));
				});
				entityWrapper.addObserver('typo3:_hiddenInIndex', function() {
					that.synchronizePageTreeVisibility(EntityWrapper.extractAttributesFromVieEntity(entityWrapper._vieEntity));
				});
				entityWrapper.addObserver('typo3:_hiddenBeforeDateTime', function() {
					that.synchronizePageTreeVisibility(EntityWrapper.extractAttributesFromVieEntity(entityWrapper._vieEntity));
				});
				entityWrapper.addObserver('typo3:_hiddenAfterDateTime', function() {
					that.synchronizePageTreeVisibility(EntityWrapper.extractAttributesFromVieEntity(entityWrapper._vieEntity));
				});
			},
			synchronizePageTreeTitle: function(attributes) {
				var node = this.getPageTreeNode();
				if (node) {
					node.setTitle(attributes.title);
				}
			},
			synchronizePageTreeVisibility: function(attributes) {
				var now = new Date().getTime(),
					node = this.getPageTreeNode();

				if (node) {
					var classes = node.data.addClass;
					if (attributes._hidden === true) {
						classes = $.trim(classes.replace(/timedVisibility/g, ''));
						classes = classes + ' neos-hidden';
					} else if (attributes._hiddenBeforeDateTime !== ''
						&& new Date(attributes._hiddenBeforeDateTime).getTime() > now
						|| attributes._hiddenAfterDateTime !== '') {
						classes = classes + ' timedVisibility';
					} else {
						classes = $.trim(classes.replace(/timedVisibility/g, ''));
						classes = $.trim(classes.replace(/neos-hidden/g, ''));
					}
					if (attributes._hiddenInIndex === true) {
						classes = classes + ' hiddenInIndex';
					} else {
						classes = $.trim(classes.replace(/hiddenInIndex/g, ''));
					}
					node.data.addClass = classes;
					node.render();
				}
			},
			synchronizePageTreeNodeName: function(attributes) {
				var node = this.getPageTreeNode();
				if (node) {
					node.data.key = node.data.key.replace(node.data.name + '@', attributes._name + '@');
					node.data.href = node.data.href.replace(node.data.name + '@', attributes._name + '@');
					node.data.name = attributes._name;
					node.render();
					if (node.hasChildren() === true) {
						node.data.isLazy = true;
						// Remove children so they can't be clicked until they are reloaded
						node.removeChildren();
						node.setLazyNodeStatus(DTNodeStatus_Loading);
						// Listen to the first page reload (can be done with ContentModule.one in Ember.js 1.0)
						ContentModule.on('pageLoaded', this, 'reloadPageNodeChildren');
					}
				}
			},
			reloadPageNodeChildren: function() {
				var node = this.getPageTreeNode();
				if (node) {
					node.reloadChildren();
				}
				ContentModule.off('pageLoaded', this, 'reloadPageNodeChildren');
			},
			getPageTreeNode: function() {
				if (this.$tree && this.$tree.children().length > 0) {
					var tree = this.$tree.dynatree('getTree'),
						pageNodePath = $('#neos-page-metainformation').attr('about');
					return tree.getNodeByKey(pageNodePath);
				}
				return null;
			},

			showCreateDocumentNodeDialog: function(activeNode) {
				var that = this;

				InsertDocumentNodePanel.createElement();
				InsertDocumentNodePanel.set('insertNode', function(nodeTypeInfo) {
					that.createAndEditNode(activeNode, 'Untitled', nodeTypeInfo.nodeType, nodeTypeInfo.icon);
					this.cancel();
				});
			},

			/**
			 * When clicking the delete Page, we show a dialog
			 */
			showDeletePageDialog: function(activeNode) {
				var that = this;
				Ember.View.create({
					template: Ember.Handlebars.compile(deletePageDialogTemplate),
					pageTitle: activeNode.data.title,
					numberOfChildren: activeNode.data.children ? activeNode.data.children.length : 0,
					didInsertElement: function() {
					},
					cancel: function() {
						var $jQueryHandle = this.$();
						this.destroy();
						$jQueryHandle.remove();
					},
					'delete': function() {
						var $jQueryHandle = this.$();
						that.deleteNode(activeNode);
						this.destroy();
						$jQueryHandle.remove();
					}
				}).appendTo('#neos-application');
			},

			editNode: function(node) {
				var prevTitle = node.data.title,
					tree = node.tree,
					that = this;

				that.editNodeTitleMode = true;
				tree.$widget.unbind();

				$('.dynatree-title', node.span).html($('<input />').attr({id: 'editNode', value: prevTitle}));

				// Focus <input> and bind keyboard handler
				$('input#editNode').focus().keydown(function(event) {
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
					// Accept new value, when user leaves <input>
					var newTitle = $('input#editNode').val(),
						title;
					// Re-enable mouse and keyboard handling
					tree.$widget.bind();
					node.focus();

					if (prevTitle === newTitle || newTitle === '') {
						title = prevTitle;
						that.editNodeTitleMode = false;
					} else {
						title = newTitle;
					}

					if (that.editNodeTitleMode === true) {
						that.editNodeTitleMode = false;
						TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.update(
							{
								__contextNodePath: node.data.key,
								title: title
							},
							function(result) {
								if (result !== null && result.success === true) {
									T3.Content.Controller.Inspector.nodeProperties.set('title', title);
									T3.Content.Controller.Inspector.apply();
								} else {
									Notification.error('Unexpected error while updating node: ' + JSON.stringify(result));
								}
							}
						);
					}
					node.focus();
				});
			},
			createAndEditNode: function(activeNode, title, nodeType, iconClass) {
				var that = this,
					position = 'into',
					node = activeNode.addChild({
						title: title,
						nodeType: nodeType,
						addClass: 'typo3_neos-page',
						iconClass: iconClass,
						expand: true
					}),
					prevTitle = node.data.title,
					tree = node.tree;
				that.editNodeTitleMode = true;
				tree.$widget.unbind();

				$('.dynatree-title', node.span).html($('<input />').attr({id: 'editCreatedNode', value: prevTitle}));
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
						// Hack for Chrome and Safari, otherwise two pages will be created, because .blur fires one request with two datasets
						if (that.editNodeTitleMode) {
							that.editNodeTitleMode = false;
							node.activate();
							node.setTitle(title);
							TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.createNodeForTheTree(
								activeNode.data.key,
								{
									nodeType: nodeType,
									//@todo give a unique nodename from the title
									properties: {
										title: title
									}
								},
								position,
								function(result) {
									if (result !== null && result.success === true) {
										// Actualizing the created dynatree node
										node.data.key = result.data.key;
										node.data.name = result.data.name;
										node.data.title = result.data.title;
										node.data.href = result.data.href;
										node.data.isFolder = result.data.isFolder;
										node.data.isLazy = result.data.isLazy;
										node.data.nodeType = result.data.nodeType;
										node.data.expand = result.data.expand;
										node.data.addClass = result.data.addClass;

										// Re-enable mouse and keyboard handling
										tree.$widget.bind();
										ContentModule.loadPage(node.data.href);
									} else {
										Notification.error('Unexpected error while creating node: ' + JSON.stringify(result));
									}
								}
							);
						}
					});
			},

			deleteNode: function(node) {
				TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController['delete'](
					node.data.key,
					function(result) {
						if (result !== null && result.success === true) {
							var parentNode = node.getParent();
							parentNode.focus();
							parentNode.activate();
							node.remove();
							ContentModule.loadPage(parentNode.data.href);
						} else {
							Notification.error('Unexpected error while deleting node: ' + JSON.stringify(result));
						}
					}
				);
			}
		});
	}
);
