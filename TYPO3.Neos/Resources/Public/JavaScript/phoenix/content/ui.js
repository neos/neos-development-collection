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
],
function(toolbarTemplate, breadcrumbTemplate, propertyPanelTemplate) {
	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};

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
	 * T3.Content.UI.BreadcrumbPage
	 *
	 * the leftmost breadcrumb menu item. Should go away in the longer run.
	 *
	 * @internal
	 */
	var BreadcrumbPage = BreadcrumbItem.extend({
		id: 't3-page',
		title: 'test',
		tagName: 'a',
		href: '#',

		// TODO Don't need to bind here actually
		attributeBindings: ['href'],
		template: SC.Handlebars.compile('{{view T3.Content.UI.Button label="Inspect"}} Page'),
		click: function(event) {
			T3.Content.Model.BlockSelection.selectPage();
			event.stopPropagation();
			return false;
		},

		schema: function() {
			return [{
						key: 'Main',
						properties: [
							{
								key: 'title',
								type: 'string',
								label: 'Package'
							}
						]
					}];
		}.property().cacheable()
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


	T3.Content.UI = {
		Toolbar: Toolbar,
		Button: Button,
		ToggleButton: ToggleButton,
		Breadcrumb: Breadcrumb,
		BreadcrumbItem: BreadcrumbItem,
		BreadcrumbPage: BreadcrumbPage,
		PropertyPanel: PropertyPanel
	};
});

