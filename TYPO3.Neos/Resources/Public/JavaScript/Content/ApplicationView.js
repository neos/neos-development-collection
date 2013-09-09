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
	'./Components/TreePanel',
	'./Inspector/InspectorView',
	'./Inspector/InspectorController',
	'./Inspector/SecondaryInspectorView',
	'./../InlineEditing/InlineEditingHandles',
	'./../InlineEditing/InsertNodePanel'
],
function(
	Ember,
	template,
	ContentContextBar,
	MenuPanel,
	TopBar,
	TreePanel,
	InspectorView,
	InspectorController,
	SecondaryInspectorView,
	InlineEditingHandles,
	InsertNodePanel
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),
		_isContentModule: window.T3.isContentModule,
		ContentContextBar: ContentContextBar,
		MenuPanel: MenuPanel,
		TreePanel: TreePanel,
		InspectorView: InspectorView,

		// We cannot name the property in UpperCamelCase, as we can not
		// use it in a binding in Handlebars then (because of some weird Ember naming convention...)
		inspectorController: InspectorController,

		SecondaryInspectorView: SecondaryInspectorView,
		InlineEditingHandles: InlineEditingHandles,
		InsertNodePanel: InsertNodePanel,

		didInsertElement: function() {
			// Make sure to create the top bar *after* the DOM is loaded completely,
			// and the #neos-top-bar is transmitted from the server.
			TopBar.create().appendTo('#neos-top-bar');
		}
	});
});