/**
 * Inspector breadcrumb
 */
define(
[
	'emberjs',
	'text!./Breadcrumb.html'
], function(
	Ember,
	template
) {
	return Ember.View.extend({
		elementId: 'neos-content-breadcrumb',
		classNameBindings: ['open:neos-open'],
		template: Ember.Handlebars.compile(template),
		open: false,

		nodes: function() {
			this.set('open', false);
			return T3.Content.Model.NodeSelection.get('nodes').toArray().reverse();
		}.property('T3.Content.Model.NodeSelection.nodes'),

		click: function() {
			this.set('open', !this.get('open'));
		}
	});
});