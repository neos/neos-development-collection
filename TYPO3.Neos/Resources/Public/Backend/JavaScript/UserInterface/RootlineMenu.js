/**
 * @include /Users/nilsdehl/Entwicklung /Workspaces/TYPO3v5 GUI/gui-mockup/t3.all.js

 */
Ext.ns("F3.TYPO3.UserInterface");
/**
 *
 * @class F3.TYPO3.UserInterface.RootlineMenu
 * @extends Ext.Toolbar
 */
F3.TYPO3.UserInterface.RootlineMenu = Ext.extend(Ext.Toolbar, {
	/**
	 * @event F3.TYPO3.UserInterface.RootlineMenu.afterInit
	 * @param {F3.TYPO3.UserInterface.RootlineMenu} a reference to the submenu.
	 * Event triggered after initialization of the menu. Should be used
	 * to add elements to the menu.
	 */

	menuConfig: {},	

	/**
	 * @cfg menu Menu as defined in {@link F3.TYPO3.Application.MenuRegistry}
	 */
	
	initComponent: function() {
		var config = {
			cls: 'F3-TYPO3-UserInterface-RootlineMenu',
			items: this._getMenuItems()
		};
		Ext.apply(this, config);
		F3.TYPO3.UserInterface.RootlineMenu.superclass.initComponent.call(this);
		F3.TYPO3.Application.fireEvent('F3.TYPO3.UserInterface.RootlineMenu.afterInit', this);

		this.on('afterrender', function(menu) {
			menu.items.each(function(menuItem, i) {
				menuItem.addListener('afterrender',	function() {
					var task = new Ext.util.DelayedTask(function () {
						this.el.fadeIn({
							duration: .2
						});
					}, this);
					task.delay(200 * i);
				});
			}, this);
		});
	},

	// private
	_getMenuItems: function() {
		var menu = F3.TYPO3.Utils.clone(this.menuConfig),
			items = [];
		this._convertMenuConfig(menu, items, 0, []);
		return items;
	},

	// private
	_convertMenuConfig: function(menu, items, level, path) {
		var itemStack = [];
		Ext.each(menu, function(menuItem) {
			var itemPath;
			if (Ext.isObject(menuItem)) {
				itemPath = path.concat([menuItem.itemId]);
				menuItem.xtype = 'F3.TYPO3.UserInterface.RootlineMenu.Button';
			} else if (menuItem === ' ') {
				itemPath = path.concat(['spacer']);
				menuItem = {
					xtype: 'tbspacer',
					width: 25
				};
			}

			menuItem.sectionId = this.itemId;
			menuItem.menuId = this.menuId;
			menuItem.menuLevel = level;
			if (level > 0) {
				menuItem.hidden = true;
			}
			menuItem.menuPath = itemPath.join('-');
			if (menuItem.xtype === 'F3.TYPO3.UserInterface.RootlineMenu.Button') {
				menuItem.toggleGroup = [this.menuId, this.itemId].concat(path).join('-');
				if (menuItem.children && menuItem.children.length > 0) {
					menuItem.leaf = false;
					itemStack.push({
						xtype: 'tbtext',
						text: '&nbsp;>&nbsp;',
						menuLevel: level + 1,
						hidden: true,
						itemId: 'F3-arrow',
						menuPath: menuItem.menuPath + '-F3-arrow'
					});
					this._convertMenuConfig(menuItem.children, itemStack, level + 1, itemPath);
				} else {
					menuItem.leaf = true;
				}
				menuItem.itemId = menuItem.menuPath + menuItem.itemId;
				delete menuItem.children;
			}
			items.push(menuItem);
		}, this);
		Ext.each(itemStack, function(item) {
			items.push(item);
		}, this);
	}
});
Ext.reg('F3.TYPO3.UserInterface.RootlineMenu', F3.TYPO3.UserInterface.RootlineMenu);