define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'Content/Model/NodeActions',
		'text!InlineEditing/Dialogs/DeleteNodeDialog.html'
	],
	function(
		$,
		Ember,
		NodeActions,
		template
	) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-ui neos-overlay-component'],

			id: 'deleteNodeDialog',

			_node: null,

			cancel: function() {
				this.destroy();
			},

			'delete': function() {
				this.get('_node').$element.remove();
				NodeActions.remove(this.get('_node'));

				this.destroy();
			}
		});
	}
);