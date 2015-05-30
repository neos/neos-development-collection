/**
 * Unapplied changes dialog shown by inspector on unexpected node switches
 */
define(
[
	'emberjs',
	'./InspectorController',
	'text!./UnappliedChangesDialog.html'
], function(
	Ember,
	InspectorController,
	template
) {
	return Ember.View.extend({
		classNames: ['inspector-dialog'],
		template: Ember.Handlebars.compile(template),

		controller: InspectorController,

		cancel: function() {
			this.destroy();
		},

		apply: function() {
			this.get('controller').apply();
			this.destroy();
		},

		revert: function() {
			this.get('controller').revert();
			this.destroy();
		}
	});
});