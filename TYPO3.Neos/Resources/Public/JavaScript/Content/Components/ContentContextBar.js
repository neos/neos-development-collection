/**
 * Context context bar
 */
define(
[
	'emberjs',
	'./ContextBar',
	'./ContentDimensionSelector',
	'../FullScreenController',
	'text!./ContentContextBar.html',
	'Shared/Configuration'
], function(
	Ember,
	ContextBar,
	ContentDimensionSelector,
	FullScreenController,
	template,
	Configuration
) {
	return ContextBar.extend({
		classNames: 'neos-content-context-bar',
		template: Ember.Handlebars.compile(template),
		ContentDimensionSelector: ContentDimensionSelector,
		fullScreenController: FullScreenController,
		Configuration: Configuration
	});
});