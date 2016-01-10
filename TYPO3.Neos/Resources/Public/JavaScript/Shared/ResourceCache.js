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

			resourceRequests[resourceUri] = new Ember.RSVP.Promise(function(resolve, reject) {
				if (data === null) {
					HttpClient.getResource(resourceUri, {dataType: 'json'}).then(
						function(data) {
							SessionStorage.setItem(resourceUri, data);
							resolve(data);
						},
						function() {
							reject(arguments);
						}
					);
				} else {
					resolve(data);
				}
			});
		},

		/**
		 * @param {string} resourceUri
		 * @return {Ember.Deferred}
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