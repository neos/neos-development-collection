window.addEventListener('DOMContentLoaded', (event) => {
	jQuery(function ($) {
		jQuery('#check-all').change(function () {
			var value = false;
			if (jQuery(this).is(':checked')) {
				value = true;
				jQuery('.batch-action').removeClass('neos-hidden').removeClass('neos-discardAllChanges').removeClass('neos-disabled').removeAttr('disabled');
				jQuery('.review-button-action').addClass('neos-hidden').addClass('neos-disabled').attr('disabled', 'disabled');
			} else {
				jQuery('.batch-action').addClass('neos-hidden').addClass('neos-disabled').attr('disabled', 'disabled');
				jQuery('.review-button-action').removeClass('neos-hidden').removeClass('neos-disabled').removeAttr('disabled', 'disabled');
			}
			jQuery('tbody input[type="checkbox"]').prop('checked', value);
		});

		jQuery('.neos-check-document').change(function () {
			var documentIdentifier = jQuery(this).val();
			var checked = jQuery(this).prop('checked');
			jQuery(this).closest('table').find('tr.neos-change.document-' + documentIdentifier + ' td.check input').prop('checked', checked);
		});

		jQuery('tbody input[type="checkbox"]').change(function () {
			if (jQuery(this).closest('tr').data('ismoved') === true || jQuery(this).closest('tr').data('isnew') === true) {
				var currentNodePath = jQuery(this).closest('tr').attr('data-nodepath') + '/';
				var checked = jQuery(this).prop('checked');

				function nodePathStartsWith(nodePath) {
					return function (index, element) {
						return nodePath.indexOf(jQuery(element).data('nodepath')) === 0;
					}
				}

				var movedOrNewParentDocuments = jQuery(this).closest('table').find('.neos-document[data-ismoved="true"], .neos-document[data-isnew="true"]').filter(nodePathStartsWith(currentNodePath));
				jQuery(movedOrNewParentDocuments).each(function (index, movedParentDocument) {
					jQuery('tr[data-nodepath^="' + jQuery(movedParentDocument).data('nodepath') + '"] td.check input').prop('checked', checked);
				});
			}

			if (jQuery('tbody input[type="checkbox"]:checked').length > 0) {
				jQuery('.batch-action').removeClass('neos-hidden').removeClass('neos-disabled').removeAttr('disabled');
				jQuery('.review-button-action').addClass('neos-hidden').addClass('neos-disabled').attr('disabled', 'disabled');
			} else {
				jQuery('.batch-action').addClass('neos-hidden').addClass('neos-disabled').attr('disabled', 'disabled');
				jQuery('.review-button-action').removeClass('neos-hidden').removeClass('neos-disabled').removeAttr('disabled', 'disabled');
			}
		});

		jQuery('.fold-toggle').click(function () {
			jQuery(this).toggleClass('fas fa-chevron-down fas fa-chevron-up');
			jQuery('tr.' + jQuery(this).data('toggle')).toggle();
		});
	});
});
