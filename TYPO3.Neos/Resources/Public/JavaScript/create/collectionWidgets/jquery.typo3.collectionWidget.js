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
					T3.Content.UI.Util.AddContentElementHandleBars($(this.options.view.el), 0, this, {_type: 'section'});

					_.each(this.options.collection.models, function(entity, iterator) {
						var id = entity.id.substring(1, entity.id.length - 1);
						T3.Content.UI.Util.AddContentElementHandleBars($('[about="' + id + '"]').first(), iterator + 1, this);
					}, this);
				}
			});
		})($);
	}
);