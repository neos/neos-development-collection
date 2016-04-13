/**
 * Application view for the main ember application
 */
define(
[
	'emberjs',
	'text!./ApplicationView.html',
	'./Components/ContentContextBar',
	'./Menu/MenuPanel',
	'./TopBar/TopBar',
	'./EditPreviewPanel/EditPreviewPanel',
	'./Navigate/NavigatePanel',
	'./Inspector/InspectorView',
	'./Inspector/InspectorController',
	'./Inspector/SecondaryInspectorView',
	'./../InlineEditing/InlineEditingHandles',
	'../Shared/EventDispatcher'
],
function(
	Ember,
	template,
	ContentContextBar,
	MenuPanel,
	TopBar,
	EditPreviewPanel,
	NavigatePanel,
	InspectorView,
	InspectorController,
	SecondaryInspectorView,
	InlineEditingHandles,
	EventDispatcher
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),
		_isContentModule: window.T3.isContentModule,
		ContentContextBar: ContentContextBar,
		MenuPanel: MenuPanel,
		EditPreviewPanel: EditPreviewPanel,
		NavigatePanel: NavigatePanel,
		InspectorView: InspectorView,

		// We cannot name the property in UpperCamelCase, as we can not
		// use it in a binding in Handlebars then (because of some weird Ember naming convention...)
		inspectorController: InspectorController,

		SecondaryInspectorView: SecondaryInspectorView,
		InlineEditingHandles: InlineEditingHandles,

		didInsertElement: function() {
			// Make sure to create the top bar *after* the DOM is loaded completely,
			// and the #neos-top-bar is transmitted from the server.
			TopBar.create({_isContentModule: this.get('_isContentModule')}).appendTo('#neos-top-bar');
			this.$('[data-neos-tooltip]').add('[data-neos-tooltip]', '#neos-top-bar').tooltip({container: '#neos-application'});

			Ember.run.next(function() {
				EventDispatcher.triggerExternalEvent('Neos.ContentModuleLoaded', 'Content module finished loading.');
			});
		}
	});
});
