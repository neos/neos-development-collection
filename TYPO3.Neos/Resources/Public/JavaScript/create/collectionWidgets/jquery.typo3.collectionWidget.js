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
					this.handles = [];
					this.options.model.url = function() {};
					this._super();
				},

				_destroy: function() {
					this.removeHandles();
				},

				enable: function() {
					this.addHandles();
				},

				addHandles: function() {
					var that = this,
						handle = T3.Content.UI.Util.AddContentElementHandleBars($(this.options.view.el), 0, this, true);
					if (handle) {
						this.handles.push(handle);
					}

					_.each(this.options.collection.models, function(entity, iterator) {
						var id = entity.id.substring(1, entity.id.length - 1),
							$element = $('[about="' + id + '"]').first(),
							handle = T3.Content.UI.Util.AddContentElementHandleBars($element, iterator + 1, this, false);
						if (handle) {
							that.handles.push(handle);
						}
						T3.Content.UI.Util.AddNotInlineEditableOverlay($element, entity);
					}, this);
				},

				removeHandles: function() {
					_.each(this.handles, function(handle) {
						handle.destroy();
					});
					this.handles = [];
				}
			});
		})($);
	}
);