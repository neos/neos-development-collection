define(
	[
		'emberjs'
	],
	function(Ember) {
		return Ember.View.extend({
			classNames: ['neos-contentelement-overlay'],
			template: Ember.Handlebars.compile('<span></span>'),

			init: function() {
				this._super();

				var that = this,
					$element = this.get('$element'),
					entity = this.get('entity');

				entity.on('change', function() {
					// If the entity changed, it might happen that the size changed as well; thus we need to reload the overlay size
					that._setOverlaySizeFn();
				});
			},

			didInsertElement: function() {
				this._setOverlaySizeFn();
				this.$().remove().prependTo(this.get('$element'));
			},

			_setOverlaySizeFn: function() {
				var that = this;
				// We use a timeout here to make sure the browser has re-drawn; thus $element
				// has a possibly updated size
				window.setTimeout(function() {
					that.$().css({
						'width': that.get('$element').outerWidth(),
						'height': that.get('$element').outerHeight()
					});
				}, 10);
			}
		});
	}
);