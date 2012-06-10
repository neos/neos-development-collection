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
	'order!Library/plupload/js/plupload.html5',
	//dynatree
	'Library/jquery-cookie/jquery.cookie',
	'Library/jquery-dynatree/js/jquery.dynatree',
	'css!Library/jquery-dynatree/css/ui.dynatree.css'
],
function(fixture, breadcrumbTemplate, inspectorTemplate, inspectorDialogTemplate) {
	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}
	T3.Content.UI = T3.Content.UI || {};
	var $ = window.Aloha.jQuery || window.jQuery;

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
	T3.Content.UI.Breadcrumb = Ember.View.extend({
		tagName: 'div',
		classNames: ['t3-breadcrumb'],
		template: Ember.Handlebars.compile(breadcrumbTemplate)
	});

	/**
	 * T3.Content.UI.BreadcrumbItem
	 *
	 * view for a single breadcrumb item
	 * @internal
	 */
	T3.Content.UI.BreadcrumbItem = Ember.View.extend({
		tagName: 'a',
		href: '#',

		// TODO Don't need to bind here actually
		attributeBindings: ['href'],
		template: Ember.Handlebars.compile('{{item.__titleAndModificationState}}'),
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
	T3.Content.UI.Inspector = Ember.View.extend({
		template: Ember.Handlebars.compile(inspectorTemplate),

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
			var view = Ember.View.create({
				template: Ember.Handlebars.compile(inspectorDialogTemplate),
				didInsertElement: function() {
					var title = this.$().find('h1').remove().html();

					this.$().dialog({
						modal: true,
						dialogClass: 't3-ui',
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

	T3.Content.UI.Inspector.PropertyEditor = Ember.ContainerView.extend({
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

			var editorClass = Ember.getPath(editorConfigurationDefinition['class']);
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
		$popoverContent: $('<div class="dynatree-container" id="t3-pagetree-container"><div id="t3-drag-newpage" class="t3-dd-newpage">New page</div><div id="t3-dd-pagetree"></div><div class="t3-dd-deletionzone" id="t3-drop-deletionzone">Drop here to delete</div></div>'),

		tree: null,
		onPopoverOpen: function() {
			if (this.tree) return;

			var siteRootNodePath = $('#t3-page-metainformation').attr('data-__siteroot');

			this.tree = $('#t3-dd-pagetree').dynatree({
				initialize: function(parent, tree, data) {
					this.parent = parent;
					this.tree = tree;
				},
				keyboard: true,
				minExpandLevel: 1,
				classNames: {
					title: 'dynatree-title'
				},
				clickFolderMode: 1,
				debugLevel: 0, // 0:quiet, 1:normal, 2:debug
				strings: {
					loading: 'Loading…',
					loadError: 'Load error!'
				},
				children: [
					{
						title: $('#t3-page-metainformation').attr('data-__sitename'),
						key: $('#t3-page-metainformation').attr('data-__siteroot'),
						isFolder: true,
						expand: false,
						isLazy: true,
						select: false,
						active: false,
						unselectable: true
					}
				],
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
					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(node.data.key, 'TYPO3.TYPO3:Page', function(result) {
						node._currentlySendingExtDirectAjaxRequest = false;
						if (result.success == true) {
							node.setLazyNodeStatus(DTNodeStatus_Ok);
						} else {
							T3.Common.Notification.error('Page Tree loading error.');
						}
						node.addChild(result.data);
					});
				},
				dnd: {
					/**
					 * Executed on beginning of drag.
					 * Returns false to cancel dragging of node.
					 */
					onDragStart: function(node) {
						if (node.data.key != siteRootNodePath) {
							$('#t3-drop-deletionzone').show();
							return true;
						} else {
							// the root node should not be draggable
							return false;
						}
					},
					autoExpandMS: 1000,
					preventVoidMoves: true, // Prevent dropping nodes 'before self', etc.

					/** sourceNode may be null for non-dynatree droppables.
					 *  Return false to disallow dropping on node. In this case
					 *  onDragOver and onDragLeave are not called.
					 *  Return 'over', 'before, or 'after' to force a hitMode.
					 *  Return ['before', 'after'] to restrict available hitModes.
					 *  Any other return value will calc the hitMode from the cursor position.
					 */
					onDragEnter: function(node, sourceNode) {
						return true;
					},
					onDragOver: function(node, sourceNode, hitMode) {
						if (node.isDescendantOf(sourceNode)) {
							return false;
						}
					},
					/** This function MUST be defined to enable dropping of items on
					 * the tree.
					 *
					 * hitmode over, after and before
					 * !sourcenode = new Node
					 */
					onDrop: function(node, sourceNode, hitMode, ui, draggable) {
						var position;
						if (!sourceNode) {
							// a new Node was created
							position = 0;
							if (hitMode === 'before') {
								position = -1;
							} else if (hitMode === 'after') {
								position = 1;
							}
							TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.create(
								node.data.key,
								{
									contentType: 'TYPO3.TYPO3:Page',
									properties: {
										title: '[New Page]'
									}
								},
								position,
								function(result) {
									if (result.success == true) {
										var parentNode = node.getParent();
										parentNode.reloadChildren();
										T3.ContentModule.loadPage(node.data.href);
									}
								}
							);
						} else {
							// it is an existing node which was moved on the tree
							var sourceNodeLevel = sourceNode.getLevel();
							var nodeLevel = node.getLevel();
							var nodeLevelDiff = nodeLevel - sourceNodeLevel;

							if (hitMode === 'before') {
								position = -1;
							} else if (hitMode === 'after') {
								position = 1;
							} else {
								position = 0;
							}
							if (position === 0 || nodeLevelDiff !== 0) {
								T3.Common.Notification.error('moving nodes inside other nodes is not possible right now');
							} else {
								sourceNode.move(node, hitMode);
								TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.move(
									sourceNode.data.key,
									node.data.key,
									position,
									function(result) {
										if (result.success == true) {
											//var parentNode = sourceNode.getParent();
											//parentNode.reloadChildren();
											T3.ContentModule.loadPage(node.data.href);
										}
									}
								);
							}
						}
					},
					onDragStop: function() {
						if ($deletePage.data('currently-deleting') == 'true') {
							$deletePage.data('currently-deleting', '');
							window.setTimeout(function() {
								$deletePage.hide();
							}, 1500);
						} else {
							$deletePage.hide();
						}
					}
				},
				onClick: function(node, event) {
					// only if the node title was clicked
					// and it was not active at this time
					// it should be navigated to the target node
					if (node.isActive() === false && node.data.key != siteRootNodePath && (node.getEventTargetType(event) == 'title' || node.getEventTargetType(event) == 'icon')) {
						T3.ContentModule.loadPage(node.data.href);
					}
				},
				onDblClick: function(node, event) {
					if (node.getEventTargetType(event) == 'title' && node.getLevel() !== 1) {
						editNode(node);
						return false;
					}
				},
				onKeydown: function(node, event) {
					switch( event.which ) {
						case 113: // [F2]
							editNode(node);
							return false;
						case 13: // [enter]
							editNode(node);
							return false;
					}
				}
			});

			$newPage = $('#t3-drag-newpage').draggable({
				revert: true,
				connectToDynatree: true,
				helper: 'clone',
				containment: '#t3-newpage-container'
			});
			//adding a new page by clicking on the newPage container, if a page is active
			$newPage.click(function() {
				var activeNode = $('#t3-dd-pagetree').dynatree('getActiveNode');
				if (activeNode !== null) {
					var position = 0;
					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.create(
						activeNode.data.key,
						{
							contentType: 'TYPO3.TYPO3:Page',
								properties: {
								title: '[New Page]'
							}
						},
						position,
						function(result) {
							if (result.success == true) {
								//reload the parent node with its childrens
								//if the parentnode has no children left the fathernode of the parenNode should be reloaded
								//editNode(node);
								var parentNode = activeNode.getParent();
								parentNode.reloadChildren();
							}
						}
					);
				}else{
					T3.Common.Notification.notice('You have to select a page');
				}
			});
			$deletePage = $('#t3-drop-deletionzone').droppable({
				over: function(event, ui) {
					$(this).addClass('ui-state-highlight');
				},
				out: function() {
					$(this).removeClass('ui-state-highlight')
				},
				drop: function(event, ui) {
					$deletePage.data('currently-deleting', 'true');
					var node = ui.helper.data('dtSourceNode') || ui.draggable;
					$(this).addClass('ui-state-highlight').find('p').html('Dropped ' + node);

					//nodes could only be deleted if they have no children
					//and they are not root
					if (node.data.key != siteRootNodePath || node.hasChildren == false) {
						TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController['delete'](
							node.data.key,
							function(result) {
								if (result.success == true) {
									//reload the parent node with its childrens
									//if the parentnode has no children left the fathernode of the parenNode should be reloaded
									reloadNodeAfterRemove(node);
								}
							}
						);
					} else {
						T3.Common.Notification.notice('This node has got children and could not be deleted.');
					}
				}
			});
			function reloadNodeAfterRemove(node) {
				// @Todo fix when the last page of a folder was deleted
				var parentNode = node.getParent();
				if (node.hasChildren() || node.isLazy()) {
					var grandFatherNode = parentNode.getParent();
					grandFatherNode.reloadChildren();
					T3.ContentModule.loadPage(grandFatherNode.data.href);
				} else {
					parentNode.reloadChildren();
					T3.ContentModule.loadPage(parentNode.data.href);
				}
			}
			function editNode(node) {
				var prevTitle = node.data.title,
				tree = node.tree;
				tree.$widget.unbind();
				$('.dynatree-title', node.span).html('<input id="editNode" value="' + prevTitle + '">');
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
					// Accept new value, when user leaves <input>
					var title = $('input#editNode').val();
					node.setTitle(title);
					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.update(
						{
							__contextNodePath: node.data.key,
							title: title
						},
						function(result) {
							if (result.success == true) {
								var parentNode = node.getParent();
								parentNode.reloadChildren();
								T3.ContentModule.loadPage(node.data.href);
							}
						}
					);
					// Re-enable mouse and keyboard handlling
					tree.$widget.bind();
					node.focus();
				});
			}
		}
	});


	/**
	 * =====================
	 * SECTION: INSPECT TREE
	 * =====================
	 * - Inspect TreeButton
	 */

	T3.Content.UI.InspectButton = T3.Content.UI.PopoverButton.extend({
		popoverTitle: 'Content Structure',
		$popoverContent: $('<div class="dynatree-container" id="t3-inspecttree-container"><div id="t3-dd-inspecttree"></div></div>'),
		popoverPosition: 'top',
		_ignoreCloseOnPageLoad: false,

		inspectTree: null,
		isLoadingLayerActive: function() {
			if (T3.ContentModule.get('_isLoadingPage')) {
				if (that.get('_ignoreCloseOnPageLoad')) {
					that.set('_ignoreCloseOnPageLoad', false);
					return;
				}
				$('.t3-inspect > button.pressed').click();
				if(this.inspectTree != null) {
					$('#t3-dd-inspecttree').dynatree('destroy');
					this.inspectTree = null;
				}
			}
		}.observes('T3.ContentModule._isLoadingPage'),

		onPopoverOpen: function() {
			var attr = $('#t3-page-metainformation').attr('data-__nodepath');
			var dataNodeTitle = $('#t3-page-metainformation').data('title');
			var dataNodePath = $('#t3-page-metainformation').attr('data-__nodepath');

				// if there is a tree and the rootnode key of the tree is different from the actual page, the tree should be reinitialised
			if (this.inspectTree) {
				var tree = $("#t3-dd-inspecttree").dynatree('getTree');
				var rootNode = tree.getRoot();
				if (dataNodePath != rootNode.childList[0].data.key) {
					$('#t3-dd-inspecttree').dynatree('destroy');
				}
			}

			this.inspectTree = $('#t3-dd-inspecttree').dynatree({
				debugLevel: 0, // 0:quiet, 1:normal, 2:debug,
				cookieId: null,
				persist: false,
				onPostInit: function (isReloading, isError, tree, node) {
					dataNodeTitle = $('#t3-page-metainformation').data('title');
					dataNodePath = $('#t3-page-metainformation').attr('data-__nodepath');
				},
				children: [
					{
						title: dataNodeTitle ,
						key: dataNodePath,
						isFolder: true,
						expand: false,
						isLazy: true,
						autoFocus: true,
						select: false,
						active: false,
						unselectable: true
					}
				],
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
					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(node.data.key, '!TYPO3.TYPO3:Page', function(result) {
						node._currentlySendingExtDirectAjaxRequest = false;
						if (result.success == true) {
							node.setLazyNodeStatus(DTNodeStatus_Ok);
						} else {
							T3.Common.Notification.error('Page Tree loading error.');
						}
						node.addChild(result.data);
					});
				},
				dnd: {
					/**
					 * Executed on beginning of drag.
					 * Returns false to cancel dragging of node.
					 */
					onDragStart: function(node) {
					},
					autoExpandMS: 1000,
					preventVoidMoves: true, // Prevent dropping nodes 'before self', etc.

					/** sourceNode may be null for non-dynatree droppables.
					 *  Return false to disallow dropping on node. In this case
					 *  onDragOver and onDragLeave are not called.
					 *  Return 'over', 'before, or 'after' to force a hitMode.
					 *  Return ['before', 'after'] to restrict available hitModes.
					 *  Any other return value will calc the hitMode from the cursor position.
					 */
					onDragEnter: function(node, sourceNode) {
						//it is only posssible to move nodes into nodes of the contentType:Section
						if(node.data.contentType === 'TYPO3.TYPO3:Section') {
							T3.Common.Notification.error('moving nodes inside other nodes is not possible right now');
							return ['before', 'after','over'];
						}
						else{
							return ['before', 'after'];
						}
					},
					onDragOver: function(node, sourceNode, hitMode) {
						if (node.isDescendantOf(sourceNode)) {
							return false;
						}
					},
					/** This function MUST be defined to enable dropping of items on
					 * the tree.
					 *
					 * hitmode over, after and before
					 */
					onDrop: function(node, sourceNode, hitMode, ui, draggable) {
						var position;

						// it is an existing node which was moved on the tree
						var sourceNodeLevel = sourceNode.getLevel();
						var nodeLevel = node.getLevel();
						var nodeLevelDiff = nodeLevel - sourceNodeLevel;
						var targetNodeContentType = node.data.contentType;

						if (hitMode === 'before') {
							position = -1;
						} else if (hitMode === 'after') {
							position = 1;
						} else {
							position = 0;
						}
						if (position === 0 && nodeLevelDiff !== 0) {
							T3.Common.Notification.error('moving nodes inside other nodes is not possible right now');
						} else {
							sourceNode.move(node, hitMode);
							TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.move(
								sourceNode.data.key,
								node.data.key,
								position,
								function(result) {
									if(result.success == true) {
										that.set('_ignoreCloseOnPageLoad', true);
										T3.ContentModule.reloadPage();
									}
								}
							);
						}
					},
					onDragStop: function() {
					}
				},
				onClick: function(node, event) {
					if (node.getEventTargetType === 'title') {
						var nodePath = node.data.key, offsetFromTop = 150;
						var block = T3.Content.Model.BlockManager.getBlockByNodePath(nodePath);
						if (!block) return;

						T3.Content.Model.BlockSelection.selectItem(block);
						var $blockDomElement = block.getContentElement();

						$('html,body').animate({
							scrollTop: $blockDomElement.offset().top - offsetFromTop
						}, 500);
					}
				}
			});
		}
	});
});
