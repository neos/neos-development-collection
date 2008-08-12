<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id$
 */

/**
 * The TYPO3 Backend View
 *
 * @package TYPO3
 * @subpackage Backend
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_Backend_View_Default_Default extends F3_FLOW3_MVC_View_AbstractView {

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 */
	public function render() {
		return '<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.1 Transitional//EN">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>TYPO3</title>
		<base href="' . (string)$this->request->getBaseURI() . '" />
		<link rel="stylesheet" href="Resources/Web/ExtJS/Public/CSS/ext-all.css" />
		<link rel="stylesheet" href="Resources/Web/ExtJS/Public/CSS/xtheme-gray.css" />
		<script type="text/javascript" src="Resources/Web/ExtJS/Public/JavaScript/adapter/ext/ext-base.js"></script>
		<script type="text/javascript" src="Resources/Web/ExtJS/Public/JavaScript/ext-all-debug.js"></script>
		<script type="text/javascript">
		' . "
		Ext.BLANK_IMAGE_URL = 'Resources/Web/ExtJS/Public/images/default/s.gif';

		var toolbar = new Ext.Toolbar({
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
			dataUrl: 'http://t3v5/typo3/service/pages.json',
			root: {
				nodeType: 'async',
				id:'ROOT',
				text:'[Site Name]'
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
					autoScroll:true,
					width: 200,
					layout:'accordion',
					layoutConfig:{
						animate:true
					},
					items: [{
						title:'Pages',
						items: pageTree,
						border:false,
						layout: 'fit',
						iconCls:'nav'
					},{
						title:'Categories',
						html:'<p>There will be categories.</p>',
						border:false,
						iconCls:'settings'
					}]
				},{
					region: 'center',
					html: 'center'
				}]
			},{
				title: 'Layout',
				layout: 'border',
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
						items: toolbar
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
//			pageTree.expandAll();
		});

		</script>
	</head>

	<body>
	</body>
</html>";
	}

}


?>