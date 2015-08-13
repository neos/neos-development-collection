/**
 * ResourceCache
 */
define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'./SessionStorage'
],
function(Ember, $, SessionStorage) {
	/**
	 * @singleton
	 */
	return Ember.Object.extend({
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

			var xhr,
				data = SessionStorage.getItem(resourceUri);
			resourceRequests[resourceUri] = new $.Deferred();
			if (data === null) {
				xhr = $.ajax(resourceUri, {
					dataType: 'json',
					success: function(data) {
						SessionStorage.setItem(resourceUri, data);
						resourceRequests[resourceUri].resolve(data);
					},
					error: function(xhr, status, error) {
						resourceRequests[resourceUri].reject(xhr, status, error);
					}
				});
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
	}).create();
});