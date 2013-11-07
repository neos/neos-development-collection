/**
 * ResourceCache
 */
define(
[
	'emberjs',
	'./SessionStorage',
	'Shared/HttpClient'
],
function(Ember, SessionStorage, HttpClient) {
	/**
	 * @singleton
	 */
	return Ember.Object.create({
		resourceRequests: {},

		/**
		 * @param {string} resourceUri
		 * @return {void}
		 */
		fetch: function(resourceUri) {
			var resourceRequests = this.get('resourceRequests');
			if (resourceRequests[resourceUri] !== undefined) {
				return;
			}

			var data = SessionStorage.getItem(resourceUri);
			resourceRequests[resourceUri] = Ember.Deferred.create();
			if (data === null) {
				HttpClient.getResource(resourceUri, {dataType: 'json'}).then(
					function(data) {
						SessionStorage.setItem(resourceUri, data);
						resourceRequests[resourceUri].resolve(data);
					},
					function() {
						resourceRequests[resourceUri].reject(arguments);
					}
				);
			} else {
				resourceRequests[resourceUri].resolve(data);
			}
		},

		/**
		 * @param {string} resourceUri
		 * @return {mixed}
		 */
		getItem: function(resourceUri) {
			var resourceRequests = this.get('resourceRequests');
			if (typeof resourceRequests[resourceUri] === 'undefined') {
				this.fetch(resourceUri);
			}
			return resourceRequests[resourceUri];
		}
	});
});