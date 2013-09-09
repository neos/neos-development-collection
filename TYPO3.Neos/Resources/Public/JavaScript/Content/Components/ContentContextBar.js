/**
 * Context context bar
 */
define(
[
	'emberjs',
	'./ContextBar',
	'./SaveIndicator',
	'text!./ContentContextBar.html'
], function(
	Ember,
	ContextBar,
	SaveIndicator,
	template
) {
	return ContextBar.extend({
		classNames: 'neos-content-context-bar',
		template: Ember.Handlebars.compile(template),
		SaveIndicator: SaveIndicator
	});
});