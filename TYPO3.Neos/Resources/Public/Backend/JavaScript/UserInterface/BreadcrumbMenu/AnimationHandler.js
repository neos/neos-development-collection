Ext.namespace('F3.TYPO3.UserInterface.BreadcrumbMenu');

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
 * @class F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler
 *
 * This class handles the animations within the BreadcrumbMenuComponent.
 * This is mainly done to keep more overview in the compononent itself
 *
 * @namespace F3.TYPO3.UserInterface.BreadcrumbMenu
 */
F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler = {

	/**
	 * @param {F3.TYPO3.UserInterface.BreadcrumbMenu.AsyncNode} node the current node object
	 * @param {Object} scope
	 * @private
	 */
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
				F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('F3.TYPO3.UserInterface.BreadcrumbMenuComponent.deactivateNode', node);

				if (node.childNodes && node.childNodes.length > 0) {
					scope._collapseSubNodes(node, scope);
				}
			}
		);
	},

	/**
	 * @param {Function} callback Callback function, which is called after the collapse
	 * @param {Object} scope
	 * @return {void}
	 */
	collapseNode: function (callback, scope) {
		this._collapseSubNodes(scope.node, scope);
		Ext.get(scope.node.ui.getEl()).removeClass('F3-TYPO3-UserInterface-BreadcrumbMenu-node-expanded');

		Ext.callback(callback);
		scope.animating = false;
	},

	/**
	 * @param {Ext.Element} ct The container
	 * @param {Function} callback Callback function which is called after the node is expanded
	 * @param {Object} scope
	 * @return {void}
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
	 * @return {void}
	 */
	nodeOnOver: function (scope) {
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
	 * @return {void}
	 */
	nodeOnOut: function (scope) {
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

	/**
	 * @param {F3.TYPO3.UserInterface.BreadcrumbMenu.AsyncNode} node The node currently being expanded
	 * @param {Object} scope
	 * @return {void}
	 */
	hideSiblings: function (node, scope) {
		Ext.each(node.parentNode.childNodes, function(sibling){
			if (sibling !== node) {
				Ext.get(sibling.ui.iconNode).shift({
					opacity: 0,
					display: 'none',
					width: '0px',
					duration: .25
				});
			}
		});
	},

	/**
	 * @param {F3.TYPO3.UserInterface.BreadcrumbMenu.AsyncNode} node The node currently being collapsed
	 * @param {Object} scope
	 * @return {void}
	 */
	showSiblings: function (node, scope) {
		Ext.each(node.parentNode.childNodes, function(sibling){
			if (sibling !== node) {
				Ext.get(sibling.ui.iconNode).shift({
					opacity: 1,
					display: 'inline-block',
					width: '47px',
					duration: .25
				});
			}
		});
	}
};

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler', F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler);