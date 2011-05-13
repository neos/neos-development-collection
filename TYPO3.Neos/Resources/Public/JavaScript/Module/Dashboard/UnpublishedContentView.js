Ext.ns('F3.TYPO3.Module.Dashboard');

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
 * @class F3.TYPO3.Module.Dashboard.UnpublishedContentView
 *
 * Grid component to display unpublished content
 *
 * @namespace F3.TYPO3.Module.Dashboard
 * @extends Ext.DataView
 */
F3.TYPO3.Module.Dashboard.UnpublishedContentView = Ext.extend(Ext.DataView, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
				store: {
					xtype: 'directstore',
					directFn: Ext.apply(function(callback) {
						F3.TYPO3_Service_ExtDirect_V1_Controller_WorkspaceController.getUnpublishedNodes(F3.TYPO3.Configuration.Application.workspaceName, callback);
					}, {directCfg: {method: {len: 0}}}),
					autoLoad: true,
					autoDestroy: true,
					root: 'data',
					fields: []
				},
				autoScroll: true,
				height: 360,
				multiSelect: true,
				itemSelector: 'div.F3-Content-Node',
				overClass: 'x-view-over',
				tpl: new Ext.XTemplate(
					'<tpl for=".">',
						'<div class="F3-Content-Node">',
							'<div class="label-wrap"><b>{__contextNodePath}</b></div>',
							'<div class="abstract-wrap">{__abstract}</div>',
						'</div>',
					'</tpl>',
					'<div class="x-clear"></div>'
				)
			};
		Ext.apply(this, config);
		F3.TYPO3.Module.Dashboard.UnpublishedContentView.superclass.initComponent.call(this);
	}
});
Ext.reg('F3.TYPO3.Module.Dashboard.UnpublishedContentView', F3.TYPO3.Module.Dashboard.UnpublishedContentView);
