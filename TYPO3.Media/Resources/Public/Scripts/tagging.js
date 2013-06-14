(function($) {
	$('.draggable-asset').each(function() {
		$(this).draggable({
			addClasses: false,
			cursorAt: {left: 5, top: 5},
			helper: 'clone',
			opacity: 0.3,
			revert: true
		});
	});

	var tagAssetForm = $('#tag-asset-form'),
		assetField = $('#tag-asset-form-asset', tagAssetForm),
		tagField = $('#tag-asset-form-tag', tagAssetForm);
	$('.droppable-tag').each(function() {
		$(this).droppable({
			addClasses: false,
			activeClass: 'neos-label-success',
			hoverClass: 'neos-label-info',
			tolerance: 'pointer',
			drop: function (event, ui) {
				var tag = $(this),
					assetIdentifier = $(ui.draggable[0]).data('asset-identifier');
				assetField.val(assetIdentifier);
				tagField.val(tag.data('tag-identifier'));
				$.post(
					tagAssetForm.attr('action'),
					$('#tag-asset-form').serialize()
				).done(function() {
					tag.effect('highlight');
					$('[data-asset-identifier="' + assetIdentifier + '"]').children('.tags').append('<span class="neos-label">' + tag.html() + '</span>');
				}).fail(function() {
					alert('Tagging the asset failed.');
				});
			}
		});
	});
})(jQuery);