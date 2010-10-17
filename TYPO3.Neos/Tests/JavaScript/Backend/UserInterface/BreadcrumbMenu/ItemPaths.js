Ext.ns("F3.TYPO3.UserInterface.BreadcrumbMenu");

F3.TYPO3.UserInterface.BreadcrumbMenu.BreadCrumbItemPathsTest = new YAHOO.tool.TestCase({

	name: "Test item path builder",

	menuUtility: {},
	registry: {},

	setUp: function() {
		this.registry = F3.TYPO3.Core.Registry;
		this.registry.initialize();

		this.registry.append('menu/main', 'content', {
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-ContentTab',
			title: 'Content',
			itemId: 'content'
		});

		this.menuUtility = F3.TYPO3.UserInterface.BreadcrumbMenu.Util;
	},

	testItemPathOfRootLevel: function() {

		this.registry.compile();

		var testObject = this.menuUtility.convertMenuConfig(this.registry.get('menu/main'), 0, '', 'menu/main/content', {sectionId:'',menuId:'mainMenu',menuPath:''});

		YAHOO.util.Assert.areEqual(Ext.encode([{
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-ContentTab',
			title: 'Content',
			itemId: 'content',
			key: 'content',
			menuId: 'mainMenu',
			path: 'menu/main/content',
			menuPath: ''
		}]), Ext.encode(testObject));
	},

	testItemPathOf1LevelMenu: function() {

		this.registry.append('menu/main/content[]', 'edit', {
			itemId: 'edit',
			text: 'Edit',
			iconCls: 'F3-TYPO3-Content-icon-edit'
		});

		this.registry.append('menu/main/content[]', 'createPage', {
			itemId: 'Create',
			text: 'Create Page',
			iconCls: 'F3-TYPO3-Content-icon-createPage'
		});

		this.registry.compile();

		var testObject = this.menuUtility.convertMenuConfig(this.registry.get('menu/main'), 0, '', 'menu/main/content', {sectionId:'',menuId:'mainMenu',menuPath:''});

		YAHOO.util.Assert.areEqual(Ext.encode([{
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-ContentTab',
			title: 'Content',
			itemId: 'content',
			children: [{
				itemId: 'edit',
				text: 'Edit',
				iconCls: 'F3-TYPO3-Content-icon-edit',
				key: 'edit',
				menuId: 'mainMenu',
				path: 'menu/main/content/children/edit',
				menuPath: 'edit'
			}, {
				itemId: 'Create',
				text: 'Create Page',
				iconCls: 'F3-TYPO3-Content-icon-createPage',
				key: 'createPage',
				menuId: 'mainMenu',
				path: 'menu/main/content/children/createPage',
				menuPath: 'createPage'
			}],
			key: 'content',
			menuId: 'mainMenu',
			path: 'menu/main/content',
			menuPath: ''
		}]), Ext.encode(testObject));
	},

	testItemPathOf2LevelMenu: function() {

		this.registry.append('menu/main/content[]', 'edit', {
			itemId: 'edit',
			text: 'Edit',
			iconCls: 'F3-TYPO3-Content-icon-edit'
		});

		this.registry.append('menu/main/content[]/edit[]', 'page', {
			itemId: 'page',
			text: 'Page'
		});

		this.registry.append('menu/main/content[]/edit[]', 'news', {
			itemId: 'news',
			text: 'News',
			iconCls: 'F3-TYPO3-Content-icon-edit'
		});

		this.registry.append('menu/main/content[]', 'createPage', {
			itemId: 'Create',
			text: 'Create Page',
			iconCls: 'F3-TYPO3-Content-icon-createPage'
		});

		this.registry.append('menu/main/content[]/createPage[]', 'selectPosition', {
			itemId: 'selectPosition',
			text: 'Select position'
		});

		this.registry.compile();
		var testObject = this.menuUtility.convertMenuConfig(this.registry.get('menu/main'), 0, '', 'menu/main/content', {sectionId:'',menuId:'mainMenu',menuPath:''});

		YAHOO.util.Assert.areEqual(Ext.encode([{
			tabCls: 'F3-TYPO3-UserInterface-SectionMenu-ContentTab',
			title: 'Content',
			itemId: 'content',
			children: [{
				itemId: 'edit',
				text: 'Edit',
				iconCls: 'F3-TYPO3-Content-icon-edit',
				children: [{
					itemId: 'page',
					text: 'Page',
					key: 'page',
					menuId: 'mainMenu',
					path: 'menu/main/content/children/edit/children/page',
					menuPath: 'edit/page'
				}, {
					itemId: 'news',
					text: 'News',
					iconCls: 'F3-TYPO3-Content-icon-edit',
					key: 'news',
					menuId: 'mainMenu',
					path: 'menu/main/content/children/edit/children/news',
					menuPath: 'edit/news'
				}],
				key: 'edit',
				menuId: 'mainMenu',
				path: 'menu/main/content/children/edit',
				menuPath: 'edit'
			}, {
				itemId: 'Create',
				text: 'Create Page',
				iconCls: 'F3-TYPO3-Content-icon-createPage',
				children: [{
					itemId: 'selectPosition',
					text: 'Select position',
					key: 'selectPosition',
					menuId: 'mainMenu',
					path: 'menu/main/content/children/createPage/children/selectPosition',
					menuPath: 'createPage/selectPosition'
				}],
				key: 'createPage',
				menuId: 'mainMenu',
				path: 'menu/main/content/children/createPage',
				menuPath: 'createPage'
			}],
			key: 'content',
			menuId: 'mainMenu',
			path: 'menu/main/content',
			menuPath: ''
		}]), Ext.encode(testObject));
	}
});
