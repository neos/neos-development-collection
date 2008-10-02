Ext.BLANK_IMAGE_URL = 'Resources/Web/ExtJS/Public/images/default/s.gif';

var F3_TYPO3_Backend_ApplicationToolBar = new Ext.Toolbar({
	items:[{
		text:'TYPO3',
		menu: new Ext.menu.Menu({
			items: [
					new Ext.menu.TextItem({text: 'About'}),
					new Ext.menu.TextItem({text: 'Help'}),
					new Ext.menu.TextItem({text: 'Log Out'})
					]
		})
	}]
});

var statusbar = new Ext.StatusBar({
	defaultText: 'Ready.',
	items: ['TYPO3 5.0.0-dev', ' ', ' ']
});

var modulebarContent = new Ext.Toolbar({
	items:[{
		text:'Page'
	},{
		text:'List'
	},{
		text:'Info'
	}]
});

var modulebarLayout = new Ext.Toolbar({
	items:[{
		text:'Setup'
	},{
		text:'Constants'
	},{
		text:'Resources'
	}]
});

var pageTree = new Ext.tree.TreePanel({
	useArrows:true,
	autoScroll:true,
	animate:false,
	containerScroll: true,
	rootVisible: false,
	root: {
		nodeType: 'async',
		id:'ROOT',
	},
	loader: sitesTreeLoader
});

var pageModuleToolbar = new Ext.Toolbar({
	items: [{
		text: 'Save'
	},{
		text: 'Close'
	}]
});

var pageModule = new Ext.Panel({
	layout: 'fit',
	tbar: pageModuleToolbar,
	items: {
	xtype: 'htmleditor'
}
});

var sections = new Ext.TabPanel({
	activeTab:0,
	items:[{
		title: 'Content',
		layout:'border',
		items:[{
			region:'north',
			height: 30,
			items: modulebarContent
		},{
			region:'west',
			title: 'Web',
			autoScroll: true,
			collapsible: true,
			split: true,
			width: 200,
			layout:'fit',
			items: pageTree,
		},{
			region: 'center',
			layout: 'fit',
			items: pageModule
		},{
			region: 'east',
			title: 'Helper',
			collapsible: true,
			split: true,
			width: 200,
			layout: 'accordion',
			items: [{
				title: 'Clipboard',
				html: 'This is your clipboard.'
			},{
				title: 'Inspector',
				html: 'The inspector.'
			}]
		}]
	},{
		title: 'Layout',
		html: 'The Layout section'
	}]
});

Ext.onReady(function(){
	var viewport = new Ext.Viewport({
		layout:'border',
		items:[
			{
				height: 30,
				region: 'north',
				items: F3_TYPO3_Backend_ApplicationToolBar
			},{
				region: 'center',
				layout:'fit',
				items: sections
			},{
				height: 30,
				region: 'south',
				items: statusbar
			}
			]
	});
});