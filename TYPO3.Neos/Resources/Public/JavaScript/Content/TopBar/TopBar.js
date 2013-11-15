/**
 * Inspector
 */
define(
[
	'emberjs',
	'../Menu/MenuPanel',
	'../Menu/MenuButton',
	'../Navigate/NavigatePanelController',
	'text!./TopBar.html'
], function(
	Ember,
	MenuPanel,
	MenuButton,
	NavigatePanelController,
	template
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),
		MenuPanel: MenuPanel,
		MenuButton: MenuButton,
		navigatePanelController: NavigatePanelController,
		_isContentModule: false
	});
});