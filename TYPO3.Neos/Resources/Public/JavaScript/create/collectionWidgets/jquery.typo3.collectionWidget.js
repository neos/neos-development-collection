define(
	[
		'Library/jquery-with-dependencies',
		'Library/underscore',
		'Model/NodeCollection',
		'Library/create'
	],
	function($, _, NodeCollection) {
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
					NodeCollection.set('content', this);
					this.options.model.url = function() {};
					this._super();
				},

				enable: function() {
					_.each(this.options.collection.models, function(entity, iterator) {
						var id = entity.id.substring(1, entity.id.length - 1),
							$element = $('[about="' + id + '"]').first();

						T3.Content.UI.Util.AddNotInlineEditableOverlay($element, entity);
					}, this);
				}
			});
		})($);
	}
);