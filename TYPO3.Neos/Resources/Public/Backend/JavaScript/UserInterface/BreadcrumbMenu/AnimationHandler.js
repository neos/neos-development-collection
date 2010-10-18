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

Ext.namespace('F3.TYPO3.UserInterface.BreadcrumbMenu');

/**
 * @class F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler
 *
 * @namespace F3.TYPO3.UserInterface.BreadcrumbMenu
 * @author Rens Admiraal <rens@rensnel.nl>
 */
F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler = {

	_collapseSubNodes: function (node, scope) {
		Ext.each(
			node.childNodes,
			function(node) {

				var icon = Ext.get(node.ui.iconNode);

				icon.setStyle({padding: '0px', margin: '0px', height: 45});

				icon.shift({
					width: 0,
					display: 'none',
					height: 45,
					opacity: 0,
					duration: .25
				});

				Ext.get(node.ui.ecNode).removeClass('F3-TYPO3-UserInterface-BreadcrumbMenu-elbow-expanded');

				if (node.childNodes && node.childNodes.length > 0) {
					scope._collapseSubNodes(node, scope);
				}
			}
		);
	},

	/**
	 * @param {Object} ct
	 * @param {Function} callback
	 * @param {Object} scope
	 * @return {void}
	 * @public
	 */
	collapseNode: function (ct, callback, scope) {
		this._collapseSubNodes(scope.node, this);
		Ext.get(scope.node.ui.getEl()).removeClass('F3-TYPO3-UserInterface-BreadcrumbMenu-node-expanded');

		Ext.callback(callback);
		scope.animating = false;
	},

	/**
	 * @param {Object} ct
	 * @param {Function} callback
	 * @param {Object} scope
	 * @return {void}
	 * @public
	 */
	expandNode: function (ct, callback, scope) {

		Ext.get(scope.node.ui.getEl()).addClass('F3-TYPO3-UserInterface-BreadcrumbMenu-node-expanded');
		Ext.get(scope.node.ui.getEl()).setStyle({width: 'auto'});

		Ext.each(
			scope.node.childNodes,
			function(node) {
				var icon = Ext.get(node.ui.iconNode);

				/**
				 * This style has to be applied now because otherwise the
				 * animation transforms because the height is not preserved
				 */
				icon.setStyle({
					padding: '0px',
					margin: '0px',
					height: '43px'
				});

				icon.shift({
					width: 47,
					display: 'inline',
					opacity: 1,
					duration: .25
				});
			}
		);

		ct.setStyle({display:'inline'});

		Ext.callback(callback);
		scope.animating = false;
	},

	/**
	 * @param {Object} scope
	 * @param {Object} e
	 * @return {void}
	 * @public
	 */
	nodeOnOver: function (scope, e) {
		var label = Ext.get(scope.node.ui.textNode);

		label.setStyle({
			width: '0px',
			display: 'inline-block',
			height: '47px',
			overflow: 'hidden',
			whiteSpace:'nowrap',
			padding: '10px 5px',
			opacity: 0
		});

		label.shift({
			width: label.getTextWidth() + 10,
			opacity: 1,
			duration: .25
		});
	},

	/**
	 * @param {Object} scope
	 * @param {Object} e
	 * @return {void}
	 * @public
	 */
	nodeOnOut: function (scope, e) {
		var label = Ext.get(scope.node.ui.textNode);
		label.shift({
			opacity: 0,
			display:'none',
			duration: .25,
			callback: function() {
				label.setStyle({display:'none'});
			}
		});
	},

	hideSiblings: function (node, scope) {

	},

	showSiblings: function (node, scope) {

	}
};

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler', F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler);