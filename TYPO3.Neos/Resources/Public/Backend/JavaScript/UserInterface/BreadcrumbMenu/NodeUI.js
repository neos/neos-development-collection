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
 * @class F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI
 *
 * Provides the UI implementation of the menu nodes
 *
 * This class extends ExtJS and therefore not all private methods are starting with a _
 *
 * @namespace F3.TYPO3.UserInterface.BreadcrumbMenu
 * @extends Ext.tree.TreeNodeUI
 */
F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI = function() {
	F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI.superclass.constructor.apply(this, arguments);
};

Ext.extend(F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI, Ext.tree.TreeNodeUI, {
	active: false,
	isLabelOpen: false,

	/**
	 * @param {Ext.EventObject} e
	 * @return {void}
	 */
	onClick: function(e) {
		F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI.superclass.onClick.call(this, e);
		if (this.active) {
			this._activate();
		} else {
			this._deactivate();
		}
	},

	/**
	 * @return {void}
	 */
	getFullPath: function() {
		return this.menuId + '-' + this.sectionId + '-' + this.menuPath;
	},

	/**
	 * @return {void}
	 */
	getModuleMenu: function() {
		return this.node.ownerTree.findParentByType(F3.TYPO3.UserInterface.ModuleMenu);
	},

	/**
	 * @param {Object} n
	 * @param {Object} a
	 * @param {Object} targetNode
	 * @param {Boolean} bulkRender
	 * @return {void}
	 * @private
	 */
	renderElements: function(n, a, targetNode, bulkRender) {
		var href = a.href ? a.href : Ext.isGecko ? "" : "#";

		var buf = [
			'<span class="F3-TYPO3-UserInterface-BreadcrumbMenu-node">',
			'<span ext:tree-node-id="',
			n.id,
			'" class="F3-TYPO3-UserInterface-BreadcrumbMenu-node-el F3-TYPO3-UserInterface-BreadcrumbMenu-node-leaf x-unselectable ',
			a.cls,
			'" unselectable="on">',

			// Icon
			'<span class="F3-TYPO3-UserInterface-BreadcrumbMenu-node-icon',
			(a.icon ? " F3-TYPO3-UserInterface-BreadcrumbMenu-node-inline-icon" : ""),
			(a.iconCls ? " "+a.iconCls : ""),
			'" unselectable="on" style="background-image: ',
			a.icon || this.emptyIcon,
			'"></span>',

			// Link / label
			'<a hidefocus="on" class="F3-TYPO3-UserInterface-BreadcrumbMenu-node-anchor" href="', 
			href,
			'" tabIndex="1" ',
			(a.hrefTarget ? ' target="' + a.hrefTarget+'"' : ""),
			'><span class="F3-TYPO3-UserInterface-BreadcrumbMenu-node-el-label" unselectable="on">',
			n.text,
			"</span></a>",

			// 'spacer'
			'<span class="F3-TYPO3-UserInterface-BreadcrumbMenu-elbow"></span>',

			"</span>",
			'<span class="F3-TYPO3-UserInterface-BreadcrumbMenu-node-ct" style="display:none;"></span>',
			"</span>"
		].join('');

		var nel;

		if (bulkRender !== true && n.nextSibling && (nel = n.nextSibling.ui.getEl())) {
			this.wrap = Ext.DomHelper.insertHtml("beforeBegin", nel, buf);
		} else {
			this.wrap = Ext.DomHelper.insertHtml("beforeEnd", targetNode, buf);
		}

		this.path = a.path;
		this.elNode = this.wrap.childNodes[0];
		this.ctNode = this.wrap.childNodes[1];
		var cs = this.elNode.childNodes;
		this.ecNode = cs[2];
		this.textNode = cs[1];
		this.iconNode = cs[0];
		var index = 3;

		this.anchor = cs[index];
		this.textNode = cs[1];
	},

	/**
	 * @param {Ext.EventObject} e
	 * @return {void}
	 * @private
	 */
	onOver : function(e) {
		Ext.get(this.getEl()).setStyle({width: 'auto'});
		Ext.get(this.elNode).setStyle({width: 'auto'});

		if (this.isLabelOpen === false) {
			this.isLabelOpen = true;
			F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler.nodeOnOver(this);
		}
	},

	/**
	 * @param {Ext.EventObject} e
	 * @return {void}
	 * @private
	 */
	onOut : function(e) {
		Ext.get(this.getEl()).setStyle({width: 'auto'});
		Ext.get(this.elNode).setStyle({width: 'auto'});
		if(this.isLabelOpen === true && !e.within(Ext.get(this.elNode), 1)) {
			this.isLabelOpen = false;
			F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler.nodeOnOut(this);
		}
	},

	/**
	 * @param {Boolean} state The current state of the node
	 * @return {void}
	 * @private
	 */
	onSelectedChange: function(state) {
	},

	/**
	 * @return {void}
	 */
	updateExpandIcon: function() {
		var elbowElement = Ext.get(this.node.ui.ecNode);
		if (this.node.expanded) {
			elbowElement.addClass('F3-TYPO3-UserInterface-BreadcrumbMenu-elbow-expanded');
		} else {
			elbowElement.removeClass('F3-TYPO3-UserInterface-BreadcrumbMenu-elbow-expanded');
		}
	},

	/**
	 * @param {Function} callback This callback is called when the animation is done
	 * @return {void}
	 */
	animExpand : function(callback){
		var ct = Ext.get(this.ctNode);
		ct.stopFx();

		if(!this.node.isExpandable()){
			this.updateExpandIcon();
			this.ctNode.style.display = "";
			Ext.callback(callback);
			return;
		}
		this.animating = true;
		this.updateExpandIcon();

		/**
		 * TODO: add configuration for this settings
		 */
		if (true) {
			F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler.hideSiblings(this.node, this);
		}

		F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler.expandNode(ct, callback, this);
	},

	/**
	 * @param {Function} callback This function is called after the collapse
	 * @return {void}
	 */
	animCollapse : function(callback){
		var ct = Ext.get(this.ctNode);
		ct.stopFx();

		this.animating = true;
		this.updateExpandIcon();

		/**
		 * TODO: add configuration for this settings
		 */
		if (true) {
			F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler.showSiblings(this.node, this);
		}

		F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler.collapseNode(callback, this);
	},

	/**
	 * @return {void}
	 */
	renderIndent : function(){
		if(this.rendered){
			this.updateExpandIcon();
		}
	},

	/**
	 * @return {void}
	 * @private
	 */
	_activate: function() {
		this.active = false;
		Ext.get(this.getEl()).removeClass('F3-TYPO3-UserInterface-BreadcrumbMenu-Node-active');
		F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('deactivate-' + this.path, this);
	},

	/**
	 * @return {void}
	 * @private
	 */
	_deactivate: function() {
		this.active = true;
		Ext.get(this.getEl()).addClass('F3-TYPO3-UserInterface-BreadcrumbMenu-Node-active');
		F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('activate-' + this.path, this);
	}
});

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI', F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI);