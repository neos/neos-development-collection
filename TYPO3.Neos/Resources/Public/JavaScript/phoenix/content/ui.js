/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'phoenix/fixture',
	'text!phoenix/templates/content/ui/breadcrumb.html',
	'text!phoenix/templates/content/ui/inspector.html',
	'text!phoenix/templates/content/ui/inspectorDialog.html',
	'phoenix/content/ui/elements',
	'phoenix/content/ui/editors',
	'Library/jquery-popover/jquery.popover',
	'Library/jquery-notice/jquery.notice',
	'css!Library/jquery-notice/jquery.notice.css',
	'Library/jcrop/js/jquery.Jcrop.min',
	'css!Library/jcrop/css/jquery.Jcrop.css',
	'order!Library/plupload/js/plupload',
	'order!Library/plupload/js/plupload.html5'
],
function(fixture, breadcrumbTemplate, inspectorTemplate, inspectorDialogTemplate) {
	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}
	T3.Content.UI = T3.Content.UI || {};
	var $ = window.alohaQuery || window.jQuery;

	/**
	 * =====================
	 * SECTION: UI CONTAINRS
	 * =====================
	 * - Breadcrumb
	 * - BreadcrumbItem
	 * - Inspector
	 */

	/**
	 * T3.Content.UI.Breadcrumb
	 *
	 * The breadcrumb menu
	 */
	T3.Content.UI.Breadcrumb = SC.View.extend({
		tagName: 'div',
		classNames: ['t3-breadcrumb'],
		template: SC.Handlebars.compile(breadcrumbTemplate)
	});

	/**
	 * T3.Content.UI.BreadcrumbItem
	 *
	 * view for a single breadcrumb item
	 * @internal
	 */
	T3.Content.UI.BreadcrumbItem = SC.View.extend({
		tagName: 'a',
		href: '#',

		// TODO Don't need to bind here actually
		attributeBindings: ['href'],
		template: SC.Handlebars.compile('{{item.__titleAndModifcationState}}'),
		click: function(event) {
			var item = this.get('item');
			T3.Content.Model.BlockSelection.selectItem(item);
			event.stopPropagation();
			return false;
		}
	});



	/**
	 * T3.Content.UI.Inspector
	 *
	 * The Inspector is displayed on the right side of the page.
	 *
	 * Furthermore, it contains *Editors*
	 */
	T3.Content.UI.Inspector = SC.View.extend({
		template: SC.Handlebars.compile(inspectorTemplate),

		/**
		 * When we are in edit mode, the click protection layer is intercepting
		 * every click outside the Inspector.
		 */
		$clickProtectionLayer: null,

		/**
		 * When pressing Enter inside a property, we apply and leave the edit mode
		 */
		keyDown: function(event) {
			if (event.keyCode === 13) {
				T3.Content.Controller.Inspector.apply();
				return false;
			}
		},

		/**
		 * When the editors have been modified, we add / remove the click
		 * protection layer.
		 */
		_onModifiedChange: function() {
			var zIndex;
			if (T3.Content.Controller.Inspector.get('_modified')) {
				zIndex = this.$().css('z-index') - 1;
				this.$clickProtectionLayer = $('<div />').addClass('t3-inspector-clickprotection').addClass('aloha-block-do-not-deactivate').css({'z-index': zIndex});
				this.$clickProtectionLayer.click(this._showUnappliedDialog);
				$('body').append(this.$clickProtectionLayer);
			} else {
				this.$clickProtectionLayer.remove();
			}
		}.observes('T3.Content.Controller.Inspector._modified'),

		/**
		 * When clicking the click protection, we show a dialog
		 */
		_showUnappliedDialog: function() {
			var view = SC.View.create({
				template: SC.Handlebars.compile(inspectorDialogTemplate),
				didInsertElement: function() {
					var title = this.$().find('h1').remove().html();

					this.$().dialog({
						modal: true,
						zIndex: 11001,
						title: title,
						close: function() {
							view.destroy();
						}
					});
				},
				cancel: function() {
					this.$().dialog('close');
				},
				apply: function() {
					T3.Content.Controller.Inspector.apply();
					this.$().dialog('close');
				},
				dontApply: function() {
					T3.Content.Controller.Inspector.revert();
					this.$().dialog('close');
				}
			});
			view.append();
		}
	});

	T3.Content.UI.Inspector.PropertyEditor = SC.ContainerView.extend({
		propertyDefinition: null,

		render: function() {
			var typeDefinition = T3.Configuration.UserInterface[this.propertyDefinition.type];
			if (!typeDefinition) {
				throw {message: 'Type defaults for "' + this.propertyDefinition.type + '" not found', code: 1316346119};
			}

			var editorConfigurationDefinition = typeDefinition;
			if (this.propertyDefinition.userInterface && this.propertyDefinition.userInterface) {
				editorConfigurationDefinition = $.extend({}, editorConfigurationDefinition, this.propertyDefinition.userInterface);
			}

			var editorClass = SC.getPath(editorConfigurationDefinition['class']);
			if (!editorClass) {
				throw 'Editor class "' + typeDefinition['class'] + '" not found';
			}

			var classOptions = $.extend({
				valueBinding: 'T3.Content.Controller.Inspector.blockProperties.' + this.propertyDefinition.key

			}, this.propertyDefinition.options || {});
			classOptions = $.extend(classOptions, typeDefinition.options || {});

			var editor = editorClass.create(classOptions);
			this.appendChild(editor);
			this._super();
		}
	});

	/**
	 * ==================
	 * SECTION: PAGE TREE
	 * ==================
	 * - PageTreeLoader
	 * - PageTreeButton
	 */
	T3.Content.UI.PageTreeButton = T3.Content.UI.PopoverButton.extend({
		popoverTitle: 'Page Tree',
		$popoverContent: $('<div class="extjs-container"></div>'),

		/**
		 * @var {Ext.tree.TreePanel} Reference to the ExtJS tree; or null if not yet built.
		 */
		_tree: null,

		onPopoverOpen: function() {
			if (this._tree) return;

			this._tree = new Ext.tree.TreePanel({
				width: 250,
				height: 350,
				useArrows: true,
				autoScroll: true,
				animate: true,
				enableDD: true,
				border: false,
				ddGroup: 'pages',
				_deletionDropZoneId: 't3-dd-pages-deletionzone',

				root: {
					id: $('#t3-page-metainformation').data('__siteroot'), // TODO: This and the following properties might later come from the SproutCore model...
					text: $('#t3-page-metainformation').data('__sitename'),
					draggable: false
				},

				loader: new Ext.tree.TreeLoader({
					/**
					 * Wrapper for extDirect call to NodeController which
					 * adds the child node type to the extDirect call as 2nd parameter.
					 *
					 * @param {String} contextNodePath the current Context Node Path to get subnodes from
					 * @param {Function} callback function after request is done
					 * @return {void}
					 */
					directFn: function(contextNodePath, callback) {
						TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(contextNodePath, 'TYPO3.TYPO3:Page', callback);
					},

					/**
					 * Here, we convert the response back to a format ExtJS understands; namely we use result.data instead of result here.
					 *
					 * @param {Object} result the result part from the response of the server request
					 * @param {Object} response the response object of the server request
					 * @param {Object} args request arguments passed through
					 * @return {void}
					 */
					processDirectResponse: function(result, response, args) {
						if (response.status) {
							this.handleResponse({
								responseData: Ext.isArray(result.data) ? result.data : null,
								responseText: result,
								argument: args
							});
						} else {
							this.handleFailure({
								argument: args
							});
						}
					}
				}),

				deleteNode: function(node) {
					var deletionZone = $('.t3-dd-deletionzone');
					deletionZone.text('Deleting page').addClass('t3-dd-deletionzone-active');
					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.delete(
						node.id,
						function(response) {
							deletionZone.removeClass('t3-dd-deletionzone-active').text('Drop here to delete').hide();
							if (response.success === true) {
								node.remove();
							}
						}
					);
				},

				listeners: {
					click: this._onTreeNodeClick,
					movenode: this._onTreeNodeMove,
					beforenodedrop: this._onTreeNodeDrop,
					startdrag: this._onTreeNodeStartDrag,
					enddrag: this._onTreeNodeEndDrag
				}
			});

			this._initNewPageDraggable();

			var $treeContainer = $('<div />');
			this.$popoverContent.append($treeContainer);

			this._initDeletionDropZone();

			this._tree.render($treeContainer.get(0));
			this._tree.getRootNode().expand();
		},

		_onTreeNodeStartDrag: function(tree, node) {
			$('.t3-dd-deletionzone').show();

				// Refresh DD zones after displaying drop zone
			var groups = {};
			groups[tree.ddGroup] = true;
			Ext.dd.DDM.refreshCache(groups);

			tree.dragZone.proxy.getGhost().addClass('t3-dd-drag-ghost-pagetree');
		},

		_onTreeNodeEndDrag: function(tree, node) {
			$('.t3-dd-deletionzone:not(.t3-dd-deletionzone-active,.t3-dd-deletionzone-pending)').hide();
		},

		/**
		 * Initializer for the "new page" draggable, creating an element
		 * and a Drag Zone.
		 */
		_initNewPageDraggable: function() {
			var $newPageDraggable = $('<div class="t3-dd-newpage">New page</div>');
			this.$popoverContent.append($newPageDraggable);

			new Ext.dd.DragZone($newPageDraggable.get(0), {
				ddGroup: this._tree.ddGroup,

				getDragData: function(event) {
					this.proxyElement = document.createElement('div');

					return {
						ddel: this.proxyElement,
						mode: 'new'
					}
				},

				onInitDrag: function() {
					this.proxyElement.shadow = false;
					this.proxyElement.innerHTML = '<div class="t3-dd-drag-ghost-pagetree">' +
						'Insert Page here' +
					'</div>';

					this.proxy.update(this.proxyElement);
				}
			});
		},

		/**
		 * Initializer for the "drop zone", deleting a page.
		 */
		_initDeletionDropZone: function() {
			var $deletionDropZone = $('<div />')
				.addClass('t3-dd-deletionzone')
				.text('Drop here to delete');
			this.$popoverContent.append($deletionDropZone);

			new Ext.dd.DropZone($deletionDropZone.get(0), {
				ddGroup: this._tree.ddGroup,

				notifyEnter: function(source, e, data) {
					$deletionDropZone.addClass('t3-dd-deletionzone-over');
					source.proxy.el.addClass('x-tree-drop-delete');
					return this;
				},

				notifyOut: function(source, e, data) {
					$deletionDropZone.removeClass('t3-dd-deletionzone-over');
					return this;
				},

				notifyDrop: function(source, e, data) {
					var node = data.node;
					if (!node) {
						return;
					}

					source.proxy.el.setVisible(false);

					var tree = node.ownerTree;

					if (node.hasChildNodes() || node.isExpandable()) {
						$deletionDropZone.addClass('t3-dd-deletionzone-pending');

						var view = SC.View.create({
							template: SC.Handlebars.compile(recursivePageDeletionDialogTemplate),
							didInsertElement: function() {
								var title = this.$().find('h1').remove().html();

								this.$().dialog({
									modal: true,
									zIndex: 11001,
									title: title,
									close: function() {
										$deletionDropZone.removeClass('t3-dd-deletionzone-pending').hide();
										view.destroy();
									}
								});
							},
							cancel: function() {
								this.$().dialog('close');
							},
							delete: function() {
								$deletionDropZone.removeClass('t3-dd-deletionzone-pending');
								view.destroy();
								tree.deleteNode(node, tree);
							}
						});
						view.append();
					} else {
						tree.deleteNode(node, tree);
					}
				}
			});
		},

		/**
		 * Callback which is executed when a TreeNode is clicked.
		 *
		 * @param {Ext.tree.TreeNode} node
		 * @param {Object} event
		 */
		_onTreeNodeClick: function(node, event) {
				// TODO: clean this up, so that clicking the "GOTO" link works without this click hack; or built some different way of handling this case.
			if ($(event.getTarget()).is('a.t3-gotoPage')) {
				T3.ContentModule.loadPage($(event.getTarget()).attr('href'));
			};
		},

		/**
		 * Callback which is executed when a TreeNode is moved to an other TreeNode.
		 */
		_onTreeNodeMove: function(tree, node, oldParent, newParent, index) {
			var beforeNode = newParent.childNodes[index - 1],
				afterNode = newParent.childNodes[index + 1],
				targetNodeId, position;
			if (beforeNode) {
				targetNodeId = beforeNode.id;
				position = 1;
			} else if (afterNode) {
				targetNodeId = afterNode.id;
				position = -1;
			} else {
				targetNodeId = newParent.id;
				position = 0;
			}

			TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.move(
				node.id,
				targetNodeId,
				position,
				function() {
					newParent.reload();
				}
			);
		},

		/**
		 * Callback, executed when something is dropped on the tree. We insert
		 * an element in case the newPageDraggable is dropped on the tree.
		 *
		 * @param {Object} event
		 */
		_onTreeNodeDrop: function(event) {
			if (event.data.mode === 'new') {
				var position = 0;
				if (event.point === 'above') {
					position = -1;
				} else if (event.point === 'below') {
					position = 1;
				}

				TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.create(
					event.target.attributes.id,
					{
						contentType: 'TYPO3.TYPO3:Page',
						properties: {
							title: '[New Page]'
						}
					},
					position,
					function() {
						event.target.parentNode.reload();
					}
				);
			}
		}
	});

	T3.Content.UI.InspectButton = T3.Content.UI.PopoverButton.extend({
		popoverTitle: 'Content Structure',
		$popoverContent: $('<div class="extjs-container" style="height: 350px"></div>'),
		popoverPosition: 'top',

		/**
		 * @var {Ext.tree.TreePanel} Reference to the ExtJS tree; or null if not yet built.
		 */
		_tree: null,

		onPopoverOpen: function() {
			if (this._tree) return;

			this._tree = new Ext.tree.TreePanel({
				width:250,
				height:350,
				useArrows: true,
				autoScroll: true,
				animate: true,
				enableDD: true,
				border: false,
				ddGroup: 'nodes',

				root: {
					id: $('#t3-page-metainformation').attr('data-__nodepath'), // TODO: This and the following properties might later come from the SproutCore model...
					text: $('#t3-page-metainformation').data('title'),
					draggable: false
				},

				loader: new Ext.tree.TreeLoader({
					/**
					 * Wrapper for extDirect call to NodeController which
					 * adds the child node type to the extDirect call as 2nd parameter.
					 *
					 * @param {String} contextNodePath the current Context Node Path to get subnodes from
					 * @param {Function} callback function after request is done
					 * @return {void}
					 */
					directFn: function(contextNodePath, callback) {
						TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(contextNodePath, '!TYPO3.TYPO3:Page', callback);
					},

					/**
					 * Here, we convert the response back to a format ExtJS understands; namely we use result.data instead of result here.
					 *
					 * @param {Object} result the result part from the response of the server request
					 * @param {Object} response the response object of the server request
					 * @param {Object} args request arguments passed through
					 * @return {void}
					 */
					processDirectResponse: function(result, response, args) {
						if (response.status) {
							this.handleResponse({
								responseData: Ext.isArray(result.data) ? result.data : null,
								responseText: result,
								argument: args
							});
						} else {
							this.handleFailure({
								argument: args
							});
						}
					}
				}),

				listeners: {
					movenode: this._onTreeNodeMove,
					click: this._onTreeNodeClick
				}
			});

			var $treeContainer = $('<div />');
			this.$popoverContent.append($treeContainer);

			this._tree.render($treeContainer.get(0));
			this._tree.getRootNode().expand();
		},

		/**
		 * Callback which is executed when a TreeNode is moved to an other TreeNode.
		 *
		 * TODO: Refactor later to common tree component
		 */
		_onTreeNodeMove: function(tree, node, oldParent, newParent, index) {
			var beforeNode = newParent.childNodes[index - 1],
				afterNode = newParent.childNodes[index + 1],
				targetNodeId, position;
			if (beforeNode) {
				targetNodeId = beforeNode.id;
				position = 1;
			} else if (afterNode) {
				targetNodeId = afterNode.id;
				position = -1;
			} else {
				targetNodeId = newParent.id;
				position = 0;
			}

			TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.move(
				node.id,
				targetNodeId,
				position,
				function() {
					newParent.reload();
				}
			);
		},

		/**
		 * Callback which is executed when a TreeNode is clicked.
		 * We activate this element in the UI and slide it into view.
		 */
		_onTreeNodeClick: function(node) {
			var nodePath = node.id, offsetFromTop = 150;
			var block = T3.Content.Model.BlockManager.getBlockByNodePath(nodePath);
			if (!block) return;

			T3.Content.Model.BlockSelection.selectItem(block);
			var $blockDomElement = block.getContentElement();

			$('html,body').animate({
				scrollTop: $blockDomElement.offset().top - offsetFromTop
			}, 500);
		}
	});
});
