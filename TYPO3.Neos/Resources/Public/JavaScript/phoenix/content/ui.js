/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'jquery',
	'emberjs',
	'vie/instance',
	'text!phoenix/templates/content/ui/breadcrumb.html',
	'text!phoenix/templates/content/ui/inspector.html',
	'text!phoenix/templates/content/ui/inspectorDialog.html',
	'text!phoenix/templates/content/ui/pageTree.html',
	'text!phoenix/templates/content/ui/deletePageDialog.html',
	'text!phoenix/templates/content/ui/inspectTree.html',
	'phoenix/content/ui/elements',
	'phoenix/content/ui/editors',
	'jquery.popover',
	'jquery.jcrop',
	'jquery.plupload',
	'jquery.plupload.html5',
	'jquery.cookie',
	'jquery.dynatree'
],
function($, Ember, vie, breadcrumbTemplate, inspectorTemplate, inspectorDialogTemplate, pageTreeTemplate, deletePageDialogTemplate, inspectTreeTemplate) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('phoenix/content/ui');

	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}
	T3.Content.UI = T3.Content.UI || {};

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
		template: Ember.Handlebars.compile('{{item.contentTypeSchema.label}} {{#if item.status}}<span class="t3-breadcrumbitem-status">({{item.status}})</span>{{/if}}'),
		click: function(event) {
			event.preventDefault();

			var item = this.get('item');
			T3.Content.Model.NodeSelection.selectNode(item);
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
				this.$clickProtectionLayer = $('<div />').addClass('t3-inspector-clickprotection').addClass('t3-ui').css({'z-index': zIndex});
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
			ember_assert('Type defaults for "' + this.propertyDefinition.type + '" not found!', !!typeDefinition);

			var editorConfigurationDefinition = typeDefinition;
			if (this.propertyDefinition.userInterface && this.propertyDefinition.userInterface) {
				editorConfigurationDefinition = $.extend({}, editorConfigurationDefinition, this.propertyDefinition.userInterface);
			}

			var editorClass = Ember.getPath(editorConfigurationDefinition['class']);
			ember_assert('Editor class "' + typeDefinition['class'] + '" not found', !!editorClass);

			var classOptions = $.extend({
				valueBinding: 'T3.Content.Controller.Inspector.nodeProperties.' + this.propertyDefinition.key

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

	var editNodeTitleMode = false;
	T3.Content.UI.PageTreeButton = T3.Content.UI.PopoverButton.extend({
		popoverTitle: 'Page Tree',
		$popoverContent: pageTreeTemplate,
		tree: null,

		/**
		 * When clicking the delete Page, we show a dialog
		 */
		showDeletePageDialog: function(activeNode) {
			var content = 'Are you sure the page "'+activeNode.data.title + '" should be deleted?';
			if(activeNode.hasChildren() === true){
				content = 'Are you sure the page "'+activeNode.data.title + '" and all its children should be deleted?';
			}
			var that = this;
			var view = Ember.View.create({
				template: Ember.Handlebars.compile(deletePageDialogTemplate),
				content: content,
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
					that.deleteNode(activeNode);
					this.$().dialog('close');
				}
			});
			view.append();
		},
		editNode: function(node){

			var prevTitle = node.data.title,
			tree = node.tree;
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
					// Accept new value, when user leaves <input>
				var newTitle = $('input#editNode').val();
				if (prevTitle == newTitle) {
					title = prevTitle;
					//node.setTitle(title);
				} else {
					title = newTitle;
					node.setTitle(title);

					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.update(
						{
							__contextNodePath: node.data.key,
							title: title
						},
						function(result) {
							if (result.success === true) {
								var parentNode = node.getParent();
								parentNode.activate()
								parentNode.focus();
								T3.ContentModule.loadPage(parentNode.data.href);
							}
						}
					);
				}
				node.setTitle(title);
					// Re-enable mouse and keyboard handling
				tree.$widget.bind();
			});
		},
	createAndEditNode: function(activeNode){
			var position = 'into';
			var node = activeNode.addChild({
				title: '[New Page]',
				contentType: 'TYPO3.Phoenix.ContentTypes:Page',
				addClass: 'typo3_typo3-page'
			});
			editNodeTitleMode = true;
			var prevTitle = node.data.title,
			tree = node.tree;
			tree.$widget.unbind();

			$('.dynatree-title', node.span).html($('<input />').attr({id: 'editcreatedNode', value: prevTitle}));
				// Focus <input> and bind keyboard handler
			$('input#editcreatedNode').focus().keydown(function(event) {
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
				var newTitle = $('input#editcreatedNode').val();
					// Re-enable mouse and keyboard handling
				tree.$widget.bind();
				activeNode.focus();

					// Accept new value, when user leaves <input>
				if (prevTitle == newTitle) {
					title = prevTitle;
				} else if(newTitle == '') {
					title = prevTitle;
				} else {
					title = newTitle;
				}
					//hack for chrome and safari, otherwise two pages will be created, because .blur fires one request with two datasets
				if(editNodeTitleMode) {
					editNodeTitleMode = false;
					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.createNodeForTheTree(
						activeNode.data.key,
						{
							contentType: 'TYPO3.Phoenix.ContentTypes:Page',
							//@todo give a unique nodename from the title
							properties: {
								title: title
							}
						},
						position,
						function(result) {
							if (result.success === true) {
									//actualizing the created js dynatree node
								node.data.key = result.data.key;
								node.data.title = result.data.title;
								node.data.href = result.data.href;
								node.data.isFolder = result.data.isFolder;
								node.data.isLazy = result.data.isLazy;
								node.data.contentType = result.data.contenType;
								node.data.expand = result.data.expand;
								node.data.addClass = result.data.addClass;

								editNodeTitleMode = false;
								activeNode.focus();
									// Re-enable mouse and keyboard handling
								tree.$widget.bind();
								T3.ContentModule.loadPage(activeNode.data.href);
							}
						}
					);
				}

				node.setTitle(title);
				editNodeTitleMode = false;
				activeNode.focus();
			});
		},

		deleteNode: function(node) {
			TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController['delete'](
				node.data.key,
				function(result) {
					if (result.success === true) {
						var parentNode = node.getParent();
						parentNode.focus();
						parentNode.activate();
						node.remove();
						T3.ContentModule.loadPage(parentNode.data.href);
					}
				}
			);
		},

		onPopoverOpen: function() {
			if (this.tree) {
				return;
			}

			var that = this,
				pageMetaInformation = $('#t3-page-metainformation'),
				siteRootNodePath = pageMetaInformation.data('__siteroot'),
				pageNodePath = $('#t3-page-metainformation').attr('about');

			that.tree = $('#t3-dd-pagetree').dynatree({
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
						title: pageMetaInformation.data('__sitename'),
						key: pageMetaInformation.data('__siteroot'),
						isFolder: true,
						expand: false,
						isLazy: true,
						select: false,
						active: false,
						unselectable: true,
						addClass: 'typo3_typo3-root'
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
					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(
						node.data.key,
						'TYPO3.TYPO3CR:Folder',
						0,
						function(result) {
							node._currentlySendingExtDirectAjaxRequest = false;
							if (result.success === true) {
								node.setLazyNodeStatus(DTNodeStatus_Ok);
							} else {
								T3.Common.Notification.error('Page Tree loading error.');
							}
							node.addChild(result.data);
							if (node.getLevel() === 1) {
								that.tree.dynatree('getTree').activateKey(pageNodePath);
							}
					});
				},
				dnd: {
					/**
					 * Executed on beginning of drag.
					 * Returns false to cancel dragging of node.
					 */
					onDragStart: function(node) {
						if (node.data.key !== siteRootNodePath) {
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
						var position = hitMode === 'over' ? 'into' : hitMode;
						sourceNode.move(node, hitMode);

						TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.move(
							sourceNode.data.key,
							node.data.key,
							position,
							function(result) {
								if (result.success === true) {
									T3.ContentModule.loadPage(node.data.href);
								}
							}
						);
					}
				},
				onClick: function(node, event) {
					if (editNodeTitleMode == true){
						return false;
					}
						// only if the node title was clicked
						// and it was not active at this time
						// it should be navigated to the target node
					if (node.isActive() === false && node.data.key !== siteRootNodePath && (node.getEventTargetType(event) === 'title' || node.getEventTargetType(event) === 'icon')) {
						T3.ContentModule.loadPage(node.data.href);
					}
				},
				onDblClick: function(node, event) {
					if (node.getEventTargetType(event) === 'title' && node.getLevel() !== 1) {
						var position = 0;
						that.editNewNode(node);
						return false;
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

				// Automatically expand the first node when opened
			that.tree.dynatree('getRoot').getChildren()[0].expand(true);

				//handles click events when a pagetitle is in editmode so clicks on other pages leads not to reloads
			$('#t3-dd-pagetree').click(function() {
				if (editNodeTitleMode == true) {
					return false;
				}
			});

				//adding a new page by clicking on the newPage container, if a page is active
			$('#t3-action-newpage').click(function() {
				var activeNode = $('#t3-dd-pagetree').dynatree('getActiveNode');
				if (activeNode !== null) {
					that.createAndEditNode(activeNode);
				} else {
					T3.Common.Notification.notice('You have to select a page');
				}
				return false;
			});
				//deleting a page by clicking on the deletePage container, if a page is active
			$('#t3-action-deletepage').click(function() {
				var activeNode = $('#t3-dd-pagetree').dynatree('getActiveNode'),
					$handle = $(this);
				if (activeNode !== null) {
					if (activeNode.data.key !== siteRootNodePath) {
						that.showDeletePageDialog(activeNode);
					}else {
						T3.Common.Notification.notice('The Root page cannot be deleted.');
					}
				} else {
					T3.Common.Notification.notice('You have to select a page');
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
		$popoverContent: inspectTreeTemplate,
		popoverPosition: 'top',
		_ignoreCloseOnPageLoad: false,

		inspectTree: null,
		isLoadingLayerActive: function() {
			if (T3.ContentModule.get('_isLoadingPage')) {
				if (this.get('_ignoreCloseOnPageLoad')) {
					this.set('_ignoreCloseOnPageLoad', false);
					return;
				}
				$('.t3-inspect > button.pressed').click();
				if (this.inspectTree !== null) {
					$('#t3-dd-inspecttree').dynatree('destroy');
					this.inspectTree = null;
				}
			}
		}.observes('T3.ContentModule.currentUri'),

		onPopoverOpen: function() {
			var page = vie.entities.get(vie.service('rdfa').getElementSubject($('#t3-page-metainformation')));

			var pageTitle = page.get(T3.ContentModule.TYPO3_NAMESPACE + 'title'),
				pageNodePath = $('#t3-page-metainformation').attr('about');

				// if there is a tree and the rootnode key of the tree is different from the actual page, the tree should be reinitialised
			if (this.inspectTree) {
				if (pageNodePath !== $('#t3-dd-inspecttree').dynatree('getTree').getRoot().getChildren()[0].data.key) {
					$('#t3-dd-inspecttree').dynatree('destroy');
				}
			}

			this.inspectTree = $('#t3-dd-inspecttree').dynatree({
				debugLevel: 0, // 0:quiet, 1:normal, 2:debug,
				cookieId: null,
				persist: false,
				children: [
					{
						title: pageTitle ,
						key: pageNodePath,
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
					TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(
						node.data.key,
						'!TYPO3.TYPO3CR:Folder',
						0,
						function(result) {
						node._currentlySendingExtDirectAjaxRequest = false;
						if (result.success === true) {
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
						if (node.data.contentType === 'TYPO3.Phoenix.ContentTypes:Section') {
							return ['before', 'after', 'over'];
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
							// it is an existing node which was moved on the tree
						var position = hitMode === 'over' ? 'into' : hitMode;

						sourceNode.move(node, hitMode);
						T3.ContentModule.showPageLoader();
						TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.move(
							sourceNode.data.key,
							node.data.key,
							position,
							function(result) {
								if (result.success === true) {
										// We need to update the node path of the moved node,
										// else we cannot move it forth and back across levels.
									sourceNode.data.key = result.data.newNodePath;
									T3.ContentModule.reloadPage();
								} else {
									T3.Common.notification.error('Unexpected error while moving node: ' + JSON.stringify(result));
								}
							}
						);

					},
					onDragStop: function() {
					}
				},
				onClick: function(node, event) {
					var nodePath = node.data.key,
						offsetFromTop = 150,
						$element = $('[about="' + nodePath + '"]');

					T3.Content.Model.NodeSelection.updateSelection($element);

					$('html,body').animate({
						scrollTop: $element.offset().top - offsetFromTop
					}, 500);

				}
			});

				// Automatically expand the first node when opened
			this.inspectTree.dynatree('getRoot').getChildren()[0].expand(true);
		}
	});

	/**
	 * ================
	 * SECTION: UTILITY
	 * ================
	 * - Content Element Handle Utilities
	 */

	T3.Content.UI.Util = T3.Content.UI.Util || {};

	/**
	 * @param {Object} $contentElement jQuery object for the element to which the handles should be added
	 * @param {Integer} contentElementIndex The position in the collection on which paste / new actions should place the new entity
	 * @param {Object} collection The VIE entity collection to which the element belongs
	 * @param {Object} options A set of options passed to the actual Ember View (will be overridden with the required properties _element, _collection and _entityCollectionIndex)
	 * @return void
	 */
	T3.Content.UI.Util.AddContentElementHandleBars = function($contentElement, contentElementIndex, collection, isSection) {
		var handleContainerClassName, handleContainer;

		if (isSection) {
				// Add container BEFORE the section DOM element
			handleContainerClassName = 't3-section-handle-container';
			if ($contentElement.prev() && $contentElement.prev().hasClass(handleContainerClassName)) {
				return;
			}
			handleContainer = $('<div />', {'class': 't3-ui ' + handleContainerClassName}).insertBefore($contentElement);

			T3.Content.UI.SectionHandle.create({
				_element: $contentElement,
				_collection: collection,
				_entityCollectionIndex: contentElementIndex
			}).appendTo(handleContainer);

		} else {
				// Add container INTO the content elements DOM element
			handleContainerClassName = 't3-contentelement-handle-container';
			if (!$contentElement || $contentElement.find('> .' + handleContainerClassName).length > 0) {
				return;
			}
			handleContainer = $('<div />', {'class': 't3-ui ' + handleContainerClassName}).prependTo($contentElement);

				// Make sure we have a minimum height to be able to hover
			if ($contentElement.height() < 25) {
				$contentElement.css('min-height', '25px');
			}

			T3.Content.UI.ContentElementHandle.create({
				_element: $contentElement,
				_collection: collection,
				_entityCollectionIndex: contentElementIndex
			}).appendTo(handleContainer);
		}
	};

	T3.Content.UI.Util.AddNotInlineEditableOverlay = function($element, entity) {
		var setOverlaySizeFn = function() {
				// we use a timeout here to make sure the browser has re-drawn; thus $element
				// has a possibly updated size
			window.setTimeout(function() {
				$element.find('> .t3-contentelement-overlay').css({
					'width': $element.width(),
					'height': $element.height()
				});
			}, 10);
		};

			// Add overlay to content elements without inline editable properties and no sub-elements
		if ($element.find('> .t3-inline-editable').length === 0 && $element.find('.t3-contentsection, .t3-contentelement').length === 0) {
			var overlay = $('<div />', {
				'class': 't3-contentelement-overlay',
				'click': function(event) {
					if ($('.t3-primary-editor-action').length > 0) {
							// We need to use setTimeout here because otherwise the popover is aligned to the bottom of the body
						setTimeout(function() {
							$('.t3-primary-editor-action').click();
							if (Ember.View.views[jQuery('.t3-primary-editor-action').attr('id')] && Ember.View.views[jQuery('.t3-primary-editor-action').attr('id')].toggle) {
								Ember.View.views[jQuery('.t3-primary-editor-action').attr('id')].toggle();
							}
						}, 1);
					}
					event.preventDefault();
				}
			}).insertBefore($element.find('> .t3-contentelement-handle-container'));

			$('<span />', {'class': 't3-contentelement-overlay-icon'}).appendTo(overlay);

			setOverlaySizeFn();

			entity.on('change', function() {
					// If the entity changed, it might happen that the size changed as well; thus we need to reload the overlay size
				setOverlaySizeFn();
			});
		};
	}
});