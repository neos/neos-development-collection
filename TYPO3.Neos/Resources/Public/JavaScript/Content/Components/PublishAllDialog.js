define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Content/Model/PublishableNodes',
		'Shared/AbstractModal',
		'text!./PublishAllDialog.html'
	],
	function(Ember, $, PublishableNodes, AbstractModal, template) {
		return AbstractModal.extend({
			actions: {
				publish: function() {
					PublishableNodes.publishAll();
					this.destroy();
				}
			},
			template: Ember.Handlebars.compile(template)
		});
	}
);