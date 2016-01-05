define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Content/Model/PublishableNodes',
		'Shared/AbstractModal',
		'text!./DirtyWorkspaceDialog.html'
	],
	function(Ember, $, PublishableNodes, AbstractModal, template) {
		return AbstractModal.extend({
			template: Ember.HTMLBars.compile(template),
			actions: {
				okay: function() {
					this.destroy();
				}
			}
		});
	}
);
