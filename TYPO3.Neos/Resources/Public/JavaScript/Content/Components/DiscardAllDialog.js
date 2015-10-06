define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Content/Model/PublishableNodes',
		'Shared/AbstractModal',
		'text!./DiscardAllDialog.html'
	],
	function(Ember, $, PublishableNodes, AbstractModal, template) {
		return AbstractModal.extend({
			actions: {
				discard: function() {
					PublishableNodes.discardAll();
					this.destroy();
				}
			},
			template: Ember.Handlebars.compile(template)
		});
	}
);