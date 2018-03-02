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
			template: Ember.Handlebars.compile(template),

			discard: function() {
				PublishableNodes.discardAll();
				this.destroy();
			}
		});
	}
);