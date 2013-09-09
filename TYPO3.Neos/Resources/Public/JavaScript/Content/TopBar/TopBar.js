/**
 * Inspector
 */
define(
[
	'emberjs',
	'../Menu/MenuPanel',
	'../Menu/MenuButton',
	'text!./TopBar.html'
], function(
	Ember,
	MenuPanel,
	MenuButton,
	template
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),
		MenuPanel: MenuPanel,
		MenuButton: MenuButton
	});
});