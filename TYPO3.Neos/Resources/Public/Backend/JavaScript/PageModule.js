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

var pageModuleToolbar = new Ext.Toolbar({
	items: [{
		text: 'Save'
	}, {
		text: 'Close'
	}]
});


var pageModule = new Ext.Panel({
	title: 'Page Module',
	layout: 'anchor',
	//tbar: pageModuleToolbar
});


function showPageDetail(conn, response, options) {
	var childNodes, node, i, j = 0, structureTree = Ext.decode(response.responseText);

	while (pageModule.items.items.length > 0) {
		pageModule.remove(pageModule.items.items[0]);
	}

	if (structureTree.hasChildNodes) {
		childNodes = structureTree.childNodes;
		for (i = 0; i < childNodes.length; i++) {
			node = childNodes[i];
			if (node.contentClass !== 'F3::TYPO3::Domain::Model::Content::Page') {
				pageModule.add(new Ext.Panel({
					anchor: '-10',
					height: 100,
					frame: true,
					draggable: true,
					title: node.label,
					style: 'margin:5px;',
					html: '<p>' + (node.content || '[empty]') + '</p>'
				}));
			}
		}
		pageModule.doLayout();
	}

	pageModule.loadMask.hide();
	statusBar.clearStatus();
}
