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
],
function(
	Ember,
	template,
	NavigationToolbar,
	TreePanel,
	Inspector
) {
	return Ember.View.extend({
		NavigationToolbar: NavigationToolbar,
		Inspector: Inspector,
		TreePanel: TreePanel,
		template: Ember.Handlebars.compile(template)
	});
});
