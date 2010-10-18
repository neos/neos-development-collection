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
 * @class F3.TYPO3.UserInterface.BreadcrumbMenu.EventModel
 *
 * @namespace F3.TYPO3.UserInterface.BreadcrumbMenu
 * @extends Ext.tree.TreeEventModel
 * @author Rens Admiraal <rens@rensnel.nl>
 */
F3.TYPO3.UserInterface.BreadcrumbMenu.EventModel = function() {
	F3.TYPO3.UserInterface.BreadcrumbMenu.EventModel.superclass.constructor.apply(this, arguments);
};

Ext.extend(F3.TYPO3.UserInterface.BreadcrumbMenu.EventModel, Ext.tree.TreeEventModel, {

	/**
	 * @return {void}
	 * @public
	 */
	initEvents : function() {
		if (this.tree.trackMouseOver !== false) {
			this.tree.mon(this.tree.innerCt, {
				scope: this,
				mouseover: this.delegateOver,
				mouseout: this.delegateOut
			});
		}
		this.tree.mon(this.tree.getTreeEl(), {
			scope: this,
			click: this.delegateClick,
			dblclick: this.delegateDblClick,
			contextmenu: this.delegateContextMenu
		});
	},

	/**
	 * @param {Object} event
	 * @return {Object}
	 * @public
	 */
	getNode : function(event) {
		var target;
		if (target = event.getTarget('.f3-BreadcrumbMenu-node-el', 10)) {
			var id = Ext.fly(target, '_treeEvents').getAttribute('tree-node-id', 'ext');
			if (id) {
				return this.tree.getNodeById(id);
			}
		}
		return null;
	},

	/**
	 * @param {Object} event
	 * @return {Object}
	 * @public
	 */
	getNodeTarget : function(event) {
		var target = event.getTarget('.f3-BreadcrumbMenu-node-icon', 1);
		if (!target) {
			target = event.getTarget('.f3-BreadcrumbMenu-node-el', 6);
		}
		return target;
	},

	/**
	 * @param {Object} event
	 * @param {Object} target
	 * @return {void}
	 * @public
	 */
	delegateOut : function(event, target) {
		if (!this.beforeEvent(event)) {
			return;
		}
		if (event.getTarget('.f3-BreadcrumbMenu-ec-icon', 1)) {
			var n = this.getNode(event);
			this.onIconOut(event, n);
			if (n == this.lastEcOver) {
				delete this.lastEcOver;
			}
		}
		if ((target = this.getNodeTarget(event)) && !event.within(target, true)) {
			this.onNodeOut(event, this.getNode(event));
		}
	},

	/**
	 * @param {Object} event
	 * @param {Object} target
	 * @return {void}
	 * @public
	 */
	delegateOver : function(event, target) {
		if (!this.beforeEvent(event)) {
			return;
		}
		if (Ext.isGecko && !this.trackingDoc) { // prevent hanging in FF
			Ext.getBody().on('mouseover', this.trackExit, this);
			this.trackingDoc = true;
		}
		if (this.lastEcOver) { // prevent hung highlight
			this.onIconOut(event, this.lastEcOver);
			delete this.lastEcOver;
		}
		if (event.getTarget('.f3-BreadcrumbMenu-ec-icon', 1)) {
			this.lastEcOver = this.getNode(event);
			this.onIconOver(event, this.lastEcOver);
		}
		if (target = this.getNodeTarget(event)) {
			this.onNodeOver(event, this.getNode(event));
		}
	},

	/**
	 * @param {Object} event
	 * @param {Object} target
	 * @return {void}
	 * @public
	 */
	delegateClick : function(event, target) {
		if (this.beforeEvent(event)) {
			if (event.getTarget('input[type=checkbox]', 1)) {
				this.onCheckboxClick(event, this.getNode(event));
			}else if (event.getTarget('.f3-BreadcrumbMenu-ec-icon', 1)) {
				this.onIconClick(event, this.getNode(event));
			}else if (this.getNodeTarget(event)) {
				this.onNodeClick(event, this.getNode(event));
			}
		} else {
			this.checkContainerEvent(event, 'click');
		}
	},

	/**
	 * @param {Object} event
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
	onIconOver : function(event, node) {
		node.ui.addClass('f3-BreadcrumbMenu-ec-over');
	},

	/**
	 * @param {Object} event
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
	onIconOut : function(event, node) {
		node.ui.removeClass('f3-BreadcrumbMenu-ec-over');
	}
});

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenu.EventModel', F3.TYPO3.UserInterface.BreadcrumbMenu.EventModel);