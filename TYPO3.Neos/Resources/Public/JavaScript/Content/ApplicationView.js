/**
 * Application view for the main ember application
 */
define(
[
	'emberjs',
	'text!./ApplicationView.html',
	'./Components/ContentContextBar',
	'./Menu/MenuButton',
	'./Menu/MenuPanel',
	'./Components/TreePanel',
	'./Inspector/InspectorButton',
	'./Inspector/Inspector',
	'./Inspector/SecondaryInspectorView',
	'./../InlineEditing/InlineEditingHandles',
	'./../InlineEditing/InsertNodePanel'
],
function(
	Ember,
	template,
	ContentContextBar,
	MenuButton,
	MenuPanel,
	TreePanel,
	InspectorButton,
	Inspector,
	SecondaryInspectorView,
	InlineEditingHandles,
	InsertNodePanel
) {
	return Ember.View.extend({
		ContentContextBar: ContentContextBar,
		MenuButton: MenuButton,
		MenuPanel: MenuPanel,
		TreePanel: TreePanel,
		InspectorButton: InspectorButton,
		Inspector: Inspector,
		SecondaryInspectorView: SecondaryInspectorView,
		InlineEditingHandles: InlineEditingHandles,
		InsertNodePanel: InsertNodePanel,
		template: Ember.Handlebars.compile(template),

		_isContentModule: window.T3.isContentModule
	});
});