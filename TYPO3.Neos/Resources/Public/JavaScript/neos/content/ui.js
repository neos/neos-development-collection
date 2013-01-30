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
	'text!neos/templates/content/ui/breadcrumb.html',
	'text!neos/templates/content/ui/inspector.html',
	'text!neos/templates/content/ui/inspectorDialog.html',
	'text!neos/templates/content/ui/pageTree.html',
	'text!neos/templates/content/ui/deletePageDialog.html',
	'text!neos/templates/content/ui/inspectTree.html',
	'text!neos/templates/content/ui/saveIndicator.html',
	'text!neos/templates/content/ui/treePanel.html',
	'neos/content/ui/elements',
	'neos/content/ui/editors',
	'jquery.popover',
	'jquery.jcrop',
	'jquery.plupload',
	'jquery.plupload.html5',
	'jquery.cookie',
	'jquery.dynatree',
	'bootstrap.dropdown'
],
function($, Ember, vie, breadcrumbTemplate, inspectorTemplate, inspectorDialogTemplate, pageTreeTemplate, deletePageDialogTemplate, inspectTreeTemplate, saveIndicatorTemplate, treePanelTemplate) {
	if (window._requirejsLoadingTrace) {
		window._requirejsLoadingTrace.push('neos/content/ui');
	}

	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}
	T3.Content.UI = T3.Content.UI || {};

	/**
	 * =====================
	 * SECTION: UI CONTAINERS
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
				},
				cancel: function() {
					view.destroy();
				},
				apply: function() {
					T3.Content.Controller.Inspector.apply();
					view.destroy();
				},
				dontApply: function() {
					T3.Content.Controller.Inspector.revert();
					view.destroy();
				}
			});
			view.appendTo('#t3-inspector');
		}
	});

	/**
	 * =====================
	 * SECTION: TREE PANEL
	 * =====================
	 */
	T3.Content.UI.TreePanel = Ember.View.extend({
		template: Ember.Handlebars.compile(treePanelTemplate),
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
				valueBinding: 'T3.Content.Controller.Inspector.nodeProperties.' + this.propertyDefinition.key,
				elementId: this.propertyDefinition.elementId
			}, this.propertyDefinition.options || {});
			classOptions = $.extend(classOptions, typeDefinition.options || {});

			var editor = editorClass.create(classOptions);
			this.appendChild(editor);
			this._super();
		}
	});

		// Is necessary otherwise a button has always the class 'btn-mini'
	T3.Content.UI.ButtonDialog = Ember.Button.extend({
		classNames: ['btn, btn-danger, t3-button'],
		attributeBindings: ['disabled'],
		classNameBindings: ['iconClass'],
		label: '',
		disabled: false,
		visible: true,
		icon: '',
		template: Ember.Handlebars.compile('{{label}}'),
		iconClass: function() {
			var icon = this.get('icon');
			return icon !== '' ? 't3-icon-' + icon : '';
		}.property('icon').cacheable()
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
		popoverAdditionalClasses: 't3-inspecttree',
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
			var page = vie.entities.get(vie.service('rdfa').getElementSubject($('#t3-page-metainformation'))),
				pageTitle = page.get(T3.ContentModule.TYPO3_NAMESPACE + 'title'),
				pageNodePath = $('#t3-page-metainformation').attr('about');

				// If there is a tree and the rootnode key of the tree is different from the actual page, the tree should be reinitialised
			if (this.inspectTree) {
				if (pageNodePath !== $('#t3-dd-inspecttree').dynatree('getTree').getRoot().getChildren()[0].data.key) {
					$('#t3-dd-inspecttree').dynatree('destroy');
				}
			}

			this.inspectTree = $('#t3-dd-inspecttree').dynatree({
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
						unselectable: true
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
							// It is only posssible to move nodes into nodes of the contentType:Section
						if (node.data.contentType === 'TYPO3.Neos.ContentTypes:Section') {
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
						T3.ContentModule.showPageLoader();
						TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.move(
							sourceNode.data.key,
							node.data.key,
							position,
							function(result) {
								if (result !== null && result.success === true) {
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
						'!TYPO3.TYPO3CR:Folder',
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

	T3.Content.UI.SaveIndicator = Ember.View.extend({
		saveRunning: false,
		lastSuccessfulTransfer: null,

		template: Ember.Handlebars.compile(saveIndicatorTemplate),

		lastSuccessfulTransferLabel: function() {
			var date = this.get('lastSuccessfulTransfer');
			if (date !== null) {
				function pad(n) {
					return n < 10 ? '0' + n : n;
				}
				return 'Saved at ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds())
			}
			return '';
		}.property('lastSuccessfulTransfer')
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
				// We use a timeout here to make sure the browser has re-drawn; thus $element
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
		}
	};
});