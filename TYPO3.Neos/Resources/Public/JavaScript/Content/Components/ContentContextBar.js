/**
 * Context context bar
 */
define(
[
	'emberjs',
	'./ContextBar',
	'text!./ContentContextBar.html',
	'./ToggleButton'
], function(
	Ember,
	ContextBar,
	template,
	ToggleButton
) {
	return ContextBar.extend({
		classNames: 'neos-content-context-bar',
		template: Ember.Handlebars.compile(template),
		ToggleButton: ToggleButton
	});
});