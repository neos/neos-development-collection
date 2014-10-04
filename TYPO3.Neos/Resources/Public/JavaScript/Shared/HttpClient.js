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
	'./RequestManager'
], function(
	Ember,
	$,
	Configuration,
	RequestManager
) {
	return Ember.Object.createWithMixins(Ember.Evented, {
		_failedRequest: null,
		_lastSuccessfulTransfer: null,
		_endpoints: {},

		_getEndpointUrl: function(endpoint) {
			if (!this._endpoints[endpoint]) {
				this._endpoints[endpoint] = $('link[rel="' + endpoint + '"]').attr('href');
			}
			return this._endpoints[endpoint];
		},

		getResource: function(url, optionsOverride) {
			return this._request(url, 'GET', optionsOverride);
		},

		updateResource: function(url, optionsOverride) {
			return this._request(url, 'PUT', optionsOverride);
		},

		createResource: function(url, optionsOverride) {
			return this._request(url, 'POST', optionsOverride);
		},

		deleteResource: function(url, optionsOverride) {
			// TODO: make DELETE method with the full REST implementation
			// For now we can not use DELETE and also pass arguments using the request body,
			// and client side we don\t have a UrlTemplates implementation yet
			return this._request(url, 'POST', optionsOverride);
		},

		_request: function(url, requestMethod, optionsOverride) {
			var that = this,
				promise = Ember.Deferred.create({
					_currentRequest: null,

					abort: function() {
						if (this.get('_currentRequest')) {
							this.get('_currentRequest').abort();
							this.set('_currentRequest', null);
						}
					}
				}),
				options = {
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

			if (window.localStorage.showDevelopmentFeatures) {
				console.log('HttpClient', requestMethod, url, options);
			}

			var xhr = $.ajax(options);
			RequestManager.add(xhr);

			promise.set(
				'_currentRequest',
				xhr.done(function() {
					RequestManager.remove(xhr);
					if (requestMethod === 'POST' || requestMethod === 'PUT') {
						that.set('_lastSuccessfulTransfer', new Date());
					}
					that.set('_failedRequest', false);

					promise.resolve.apply(promise, arguments);
				}).fail(function(jqXHR, textStatus, errorThrown) {
					RequestManager.remove(xhr);
					that.set('_failedRequest', true);
					if (window.localStorage.showDevelopmentFeatures) {
						that.trigger('failure', textStatus, ['requestMethod: ' + requestMethod, 'url: ' + url, 'data: ' + JSON.stringify(options)].join(' '));
					} else {
						that.trigger('failure', textStatus, errorThrown, jqXHR);
					}
					promise.reject.apply(promise, arguments);
				})
			);

			return promise;
		}
	});
});
