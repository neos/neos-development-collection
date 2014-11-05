define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'../Navigate/NavigatePanelController',
	'./NodeTree',
	'./ContextStructureTree',
	'Shared/EventDispatcher',
	'text!./NavigatePanel.html'
], function(
	Ember,
	$,
	NavigatePanelController,
	NodeTree,
	ContextStructureTree,
	EventDispatcher,
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
			if (this.$()) {
				var that = this;
				this.$().one('webkitTransitionEnd transitionend msTransitionEnd oTransitionEnd', function () {
					if (that.get('controller.navigatePanelMode') === true) {
						EventDispatcher.triggerExternalEvent('Neos.NavigatePanelOpened');
					} else {
						EventDispatcher.triggerExternalEvent('Neos.NavigatePanelClosed');
					}
					EventDispatcher.triggerExternalEvent('Neos.LayoutChanged');
				});
			}
			if (this.get('controller.navigatePanelMode') === true) {
				$('body').addClass('neos-navigate-panel-open');
			} else {
				$('body').removeClass('neos-navigate-panel-open');
			}
		}.observes('controller.navigatePanelMode').on('init')
	});
});