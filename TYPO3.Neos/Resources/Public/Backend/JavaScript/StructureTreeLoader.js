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

/**
 * A structure tree loader.
 *
 * @package TYPO3
 * @subpackage Backend
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
Ext.app.StructureTreeLoader = Ext.extend(Ext.ux.ProcessingTreeLoader, {

	/**
	 * Set text, children, lead and iconCls
	 */
	processAttributes : function(attributes) {
			// Set the node text that will show in the tree since our raw data does not include a text attribute:
		attributes.text = attributes.label;
		attributes.children = attributes.childNodes;
		attributes.leaf = !attributes.hasChildNodes;
		attributes.iconCls = attributes.contentClass || 'F3_TYPO3_Backend_Icon_Page';
	},

	/**
	 * Set the URL depending on current node
	 * @todo replace by something less intrusive
	 */
	requestData : function(node, callback){
		if (this.fireEvent("beforeload", this, node, callback) !== false) {
			this.transId = Ext.Ajax.request({
				method: 'GET',
				url: this.dataUrl + (node.attributes.rootStructureNode || node.id) + '.json',
				success: this.handleResponse,
				failure: this.handleFailure,
				scope: this,
				argument: {callback: callback, node: node},
				params: this.getParams(node)
			});
		} else {
			// if the load is cancelled, make sure we notify
			// the node that we are done
			if (typeof callback == "function") {
				callback();
			}
		}
	}
});


var structureTreeLoader = new Ext.app.StructureTreeLoader({
	dataUrl:'typo3/service/v1/structuretrees/',
	preloadChildren: true,
	requestMethod:'GET'
});
