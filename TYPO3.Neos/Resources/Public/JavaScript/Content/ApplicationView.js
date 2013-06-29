/**
 * Application view for the main ember application
 */
define(
[
	'emberjs',
	'text!./ApplicationView.html',
	'./Components/NavigationToolbar',
	'./Components/TreePanel',
	'./Inspector/InspectorButton',
	'./Inspector/Inspector',
	'./../InlineEditing/InlineEditingHandles',
	'./../InlineEditing/InsertNodePanel'
],
function(
	Ember,
	template,
	NavigationToolbar,
	TreePanel,
	InspectorButton,
	Inspector,
	InlineEditingHandles,
	InsertNodePanel
) {
	return Ember.View.extend({
		NavigationToolbar: NavigationToolbar,
		InspectorButton: InspectorButton,
		Inspector: Inspector,
		TreePanel: TreePanel,
		InlineEditingHandles: InlineEditingHandles,
		InsertNodePanel: InsertNodePanel,
		template: Ember.Handlebars.compile(template)
	});
});