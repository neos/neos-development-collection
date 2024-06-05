window.addEventListener('DOMContentLoaded', () => {
	(function () {
		const NeosMediaBrowserCallbacks = window.parent.NeosMediaBrowserCallbacks;
		const NeosCMS = window.NeosCMS;

		if (window.parent === window || !NeosCMS || !NeosMediaBrowserCallbacks || typeof NeosMediaBrowserCallbacks.assetChosen !== 'function') {
			return;
		}

		function importAsset(asset) {
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
					NeosMediaBrowserCallbacks.assetChosen(data.localAssetIdentifier);
					asset.removeAttribute('data-import-in-process');
				})
				.catch((error) => {
					NeosCMS.Notification.error(NeosCMS.I18n.translate(
						'assetImport.importError',
						'Asset could not be imported. Please try again.',
						'Neos.Media.Browser'
					), error);
					console.error('Error:', error);
				})
		}

		const assets = document.querySelectorAll('[data-asset-identifier]');
		assets.forEach((asset) => {
			asset.addEventListener('click', (e) => {
				const assetLink = e.target.closest('a[data-asset-identifier], button[data-asset-identifier]');
				if (!assetLink) {
					return;
				}

				const localAssetIdentifier = asset.dataset.localAssetIdentifier;
				if (localAssetIdentifier !== '' && !NeosCMS.isNil(localAssetIdentifier)) {
					NeosMediaBrowserCallbacks.assetChosen(localAssetIdentifier);
				} else {
					if (asset.dataset.importInProcess !== 'true') {
						asset.dataset.importInProcess = 'true';
						const message = NeosCMS.I18n.translate(
							'assetImport.importInfo',
							'Asset is being imported. Please wait.',
							'Neos.Media.Browser'
						);
						NeosCMS.Notification.ok(message);

						importAsset(asset);
					} else {
						const message = NeosCMS.I18n.translate(
							'assetImport.importInProcess',
							'Import still in process. Please wait.',
							'Neos.Media.Browser'
						);
						NeosCMS.Notification.warning(message);
					}
				}
				e.preventDefault();
			});
		});
	})();
});
