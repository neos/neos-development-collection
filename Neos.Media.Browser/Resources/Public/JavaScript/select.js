window.addEventListener('DOMContentLoaded', () => {
	(function () {
		if (window.parent !== window && window.parent.NeosMediaBrowserCallbacks) {
			// we are inside iframe
			const assets = document.querySelectorAll('[data-asset-identifier]');
			assets.forEach((asset) => {
				asset.addEventListener('click', (e) => {
					if (
						e.target.closest('a:not([data-asset-identifier]), button:not([data-asset-identifier])') === null &&
						window.parent.NeosMediaBrowserCallbacks &&
						typeof window.parent.NeosMediaBrowserCallbacks.assetChosen === 'function'
					) {
						let localAssetIdentifier = asset.dataset.localAssetIdentifier;
						if (localAssetIdentifier !== '') {
							window.parent.NeosMediaBrowserCallbacks.assetChosen(localAssetIdentifier);
						} else {
							if (asset.dataset.importInProcess !== 'true') {
								asset.dataset.importInProcess = 'true';
								const message = window.NeosCMS.I18n.translate(
									'assetImport.importInfo',
									'Asset is being imported. Please wait.',
									'Neos.Media.Browser'
								);
								window.NeosCMS.Notification.ok(message);

								const params = new URLSearchParams();
								params.append('assetSourceIdentifier', asset.dataset.assetSourceIdentifier);
								params.append('assetIdentifier', asset.dataset.assetIdentifier);
								params.append('__csrfToken', document.querySelector('body').dataset.csrfToken);

								fetch(
									document
										.querySelector('link[rel="neos-media-browser-service-assetproxies-import"]')
										.getAttribute('href'),
									{
										headers: {
											'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
										},
										method: 'POST',
										credentials: 'include',
										body: params.toString(),
									}
								)
									.then((response) => {
										if (!response.ok) {
											throw new Error(`HTTP error! status: ${response.status}`);
										}
										return response.json();
									})
									.then((data) => {
										window.parent.NeosMediaBrowserCallbacks.assetChosen(data.localAssetIdentifier);
										asset.removeAttribute('data-import-in-process');
									})
									.catch((error) => console.error('Error:', error))
								e.preventDefault();
							} else {
								const message = window.NeosCMS.I18n.translate(
									'assetImport.importInProcess',
									'Import still in process. Please wait.',
									'Neos.Media.Browser'
								);
								window.NeosCMS.Notification.warning(message);
							}
						}
						e.preventDefault();
					}
				});
			});
		}
	})();
});
