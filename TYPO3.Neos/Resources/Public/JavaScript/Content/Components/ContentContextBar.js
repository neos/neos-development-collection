/**
 * Context context bar
 */
define(
[
	'emberjs',
	'./ContextBar',
	'./SaveIndicator',
	'../FullScreenController',
	'Shared/Endpoint/NodeEndpoint',
	'text!./ContentContextBar.html'
], function(
	Ember,
	ContextBar,
	SaveIndicator,
	FullScreenController,
	NodeEndpoint,
	template
) {
	return ContextBar.extend({
		classNames: 'neos-content-context-bar',
		_nodeEndpoint: NodeEndpoint,
		template: Ember.Handlebars.compile(template),
		SaveIndicator: SaveIndicator,
		fullScreenController: FullScreenController
	});
});