define(
	[
		'Library/jquery-with-dependencies',
		'emberjs',
		'text!InlineEditing/Dialogs/DeleteNodeDialog.html'
	],
	function($, Ember, template) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-ui neos-overlay-component'],

			id: 'deleteNodeDialog',

			_node: null,
			_entity: null,

			/**
			 * This method returns a possible label / title readable for the editor.
			 * If none found null is returned.
			 *
			 * @return {string}
			 */
			nodeLabel: function() {
				if (this.get('_entity').get('typo3:title') !== undefined) {
					return this.get('_entity').get('typo3:title');
				}

				return '';
			}.property('_node'),

			cancel: function() {
				this.destroy();
			},

			'delete': function() {
				this.get('_node').$element.remove();
				T3.Content.Controller.NodeActions.remove(this.get('_entity'));

				this.destroy();
			}
		});
	}
);