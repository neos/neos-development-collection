(function($) {
	$('.draggable-asset').each(function() {
		$(this).draggable({
			helper: 'clone',
			opacity: 0.3,
			revert: 'invalid',
			revertDuration: 200,
			start: function(event, ui) {
				$(event.toElement).closest('a').one('click', function(e) {
					e.preventDefault();
				});
			},
			stop: function(event, ui) {
				// event.toElement is the element that was responsible
				// for triggering this event. The handle, in case of a draggable.
				$(event.toElement).closest('a').off('click');
			}
		});
	});

	var tagAssetForm = $('#tag-asset-form'),
		assetField = $('#tag-asset-form-asset', tagAssetForm),
		tagField = $('#tag-asset-form-tag', tagAssetForm);
	$('.droppable-tag').each(function() {
		$(this).droppable({
			addClasses: false,
			activeClass: 'neos-drag-active',
			hoverClass: 'neos-drag-hover',
			tolerance: 'pointer',
			drop: function (event, ui) {
				var tag = $(this),
					asset = $(ui.draggable[0]),
					assetIdentifier = asset.data('asset-identifier'),
					countElement = tag.children('span'),
					count = parseInt(countElement.text());
				assetField.val(assetIdentifier);
				tagField.val(tag.data('tag-identifier'));
				countElement.html('<div class="neos-ellipsis" />');
				$.post(
					tagAssetForm.attr('action'),
					$('#tag-asset-form').serialize(),
					'json'
				).done(function(result) {
					if (result === true) {
						countElement.html(count + 1);
						var text = tag.clone();
						text.children().remove();
						$('[data-asset-identifier="' + assetIdentifier + '"]').children('.tags').append('\n<span class="neos-label">' + text.text() + '</span>');
						if (asset.hasClass('neos-media-untagged')) {
							var untagged = $('.count', '.neos-media-list-untagged');
							untagged.text(parseInt(untagged.text()) - 1);
							asset.removeClass('neos-media-untagged');
						}
					} else {
						countElement.html(count);
					}
				}).fail(function() {
					var message = 'Tagging the asset failed.';
					if (window.Typo3Neos) {
						window.Typo3Neos.Notification.error(message);
					} else {
						alert(message);
					}
				});
			}
		});
	});

	$('#neos-tags-list-edit-toggle').click(function() {
		$(this).toggleClass('neos-active');
		$('.neos-tags-list, #neos-tags-create-form').toggleClass('neos-tags-list-editing-active');
	});

	$('#neos-tags-create-form').submit(function(e) {
		var tag = $('#neos-tags-create-textfield');
		if ($.trim(tag.val()) === '') {
			tag.focus();
			e.preventDefault();
		} else {
			$('button[type="submit"]', this).addClass('neos-disabled').html('Creating<span class="neos-ellipsis" />');
		}
	});
})(jQuery);