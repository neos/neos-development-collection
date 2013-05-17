/**
 * Navigation toolbar
 */
define(
[
	'./Toolbar',
	'text!./NavigationToolbar.html',
	'./ToggleButton',
	'./PublishPageButton'
], function(
	Toolbar,
	template,
	ToggleButton,
	PublishPageButton
) {
	return Toolbar.extend({
		elementId: 't3-toolbar',
		template: Ember.Handlebars.compile(template),
		ToggleButton: ToggleButton,
		PublishPageButton: PublishPageButton
	});
});