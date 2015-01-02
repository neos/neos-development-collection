define([
	'emberjs',
	'Shared/AbstractModal',
	'text!./AbstractInsertNodePanel.html'
], function(
	Ember,
	AbstractModal,
	template
) {
	return AbstractModal.extend({
		template: Ember.Handlebars.compile(template),
		nodeTypeGroups: Ember.required,
		insertNode: Ember.required
	});
});