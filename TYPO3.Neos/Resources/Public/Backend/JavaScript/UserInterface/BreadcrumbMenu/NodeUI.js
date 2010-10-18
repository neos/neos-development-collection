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
 * @class F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI
 * @namespace F3.TYPO3.UserInterface.BreadcrumbMenu
 * @extends Ext.tree.TreeNodeUI
 * @author Rens Admiraal <rens@rensnel.nl>
 */
F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI = function() {
	F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI.superclass.constructor.apply(this, arguments);
};

Ext.extend(F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI, Ext.tree.TreeNodeUI, {
	active: false,
	isLabelOpen: false,

	onClick: function(e) {
		F3.TYPO3.UserInterface.BreadcrumbMenu.NodeUI.superclass.onClick.call(this, e);
		if (this.active) {
			this.activate();
		} else {
			this.deactivate();
		}
	},

	getFullPath: function() {
		return this.menuId + '-' + this.sectionId + '-' + this.menuPath;
	},

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
			'<span class="f3-BreadcrumbMenu-node">',
				'<span ext:tree-node-id="', n.id,
				'" class="f3-BreadcrumbMenu-node-el f3-BreadcrumbMenu-node-leaf x-unselectable ', a.cls,
				'" unselectable="on">',
					// Icon
					'<span class="f3-BreadcrumbMenu-node-icon', (a.icon ? " f3-BreadcrumbMenu-node-inline-icon" : ""),
					(a.iconCls ? " "+a.iconCls : ""), '" unselectable="on" style="background-image: ',
					a.icon || this.emptyIcon, '"></span>',

					// Link / label
					'<a hidefocus="on" class="f3-BreadcrumbMenu-node-anchor" href="', href, '" tabIndex="1" ',
					a.hrefTarget ? ' target="' + a.hrefTarget+'"' : "", '><span class="f3-BreadcrumbMenu-node-el-label" unselectable="on">',
					n.text,"</span></a>",
					// 'spacer'
					'<span class="f3-BreadcrumbMenu-elbow"></span>',

				"</span>",
				'<span class="f3-BreadcrumbMenu-node-ct" style="display:none;"></span>',
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
	 * @param {Object} e
	 * @return {void}
	 * @private
	 */
	onOver : function(e) {
		Ext.get(this.getEl()).setStyle({width: 'auto'});
		Ext.get(this.elNode).setStyle({width: 'auto'});

		if (this.isLabelOpen === false) {
			this.isLabelOpen = true;
			F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler.nodeOnOver(this, e);
		}
	},

	/**
	 * @param {Object} e
	 * @return {void}
	 * @private
	 */
	onOut : function(e) {
		Ext.get(this.getEl()).setStyle({width: 'auto'});
		Ext.get(this.elNode).setStyle({width: 'auto'});

		if(this.isLabelOpen === true && !e.within(Ext.get(this.elNode), 1)) {
			this.isLabelOpen = false;
			F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler.nodeOnOut(this, e);
		}
	},

	/**
	 * @param {Boolean} state
	 * @return {void}
	 * @private
	 */
	onSelectedChange: function(state) {
	},

	updateExpandIcon: function() {
		var elbowElement = Ext.get(this.node.ui.ecNode);
		if (this.node.expanded) {
			elbowElement.addClass('f3-BreadcrumbMenu-elbow-expanded');
		} else {
			elbowElement.removeClass('f3-BreadcrumbMenu-elbow-expanded');
		}
	},

	/**
	 * @param {Function} callback
	 * @return {void}
	 * @private
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
	 * @param {Function}
	 * @return {void}
	 * @private
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

		F3.TYPO3.UserInterface.BreadcrumbMenu.AnimationHandler.collapseNode(ct, callback, this);
	},

	/**
	 * @return {void}
	 * @private
	 */
	renderIndent : function(){
		if(this.rendered){
			this.updateExpandIcon();
		}
	},

	activate: function() {
		this.active = false;
		Ext.get(this.getEl()).removeClass('F3-TYPO3-UserInterface-BreadcrumbMenu-Node-active');
		F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('deactivate-' + this.path, this);
	},

	deactivate: function() {
		this.active = true;
		Ext.get(this.getEl()).addClass('F3-TYPO3-UserInterface-BreadcrumbMenu-Node-active');
		F3.TYPO3.UserInterface.UserInterfaceModule.fireEvent('activate-' + this.path, this);
	}
});

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenu', F3.TYPO3.UserInterface.BreadcrumbMenu);