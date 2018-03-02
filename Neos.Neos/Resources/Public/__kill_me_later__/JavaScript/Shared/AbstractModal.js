define(
	[
		'emberjs',
		'Library/jquery-with-dependencies',
		'LibraryExtensions/Mousetrap'
	],
	function (Ember, $, Mousetrap) {
		return Ember.View.extend({
			classNames: ['neos-overlay-component'],

			init: function() {
				this._super();
				this.appendTo('#neos-application');
			},

			didInsertElement: function() {
				var that = this;
				Mousetrap.bind('esc', function() {
					that.cancel();
				});

				this.focus();
			},

			focus: function() {
				this.$().find('button:last').focus();
			},

			destroy: function() {
				this._super();
				Mousetrap.unbind('esc');
			},

			cancel: function() {
				this.destroy();
			}
		});
	}
);