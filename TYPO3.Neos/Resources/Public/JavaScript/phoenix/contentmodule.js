/**
 * T3.ContentModule
 *
 * Entry point which initializes the Content Module UI
 */

define(
[
	'phoenix/common',
	'phoenix/content/model',
	'phoenix/content/ui',
	'phoenix/content/controller'
],
function() {

	var T3 = window.T3 || {};
	var ContentModule = SC.Application.create({

		bootstrap: function() {
			this._initializePropertyPanel();
			this._initializeToolbar();
			this._initializeFooter();
			this._initializeLauncher();

			// When aloha is loaded, blockify our content.
			// TODO: Later, we will only have one generic TYPO3-AlohaBlock here
			// instead of multiple ones.
			Aloha.bind('aloha', function() {
				$('.t3-plugin').alohaBlock({
					'block-type': 'PluginBlock'
				});

				$('.t3-text').alohaBlock({
					'block-type': 'TextBlock'
				});
				T3.Content.Model.Changes._readFromLocalStore();
			});
			 // TODO: should be only set when header and property panel is visible
			$('body').addClass('t3-ui-controls-active');
			$('body').addClass('t3-backend');
		},

		_initializePropertyPanel: function() {
			var propertyPanel = T3.Content.UI.PropertyPanel.create({
				elementId: 't3-rightarea',
				classNames: ['t3-ui', 't3-rightarea', 'aloha-block-do-not-deactivate']
			});

			propertyPanel.appendTo($('body'));
		},

		_initializeToolbar: function() {
			var toolbar = T3.Content.UI.Toolbar.create({
				elementId: 't3-toolbar',
				classNames: ['t3-ui'],
				left: [
					T3.Content.UI.ToggleButton.extend({
						label: 'Pages'
					}),
					T3.Content.UI.ToggleButton.extend({
						target: 'T3.Content.Controller.Preview',
						action: 'togglePreview',
						label: 'Preview',
						icon: 'preview'
					})
				],
				right: [
					T3.Content.UI.Button.extend({
						label: 'Revert',
						disabledBinding: 'T3.Content.Model.Changes.noChanges',
						target: 'T3.Content.Model.Changes',
						action: 'revert'
					}),
					T3.Content.UI.Button.extend({
						label: 'Save',
						disabledBinding: 'T3.Content.Model.Changes.noChanges',
						target: 'T3.Content.Model.Changes',
						action: 'save'
					})
				]
			});
			toolbar.appendTo($('body'));
		},

		_initializeLauncher: function() {
			var launcher = T3.Common.Launcher.create({
				modulesBinding: 'T3.Common.ModulesController.filteredModules',
				valueBinding: 'T3.Common.ModulesController.filterValue'
			});
			launcher.appendTo($('#t3-ui-top'));
		},

		_initializeFooter: function() {
			var breadcrumb = T3.Content.UI.Breadcrumb.extend({
				contentBinding: 'T3.Content.Model.BlockSelection.blocks'
			});
			var footer = T3.Content.UI.Toolbar.create({
				elementId: 't3-footer',
				classNames: ['t3-ui'],
				left: [
					breadcrumb
				]
			});
			footer.appendTo($('body'));
		},

		_onBlockSelectionChange: function(blocks) {
			T3.Content.Model.BlockSelection.updateSelection(blocks);
		}
	});

	T3.ContentModule = ContentModule;
	window.T3 = T3;
});