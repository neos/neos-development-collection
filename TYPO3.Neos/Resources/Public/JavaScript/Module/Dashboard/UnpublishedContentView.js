Ext.ns('TYPO3.TYPO3.Module.Dashboard');

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @class TYPO3.TYPO3.Module.Dashboard.UnpublishedContentView
 *
 * Grid component to display unpublished content
 *
 * @namespace TYPO3.TYPO3.Module.Dashboard
 * @extends Ext.DataView
 */
TYPO3.TYPO3.Module.Dashboard.UnpublishedContentView = Ext.extend(Ext.DataView, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
				store: {
					xtype: 'directstore',
					directFn: Ext.apply(function(callback) {
						TYPO3_TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.getUnpublishedNodes(TYPO3.TYPO3.Configuration.Application.workspaceName, callback);
					}, {directCfg: {method: {len: 0}}}),
					autoLoad: true,
					autoDestroy: true,
					root: 'data',
					fields: []
				},
				autoScroll: true,
				height: 360,
				multiSelect: true,
				itemSelector: 'div.TYPO3-Content-Node',
				overClass: 'x-view-over',
				tpl: new Ext.XTemplate(
					'<tpl for=".">',
						'<div class="TYPO3-Content-Node">',
							'<div class="label-wrap"><b>{__contextNodePath}</b></div>',
							'<div class="abstract-wrap">{__abstract}</div>',
						'</div>',
					'</tpl>',
					'<div class="x-clear"></div>'
				)
			};
		Ext.apply(this, config);
		TYPO3.TYPO3.Module.Dashboard.UnpublishedContentView.superclass.initComponent.call(this);
	}
});
Ext.reg('TYPO3.TYPO3.Module.Dashboard.UnpublishedContentView', TYPO3.TYPO3.Module.Dashboard.UnpublishedContentView);