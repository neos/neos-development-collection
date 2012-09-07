define(
	[
		'jquery',
		'jquery-ui',
		'createjs'
	],
	function(jQuery) {
		(function (jQuery, undefined) {
			jQuery.widget('typo3.typo3CollectionWidget', jQuery.Midgard.midgardCollectionAddBetween, {

				enable: function() {
					this.addHandles();
				},

				addHandles: function() {
					_.each(this.options.collection.models, function(entity, iterator) {
						var id = entity.id.substring(1, entity.id.length - 1);
						T3.Content.UI.Util.AddContentElementHandleBars(jQuery('[about="' + id + '"]').first(), iterator, this);
					}, this);
				}
			});
		})(jQuery);
	}
);