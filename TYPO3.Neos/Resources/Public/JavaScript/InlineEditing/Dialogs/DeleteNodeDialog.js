define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Content/Model/NodeActions',
		'Shared/AbstractModal',
		'text!./DeleteNodeDialog.html'
	],
	function(
		$,
		Ember,
		NodeActions,
		AbstractModal,
		template
	) {
		return AbstractModal.extend({
			template: Ember.Handlebars.compile(template),
			_node: null,

			'delete': function() {
				this.get('_node').$element.remove();
				NodeActions.remove(this.get('_node'));
				this.destroy();
			}
		});
	}
);