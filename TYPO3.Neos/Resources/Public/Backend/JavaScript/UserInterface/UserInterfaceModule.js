Ext.ns("F3.TYPO3.UserInterface");

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @class F3.TYPO3.UserInterface.UserInterfaceModule
 *
 * Module descriptor for the user interface parts..
 *
 * @namespace F3.TYPO3.UserInterface
 * @singleton
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.UserInterface.UserInterfaceModule', {

	/**
	 * @event activate-[FullyQualifiedButtonPath]
	 * @event deactivate-[FullyQualifiedButtonPath]
	 * @param {F3.TYPO3.UserInterface.BreadcrumbMenuComponent} -- needs the getNodePath...
	 */

	/**
	 * @event _ContentArea.initialized
	 * @param {F3.TYPO3.UserInterface.ContentArea} contentArea the content area which has been initialized
	 * @private
	 *
	 * Fired after a content area has been initialized. Internally used to
	 * add elements to the content area.
	 */

	/**
	 * @event Viewport.initialized
	 * @param {F3.TYPO3.UserInterface.Layout} the viewport reference
	 *
	 * Fired after the viewport has been initialized.
	 */

	/**
	 * @var F3.TYPO3.UserInterface.Layout
	 * @api
	 */
	viewport: null,

	/**
	 * Register default form editors
	 *
	 * @param {F3.TYPO3.Core.Registry} The registry
	 * @return {void}
	 */
	configure: function(registry) {
		registry.set('form/editor', {
			// By type
			'string': {
				xtype: 'textfield'
			},
			'superStringEditor': {
				xtype: 'textarea',
				transform: function(a) { }
			}
		});
	},

	/**
	 * Initialize the viewport after boostrap
	 *
	 * @param {F3.TYPO3.Core.Application} The Application object
	 * @return {void}
	 */
	initialize: function(application) {
		application.on('afterBootstrap', this._initViewport, this);
	},

	/**
	 * Create the main viewport for layouting all components in a full
	 * width and height browser window.
	 *
	 * @private
	 */
	_initViewport: function() {
		this.viewport = new F3.TYPO3.UserInterface.Layout();
	},

	/**
	 * @param string path The path to a button (like menu[main]/content[]/...). If this button is pressed, the module
	 * dialog is shown; if it is unpressed, it is hidden.
	 *
	 * @param object ExtJS Component Configuration for the Module Dialog
	 * @param object ExtJS Component Configuration for the Content Dialog
	 * @api
	 */
	moduleDialogOn: function(path, moduleDialogConfiguration, contentDialogConfiguration) {
		path = F3.TYPO3.Core.Registry.rewritePath(path);

		this.on('activate-' + path, function(node) {
			var moduleDialog = node.getModuleMenu().showModuleDialog(moduleDialogConfiguration, contentDialogConfiguration);

			// Untoggle button on module dialog destroy
			moduleDialog.on('destroy', function() {
				node.getModuleMenu().breadcrumbMenu.deactivateItem(path);
			});
		});

		this.on('deactivate-' + path, function(button) {
			button.getModuleMenu().removeModuleDialog();
		});
	},

	/**
	 * Add a content area to the user interface
	 *
	 * @param {String} sectionId The section where the area should be added
	 * @param {String} itemId The id of the content area inside the section (e.g. 'managementView')
	 * @param {Object} configuration Configuration for building the component
	 * @return {void}
	 */
	addContentArea: function(sectionId, itemId, configuration) {
		// TODO: if default content area, we activate it.
		this.on('_ContentArea.initialized', function(contentArea) {
			if (sectionId + '-contentArea' == contentArea.itemId) {
				contentArea.add(Ext.apply(configuration, {
					itemId: itemId
				}));
			}
		});
	},

	/**
	 * Add a listener to a menu to activate the given content area if the menu item
	 * is activated.
	 *
	 * @param {String} path The menu item path (e.g. 'menu[main]/management')
	 * @param {String} sectionId The section of the content area
	 * @param {String} itemId The id of the content area inside the section
	 * @return {void}
	 */
	contentAreaOn: function(path, sectionId, itemId) {
		path = F3.TYPO3.Core.Registry.rewritePath(path);
		// TODO: "path" is not always a reference to a tab, so it might not be safe to go locally from the button to the moduleMenu.
		this.on('activate-' + path, function() {
			var viewport = F3.TYPO3.UserInterface.UserInterfaceModule.viewport;
			var tab = viewport.sectionMenu.getComponent(sectionId);
			tab.contentArea.getLayout().setActiveItem(itemId);
			tab.contentArea.doLayout();
		});
	}
});