Ext.ns("F3.TYPO3.Management");

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
 * @class F3.TYPO3.Management.ManagementView
 *
 * the main container component that splits the management module
 * into two columns. If you want to have a different split/layout you would
 * need to replace this component.
 *
 * @namespace F3.TYPO3.Management
 * @extends Ext.Container
 */
F3.TYPO3.Management.ManagementView = Ext.extend(Ext.Container, {

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			layout: 'border',
			items: [{
				title: 'TYPOtree',
				region:'west',
				margins: '0 0 0 0',
				width: 200,
				split: true,
				collapsible: false,
				layout: 'fit',
				xtype: 'F3.TYPO3.Management.ManagementTree'
			},
			{
				region: 'center',
				layout: 'fit',
				margins: '0 5 0 5',
				xtype: 'F3.TYPO3.Management.ManagementGrid'
			}]
		};
		Ext.apply(this, config);

		F3.TYPO3.Management.ManagementView.superclass.initComponent.call(this);
	}
});

Ext.reg('F3.TYPO3.Management.ManagementView', F3.TYPO3.Management.ManagementView);