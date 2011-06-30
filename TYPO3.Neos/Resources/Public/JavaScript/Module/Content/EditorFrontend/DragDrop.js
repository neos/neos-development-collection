Ext.ns('TYPO3.TYPO3.Module.Content.EditorFrontend');

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
 * @class TYPO3.TYPO3.Module.Content.EditorFrontend.DragDrop
 *
 * This class handles the drag and drop functionality of content elements
 *
 * @namespace TYPO3.TYPO3.Module.Content.EditorFrontend
 * @singleton
 */
TYPO3.TYPO3.Module.Content.EditorFrontend.DragDrop = {
	/**
	 * Initializer, called on page load. Is used to register event
	 * listeners on the core.
	 *
	 * @param {TYPO3.TYPO3.Module.Content.EditorFrontend.Core} core
	 * @return {void}
	 */
	initialize: function(core) {
		core.on('afterPageLoad', function() {
			this._addDropZones();
			this._enableDragDrop();
			Ext.dd.DragDropMgr.lock();
		}, this);

		core.on('enableSelectionMode', function() {
			Ext.dd.DragDropMgr.unlock();
		}, this);
		core.on('disableSelectionMode', function() {
			Ext.dd.DragDropMgr.lock();
		}, this);
	},

	/**
	 * Add drop zones before and after each content element.
	 *
	 * @return {void}
	 * @private
	 */
	_addDropZones: function() {
		var elementDefinition = {
			tag: 'div',
			cls: 'typo3-typo3-dropzone'
		};

		Ext.select('.typo3-typo3-contentelement').each(function(el) {
			Ext.DomHelper.insertBefore(el, Ext.apply(elementDefinition, {
				'data-nodepath': el.getAttribute('about'),
				'data-position': 'before'
			}));
		});

		Ext.select('.typo3-typo3-contentelement').each(function(el) {
			if (el.next() && !el.next().hasClass('typo3-typo3-dropzone')) {
				Ext.DomHelper.insertAfter(el, Ext.apply(elementDefinition, {
					'data-nodepath': el.getAttribute('about'),
					'data-position': 'after'
				}));
			}
		});
	},

	/**
	 * Enable drag and drop functionality
	 *
	 * @return {void}
	 * @private
	 */
	_enableDragDrop: function() {
		var ddTargets = [];

		var overrides = {
			startDrag: function() {
				// Show drop zones
				Ext.select('.typo3-typo3-dropzone').addClass('typo3-typo3-dropzone-visible');
				window.setTimeout(function() {
					Ext.select('.typo3-typo3-dropzone').each(function(el) {
						ddTargets.push(new Ext.dd.DDTarget(el, 'typo3-typo3-contentelements'));
					});
				}, 500);

				// Style drag proxy and the element to move
				var dragProxy = Ext.get(this.getDragEl());
				var sourceElement = Ext.get(this.getEl());

				sourceElement.setStyle('opacity', 0.5);
				dragProxy.addClass('ddProxy');
				dragProxy.update(sourceElement.getAttribute('about'));
			},
			onDragEnter : function(evtObj, targetElId) {
				var targetEl = Ext.get(targetElId);
				targetEl.addClass('dropzoneOver');

				var currentDragDropTarget = Ext.dd.Registry.getHandle(targetElId);
				//console.log(this);
				this.DDM.getDDById(targetElId).setPadding(0,0,50,0);
				/*console.log(this.DDM.getDDById(targetElId));
				console.log(targetElId);
				console.log(Ext.dd.Registry);
				console.log(currentDragDropTarget);
				console.log(Ext.dd.Registry.getTarget(targetElId));*/
				//currentDragDropTarget.setPadding(0, 0, 50, 0);
			},
			onDragOut : function(evtObj, targetElId) {
				var targetEl = Ext.get(targetElId);
				targetEl.removeClass('dropzoneOver');
			},
			onInvalidDrop : function() {
				this.invalidDrop = true;
			},
			onDragDrop: function(evtObj, targetElId) { // Only called on valid drop
				var targetEl = Ext.get(targetElId);
				var sourceElement = Ext.get(this.getEl());

				if (!window.parent.TYPO3) return;

				var sourceContextNodePath = sourceElement.getAttribute('about');
				var targetContextNodePath = targetEl.getAttribute('data-contextnodepath');

				var onMoveFinished = function() {
					window.location.reload();
				};
				if (targetEl.getAttribute('data-position') == 'before') {
					window.parent.TYPO3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.moveBefore(
						sourceContextNodePath,
						targetContextNodePath,
						onMoveFinished
					);
				} else {
					window.parent.TYPO3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.moveAfter(
						sourceContextNodePath,
						targetContextNodePath,
						onMoveFinished
					);
				}
			},
			endDrag: function() { // Called both on invalid and on valid drop
				if (this.invalidDrop) {
					var sourceElement = Ext.get(this.getEl());
					sourceElement.setStyle('opacity', 1);
					delete this.invalidDrop;
				}

				Ext.select('.typo3-typo3-dropzone').removeClass('typo3-typo3-dropzone-visible').removeClass('dropzoneOver');
			}
		};

		Ext.select('.typo3-typo3-contentelement').each(function(el) {
			var dd = new Ext.dd.DDProxy(el, 'typo3-typo3-contentelements', {
				isTarget: false
			});
			Ext.apply(dd, overrides);
		});
	}
};
TYPO3.TYPO3.Module.Content.EditorFrontend.Core.registerModule(TYPO3.TYPO3.Module.Content.EditorFrontend.DragDrop);