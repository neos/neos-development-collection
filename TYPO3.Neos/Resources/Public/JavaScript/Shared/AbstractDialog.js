define(
	[
		'emberjs',
		'Library/jquery-with-dependencies'
	],
	function (Ember, $) {
		return Ember.View.extend({
			classNames: ['neos-overlay-component'],

			createElement: function () {
				var that = this;
				that._super();
				that.$().appendTo($('#neos-application'));

				Mousetrap.bind('esc', function () {
					that.cancel();
				});
			},

			destroyElement: function () {
				this._super();
				Mousetrap.unbind('esc');
			},

			cancel: function () {
				this.destroyElement();
			}
		});
	}
);