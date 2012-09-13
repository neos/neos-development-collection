define(
	[
		'jquery',
		'jquery-ui',
		'createjs'
	],
	function($) {
		(function ($, undefined) {
			$.widget('typo3.typo3CollectionWidget', $.Midgard.midgardCollectionAddBetween, {

				/**
				 * The midgardCollectionAddBetween widget tries to detect the correct way to store
				 * changes in the _create() method. This will fail on finding creates localStorage
				 * widget, and our model does not have a url set. By introducing this dummy function
				 * we prevent an error console.log() from create. This method is called without
				 * further arguments, and does not change behaviour.
				 */
				_create: function() {
					this.options.model.url = function() {};
					this._super();
				},

				enable: function() {
					this.addHandles();
				},

				addHandles: function() {
					T3.Content.UI.Util.AddContentElementHandleBars($(this.options.view.el), 0, this, true);

					_.each(this.options.collection.models, function(entity, iterator) {
						var id = entity.id.substring(1, entity.id.length - 1);
						T3.Content.UI.Util.AddContentElementHandleBars($('[about="' + id + '"]').first(), iterator + 1, this, false);
						T3.Content.UI.Util.AddNotInlineEditableOverlay($('[about="' + id + '"]').first());
					}, this);
				}
			});
		})($);
	}
);