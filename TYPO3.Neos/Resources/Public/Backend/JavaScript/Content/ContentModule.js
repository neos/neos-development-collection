Ext.ns("F3.TYPO3.Content");

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
 * @class F3.TYPO3.Content.ContentModule
 *
 * The Module Descriptor for the Content module
 *
 * @namespace F3.TYPO3.Content
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.Content.ContentModule', {

	/**
	 * Are we currently in Editing Mode?
	 *
	 * @var {Boolean}
	 */
	_isEditing: false,

	/**
	 * @event AlohaConnector.contentChanged
	 *
	 * fires when there is changed content which should be persisted by the TYPO3 backend.
	 * @param {Object} data <ul>
	 *   <li><b>__context</b>: <ul>
	 *     <li><b>workspaceName</b>: Name of workspace the object should be saved into</li>
	 *     <li><b>nodePath</b>: Path to node which should be saved
	 *   </ul></li>
	 *   <li><b>properties</b>: All properties of the content object, which should be saved.</li>
	 * </ul>
	 */

	/**
	 * Modify the registry
	 *
	 * @param {F3.TYPO3.Core.Registry} registry
	 * @return {void}
	 */
	configure: function(registry) {
		registry.append('menu/main', 'content', {
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-ContentTab',
			title: 'Content',
			itemId: 'content',
			viewFilter: {
				xtype: 'F3.TYPO3.UserInterface.ViewFilterToolbar'
			}
		});
		registry.append('menu/main/content[]', 'edit', {
			itemId: 'edit',
			text: 'Edit',
			iconCls: 'F3-TYPO3-Content-icon-edit'
		});
		registry.append('menu/main/content[]/edit[]', 'pageProperties', {
			itemId: 'pageProperties',
			text: 'Page properties',
			iconCls: 'F3-TYPO3-Content-icon-pageProperties'
		});
		registry.append('menu/main/content[]', 'createPage', {
			itemId: 'Create',
			text: 'Create Page',
			iconCls: 'F3-TYPO3-Content-icon-createPage'
		});
		registry.append('menu/main/content[]', 'deletePage', {
			itemId: 'Delete',
			text: 'Delete Page',
			iconCls: 'F3-TYPO3-Content-icon-deletePage'
		});

		registry.append('menu/viewFilterToolbar', 'workspaceName', {
			text: F3.TYPO3.Configuration.Application.workspaceName,
			cls: 'F3-TYPO3-ContextToolbar-icon-workspaceName'
		});
		registry.append('menu/viewFilterToolbar', 'siteName', {
			text: F3.TYPO3.Configuration.Application.siteName,
			cls: 'F3-TYPO3-ContextToolbar-icon-siteName',
			disabled: true
		});
		registry.append('menu/viewFilterToolbar', 'contextLocale', {
			text: F3.TYPO3.Configuration.Application.contextLocale,
			cls: 'F3-TYPO3-ContextToolbar-icon-contextLocale',
			disabled: true
		});

			// This will come from the server later on
		registry.set('schema/type', {
			"TYPO3:Page": {
				service: {
					show: 'F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.show',
					update: 'F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.update',
					create: 'F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.create',
						// "delete" is a special case because it's a reserved keyword.
						// Because of this, it needs to be quoted on the left side.
					'delete': 'F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.delete'
				},
				properties: {
					'nodeName': {
						type: 'string',
						validations: [{
							type: 'NotEmpty'
						}, {
							type: 'RegularExpression',
							options: {
								regularExpression: '^[a-zA-Z0-9][a-zA-Z0-9\\-\\/][a-zA-Z0-9]{0,254}$'
							}
						}]
					},
					'properties.title': {
						type: 'string',
						validations: [{
							type: 'NotEmpty'
						}]
					}
				}
			}
		});

			// Set forms for default types
		registry.set('form/type', {
			"TYPO3:Page": {
				standard: {
					title: 'Page',
					children: [{
						key: 'pageProperties',
						type: 'fieldset',
						title: 'Page properties',
						children: [{
							key: 'title',
							type: 'field',
							property: 'properties.title',
							title: 'Page title'
						}]
					}]
				},
				pageProperties: {
					title: 'Page properties',
					children: [{
						key: 'title',
						type: 'field',
						property: 'properties.title',
						title: 'Page title'
					}]
				},
				create: {
					title: 'Create page',
					children: [{
						key: 'nodeName',
						type: 'field',
						property: 'nodeName',
						title: 'Node name'
					}, {
						key: 'title',
						type: 'field',
						property: 'properties.title',
						title: 'Page title'
					}]
				}
			}
		});
	},

	/**
	 * Set up event handlers
	 *
	 * @param {F3.TYPO3.Core.Application} The Application object
	 * @return {void}
	 */
	initialize: function(application) {
		application.afterInitializationOf('F3.TYPO3.UserInterface.UserInterfaceModule', function(userInterfaceModule) {
			userInterfaceModule.addContentArea('content', 'frontendEditor', {
				xtype: 'F3.TYPO3.Content.ContentEditor',
				id: 'F3.TYPO3.Content.ContentEditor'
			});
			userInterfaceModule.contentAreaOn('menu/main/content', 'content', 'frontendEditor');

			userInterfaceModule.moduleDialogOn('menu/main/content[]/edit[]/pageProperties',
				{xtype: 'F3.TYPO3.Content.Edit.PagePropertiesDialog'},
				{
					xtype: 'F3.TYPO3.UserInterface.ContentDialog',
					okButton: 'Update Page',
					cancelButton: 'Cancel',
					mode: 'positive'
				}
			);
			userInterfaceModule.moduleDialogOn('menu/main/content[]/createPage',
				{xtype: 'F3.TYPO3.Content.Edit.CreatePageDialog'},
				{
					xtype: 'F3.TYPO3.UserInterface.ContentDialog',
					okButton: 'Create Page',
					cancelButton: 'Cancel',
					mode: 'positive'
				}
			);
			userInterfaceModule.moduleDialogOn('menu/main/content[]/deletePage',
				{xtype: 'F3.TYPO3.Content.Edit.DeletePageDialog'},
				{
					xtype: 'F3.TYPO3.UserInterface.ContentDialog',
					infoText: 'Are you sure you want to delete this page? Any content on this page will be lost.',
					okButton: 'Delete Page',
					cancelButton: 'Cancel',
					mode: 'negative'
				}
			);

			userInterfaceModule.on('activate-menu/main/content/children/edit', function() {
				Ext.getCmp('F3.TYPO3.Content.ContentEditor').enableEditing();
				F3.TYPO3.Content.ContentModule._isEditing = true;
			});

			userInterfaceModule.on('deactivate-menu/main/content/children/edit', function() {
				Ext.getCmp('F3.TYPO3.Content.ContentEditor').disableEditing();
				F3.TYPO3.Content.ContentModule._isEditing = false;
			});
		});
	},

	/**
	 * @return {Boolean} true if the Aloha Editing mode is active, false otherwise.
	 */
	isEditing: function() {
		return this._isEditing;
	},

	/**
	 * Enable the editing mode, selecting the appropriate element in the breadcrumb menu,
	 * and thus firing the events for activating an element.
	 *
	 * @return {void}
	 */
	enableEditing: function() {
		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.setActiveTab('content');
		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.getComponent('content').moduleMenu.breadcrumbMenu.activateItem('menu/main/content[]/edit');
	},

	/**
	 * Disable editing mode
	 *
	 * @return {void}
	 */
	disableEditing: function() {
		F3.TYPO3.UserInterface.UserInterfaceModule.viewport.sectionMenu.getComponent('content').moduleMenu.breadcrumbMenu.deactivateItem('menu/main/content[]/edit');
	},

	/**
	 * Get the frontend context of the page currently being displayed in the
	 * iframe.
	 *
	 * @return {Object} the current frontend context
	 */
	getCurrentContentContext: function() {
		return Ext.getCmp('F3.TYPO3.Content.ContentEditor').getCurrentContext();
	},

	/**
	 * Load a certain page inside the ContentEditor iframe
	 *
	 * @param {String} uri the URI to load inside the ContentEditor
	 * @return {void}
	 */
	loadPage: function(uri) {
		Ext.getCmp('F3.TYPO3.Content.ContentEditor').loadPage(uri);
	}
});