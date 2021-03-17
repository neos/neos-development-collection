window.addEventListener('DOMContentLoaded', (event) => {
	jQuery(function() {
			if (window.parent !== window && window.parent.NeosMediaBrowserCallbacks) {
					// we are inside iframe
					jQuery('.asset-list').on('click', '[data-asset-identifier]', function(e) {
							if (
								jQuery(e.target).closest('a, button').not('[data-asset-identifier]').length === 0 &&
									window.parent.NeosMediaBrowserCallbacks &&
									typeof window.parent.NeosMediaBrowserCallbacks.assetChosen === 'function'
							) {
									let localAssetIdentifier = jQuery(this).attr('data-local-asset-identifier');
									if (localAssetIdentifier !== '') {
											window.parent.NeosMediaBrowserCallbacks.assetChosen(localAssetIdentifier);
									} else {
										jQuery.post(
													jQuery('link[rel="neos-media-browser-service-assetproxies-import"]').attr('href'),
													{
															assetSourceIdentifier: jQuery(this).attr('data-asset-source-identifier'),
															assetIdentifier: jQuery(this).attr('data-asset-identifier'),
															__csrfToken: jQuery('body').attr('data-csrf-token')
													},
													function(data) {
															window.parent.NeosMediaBrowserCallbacks.assetChosen(data.localAssetIdentifier);
													}
											);
									}
									e.preventDefault();
							}
					});
			}
	});
});
