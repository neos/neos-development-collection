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

	var PopoverButton = ToggleButton.extend({
		popoverTitle: '',
		_popoverContent: $('<div></div>'),
		popoverPosition: 'bottom',
		didInsertElement: function() {
			var that = this;
			this.$().popover({
				header: $('<div>' + that.get('popoverTitle') + '</div>'),
				content: that._popoverContent,
				preventLeft: (that.get('popoverPosition')==='left' ? false : true),
				preventRight: (that.get('popoverPosition')==='right' ? false : true),
				preventTop: (that.get('popoverPosition')==='top' ? false : true),
				preventBottom: (that.get('popoverPosition')==='bottom' ? false : true),
				closeEvent: function() {
					that.set('pressed', false);
				},
				openEvent: function() {
					that._onPopoverOpen.call(that);
				}
			});
		},

		_onPopoverOpen: function() {
			// template method, to be implemented in subclasses.
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
		template: SC.Handlebars.compile('{{item._title}}'),
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
				"class": contextData.key,
				valueBinding: "T3.Content.Model.BlockSelection.selectedBlock." + contextData.key
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
	var getPageTreeLoader = function() {
		// We need to wrap the Ext.extend() call in a function which is evaluated lazily,
		// as ExtJS from Aloha is *not yet loaded* when this file is first included.
		return Ext.extend(Ext.tree.TreeLoader, {
			/**
			 * Wrapper for extDirect call to NodeController which
			 * adds the current node path information to the extDirect call
			 *
			 * @param {String} contextNodePath the current Context Node Path to get subnodes from
			 * @param {Function} callback function after request is done
			 * @return {void}
			 */
			directFn: function(contextNodePath, callback) {
				TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesForTree(contextNodePath, 'TYPO3.TYPO3:Page', callback);
			},

			/**
			 * Process the response of directFn and give the appropriate data to handleResponse
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
		});
	};

	var PageTreeButton = PopoverButton.extend({
		_popoverContent: $('<div class="extjs-container"></div>'),
		_tree: null,
		_onPopoverOpen: function() {
			if (this._tree) return;

			var PageTreeLoader = getPageTreeLoader();
			this._tree = new Ext.tree.TreePanel({
				width:250,
				height:350,
				useArrows: true,
				autoScroll: true,
				animate: true,
				enableDD: true,
				border: false,

				root: {
					id: $('body').data('_siteroot'), // TODO: This and the following properties might later come from the SproutCore model...
					text: $('body').data('_sitename'),
					draggable: false
				},
				listeners: {
					click: function(node, event) {
							// TODO: clean this up, so that clicking the "GOTO" link works without this click hack; or built some different way of handling this case.
						if ($(event.getTarget()).is('a.t3-gotoPage')) {
							window.location.href = $(event.getTarget()).attr('href');
						}
					}
				},
				loader: new PageTreeLoader()
			});
			this._tree.render(this._popoverContent[0]);

			this._tree.getRootNode().expand();
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

