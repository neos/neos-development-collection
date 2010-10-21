Ext.ns("F3.TYPO3.UserInterface");

/**
 * @singleton
 */
F3.TYPO3.UserInterface.UserInterfaceModule = F3.TYPO3.Core.Application.createModule('F3.TYPO3.UserInterface.UserInterfaceModule', {

	/**
	 * @event activate-[FullyQualifiedButtonPath]
	 * @event deactivate-[FullyQualifiedButtonPath]
	 */

	/**
	 * @var F3.TYPO3.UserInterface.Layout
	 * @api
	 */
	viewport: null,

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

	initialize: function(application) {
		application.on('afterBootstrap', this.initViewport, this);
	},

	/**
	 * Create the main viewport for layouting all components in a full
	 * width and height browser window.
	 */
	initViewport: function() {
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

		// TODO: "path" is not always a reference to a button, so it might not be safe to go locally from the button to the moduleMenu.
		this.on('activate-' + path, function(node) {
			var moduleDialog = node.getModuleMenu().showModuleDialog(moduleDialogConfiguration, contentDialogConfiguration);

			// Untoggle button on module dialog destroy
			moduleDialog.on('destroy', function() {
				node.active = false;
			});
		});

		this.on('deactivate-' + path, function(button) {
			button.getModuleMenu().removeModuleDialog();
		});
	},

	addContentArea: function(sectionId, itemId, configuration) {
		// TODO: if default content area, we activate it.
		this.on('ContentArea.initialized', function(contentArea) {
			if (sectionId + '-contentArea' == contentArea.itemId) {
				contentArea.add(Ext.apply(configuration, {
					itemId: itemId
				}));
			}
		});
	},
	contentAreaOn: function(path, sectionId, contentAreaItemId) {
		path = F3.TYPO3.Core.Registry.rewritePath(path);
		// TODO: "path" is not always a reference to a tab, so it might not be safe to go locally from the button to the moduleMenu.
		this.on('activate-' + path, function() {
			var viewport = F3.TYPO3.UserInterface.UserInterfaceModule.viewport;
			var tab = viewport.sectionMenu.getComponent(sectionId);
			tab.contentArea.getLayout().setActiveItem(contentAreaItemId);
			tab.contentArea.doLayout();
		});
	}
});