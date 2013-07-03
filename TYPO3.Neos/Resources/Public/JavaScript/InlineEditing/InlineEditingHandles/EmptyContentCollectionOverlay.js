define(
	[
		'Library/jquery-with-dependencies',
		'emberjs'
	],
	function($, Ember) {
		return Ember.Object.create({
			show: function(collectionWidget) {
				if (collectionWidget.element.find('.neos-empty-contentcollection-overlay').length === 0) {
					var $overlay = $('<div />', {'class': 'neos', html: '<div class="neos-empty-contentcollection-overlay"></div>'}).prependTo(collectionWidget.element);

					$overlay.on('click', function() {
						T3.Content.Model.NodeSelection.updateSelection($(this).parents('[rel="typo3:content-collection"]').eq(0));
					});
				}
			},

			hide: function(collectionWidget) {
				collectionWidget.element.find('.neos-empty-contentcollection-overlay').parent().remove();
			}
		})
	}
);