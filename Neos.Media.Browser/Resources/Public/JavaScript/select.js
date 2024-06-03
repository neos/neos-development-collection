window.addEventListener('DOMContentLoaded', (event) => {
	$(function() {
			if (window.parent !== window && window.parent.NeosMediaBrowserCallbacks) {
					// we are inside iframe
					$('.asset-list').on('click', '[data-asset-identifier]', function(e) {
							if (
								$(e.target).closest('a, button').not('[data-asset-identifier]').length === 0 &&
									window.parent.NeosMediaBrowserCallbacks &&
									typeof window.parent.NeosMediaBrowserCallbacks.assetChosen === 'function'
							) {
									let localAssetIdentifier = $(this).attr('data-local-asset-identifier');
									if (localAssetIdentifier !== '') {
											window.parent.NeosMediaBrowserCallbacks.assetChosen(localAssetIdentifier);
									} else {
										if ($(this).attr('data-import-in-process') !== 'true') {
											$(this).attr('data-import-in-process', 'true')
											const message = window.NeosCMS.I18n.translate(
												'assetImport.importInfo',
												'Asset is being imported. Please wait.',
												'Neos.Media.Browser',
												'Main',
												[]
											)
											window.NeosCMS.Notification.ok(message)
											$.post(
												$('link[rel="neos-media-browser-service-assetproxies-import"]').attr('href'),
												{
													assetSourceIdentifier: $(this).attr('data-asset-source-identifier'),
													assetIdentifier: $(this).attr('data-asset-identifier'),
													__csrfToken: $('body').attr('data-csrf-token')
												},
												function (data) {
													window.parent.NeosMediaBrowserCallbacks.assetChosen(data.localAssetIdentifier);
													$(this).remove('data-import-in-process')
												}
											);
										}
										else {
											const message = window.NeosCMS.I18n.translate(
												'assetImport.importInProcess',
												'Import still in process. Please wait.',
												'Neos.Media.Browser',
												'Main',
												[]
											)
											window.NeosCMS.Notification.warning(message)
										}
									}
									e.preventDefault();
							}
					});
			}
	});
});
