define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'../Navigate/NavigatePanelController',
	'./NodeTree',
	'./ContextStructureTree',
	'text!./NavigatePanel.html'
], function(
	Ember,
	$,
	NavigatePanelController,
	NodeTree,
	ContextStructureTree,
	template
) {
	return Ember.View.extend({
		elementId: 'neos-navigate-panel',
		template: Ember.Handlebars.compile(template),
		classNameBindings: ['controller.contextStructureMode:neos-navigate-panel-context-structure-open'],
		NodeTree: NodeTree,
		ContextStructureTree: ContextStructureTree,

		controller: NavigatePanelController,

		onNavigatePanelModeChanged: function() {
			if (this.get('controller.navigatePanelMode') === true) {
				$('body').addClass('neos-navigate-panel-open');
			} else {
				$('body').removeClass('neos-navigate-panel-open');
			}
		}.observes('controller.navigatePanelMode').on('init')
	});
});