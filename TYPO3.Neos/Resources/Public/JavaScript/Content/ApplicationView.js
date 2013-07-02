/**
 * Application view for the main ember application
 */
define(
[
	'emberjs',
	'text!./ApplicationView.html',
	'./Components/NavigationToolbar',
	'./Components/TreePanel',
	'./Inspector/Inspector',
	'./../InlineEditing/InlineEditingHandles'
],
function(
	Ember,
	template,
	NavigationToolbar,
	TreePanel,
	Inspector,
	InlineEditingHandles,
	InsertNodePanel
) {
	return Ember.View.extend({
		NavigationToolbar: NavigationToolbar,
		Inspector: Inspector,
		TreePanel: TreePanel,
		InlineEditingHandles: InlineEditingHandles,
		InsertNodePanel: InsertNodePanel,
		template: Ember.Handlebars.compile(template)
	});
});
