define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Content/Model/PublishableNodes',
		'Shared/AbstractDialog',
		'text!./PublishAllDialog.html'
	],
	function(Ember, $, PublishableNodes, AbstractDialog, template) {
		return AbstractDialog.extend({
			template: Ember.Handlebars.compile(template),

			publish: function() {
				PublishableNodes.publishAll();
				this.destroy();
			}
		});
	}
);