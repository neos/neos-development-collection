define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'Content/Model/PublishableNodes',
		'text!./DiscardAllDialog.html'
	],
	function(Ember, $, PublishableNodes, template) {
		return Ember.View.extend({
			template: Ember.Handlebars.compile(template),
			classNames: ['neos-overlay-component'],

			createElement: function() {
				this._super();
				this.$().appendTo($('#neos-application'));
			},

			discard: function() {
				PublishableNodes.discardAll();
				this.destroyElement();
			},

			cancel: function() {
				this.destroyElement();
			}
		});
	}
);