Ext.ns('F3.TYPO3.Module.Dummy');

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
 * @class F3.TYPO3.Module.Dummy.DummyContentArea
 *
 * A Dummy content area which just displays the name of the content area.
 *
 * @namespace F3.TYPO3.Module.Dummy
 * @extends Ext.Panel
 */
F3.TYPO3.Module.Dummy.DummyContentArea = Ext.extend(Ext.Panel, {

	layout: 'card',

	/**
	 * Initializer
	 */
	initComponent: function() {
		var config = {
			html: '<h1 style="font-family:Share-Bold, sans-serif; margin-top:30px; text-align:center;font-size:45px;">' + this.name + '</h1>'
		};
		Ext.apply(this, config);
		F3.TYPO3.Module.Dummy.DummyContentArea.superclass.initComponent.call(this);
	}
});
Ext.reg('F3.TYPO3.Module.Dummy.DummyContentArea', F3.TYPO3.Module.Dummy.DummyContentArea);