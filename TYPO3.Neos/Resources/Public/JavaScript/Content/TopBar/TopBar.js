/**
 * Inspector
 */
define(
[
	'emberjs',
	'../Menu/MenuPanel',
	'../Menu/MenuButton',
	'../Navigate/NavigatePanelController',
	'../EditPreviewPanel/EditPreviewPanelController',
	'text!./TopBar.html'
], function(
	Ember,
	MenuPanel,
	MenuButton,
	NavigatePanelController,
	EditPreviewPanelController,
	template
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),
		MenuPanel: MenuPanel,
		MenuButton: MenuButton,
		navigatePanelController: NavigatePanelController,
		editPreviewPanelController: EditPreviewPanelController,
		_isContentModule: false
	});
});