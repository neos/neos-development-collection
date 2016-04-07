/**
 * Context context bar
 */
define(
[
	'emberjs',
	'./ContextBar',
	'./ContentDimensionSelector',
	'../FullScreenController',
	'../Application',
	'Shared/Configuration',
	'text!./ContentContextBar.html'
], function(
	Ember,
	ContextBar,
	ContentDimensionSelector,
	FullScreenController,
	ContentModule,
	Configuration,
	template
) {
	return ContextBar.extend({
		classNames: 'neos-content-context-bar',
		template: Ember.Handlebars.compile(template),
		ContentDimensionSelector: ContentDimensionSelector,
		fullScreenController: FullScreenController,
		Configuration: Configuration,
		init: function() {
			this.updateCurrentUri();
			var that = this;
			ContentModule.on('pageLoaded', function() {
				that.updateCurrentUri();
			});
		},
		updateCurrentUri: function() {
			this.set('liveUri', location.href.replace(/@[A-Za-z0-9;&,\-_=]+/g, ''));
		},

		didInsertElement: function() {
			this.$('[data-neos-tooltip]').tooltip();
		}
	});
});
