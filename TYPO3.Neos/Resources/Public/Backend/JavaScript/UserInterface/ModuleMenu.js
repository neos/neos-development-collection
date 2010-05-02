Ext.ns("F3.TYPO3.UserInterface");

/**
 * @class F3.TYPO3.UserInterface.ModuleMenu
 * @namespace F3.TYPO3.UserInterface
 * @extends Ext.Panel
 */
F3.TYPO3.UserInterface.ModuleMenu = Ext.extend(Ext.Panel, {
	menuId: null,
	menuConfig: {},

	initComponent: function() {
		var config = {
			cls: 'F3-TYPO3-UserInterface-ModuleMenu',
			height: 50,
			layout: 'vbox',
			layoutConfig: {
				align: 'stretch'
			},
			items: [{
				xtype: 'F3.TYPO3.UserInterface.RootlineMenu',
				itemId: this.itemId,
				ref: 'rootlineMenu',
				menuId: this.menuId,
				menuConfig: this.menuConfig,
				height: 50,
				flex: 0
			}, {
				xtype: 'container',
				layout: 'vbox',
				layoutConfig: {
					align: 'stretch'
				},
				itemId: 'moduleDialogContainer',
				ref: 'moduleDialogContainer',
				flex: 1
			}]
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.ModuleMenu.superclass.initComponent.call(this);
	},

	/**
	 * Show a module dialog inside this module menu.
	 * The xtype in the config should extend F3.TYPO3.UserInterface.ModuleDialog.
	 *
	 * The module dialog will be automatically removed, if any button in the menu
	 * gets clicked.
	 *
	 * @param {Array} config The module dialog component config
	 */
	showModuleDialog: function(config, contentDialogConfig) {
		var dialogRemoved = false,
			// TODO use special type for section menu tabs
			sectionMenuTab = this.findParentByType('container');
		config = Ext.apply(config, {
			listeners: {
				removed: function() {
					dialogRemoved = true;
				}
			}
		});

		this.addedModuleHeight = 0;
		if (config) {
			this.moduleDialog = this.moduleDialogContainer.add(config);
			this.moduleDialog.moduleMenu = this;
			this.addedModuleHeight += this.moduleDialog.height;
		}

		if (contentDialogConfig) {
			// Reserve space for separator bar
			this.addedModuleHeight += 13;
		}

		this.setHeight(this.getHeight() + this.addedModuleHeight);
		F3.TYPO3.UserInterface.viewport.doLayout();

		if (contentDialogConfig) {
			// TODO Too much coupling to the container markup
			var innerTabContainerEl = sectionMenuTab.getEl().child('.x-box-inner');
		
			this.contentDialog = Ext.ComponentMgr.create(contentDialogConfig);
			this.contentDialog.moduleDialog = this.moduleDialog;
			innerTabContainerEl.setStyle('position', 'relative');
			this.contentDialog.render(innerTabContainerEl);
			// TODO calculate height dynamically!
			this.contentDialog.setPosition(0, 130);
		}

		// Remove module dialog if any button is pressed
		F3.TYPO3.Application.MenuRegistry.on('F3.TYPO3.UserInterface.RootlineMenu.buttonPressed', function(button) {		
			if (!dialogRemoved) {
				this.removeModuleDialog();
			}
		}, this, {single: true});

		return this.moduleDialog;
	},

	/**
	 * Remove an existing module dialog AND content dialog from the module menu.
	 *
	 */
	removeModuleDialog: function() {
		if (this.moduleDialog) {
			this.moduleDialogContainer.removeAll();
			delete this.moduleDialog;
		}
		if (this.contentDialog) {
			this.contentDialog.destroy();
			delete this.contentDialog;
		}
		this.setHeight(this.getHeight() - this.addedModuleHeight);
		F3.TYPO3.UserInterface.viewport.doLayout();

		this.addedModuleHeight = 0;
	}
});
Ext.reg('F3.TYPO3.UserInterface.ModuleMenu', F3.TYPO3.UserInterface.ModuleMenu);