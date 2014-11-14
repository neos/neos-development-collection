define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'Shared/AbstractDialog',
	'text!./DeleteNodeDialog.html'
],
function(Ember, $, AbstractDialog, template) {
	return AbstractDialog.extend({
		template: Ember.Handlebars.compile(template),
		title: '',
		numberOfChildren: 0,
		deleteNode: Ember.K,

		cancel: function() {
			this._super();
			this.set('deleteNode', Ember.K);
		}
	}).create();
});