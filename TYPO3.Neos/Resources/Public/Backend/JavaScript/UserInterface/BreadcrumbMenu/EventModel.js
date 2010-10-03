/*                                                                        *
 * This script belongs to the TYPO3 project.                              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
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
	initEvents : function(){
        if(this.tree.trackMouseOver !== false){
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
	 * @param {Object} e
	 * @return {Object}
	 * @public
	 */
    getNode : function(e){
		var t;
        if(t = e.getTarget('.f3-BreadcrumbMenu-node-el', 10)){
            var id = Ext.fly(t, '_treeEvents').getAttribute('tree-node-id', 'ext');
            if(id){
                return this.tree.getNodeById(id);
            }
        }
        return null;
    },

	/**
	 * @param {Object} e
	 * @return {Object}
	 * @public
	 */
    getNodeTarget : function(e){
        var t = e.getTarget('.f3-BreadcrumbMenu-node-icon', 1);
        if(!t){
            t = e.getTarget('.f3-BreadcrumbMenu-node-el', 6);
        }
        return t;
    },

	/**
	 * @param {Object} e
	 * @param {Object} t
	 * @return {void}
	 * @public
	 */
    delegateOut : function(e, t){
        if(!this.beforeEvent(e)){
            return;
        }
        if(e.getTarget('.f3-BreadcrumbMenu-ec-icon', 1)){
            var n = this.getNode(e);
            this.onIconOut(e, n);
            if(n == this.lastEcOver){
                delete this.lastEcOver;
            }
        }
        if((t = this.getNodeTarget(e)) && !e.within(t, true)){
            this.onNodeOut(e, this.getNode(e));
        }
    },

	/**
	 * @param {Object} e
	 * @param {Object} t
	 * @return {void}
	 * @public
	 */
    delegateOver : function(e, t){
        if(!this.beforeEvent(e)){
            return;
        }
        if(Ext.isGecko && !this.trackingDoc){ // prevent hanging in FF
            Ext.getBody().on('mouseover', this.trackExit, this);
            this.trackingDoc = true;
        }
        if(this.lastEcOver){ // prevent hung highlight
            this.onIconOut(e, this.lastEcOver);
            delete this.lastEcOver;
        }
        if(e.getTarget('.f3-BreadcrumbMenu-ec-icon', 1)){
            this.lastEcOver = this.getNode(e);
            this.onIconOver(e, this.lastEcOver);
        }
        if(t = this.getNodeTarget(e)){
            this.onNodeOver(e, this.getNode(e));
        }
    },

	/**
	 * @param {Object} e
	 * @return {void}
	 * @public
	 */
    trackExit : function(e){
        if(this.lastOverNode){
            if(this.lastOverNode.ui && !e.within(this.lastOverNode.ui.getEl())){
                this.onNodeOut(e, this.lastOverNode);
            }
            delete this.lastOverNode;
            Ext.getBody().un('mouseover', this.trackExit, this);
            this.trackingDoc = false;
        }

    },

	/**
	 * @param {Object} e
	 * @param {Object} t
	 * @return {void}
	 * @public
	 */
    delegateClick : function(e, t){
        if(this.beforeEvent(e)){
            if(e.getTarget('input[type=checkbox]', 1)){
                this.onCheckboxClick(e, this.getNode(e));
            }else if(e.getTarget('.f3-BreadcrumbMenu-ec-icon', 1)){
                this.onIconClick(e, this.getNode(e));
            }else if(this.getNodeTarget(e)){
                this.onNodeClick(e, this.getNode(e));
            }
        }else{
            this.checkContainerEvent(e, 'click');
        }
    },

	/**
	 * @param {Object} e
	 * @param {Object} t
	 * @return {void}
	 * @public
	 */
    delegateDblClick : function(e, t){
        if(this.beforeEvent(e)){
            if(this.getNodeTarget(e)){
                this.onNodeDblClick(e, this.getNode(e));
            }
        }else{
            this.checkContainerEvent(e, 'dblclick');
        }
    },

	/**
	 * @param {Object} e
	 * @param {Object} t
	 * @return {void}
	 * @public
	 */
    delegateContextMenu : function(e, t){
        if(this.beforeEvent(e)){
            if(this.getNodeTarget(e)){
                this.onNodeContextMenu(e, this.getNode(e));
            }
        }else{
            this.checkContainerEvent(e, 'contextmenu');
        }
    },

	/**
	 * @param {Object} e
	 * @param {Object} type
	 * @return {Boolean}
	 * @public
	 */
    checkContainerEvent: function(e, type){
        if(this.disabled){
            e.stopEvent();
            return false;
        }
        this.onContainerEvent(e, type);
    },

	/**
	 * @param {Object} e
	 * @param {Object} type
	 * @return {void}
	 * @public
	 */
    onContainerEvent: function(e, type){
        this.tree.fireEvent('container' + type, this.tree, e);
    },

	/**
	 * @param {Object} e
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
    onNodeClick : function(e, node){
        node.ui.onClick(e);
    },

	/**
	 * @param {Object} e
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
    onNodeOver : function(e, node){
        this.lastOverNode = node;
        node.ui.onOver(e);
    },

	/**
	 * @param {Object} e
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
    onNodeOut : function(e, node){
        node.ui.onOut(e);
    },

	/**
	 * @param {Object} e
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
    onIconOver : function(e, node){
        node.ui.addClass('f3-BreadcrumbMenu-ec-over');
    },

	/**
	 * @param {Object} e
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
    onIconOut : function(e, node){
        node.ui.removeClass('f3-BreadcrumbMenu-ec-over');
    },

	/**
	 * @param {Object} e
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
    onIconClick : function(e, node){
        node.ui.ecClick(e);
    },

	/**
	 * @param {Object} e
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
    onCheckboxClick : function(e, node){
        node.ui.onCheckChange(e);
    },

	/**
	 * @param {Object} e
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
    onNodeDblClick : function(e, node){
        node.ui.onDblClick(e);
    },

	/**
	 * @param {Object} e
	 * @param {Object} node
	 * @return {void}
	 * @public
	 */
    onNodeContextMenu : function(e, node){
        node.ui.onContextMenu(e);
    },

	/**
	 * @param {Object} e
	 * @return {Boolean}
	 * @public
	 */
    beforeEvent : function(e){
        var node = this.getNode(e);
        if(this.disabled || !node || !node.ui){
            e.stopEvent();
            return false;
        }
        return true;
    },

	/**
	 * @return {void}
	 * @public
	 */
    disable: function(){
        this.disabled = true;
    },

	/**
	 * @return {void}
	 * @public
	 */
    enable: function(){
        this.disabled = false;
    }
});

Ext.reg('F3.TYPO3.UserInterface.BreadcrumbMenu.EventModel', F3.TYPO3.UserInterface.BreadcrumbMenu.EventModel);