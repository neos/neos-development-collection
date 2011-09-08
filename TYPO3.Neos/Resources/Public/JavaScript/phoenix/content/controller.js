/**
 * Controllers which are not model- but appearance-related
 */

define(
[],
function() {

	var T3 = window.T3 || {};
	T3.Content = T3.Content || {};
	var $ = window.alohaQuery || window.jQuery;

	/**
	 * This controller toggles the preview mode on and off.
	 */
	var Preview = SC.Object.create({
		previewMode: false,

		togglePreview: function(isPreviewEnabled) {
			var i = 0, count = 5, allDone = function() {
				i++;
				if (i >= count) {
					if (isPreviewEnabled) {
						$('body').removeClass('t3-ui-controls-active');
						$('body').addClass('t3-ui-controls-inactive');
						Aloha.editables.forEach(function(editable) {
							editable.disable();
						});
					} else {
						$('body').addClass('t3-ui-controls-active');
						$('body').removeClass('t3-ui-controls-inactive');

						Aloha.editables.forEach(function(editable) {
							editable.enable();
						});
					}
				}
			};
			if (isPreviewEnabled) {
				$('body').animate({
					'margin-top': 30,
					'margin-right': 0
				}, 'fast', allDone);
				$('#t3-footer').animate({
					height: 0
				}, 'fast', allDone);
				$('#t3-toolbar').animate({
					top: 0,
					right: 0
				}, 'fast', allDone);
				$('#t3-ui-top').slideUp('fast', allDone);
				$('#t3-rightarea').animate({
					width: 0
				}, 'fast', allDone);
				$('body').animate({
					'margin-right': 0
				}, 'fast', allDone);
			} else {
				// TODO Store initial sizes and reuse, to remove concrete values
				$('body').animate({
					'margin-top': 55,
					'margin-right': 200
				}, 'fast', allDone);
				$('#t3-footer').animate({
					height: 30
				}, 'fast', allDone);
				$('#t3-toolbar').animate({
					top: 50,
					right: 200
				}, 'fast', allDone);
				$('#t3-ui-top').slideDown('fast', allDone);
				$('#t3-rightarea').animate({
					width: 200
				}, 'fast', allDone);
				$('body').animate({
					'margin-right': 200
				}, 'fast', allDone);
			}
			this.set('previewMode', isPreviewEnabled);
		}
	});

	/**
	 * This controller toggles the preview mode on and off.
	 */
	var Inspect = SC.Object.create({
		inspectMode: false,

		toggleInspect: function(isInspectEnabled) {
			if (isInspectEnabled) {
				$('body').addClass('t3-inspect-active');
			} else {
				$('body').removeClass('t3-inspect-active');
			}
			this.set('inspectMode', isInspectEnabled);
		}
	});

	/**
	 * The BlockActions is a container for numerous actions which can happen with blocks.
	 * They are normally triggered when clicking Block UI handles.
	 * Examples include:
	 * - deletion of content
	 * - creation of content
	 *
	 * @singleton
	 */
	var BlockActions = SC.Object.create({

		/**
		 * Initialization lifecycle method. Here, we connect the create-new-content button
		 * which is displayed when a ContentArray is empty.
		 */
		init: function() {
			var that = this;
			$('button.t3-create-new-content').live('click', function() {
				that.addInside($(this).attr('data-node'));
			});
		},
		deleteBlock: function(block) {
			T3.Common.Dialog.confirm('Are you sure you want to delete this content element?', function() {
				TYPO3_TYPO3_Service_ExtDirect_V1_Controller_NodeController['delete'].call(
					this,
					block.get('nodePath'),
					function (result) {
						if (result.success) {
							T3.ContentModule.reloadPage();
						}
					}
				);
			});
		},

		addAbove: function(block) {
			this._add(block.get('nodePath'), 'above');
		},
		addBelow: function(block) {
			this._add(block.get('nodePath'), 'below');
		},
		addInside: function(nodePath) {
			this._add(nodePath, 'inside');
		},
		_add: function(nodePath, position) {
			T3.Common.Dialog.open(
				'/typo3/content/new',
				{
					position: position,
					referenceNode: nodePath
				},
				{
					'created-new-content': function($callbackDomElement) {
						T3.ContentModule.reloadPage();
					}
				}
			);
		}
	});

	T3.Content.Controller = {
		Preview: Preview,
		Inspect: Inspect,
		BlockActions: BlockActions
	}
	window.T3 = T3;
});