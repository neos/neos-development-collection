/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3
 * @subpackage Backend
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 */

Ext.BLANK_IMAGE_URL = 'Resources/Web/ExtJS/Public/images/default/s.gif';

var F3_TYPO3_Backend_ApplicationToolBar = new Ext.Toolbar({
	items: [{
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

var statusBar = new Ext.StatusBar({
	defaultText: 'Ready.',
	items: ['TYPO3 5.0.0-dev', ' ', ' ']
});

var modulebarContent = new Ext.Toolbar({
	items:[{
		text: 'Page'
	}, {
		text: 'List'
	}, {
		text: 'Info'
	}]
});

var modulebarLayout = new Ext.Toolbar({
	items: [{
		text: 'Setup'
	}, {
		text: 'Constants'
	}, {
		text: 'Resources'
	}]
});

function handleStructureNodeClick(node) {
	if (node.getDepth() > 1) {
		statusBar.setStatus({
			text: 'Loading...'
		});
		statusBar.showBusy();
		pageModule.setTitle(node.attributes.label);
		var c = new Ext.data.Connection({
			listeners: {
				requestcomplete: {
					fn: showPageDetail
				}
			}
		}).request({
			url: 'typo3/service/v1/structurenodes/' + node.id + '.json',
			method: 'GET'
		});
	}
}

var pageTree = new Ext.tree.TreePanel({
	useArrows: true,
	autoScroll: true,
	animate: false,
	containerScroll: true,
	rootVisible: false,
	root: {
		nodeType: 'async',
		id: 'ROOT'
	},
	loader: sitesTreeLoader,
	listeners: {
		click: {
			fn: handleStructureNodeClick
		}
	}
});

var sections = new Ext.TabPanel({
	activeTab: 0,
	items: [{
		title: 'Content',
		layout:'border',
		items: [{
			region: 'north',
			height: 30,
			items: modulebarContent
		}, {
			region: 'west',
			title: 'Web',
			autoScroll: true,
			collapsible: true,
			split: true,
			width: 200,
			layout:'fit',
			items: pageTree
		}, {
			region: 'center',
			layout: 'fit',
			items: pageModule
		}, {
			region: 'east',
			title: 'Helper',
			collapsible: true,
			split: true,
			width: 200,
			layout: 'accordion',
			items: [{
				title: 'Clipboard',
				html: 'This is your clipboard.'
			}, {
				title: 'Inspector',
				html: 'The inspector.'
			}]
		}]
	}, {
		title: 'Layout',
		html: 'The Layout section'
	}]
});

Ext.onReady(function(){
	var viewport = new Ext.Viewport({
		layout: 'border',
		items:[
			{
				height: 30,
				region: 'north',
				items: F3_TYPO3_Backend_ApplicationToolBar
			}, {
				region: 'center',
				layout: 'fit',
				items: sections
			}, {
				height: 30,
				region: 'south',
				items: statusBar
			}
			]
	});
});