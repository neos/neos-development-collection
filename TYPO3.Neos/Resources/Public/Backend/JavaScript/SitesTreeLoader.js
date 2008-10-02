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
 * @subpackage Service
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 * A sites tree loader. It attaches a structure tree loader to nodes loaded.
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3::TYPO3::View::Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
Ext.app.SitesTreeLoader = Ext.extend(Ext.ux.ProcessingTreeLoader, {

	/**
	 * Set text, iconCls and loader
	 */
	processAttributes : function(attributes) {
		attributes.text = attributes.name;
		attributes.iconCls = 'F3_TYPO3_Backend_Icon_Site';
		if (attributes.id !== 'ROOT') {
			attributes.loader = structureTreeLoader;
		}
	}
});


var sitesTreeLoader = new Ext.app.SitesTreeLoader({
	dataUrl:'typo3/service/v1/sites.json',
	requestMethod: 'GET'
});
