define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/AbstractModal',
	'text!./DeleteNodeDialog.html'
],
function(Ember, $, AbstractModal, template) {
	return AbstractModal.extend({
		template: Ember.Handlebars.compile(template),
		title: '',
		numberOfChildren: 0,
		deleteNode: Ember.required
	});
});