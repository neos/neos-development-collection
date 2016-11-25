/**
 * Inspector breadcrumb
 */
define(
[
	'emberjs',
	'Content/Model/NodeSelection',
	'text!./Breadcrumb.html'
], function(
	Ember,
	NodeSelection,
	template
) {
	return Ember.View.extend({
		elementId: 'neos-content-breadcrumb',
		classNameBindings: ['open:neos-open', 'multiple:neos-breadcrumb-multiple'],
		template: Ember.Handlebars.compile(template),
		open: false,

		nodeSelection: NodeSelection,

		nodes: function() {
			this.set('open', false);
			return NodeSelection.get('nodes').toArray().reverse();
		}.property('nodeSelection.nodes'),

		currentNode: function() {
			return this.get('nodes')[0];
		}.property('nodes'),

		click: function() {
			this.set('open', !this.get('open'));
		},

		multiple: function() {
			return this.get('nodes').length > 1;
		}.property('nodes')
	});
});
