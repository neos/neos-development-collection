/**
 * HTTP service for communication with the server-side backend.
 *
 * This is the new REST service which should be used by all endpoints after migrating them to REST style.
 * See https://jira.typo3.org/browse/NEOS-190 for the current state and to-dos regarding this migration.
 */
define([
	'emberjs',
	'Library/jquery-with-dependencies',
	'./Configuration',
	'./RequestManager'
], function(
	Ember,
	$,
	Configuration,
	RequestManager
) {
	return Ember.Object.createWithMixins(Ember.Evented, {
		_endpoints: {},
		_responseStatus: null,

		/**
		 * Determines the URL of the REST service endpoint specified by the given endpoint name
		 *
		 * @param {string} endpoint For example "neos-service-nodes"
		 * @returns {string} URL of the specified endpoint
		 * @private
		 */
		_getEndpointUrl: function(endpoint) {
			if (!this._endpoints[endpoint]) {
				this._endpoints[endpoint] = $('link[rel="' + endpoint + '"]').attr('href');
			}
			return this._endpoints[endpoint];
		},

		/**
		 * Retrieve a resource from the REST service
		 *
		 * @param {string} endpointName Name of the endpoint, for example "neos-service-nodes"
		 * @param {string} resourceIdentifier An optional resource identifier, format depends on the actual service endpoint
		 * @param {object} optionsOverride Additional options to send with the request
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
		 * @param {object} optionsOverride Additional options to send with the request, especially the properties to update
		 * @returns {Promise} A promise which is invoked when the service call is done
		 */
		updateResource: function(endpointName, resourceIdentifier, optionsOverride) {
			return this._request(this._getEndpointUrl(endpointName) + (resourceIdentifier ? '/' + resourceIdentifier : ''), 'PUT', optionsOverride);
		},

		/**
		 * Create a new resource through the REST service
		 *
		 * @param {string} endpointName Name of the endpoint, for example "neos-service-nodes"
		 * @param {object} optionsOverride Additional options to send with the request, usually data for the new resource
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
		 * @param {object} optionsOverride Additional options to send with the request
		 * @returns {Promise} A promise which is invoked when the service call is done
		 */
		deleteResource: function(endpointName, resourceIdentifier, optionsOverride) {
			// TODO: make DELETE method with the full REST implementation
			// For now we can not use DELETE and also pass arguments using the request body,
			// and client side we don\t have a UrlTemplates implementation yet
			window.console.log('HttpRestClient: deleteResource() is not implemented yet, see code for more information.');
			return;
		},

		/**
		 * Internal function which executes an Ajax request to the REST endpoint
		 *
		 * @param {string} url The absolute URL of the service endpoint
		 * @param {string} requestMethod The HTTP request method
		 * @param {object} optionsOverride Options to send as query parameters or body arguments
		 * @returns {Promise} An RSVP promise
		 * @private
		 */
		_request: function(url, requestMethod, optionsOverride) {
			var options = {
					type: requestMethod,
					url: url,
					data: {}
				};

			if (optionsOverride) {
				$.extend(options, optionsOverride);
			}

			if (requestMethod !== 'GET' && requestMethod !== 'HEAD') {
				options.data.__csrfToken = Configuration.get('CsrfToken');
			}

			return new Ember.RSVP.Promise(function(resolve, reject) {
				var xhr = $.ajax(options);
				RequestManager.add(xhr);

				xhr.done(function(data) {
					RequestManager.remove(xhr);
					resolve({
						'resource': $.parseHTML(data),
						'xhr': xhr
					});
				});

				xhr.fail(function(xhr, textStatus, errorThrown) {
					RequestManager.remove(xhr);
					reject({
						'error': errorThrown,
						'xhr': xhr
					});
				});

				if (window.localStorage.showDevelopmentFeatures) {
					window.console.log('HttpRestClient: _request() sent', requestMethod, url, options);
				}
			});
		}
	});
});