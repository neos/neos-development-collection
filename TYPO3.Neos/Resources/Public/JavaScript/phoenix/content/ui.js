/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'text!phoenix/content/ui/toolbar.html',
	'text!phoenix/content/ui/breadcrumb.html',
	'text!phoenix/content/ui/propertypanel.html',
	'Library/jquery-popover/jquery.popover',
	'css!Library/jquery-popover/jquery.popover.css',

],
function(toolbarTemplate, breadcrumbTemplate, propertyPanelTemplate) {
	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};
	var $ = window.alohaQuery || window.jQuery;


	/**
	 * ===========================
	 * SECTION: SIMPLE UI ELEMENTS
	 * ===========================
	 * - Toolbar
	 * - Button
	 * - ToggleButton
	 * - PopoverButton
	 */

	/**
	 * T3.Content.UI.Toolbar
	 *
	 * Toolbar which can contain other views. Has two areas, left and right.
	 */
	var Toolbar = SC.View.extend({
		tagName: 'div',
		classNames: ['t3-toolbar', 'aloha-block-do-not-deactivate'],
		template: SC.Handlebars.compile(toolbarTemplate)
	});

	/**
	 * T3.Content.UI.Button
	 *
	 * A simple, styled TYPO3 button.
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	var Button = SC.Button.extend({
		classNames: ['t3-button'],
		attributeBindings: ['disabled'],
		classNameBindings: ['iconClass'],
		label: '',
		disabled: false,
		visible: true,
		icon: '',
		template: SC.Handlebars.compile('{{label}}'),
		iconClass: function() {
			var icon = this.get('icon');
			return icon !== '' ? 't3-icon-' + icon : '';
		}.property('icon').cacheable()
	});

	/**
	 * T3.Content.UI.ToggleButton
	 *
	 * A button which has a "pressed" state
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	var ToggleButton = Button.extend({
		classNames: ['t3-button'],
		classNameBindings: ['pressed'],
		pressed: false,
		toggle: function() {
			this.set('pressed', !this.get('pressed'));
		},
		mouseUp: function(event) {
			if (this.get('isActive')) {
				var action = this.get('action'),
				target = this.get('targetObject');

				this.toggle();
				if (target && action) {
					if (typeof action === 'string') {
						action = target[action];
					}
					action.call(target, this.get('pressed'), this);
				}

				this.set('isActive', false);
			}

			this._mouseDown = false;
			this._mouseEntered = false;
		}
	});

	/**
	 * T3.Content.UI.PopoverButton
	 *
	 * A button which, when pressed, shows a "popover". You will subclass
	 * this class and implement onPopoverOpen / popoverTitle / $popoverContent
	 */
	var PopoverButton = ToggleButton.extend({

		/**
		 * @var {String} title of the popover
		 */
		popoverTitle: '',

		/**
		 * @var {jQuery} content of the popover. to be manipulated in the onPopoverOpen function
		 */
		$popoverContent: $('<div></div>'),

		/**
		 * @var {String} one of "top, bottom, left, right". Specifies the popover position.
		 */
		popoverPosition: 'bottom',

		/**
		 * Lifecycle method by SproutCore, executed as soon as the element has been
		 * inserted in the DOM and the $() method is executable. We initialize the
		 * popover at this point.
		 */
		didInsertElement: function() {
			var that = this;
			this.$().popover({
				header: $('<div>' + that.get('popoverTitle') + '</div>'),
				content: that.$popoverContent,
				preventLeft: (that.get('popoverPosition')==='left' ? false : true),
				preventRight: (that.get('popoverPosition')==='right' ? false : true),
				preventTop: (that.get('popoverPosition')==='top' ? false : true),
				preventBottom: (that.get('popoverPosition')==='bottom' ? false : true),
				zindex: 10090,
				closeEvent: function() {
					that.set('pressed', false);
				},
				openEvent: function() {
					that.onPopoverOpen.call(that);
				}
			});
		},

		/**
		 * Template method, to be implemented in subclasses. Usually,
		 * you want to manipulate this.$popoverContent in this method.
		 */
		onPopoverOpen: function() {
		}
	});

	/**
	 * =====================
	 * SECTION: UI CONTAINRS
	 * =====================
	 * - Breadcrumb
	 * - BreadcrumbItem
	 * - PropertyPanel
	 */

	/**
	 * T3.Content.UI.Breadcrumb
	 *
	 * The breadcrumb menu
	 */
	var Breadcrumb = SC.View.extend({
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
	var BreadcrumbItem = SC.View.extend({
		tagName: 'a',
		href: '#',

		// TODO Don't need to bind here actually
		attributeBindings: ['href'],
		template: SC.Handlebars.compile('{{item._titleAndModifcationState}}'),
		click: function(event) {
			var item = this.get('item');
			T3.Content.Model.BlockSelection.selectItem(item);
			event.stopPropagation();
			return false;
		}
	});

	/**
	 * T3.Content.UI.PropertyPanel
	 *
	 * The Property Panel displayed on the right side of the page.
	 */
	var PropertyPanel = SC.View.extend({
		template: SC.Handlebars.compile(propertyPanelTemplate)
	});

	var propertyTypeMap = {
		'boolean': 'SC.Checkbox',
		'string': 'SC.TextField'
	};

	Handlebars.registerHelper('propertyEditWidget', function(x) {
		var contextData = this.get('content');

		var viewClassPath = propertyTypeMap[contextData.type];

		// todo: understand all options and clean
		var options = {
			data: {
				view: this
			},
			hash: {
				'class': contextData.key, // we need to escape "class" as it is a reserved keyword in JS
				classBinding: 'T3.Content.Model.BlockSelection.selectedBlock._valueModified.' + contextData.key,
				valueBinding: 'T3.Content.Model.BlockSelection.selectedBlock.' + contextData.key
			}
		};

		return SC.Handlebars.ViewHelper.helper(this, viewClassPath, options);
	});

	/**
	 * ==================
	 * SECTION: PAGE TREE
	 * ==================
	 * - PageTreeLoader
	 * - PageTreeButton
	 */
	var PageTreeButton = PopoverButton.extend({
		$popoverContent: $('<div class="extjs-container"></div>'),

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
				ddGroup: 'pages',

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

				listeners: {
					click: this._onTreeNodeClick,
					movenode: this._onTreeNodeMove,
					beforenodedrop: this._onTreeNodeDrop
				}
			});

			this._initNewPageDraggable();

			var $treeContainer = $('<div />');
			this.$popoverContent.append($treeContainer);

			this._tree.render($treeContainer[0]);
			this._tree.getRootNode().expand();
		},

		/**
		 * Initializer for the "new page" draggable, creating an element
		 * and a Drag Zone.
		 */
		_initNewPageDraggable: function() {
			var $newPageDraggable = $('<div>New page</div>');
			this.$popoverContent.append($newPageDraggable);

			new Ext.dd.DragZone($newPageDraggable[0], {
				ddGroup: 'pages',

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
		_onTreeNodeMove: function() {
			// TODO: implement
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

	T3.Content.UI = {
		Toolbar: Toolbar,
		Button: Button,
		ToggleButton: ToggleButton,
		PopoverButton: PopoverButton,
		PageTreeButton: PageTreeButton,
		Breadcrumb: Breadcrumb,
		BreadcrumbItem: BreadcrumbItem,
		PropertyPanel: PropertyPanel
	};
});

