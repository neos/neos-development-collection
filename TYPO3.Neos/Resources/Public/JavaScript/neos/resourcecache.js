/**
 * T3.ResourceCache
 */
define(
[
	'jquery'
],
function($) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/resourcecache');

	var T3 = window.T3 || {};

	var resourceRequests = {};

	function preload(resourceUri) {
		if (resourceRequests[resourceUri] !== undefined) {
			return;
		}

		var xhr;
		var data = (window.sessionStorage ? window.sessionStorage.getItem(resourceUri) : null);
		resourceRequests[resourceUri] = new $.Deferred();
		if (data === null) {
			xhr = $.ajax(resourceUri, {
				dataType: 'text',
				success: function(data) {
					if (window.sessionStorage) {
						window.sessionStorage.setItem(resourceUri, data);
					}
					resourceRequests[resourceUri].resolve(data);
				},
				error: function(xhr, status, error) {
					resourceRequests[resourceUri].reject(xhr, status, error);
				}
			});
		} else {
			resourceRequests[resourceUri].resolve(data);
		}
	}

	/**
	 *
	 * @param {string} resourceUri
	 */
	function get(resourceUri) {
		if (resourceRequests[resourceUri] === undefined) {
			preload(resourceUri);
		}
		return resourceRequests[resourceUri];
	}

	T3.ResourceCache = {
		preload: preload,
		get: get
	};

	window.T3 = T3;
});
