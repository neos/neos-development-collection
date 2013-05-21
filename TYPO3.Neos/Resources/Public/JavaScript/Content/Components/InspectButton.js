/**
 * Footer toolbar
 */
define(
	[
		'./PopoverButton'
	], function(PopoverButton) {

		return PopoverButton.extend({
			popoverTitle: 'Content Structure',
			$popoverContent: inspectTreeTemplate,
			popoverPosition: 'top',
			popoverAdditionalClasses: 'neos-inspecttree',
			_ignoreCloseOnPageLoad: false,
			inspectTree: null,

			init: function() {
				this._super();
				var that = this;
				ContentModule.on('pageLoaded', function() {
					entityWrapper = T3.Content.Model.NodeSelection._createEntityWrapper($('#neos-page-metainformation'));
					entityWrapper.addObserver('typo3:title', function() {
						var attributes = EntityWrapper.extractAttributesFromVieEntity(entityWrapper._vieEntity);
						that.synchronizeInspectTreeTitle(attributes);
					});
				});
			},
			synchronizeInspectTreeTitle: function(attributes) {
				var rootNode = this.getInspectTreeRootNode();
				if (rootNode) {
					rootNode.setTitle(attributes.title);
				}
			},
			getInspectTreeRootNode: function() {
				var pageNodePath = $('#neos-page-metainformation').attr('about');
				if ($('#neos-dd-inspecttree').children().length > 0) {
					var tree = $('#neos-dd-inspecttree').dynatree('getTree');
					var rootNode = tree.getNodeByKey(pageNodePath);
					return rootNode;
				} else {
					return null;
				}
			},

			isLoadingLayerActive: function() {
				if (ContentModule.get('_isLoadingPage')) {
					if (this.get('_ignoreCloseOnPageLoad')) {
						this.set('_ignoreCloseOnPageLoad', false);
						return;
					}
					this.resetInspectTree();
				}
			}.observes('ContentModule.currentUri'),

			resetInspectTree: function() {
				$('.neos-inspect > button.pressed').click();
				if (this.inspectTree !== null) {
					$('#neos-dd-inspecttree').dynatree('destroy');
					this.inspectTree = null;
				}
			},

			onPopoverOpen: function() {
				var page = vie.entities.get(vie.service('rdfa').getElementSubject($('#neos-page-metainformation'))),
					pageTitle = page.get(ContentModule.TYPO3_NAMESPACE + 'title'),
					pageNodePath = $('#neos-page-metainformation').attr('about');

					// If there is a tree and the rootnode key of the tree is different from the actual page, the tree should be reinitialised
				if (this.inspectTree) {
					if (pageNodePath !== $('#neos-dd-inspecttree').dynatree('getTree').getRoot().getChildren()[0].data.key) {
						$('#neos-dd-inspecttree').dynatree('destroy');
					}
				}

				this.inspectTree = $('#neos-dd-inspecttree').dynatree({
					debugLevel: 0, // 0: quiet, 1: normal, 2: debug
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
							unselectable: true,
							addClass: 'typo3_neos-page'
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
								// It is only posssible to move nodes into nodes of the nodeType ContentCollection
							if (node.data.nodeType === 'TYPO3.Neos:ContentCollection') {
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

						/**
						 * This function MUST be defined to enable dropping of items on
						 * the tree.
						 *
						 * hitmode over, after and before
						 */
						onDrop: function(node, sourceNode, hitMode, ui, draggable) {
								// It is an existing node which was moved on the tree
							var position = hitMode === 'over' ? 'into' : hitMode;

							sourceNode.move(node, hitMode);
							ContentModule.showPageLoader();
							TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.move(
								sourceNode.data.key,
								node.data.key,
								position,
								function(result) {
									if (result !== null && result.success === true) {
											// We need to update the node path of the moved node,
											// else we cannot move it forth and back across levels.
										sourceNode.data.key = result.data.newNodePath;
										ContentModule.reloadPage();
									} else {
										T3.Common.notification.error('Unexpected error while moving node: ' + JSON.stringify(result));
									}
								}
							);
						},

						onDragStop: function() {
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
							'!TYPO3.Neos:Document',
							0,
							function(result) {
								node._currentlySendingExtDirectAjaxRequest = false;
								if (result !== null && result.success === true) {
									node.setLazyNodeStatus(DTNodeStatus_Ok);
									node.addChild(result.data);
								} else {
									node.setLazyNodeStatus(DTNodeStatus_Error);
									T3.Common.Notification.error('Page Tree loading error.');
								}
							}
						);
					},

					onClick: function(node, event) {
						var nodePath = node.data.key,
							offsetFromTop = 150,
							$element = $('[about="' + nodePath + '"]');

						T3.Content.Model.NodeSelection.updateSelection($element);
						$('html, body').animate({
							scrollTop: $element.offset().top - offsetFromTop
						}, 500);
					}
				});

					// Automatically expand the first node when opened
				this.inspectTree.dynatree('getRoot').getChildren()[0].expand(true);
			}
		});
	}
);