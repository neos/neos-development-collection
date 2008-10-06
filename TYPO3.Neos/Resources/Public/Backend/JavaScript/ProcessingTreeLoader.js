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
 * A processing tree loader for ExtJS, allowing to post-process JSON data before
 * it is handed over to the actual tree.
 *
 * @package TYPO3
 * @subpackage Backend
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
Ext.ux.ProcessingTreeLoader = Ext.extend(Ext.tree.TreeLoader, {

	/**
	 * Get rid of the node=[id] automatism...
	 */
	getParams: function (node) {
		var key, buf = [], bp = this.baseParams;
		for (key in bp) {
			if (typeof bp[key] !== "function") {
				buf.push(encodeURIComponent(key), "=", encodeURIComponent(bp[key]), "&");
			}
		}
		return buf.join("");
	},

	/**
	 * Introduces preprocessing of attributes before node creation
	 */
	createNode: function (attributes) {
		this.processAttributes(attributes);
		return Ext.ux.ProcessingTreeLoader.superclass.createNode.call(this, attributes);
	},

	/**
	 * Template method intended to be overridden by subclasses that need to provide
	 * custom attribute processing prior to the creation of each TreeNode. This method
	 * will be passed a config object containing existing TreeNode attribute name/value
	 * pairs which can be modified as needed directly (no need to return the object).
	 */
	processAttributes: Ext.emptyFn
});
