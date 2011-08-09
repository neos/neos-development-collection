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

	var T3 = window.T3 || {},
		$ = window.alohaQuery || window.jQuery;
	var ContentModule = SC.Application.create({

		/**
		 * The following setting is set to "true" when unfinished features should be shown.
		 *
		 * You can use it in the UI as following:
		 *
		 * SC.View.extend({
		 *    template: SC.Handlebars.compile('<span style="color:white">!!! Development mode !!!</span>'),
		 *    isVisibleBinding: 'T3.ContentModule.showDevelopmentFeatures'
		 * })
		 *
		 * OR
		 *
		 * {{view T3.Content.UI.Button label="Inspect" isVisibleBinding="T3.ContentModule.showDevelopmentFeatures"}}
		 *
		 * OR
		 * {{#boundIf T3.ContentModule.showDevelopmentFeatures}}
		 *   Display only in development mode
		 * {{/boundif}}
		 */
		showDevelopmentFeatures: false,

		bootstrap: function() {
			this._initializePropertyPanel();
			this._initializeToolbar();
			this._initializeFooter();
			this._initializeLauncher();

			var that = this;
			window.addEventListener("hashchange", function() {
				that._enableDevelopmentFeaturesIfNeeded();
			}, false);
			this._enableDevelopmentFeaturesIfNeeded();

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

				T3.Content.Model.BlockManager.initializeBlocks();
				T3.Content.Model.Changes._readFromLocalStore();
			});
			 // TODO: should be only set when header and property panel is visible
			$('body').addClass('t3-ui-controls-active');
			$('body').addClass('t3-backend');
		},

		_enableDevelopmentFeaturesIfNeeded: function() {
			if (window.location.hash === '#dev') {
				this.set('showDevelopmentFeatures', true);
				window.localStorage['showDevelopmentFeatures'] = true;
			} else if (window.location.hash === '#nodev') {
				this.set('showDevelopmentFeatures', false);
				delete window.localStorage['showDevelopmentFeatures']
			} else if(window.localStorage['showDevelopmentFeatures']) {
				this.set('showDevelopmentFeatures', 'true');
			}
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
					T3.Content.UI.PageTreeButton.extend({
						label: 'Pages',
						popoverTitle: 'Page Tree',
						isVisibleBinding: 'T3.ContentModule.showDevelopmentFeatures'
					}),
					T3.Content.UI.ToggleButton.extend({
						target: 'T3.Content.Controller.Preview',
						action: 'togglePreview',
						label: 'Preview',
						icon: 'preview'
					})
				],
				right: [
					SC.View.extend({
						template: SC.Handlebars.compile('<span style="color:white">!!! Development mode !!!</span>'),
						isVisibleBinding: 'T3.ContentModule.showDevelopmentFeatures'
					}),
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
					}),
					T3.Content.UI.Button.extend({
						label: 'Publish',
						disabledBinding: 'T3.Content.Model.PublishableBlocks.noChanges',
						target: 'T3.Content.Model.PublishableBlocks',
						action: 'publishAll'
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