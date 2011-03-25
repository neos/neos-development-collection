Ext.ns('F3.TYPO3.Components');

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
 * @class F3.TYPO3.Components.OrderSelect
 *
 * An extended Ext.grid.GridPanel Component with clean markup, ready to be styled by CSS3.
 * Used for selecting a position of a node within the parent node's child nodes
 *
 * @namespace F3.TYPO3.Components
 * @extends Ext.grid.GridPanel
 */
F3.TYPO3.Components.OrderSelect = Ext.extend(Ext.grid.GridPanel, {
	cls: 'F3-TYPO3-Components-OrderSelect',
	enableDragDrop: true,
	ddGroup: 'orderSelect',
	// @TODO: Check actual design implementation if a fixed height should be used, keep in mind that it's added because the height of the hBox layout is set before the directFn call so the height isn't defined by how many siblings it has, also maybe add a maxHeight value to make sure a scrollbar is used
	height: 150,
	autoWidth: true,
	hideHeaders: true,
	autoExpandColumn: 'title',
	dragableId: '',
	move: false,
	stripeRows: true,
	columns: [
		{
			id: 'id',
			hidden: true
		}, {
			id: 'title',
			dataIndex: 'title'
		}
	],
	nodePath: '',
	position: 0,
	listeners: {
		'viewready': function(scope) {
			var store = scope.getStore(),
				sm = scope.getSelectionModel(),
				view = scope.getView();
			// Select dragable row
			scope.selectDragable();
			// Make sure only the dragable element can be dragged
			view.dragZone.onBeforeDrag = function(data) {
				return (store.getAt(data.rowIndex).id === scope.dragableId);
			};
			// Create custom dropTarget
			var ddrow = new Ext.dd.DropTarget(view.mainBody, {
				ddGroup: 'orderSelect',
				notifyOver: function(dd, e, data) {
					var row = sm.getSelected();
					var cIndex = dd.getDragData(e).rowIndex;
					if (sm.hasSelection() && (cIndex !== undefined) && (store.indexOf(row) !== cIndex)) {
						store.remove(store.getById(row.id));
						store.insert(cIndex, row);
						sm.selectRow(cIndex);
						scope.updateContextAndPosition(cIndex);
					}
				}
			});
		}
	},

	/**
	 * Initializer
	 *
	 * @return {void}
	 */
	initComponent: function() {
		var self = this,
			nodePath = Ext.getCmp('F3.TYPO3.Content.WebsiteContainer').getCurrentPagePath();

		this.ddText = F3.TYPO3.UserInterface.I18n.get('TYPO3', 'orderSelectDrag');

		var directFn = function(callback) {
			if(self.move) {
				F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodesFromParent({__nodePath: nodePath}, 'TYPO3:Page', 1, callback); // TODO: the {__nodePath:nodePath} can be replaced by "nodePath" once the new property mapper has landed in core.
			} else {
				F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.getChildNodes({__nodePath: nodePath}, 'TYPO3:Page', 1, callback); // TODO: same as above
			}
		};
		directFn.directCfg = {
			method: {
				len: 0
			}
		};

		this.store = new Ext.data.DirectStore({
			directFn: directFn,
			autoLoad: true,
			autoDestroy: true,
			root: 'data',
			idProperty: 'id',
			fields: [],
			listeners: {
				'load': function(store) {
					var dragableId;
					if(self.move) {
						dragableId = nodePath;
					} else {
						var dragable = new Ext.data.Record({'title': F3.TYPO3.UserInterface.I18n.get('TYPO3', 'orderSelectAddNew')});
						store.insert(0, dragable);
						dragableId = dragable.id;
					}
					self.dragableId = dragableId;
					self.selectDragable();
				}
			}
		});

		this.sm = new Ext.grid.RowSelectionModel({
			singleSelect: true,
			listeners: {
				beforerowselect: function(sm, i, ke, row) {
					return (row.id === self.dragableId);
				}
			}
		});

		F3.TYPO3.Components.OrderSelect.superclass.initComponent.call(this);
	},

	/**
	 * Update context and position of list item
	 *
	 * @param {integer} index
	 * @return {void}
	 */
	updateContextAndPosition: function(index) {
		var store = this.getStore();
		if (store.getCount() > 1) {
			var node, position;
			if (index === 0) {
				// Set position to -1 to create before the first sibling
				node = store.getAt(1);
				position = -1;
			} else {
				// Set the position to 1 to create after the previous sibling
				node = store.getAt((index - 1));
				position = 1;
			}
			this.nodePath = node.data['__nodePath'];
			this.position = position;
		} else {
			// Find current context if no siblings are available
			this.nodePath = Ext.getCmp('F3.TYPO3.Content.WebsiteContainer').getCurrentPagePath();
			this.position = 0;
		}
	},

	/**
	 * Select the dragable item in the list
	 *
	 * @return {void}
	 */
	selectDragable: function() {
		var dragable = this.getStore().indexOfId(this.dragableId);
		// indexOfId returns -1 if not found
		if (dragable !== -1) {
			this.getSelectionModel().selectRow(dragable);
			this.updateContextAndPosition(dragable);
		}
	},

	/**
	 * Get the current node path
	 *
	 * @return {Object}
	 */
	getNodePath: function() {
		return this.nodePath;
	},

	/**
	 * Get the current position
	 *
	 * @return {integer}
	 */
	getPosition: function() {
		return this.position;
	}
});
Ext.reg('F3.TYPO3.Components.OrderSelect', F3.TYPO3.Components.OrderSelect);