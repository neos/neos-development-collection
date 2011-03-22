Ext.ns('F3.TYPO3.Content.ContentEditorFrontend');

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
 * @class F3.TYPO3.Content.ContentEditorFrontend.DragDrop
 *
 * This class handles the drag and drop functionality of content elements
 *
 * @namespace F3.TYPO3.Content.ContentEditorFrontend
 * @singleton
 */
F3.TYPO3.Content.ContentEditorFrontend.DragDrop = {
	/**
	 * Initializer, called on page load. Is used to register event
	 * listeners on the core.
	 *
	 * @param {F3.TYPO3.Content.ContentEditorFrontend.Core} core
	 * @return {void}
	 */
	initialize: function(core) {
		core.on('afterPageLoad', function() {
			this._addDropZones();
			this._enableDragDrop();
			Ext.dd.DragDropMgr.lock();
		}, this);

		core.on('enableEditing', function() {
			Ext.dd.DragDropMgr.unlock();
		}, this);
		core.on('disableEditing', function() {
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
			cls: 'f3-typo3-dropzone',
			html: top.F3.TYPO3.UserInterface.I18n.get('TYPO3', 'dropContentHere')
		};

		Ext.select('.f3-typo3-contentelement').each(function(el) {
			Ext.DomHelper.insertBefore(el, Ext.apply(elementDefinition, {
				'about': el.getAttribute('about'),
				'data-workspacename': el.getAttribute('data-workspacename'),
				'data-position': 'before'
			}));
		});

		Ext.select('.f3-typo3-contentelement').each(function(el) {
			if (el.next() && !el.next().hasClass('f3-typo3-dropzone')) {
				Ext.DomHelper.insertAfter(el, Ext.apply(elementDefinition, {
					'about': el.getAttribute('about'),
					'data-workspacename': el.getAttribute('data-workspacename'),
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
				Ext.select('.f3-typo3-dropzone').addClass('f3-typo3-dropzone-visible');
				window.setTimeout(function() {
					Ext.select('.f3-typo3-dropzone').each(function(el) {
						ddTargets.push(new Ext.dd.DDTarget(el, 'f3-typo3-contentelements'));
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

				if (!window.parent.F3) return;

				var sourceNode = F3.TYPO3.Content.ContentEditorFrontend.Core.createNode(sourceElement.getAttribute('about'), sourceElement.getAttribute('data-workspacename'));
				var targetNode = F3.TYPO3.Content.ContentEditorFrontend.Core.createNode(targetEl.getAttribute('about'), targetEl.getAttribute('data-workspacename'));

				var onMoveFinished = function() {
					window.location.reload();
				};
				if (targetEl.getAttribute('data-position') == 'before') {
					window.parent.F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.moveBefore(sourceNode, targetNode, onMoveFinished);
				} else {
					window.parent.F3.TYPO3_Service_ExtDirect_V1_Controller_NodeController.moveAfter(sourceNode, targetNode, onMoveFinished);
				}
			},
			endDrag: function() { // Called both on invalid and on valid drop
				if (this.invalidDrop) {
					var sourceElement = Ext.get(this.getEl());
					sourceElement.setStyle('opacity', 1);
					delete this.invalidDrop;
				}

				Ext.select('.f3-typo3-dropzone').removeClass('f3-typo3-dropzone-visible').removeClass('dropzoneOver');
				Ext.each(ddTargets, function(ddTarget) {
					ddTarget.destroy();
				});
			}
		};
		Ext.select('.f3-typo3-contentelement').each(function(el) {
			var dd = new Ext.dd.DDProxy(el, 'f3-typo3-contentelements', {
				isTarget: false
			});
			Ext.apply(dd, overrides);
		});
	}
};
F3.TYPO3.Content.ContentEditorFrontend.Core.registerModule(F3.TYPO3.Content.ContentEditorFrontend.DragDrop);
