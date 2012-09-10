define(
	[
		'jquery',
		'jquery-ui',
		'createjs'
	],
	function($) {
		(function ($, undefined) {
			$.widget('typo3.typo3CollectionWidget', $.Midgard.midgardCollectionAddBetween, {

				enable: function() {
					this.addHandles();
				},

				addHandles: function() {
					_.each(this.options.collection.models, function(entity, iterator) {
						var id = entity.id.substring(1, entity.id.length - 1);
						T3.Content.UI.Util.AddContentElementHandleBars($('[about="' + id + '"]').first(), iterator, this);
					}, this);
				}
			});
		})($);
	}
);