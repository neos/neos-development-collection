Ext.ns("F3.TYPO3.Application");
/**
 * @class F3.TYPO3.Application.MenuRegistry
 * @namespace F3.TYPO3.Application
 * @extends Ext.util.Observable
 *
 * The menu registry provides the structure of all menus used in the application.
 * 
 * @singleton
 */
F3.TYPO3.Application.MenuRegistry = Ext.apply(new Ext.util.Observable, {

	/**
	 * @event F3.TYPO3.UserInterface.RootlineMenu.buttonPressed
	 * @param {F3.TYPO3.UserInterface.RootlineMenu.Button} button the button being pressed
	 * Called if a button is pressed.
	 */

	/**
	 * @event F3.TYPO3.UserInterface.RootlineMenu.buttonUnpressed
	 * @param {F3.TYPO3.UserInterface.RootlineMenu.Button} button the button being released
	 * Called if a button is unpressed.
	 */
	
	items: {
		mainMenu: [{
			// tabMargin: 50,
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-ContentTab',
			title: 'Content',
			itemId: 'content'
		}, {
			title: 'Management',
			itemId: 'management'
		}, {
			title: 'Report',
			itemId: 'report'
		}, {
			title: 'Layout',
			itemId: 'layout'
		}, {
			title: 'System',
			itemId: 'system'
		}, {
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-DashboardTab',
			title: 'Dashboard',
			itemId: 'dashboard'
		}]
	},

	// private
	// FIXME Only a quick implementation
	addMenuItems: function(path, items) {
		var menuName = path.shift();
		if (typeof this.items[menuName] == 'undefined') {
			this.items[menuName] = {};
		}
		if (path.length == 0) {
			this.items[menuName] = items;
		} else {
			var menuItems = this.items[menuName], t;
			Ext.each(path, function(pathEntry) {
				var found = false;
				Ext.each(menuItems, function(menuItem) {
					if (menuItem.itemId === pathEntry) {
						menuItem.children = menuItem.children || [];
						menuItems = menuItem.children;
						found = true;
					}
				});
				if (!found) {
					t = [];
					menuItems.push({
						itemId: pathEntry,
						children: t
					});
					menuItems = t;
				}
			}, this);

			menuItems.push.apply(menuItems, items);
		}
	}
});