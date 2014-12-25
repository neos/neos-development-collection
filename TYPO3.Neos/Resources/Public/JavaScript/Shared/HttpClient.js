/**
 * HTTP service for communication with the server-side backend.
 *
 * This is the OLD service which exists for backwards compatibility with not yet refactored endpoints.
 * See https://jira.typo3.org/browse/NEOS-190 for the current state and to-dos regarding this migration.
 */
define([
	'emberjs',
	'Library/jquery-with-dependencies',
	'./Configuration',
	'./RequestManager',
	'Content/Components/LoginDialog'
], function(
	Ember,
	$,
	Configuration,
	RequestManager,
	LoginDialog
) {
	return Ember.Object.createWithMixins(Ember.Evented, {
		_failedRequest: null,
		_lastSuccessfulTransfer: null,
		_endpoints: {},

		/**
		 * Determines the URL of the endpoint specified by the given endpoint name
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
		 * Retrieve a resource from the url
		 *
		 * @param {string} url Url for resource
		 * @param {object} optionsOverride Additional options to send with the request
		 * @returns {Promise} A promise which is invoked when the service call is done
		 */
		getResource: function(url, optionsOverride) {
			return this._request(url, 'GET', optionsOverride);
		},

		/**
		 * Update a resource through the url
		 *
		 * @param {string} url Url for resource
		 * @param {object} optionsOverride Additional options to send with the request, especially the properties to update
		 * @returns {Promise} A promise which is invoked when the service call is done
		 */
		updateResource: function(url, optionsOverride) {
			return this._request(url, 'PUT', optionsOverride);
		},

		/**
		 * Create a new resource through the url
		 *
		 * @param {string} url Url for resource
		 * @param {object} optionsOverride Additional options to send with the request, usually data for the new resource
		 * @returns {Promise} A promise which is invoked when the service call is done
		 */
		createResource: function(url, optionsOverride) {
			return this._request(url, 'POST', optionsOverride);
		},

		/**
		 * Delete an existing resource through the url
		 *
		 * @param {string} url Url for resource
		 * @param {object} optionsOverride Additional options to send with the request
		 * @returns {Promise} A promise which is invoked when the service call is done
		 */
		deleteResource: function(url, optionsOverride) {
			// TODO: make DELETE method with the full REST implementation
			// For now we can not use DELETE and also pass arguments using the request body,
			// and client side we don\t have a UrlTemplates implementation yet
			return this._request(url, 'POST', optionsOverride);
		},

		/**
		 * Internal function which executes an Ajax request to the endpoint
		 *
		 * @param {string} url The absolute URL of the service endpoint
		 * @param {string} requestMethod The HTTP request method
		 * @param {object} optionsOverride Options to send as query parameters or body arguments
		 * @returns {Promise} An RSVP promise
		 * @private
		 */
		_request: function(url, requestMethod, optionsOverride) {
			var that = this,
				isSafeRequest = (requestMethod === 'GET' || requestMethod === 'HEAD'),
				options = {
					type: requestMethod,
					url: url,
					data: {}
				};

			if (optionsOverride) {
				$.extend(options, optionsOverride);
			}

			if (!isSafeRequest) {
				options.data.__csrfToken = Configuration.get('CsrfToken');
			}

			if (window.localStorage.showDevelopmentFeatures) {
				console.log('HttpClient', requestMethod, url, options);
			}

			var request,
				promise = Ember.RSVP.Promise(function(resolve, reject) {
					options = $.extend(options, {
						success: function(data, textStatus, xhr) {
							if (!isSafeRequest) {
								RequestManager.remove(xhr);
							} else {
								that.set('_lastSuccessfulTransfer', new Date());
							}
							that.set('_failedRequest', false);
							that._success(resolve, data, textStatus, xhr);
						},
						error: function(xhr, textStatus, errorThrown) {
							if (!isSafeRequest) {
								RequestManager.remove(xhr);
							}
							if (xhr.status === 401) {
								LoginDialog.show(function() {
									if (isSafeRequest) {
										options.data.__csrfToken = Configuration.get('CsrfToken');
									} else {
										RequestManager.add($.ajax(options));
									}
								});
							} else {
								that.set('_failedRequest', true);
								that.trigger('failure', xhr, textStatus, errorThrown);
								that._fail(reject, xhr, textStatus, errorThrown);
							}
						}
					});
					request = $.ajax(options);
					if (!isSafeRequest) {
						RequestManager.add(request);
					}

					if (window.localStorage.showDevelopmentFeatures) {
						window.console.log('HttpRestClient: _request() sent', requestMethod, url, options);
					}
				});

			promise.abort = function() {
				request.abort();
			};

			return promise;
		},

		_success: function(resolve, data, textStatus, xhr) {
			resolve(data);
		},

		_fail: function(reject, xhr, textStatus, errorThrown) {
			reject({
				'xhr': xhr,
				'status': textStatus,
				'message': errorThrown
			});
		}
	});
});
