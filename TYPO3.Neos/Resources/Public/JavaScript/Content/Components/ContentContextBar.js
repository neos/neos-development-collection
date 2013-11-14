/**
 * Context context bar
 */
define(
[
	'emberjs',
	'./ContextBar',
	'./SaveIndicator',
	'../FullScreenController',
	'text!./ContentContextBar.html'
], function(
	Ember,
	ContextBar,
	SaveIndicator,
	FullScreenController,
	template
) {
	return ContextBar.extend({
		classNames: 'neos-content-context-bar',
		template: Ember.Handlebars.compile(template),
		SaveIndicator: SaveIndicator,
		fullScreenController: FullScreenController
	});
});