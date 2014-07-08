/**
 * Context context bar
 */
define(
[
	'emberjs',
	'./ContextBar',
	'./ContentDimensionSelector',
	'./SaveIndicator',
	'../FullScreenController',
	'Shared/Endpoint/NodeEndpoint',
	'text!./ContentContextBar.html',
	'Shared/Configuration'
], function(
	Ember,
	ContextBar,
	ContentDimensionSelector,
	SaveIndicator,
	FullScreenController,
	NodeEndpoint,
	template,
	Configuration
) {
	return ContextBar.extend({
		classNames: 'neos-content-context-bar',
		_nodeEndpoint: NodeEndpoint,
		template: Ember.Handlebars.compile(template),
		ContentDimensionSelector: ContentDimensionSelector,
		SaveIndicator: SaveIndicator,
		fullScreenController: FullScreenController,
		Configuration: Configuration
	});
});