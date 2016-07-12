define(
	[
		'Library/jquery-with-dependencies',
		'Library/underscore',
		'vie',
		'InlineEditing/EmptyContentCollectionOverlay',
		'InlineEditing/NotInlineEditableOverlay',
		'Library/create'
	],
	function($, _, vie, EmptyContentCollectionOverlay, NotInlineEditableOverlay) {
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
				var that = this,
					toggleEmptySectionOverlay = function() {
						// We foreach over the collection as the where() method fails with
						// TypeError: Object #<Object> has no method 'call'
						var numberOfChildEntities = 0;

						that.options.collection.forEach(function(entity) {
							if (entity.get('typo3:_removed') === false) {
								numberOfChildEntities++;
							}
						});

						if (numberOfChildEntities === 0) {
							EmptyContentCollectionOverlay.show(that);
						} else {
							EmptyContentCollectionOverlay.hide(that);
						}
					};

				this.options.collection.on('change', toggleEmptySectionOverlay);
				toggleEmptySectionOverlay();

				vie.entities.get(vie.service('rdfa').getElementSubject(this.element))._enclosingCollectionWidget = that;
				_.each(this.options.collection.models, function(entity, iterator) {
					entity._enclosingCollectionWidget = that;
					var id = entity.id.slice(1, -1),
						$element = $('[about="' + id + '"]').first();
					if ($element.hasClass('neos-not-inline-editable')) {
						NotInlineEditableOverlay.create({$element: $element, entity: entity}).appendTo($element);
					}
				}, this);
			}
		});
	}
);