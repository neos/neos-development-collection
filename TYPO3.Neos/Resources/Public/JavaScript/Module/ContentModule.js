Ext.ns('F3.TYPO3.Module');

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
 * @class F3.TYPO3.Module.Content.ContentModule
 *
 * The Module Descriptor for the Content module
 *
 * @namespace F3.TYPO3.Module.Content
 * @extends Ext.util.Observable
 * @singleton
 */
F3.TYPO3.Core.Application.createModule('F3.TYPO3.Module.ContentModule', {

	/**
	 * Modify the registry
	 *
	 * @param {F3.TYPO3.Core.Registry} registry
	 * @return {void}
	 */
	configure: function(registry) {
		this._configureMainMenu(registry);
		this._configureSchema(registry);
		this._configureSelectionModeFloatingMenu(registry);
	},

	/**
	 * Add items to the Main Menu
	 *
	 * @param {F3.TYPO3.Core.Registry} registry
	 * @return {void}
	 * @private
	 */
	_configureMainMenu: function(registry) {
		registry.append('menu/main', 'content', {
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-ContentTab',
			iconCls: 'f3-typo3-content-mode-indicator',
			title: F3.TYPO3.Core.I18n.get('TYPO3', 'content'),
			itemId: 'content',
			viewFilter: {
				xtype: 'F3.TYPO3.Module.UserInterface.ViewFilterToolbar'
			}
		});

		registry.append('menu/main/content[]', 'edit', {
			itemId: 'edit',
			text: F3.TYPO3.Core.I18n.get('TYPO3', 'edit'),
			iconCls: 'F3-TYPO3-Content-icon-edit'
		});
		registry.append('menu/main/content[]/edit[]', 'pageProperties', {
			itemId: 'pageProperties',
			text: F3.TYPO3.Core.I18n.get('TYPO3', 'pageProperties'),
			iconCls: 'F3-TYPO3-Content-icon-pageProperties'
		});

		registry.append('menu/main/content[]', 'movePage', {
			itemId: 'movePage',
			text: F3.TYPO3.Core.I18n.get('TYPO3', 'movePage'),
			iconCls: 'F3-TYPO3-Content-icon-movePage'
		});
		registry.append('menu/main/content[]', 'createPage', {
			itemId: 'Create',
			text: F3.TYPO3.Core.I18n.get('TYPO3', 'createPage'),
			iconCls: 'F3-TYPO3-Content-icon-createPage'
		});
		registry.append('menu/main/content[]', 'deletePage', {
			itemId: 'Delete',
			text: F3.TYPO3.Core.I18n.get('TYPO3', 'deletePage'),
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
	},

	/**
	 * Configure the Schema to be used for the forms. This will come from
	 * the server lateron.
	 *
	 * @param {F3.TYPO3.Core.Registry} registry
	 * @return {void}
	 * @private
	 */
	_configureSchema: function(registry) {
		registry.set('schema/type', {
			'TYPO3:Page': {
				service: {
					show: 'F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.show',
					update: 'F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.update',
					create: 'F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.create',
					move: 'F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.move',
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
								regularExpression: '^[a-z0-9-]{0,254}$'
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
			'TYPO3:Page': {
				standard: {
					title: F3.TYPO3.Core.I18n.get('TYPO3', 'page'),
					children: [{
						key: 'pageProperties',
						type: 'fieldset',
						title: F3.TYPO3.Core.I18n.get('TYPO3', 'pageProperties'),
						children: [{
							key: 'title',
							type: 'field',
							property: 'properties.title',
							title: F3.TYPO3.Core.I18n.get('TYPO3', 'pageTitle')
						}]
					}]
				},
				pageProperties: {
					title: F3.TYPO3.Core.I18n.get('TYPO3', 'pageProperties'),
					children: [{
						key: 'title',
						type: 'field',
						property: 'properties.title',
						title: F3.TYPO3.Core.I18n.get('TYPO3', 'pageTitle')
					}]
				},
				move: {
					title: F3.TYPO3.Core.I18n.get('TYPO3', 'movePage'),
					layout: 'hbox',
					children: [{
						type: 'custom',
						xtype: 'container',
						flex: 1,
						children: [{
							type: 'custom',
							xtype: 'F3.TYPO3.Components.OrderSelect',
							id: 'F3.TYPO3.Module.ContentModule.move',
							move: true
						}]
					}]
				},
				create: {
					title: F3.TYPO3.Core.I18n.get('TYPO3', 'createPage'),
					layout: 'hbox',
					children: [{
						type: 'custom',
						xtype: 'container',
						layout: 'form',
						flex: 3,
						children: [{
							key: 'nodeName',
							property: 'nodeName',
							title: F3.TYPO3.Core.I18n.get('TYPO3', 'nodeName')
						}, {
							key: 'title',
							property: 'properties.title',
							title: F3.TYPO3.Core.I18n.get('TYPO3', 'pageTitle')
						}]
					}, {
						type: 'custom',
						xtype: 'container',
						flex: 1,
						children: [{
							type: 'custom',
							xtype: 'F3.TYPO3.Components.OrderSelect',
							id: 'F3.TYPO3.Module.ContentModule.create'
						}]
					}]
				}
			}
		});
	},

	/**
	 * Add items to the Selection Mode floating menu.
	 *
	 * Right now, the content elements displayed here are hard-coded,
	 * but this has to come from the server side lateron.
	 *
	 * @param {F3.TYPO3.Core.Registry} registry
	 * @return {void}
	 * @private
	 */
	_configureSelectionModeFloatingMenu: function(registry) {

		registry.append('menu/selectionModeFloating[]', 'deleteNode', {
			itemId: 'Delete',
			text: 'Delete Node',
			iconCls: 'F3-TYPO3-Content-icon-deletePage'
		});

		registry.append('menu/selectionModeFloating[]', 'createNode', {
			text: 'Create Node',
			iconCls: 'F3-TYPO3-Content-icon-createPage'
		});

		registry.append('menu/selectionModeFloating[]/createNode[]', 'text', {
			text: 'Text',
			contentType: 'TYPO3:Text',
			iconCls: 'F3-TYPO3-Icon-ContentType-TYPO3_Text'
		});

		registry.append('menu/selectionModeFloating[]/createNode[]', 'html', {
			text: 'HTML',
			contentType: 'TYPO3:Html',
			iconCls: 'F3-TYPO3-Icon-ContentType-TYPO3_Html'
		});

		registry.append('menu/selectionModeFloating[]/createNode[]', 'plugin', {
			text: 'Plugin',
			iconCls: 'F3-TYPO3-Icon-ContentType-TYPO3_Plugin'
		});

		registry.append('menu/selectionModeFloating[]/createNode[]/plugin[]', 'twitter', {
			text: 'Twitter',
			contentType: 'Twitter:LatestTweets',
			iconCls: 'F3-TYPO3-Icon-ContentType-Twitter_LatestTweets'
		});
	},

	/**
	 * Set up event handlers
	 *
	 * @param {F3.TYPO3.Core.Application} The Application object
	 * @return {void}
	 */
	initialize: function(application) {
		application.afterInitializationOf('F3.TYPO3.Module.UserInterfaceModule', function(userInterfaceModule) {
			userInterfaceModule.addContentArea('content', 'frontendEditor', {
				xtype: 'F3.TYPO3.Module.Content.WebsiteContainer',
				id: 'F3.TYPO3.Module.Content.WebsiteContainer'
			});
			userInterfaceModule.contentAreaOn('menu/main/content', 'content', 'frontendEditor');

			userInterfaceModule.moduleDialogOn('menu/main/content[]/edit[]/pageProperties',
				{xtype: 'F3.TYPO3.Module.Content.Edit.PagePropertiesDialog'},
				{
					xtype: 'F3.TYPO3.Components.Content.Dialog',
					okButton: F3.TYPO3.Core.I18n.get('TYPO3', 'updatePage'),
					cancelButton: F3.TYPO3.Core.I18n.get('TYPO3', 'cancel'),
					mode: 'positive'
				}
			);
			userInterfaceModule.moduleDialogOn('menu/main/content[]/movePage',
				{xtype: 'F3.TYPO3.Module.Content.Edit.MovePageDialog'},
				{
					xtype: 'F3.TYPO3.Components.Content.Dialog',
					okButton: F3.TYPO3.Core.I18n.get('TYPO3', 'movePage'),
					cancelButton: F3.TYPO3.Core.I18n.get('TYPO3', 'cancel'),
					mode: 'positive'
				}
			);
			userInterfaceModule.moduleDialogOn('menu/main/content[]/createPage',
				{xtype: 'F3.TYPO3.Module.Content.Edit.CreatePageDialog'},
				{
					xtype: 'F3.TYPO3.Components.Content.Dialog',
					okButton: F3.TYPO3.Core.I18n.get('TYPO3', 'createPage'),
					cancelButton: F3.TYPO3.Core.I18n.get('TYPO3', 'cancel'),
					mode: 'positive'
				}
			);
			userInterfaceModule.moduleDialogOn('menu/main/content[]/deletePage',
				{xtype: 'F3.TYPO3.Module.Content.Edit.DeletePageDialog'},
				{
					xtype: 'F3.TYPO3.Components.Content.Dialog',
					infoText: F3.TYPO3.Core.I18n.get('TYPO3', 'pageDeleteConfirm'),
					okButton: F3.TYPO3.Core.I18n.get('TYPO3', 'deletePage'),
					cancelButton: F3.TYPO3.Core.I18n.get('TYPO3', 'cancel'),
					mode: 'negative'
				}
			);

			var modeChangeEventListenerAdded = false;
			userInterfaceModule.on('activate-menu/main/content/children/edit', function(node) {
				this.getWebsiteContainer().enableSelectionMode();
				if (!modeChangeEventListenerAdded) {
					this.getWebsiteContainer().on('modeChange', this._onModeChange, this);
					this._onModeChange();
					modeChangeEventListenerAdded = true;
				}
			}, this);
			userInterfaceModule.on('deactivate-menu/main/content/children/edit', function(button) {
				this.getWebsiteContainer().enableNavigationMode();
			}, this);

		}, this);
	},

	/**
	 * Called on a mode change in the Website Container. Used to change the
	 * icon in the "Content" tab next to the label.
	 * @private
	 */
	_onModeChange: function() {
		var viewport = F3.TYPO3.Module.UserInterfaceModule.viewport;
		var tab = viewport.sectionMenu.getComponent('content');
		Ext.fly(tab.tabEl).addClass('');
		if (this.getWebsiteContainer().isSelectionModeEnabled()) {
			Ext.fly(tab.tabEl).addClass('f3-typo3-content-mode-select');
			Ext.fly(tab.tabEl).removeClass('f3-typo3-content-mode-edit');
		} else if (this.getWebsiteContainer().isEditingModeEnabled()) {
			Ext.fly(tab.tabEl).removeClass('f3-typo3-content-mode-select');
			Ext.fly(tab.tabEl).addClass('f3-typo3-content-mode-edit');
		} else {
			Ext.fly(tab.tabEl).removeClass('f3-typo3-content-mode-select');
			Ext.fly(tab.tabEl).removeClass('f3-typo3-content-mode-edit');
		}
	},
	/**
	 * Get the website container
	 *
	 * @return {Ext.Component}
	 * @api
	 */
	getWebsiteContainer: function() {
		return Ext.getCmp('F3.TYPO3.Module.Content.WebsiteContainer');
	}

});