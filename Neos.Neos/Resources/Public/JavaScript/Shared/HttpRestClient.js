/**
 * HTTP service for communication with the server-side backend.
 *
 * This is the new REST service which should be used by all endpoints after migrating them to REST style.
 * See https://jira.typo3.org/browse/NEOS-190 for the current state and to-dos regarding this migration.
 */
define([
	'emberjs',
	'Shared/HttpClient',
	'Library/jquery-with-dependencies'
], function(
	Ember,
	HttpClient,
	$
) {
	return Ember.Object.extend(HttpClient, {
		/**
		 * Retrieve a resource from the REST service
		 *
		 * @param {string} endpointName Name of the endpoint, for example "neos-service-nodes"
		 * @param {string} resourceIdentifier An optional resource identifier, format depends on the actual service endpoint
		 * @param {object} optionsOverride Additional options to send with the request. Valid keys for options are: "type", "url" and "data"
		 * @returns {Promise} A promise which is invoked when the service call is done
		 */
		getResource: function(endpointName, resourceIdentifier, optionsOverride) {
			return this._request(this._getEndpointUrl(endpointName) + (resourceIdentifier ? '/' + resourceIdentifier : ''), 'GET', optionsOverride);
		},

		/**
		 * Update a resource through the REST service
		 *
		 * @param {string} endpointName Name of the endpoint, for example "neos-service-nodes"
		 * @param {string} resourceIdentifier A mandatory resource identifier, format depends on the actual service endpoint
		 * @param {object} optionsOverride Additional options to send with the request, especially the properties to update. Valid keys for options are: "type", "url" and "data"
		 * @returns {Promise} A promise which is invoked when the service call is done
		 */
		updateResource: function(endpointName, resourceIdentifier, optionsOverride) {
			return this._request(this._getEndpointUrl(endpointName) + (resourceIdentifier ? '/' + resourceIdentifier : ''), 'PUT', optionsOverride);
		},

		/**
		 * Create a new resource through the REST service
		 *
		 * @param {string} endpointName Name of the endpoint, for example "neos-service-nodes"
		 * @param {object} optionsOverride Additional options to send with the request, usually data for the new resource. Valid keys for options are: "type", "url" and "data"
		 * @returns {Promise} A promise which is invoked when the service call is done
		 */
		createResource: function(endpointName, optionsOverride) {
			return this._request(this._getEndpointUrl(endpointName), 'POST', optionsOverride);
		},

		/**
		 * Delete an existing resource through the REST service
		 *
		 * @param {string} endpointName Name of the endpoint, for example "neos-service-nodes"
		 * @param {string} resourceIdentifier A mandatory resource identifier, format depends on the actual service endpoint
		 * @param {object} optionsOverride Additional options to send with the request. Valid keys for options are: "type", "url" and "data"
		 * @returns {Promise} A promise which is invoked when the service call is done
		 */
		deleteResource: function(endpointName, resourceIdentifier, optionsOverride) {
			// TODO: make DELETE method with the full REST implementation
			// For now we can not use DELETE and also pass arguments using the request body,
			// and client side we don\t have a UrlTemplates implementation yet
			window.console.log('HttpRestClient: deleteResource() is not implemented yet, see code for more information.');
			return;
		},

		_success: function(resolve, data, textStatus, xhr) {
			resolve({
				'resource': $.parseHTML(data),
				'xhr': xhr
			});
		}
	}).create();
});