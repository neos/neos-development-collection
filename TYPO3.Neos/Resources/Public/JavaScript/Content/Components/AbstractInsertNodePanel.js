define([
	'emberjs',
	'Shared/AbstractModal',
	'./HelpMessage',
	'text!./AbstractInsertNodePanel.html'
], function(
	Ember,
	AbstractModal,
	HelpMessage,
	template
) {
	return AbstractModal.extend({
		template: Ember.Handlebars.compile(template),
		nodeTypeGroups: Ember.required,
		insertNode: Ember.required,
		HelpMessage: HelpMessage
	});
});
