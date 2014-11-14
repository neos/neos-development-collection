define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Content/Model/NodeActions',
		'Shared/AbstractDialog',
		'text!InlineEditing/Dialogs/DeleteNodeDialog.html'
	],
	function(
		$,
		Ember,
		NodeActions,
		AbstractDialog,
		template
	) {
		return AbstractDialog.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-ui neos-overlay-component'],

			id: 'deleteNodeDialog',

			_node: null,

			'delete': function() {
				this.get('_node').$element.remove();
				NodeActions.remove(this.get('_node'));

				this.destroy();
			}
		});
	}
);