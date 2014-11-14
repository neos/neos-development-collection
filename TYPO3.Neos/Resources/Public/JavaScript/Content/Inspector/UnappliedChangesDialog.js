/**
 * Unapplied changes dialog shown by inspector on unexpected node switches
 */
define(
[
	'emberjs',
	'./InspectorController',
	'Shared/AbstractDialog',
	'text!./UnappliedChangesDialog.html'
], function(
	Ember,
	InspectorController,
	AbstractDialog,
	template
) {
	return AbstractDialog.extend({
		classNames: ['inspector-dialog'],
		template: Ember.Handlebars.compile(template),

		controller: InspectorController,

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