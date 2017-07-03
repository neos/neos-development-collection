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
			template: Ember.Handlebars.compile(template),

			publish: function() {
				PublishableNodes.publishAll();
				this.destroy();
			}
		});
	}
);