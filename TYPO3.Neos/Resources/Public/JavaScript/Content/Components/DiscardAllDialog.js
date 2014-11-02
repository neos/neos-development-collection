define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Content/Model/PublishableNodes',
		'Shared/AbstractDialog',
		'text!./DiscardAllDialog.html'
	],
	function(Ember, $, PublishableNodes, AbstractDialog, template) {
		return AbstractDialog.extend({
			template: Ember.Handlebars.compile(template),

			discard: function() {
				PublishableNodes.discardAll();
				this.destroy();
			}
		});
	}
);