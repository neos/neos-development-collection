/**
 * Unapplied changes dialog shown by inspector on unexpected node switches
 */
define(
[
	'emberjs',
	'./InspectorController',
	'Shared/AbstractModal',
	'text!./UnappliedChangesDialog.html'
], function(
	Ember,
	InspectorController,
	AbstractModal,
	template
) {
	return AbstractModal.extend({
		actions: {
			apply: function() {
				this.get('controller').apply();
				this.destroy();
			},

			revert: function() {
				this.get('controller').revert();
				this.destroy();
			}
		},
		classNames: ['inspector-dialog'],
		template: Ember.HTMLBars.compile(template),
		controller: InspectorController
	});
});