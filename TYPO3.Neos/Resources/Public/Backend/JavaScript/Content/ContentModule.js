Ext.ns("F3.TYPO3.Content");

F3.TYPO3.Content.ContentModule = F3.TYPO3.Core.Application.createModule('F3.TYPO3.Content.ContentModule', {

	/**
	 * @event AlohaConnector.contentChanged
	 * fires when there is changed content which should persist by the TYPO3 backend.<br>
	 * this event is fired in the following <b>scope:</b> <i>window.parent.F3.TYPO3.Core.Application</i>
	 * @param {object} data <ul>
	 * <li><b>identity:</b> Identity of the element</li>
	 * <li><b>html:</b> the html content</li>
	 * </ul>
	 */

	configure: function(registry) {
		registry.append('menu[main]', 'content', {
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-ContentTab',
			title: 'Content',
			itemId: 'content'
		});

		registry.append('menu[main]/content[]', 'edit', {
			itemId: 'edit',
			text: 'Edit',
			iconCls: 'F3-TYPO3-Content-icon-edit'
		});

		registry.append('menu[main]/content[]/edit[]', 'pageProperties', {
			itemId: 'pageProperties',
			text: 'Page properties',
			iconCls: 'F3-TYPO3-Content-icon-pageProperties'
		});

		registry.append('menu[main]/content[]', 'createPage', {
			itemId: 'Create',
			text: 'Create Page',
			iconCls: 'F3-TYPO3-Content-icon-createPage'
		});

		registry.append('menu[main]/content[]', 'deletePage', {
			itemId: 'Delete',
			text: 'Delete Page',
			iconCls: 'F3-TYPO3-Content-icon-deletePage'
		});

			// This will come from the server later on
		registry.set('schema', {
			"TYPO3:Page": {
				service: {
					show: 'F3.TYPO3_Controller_NodeController.show',
					update: 'F3.TYPO3_Controller_NodeController.update',
					create: 'F3.TYPO3_Controller_NodeController.create',
						// "delete" is a special case because it's a reserved keyword.
						// Need this workaround at least for WebKit:
					'delete': 'F3.TYPO3_Controller_NodeController["delete"]'
				},
				properties: {
					'nodeName': {
						type: 'string'
					},
					'properties.title': {
						type: 'string',
						validations: [{
							key: 'v1',
							type: 'NotEmpty'
						}, {
							key: 'v2',
							type: 'Label'
						}, {
							key: 'v3',
							type: 'StringLength',
							options: {
								maximum: 50
							}
						}]
					},
					'properties.navigationTitle': {
						type: 'string'
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
						}, {
							key: 'navigationTitle',
							type: 'field',
							property: 'properties.navigationTitle',
							title: 'Navigation title'
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
					}, {
						key: 'navigationTitle',
						type: 'field',
						property: 'properties.navigationTitle',
						title: 'Navigation title'
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
	initialize: function(application) {
		application.afterInitializationOf('F3.TYPO3.UserInterface.UserInterfaceModule', function(userInterfaceModule) {
			userInterfaceModule.addContentArea('content', 'frontendEditor', {
				xtype: 'F3.TYPO3.Content.FrontendEditor',
				id: 'F3.TYPO3.Content.FrontendEditor'
			});
			userInterfaceModule.contentAreaOn('menu[main]/content', 'content', 'frontendEditor');

			userInterfaceModule.moduleDialogOn('menu[main]/content[]/edit[]/pageProperties',
				{ xtype: 'F3.TYPO3.Content.Edit.PagePropertiesDialog' },
				{ xtype: 'F3.TYPO3.UserInterface.ContentDialog' }
			);
			userInterfaceModule.moduleDialogOn('menu[main]/content[]/createPage',
				{ xtype: 'F3.TYPO3.Content.Edit.CreatePageDialog' },
				{ xtype: 'F3.TYPO3.UserInterface.ContentDialog' }
			);
			userInterfaceModule.moduleDialogOn('menu[main]/content[]/deletePage',
				{ xtype: 'F3.TYPO3.Content.Edit.DeletePageDialog' },
				{
					xtype: 'F3.TYPO3.UserInterface.ContentDialog',
					cls: 'F3-TYPO3-UserInterface-ContentDialog F3-TYPO3-UserInterface-ContentDialog-warning'
				}
			);
		});
	}
});