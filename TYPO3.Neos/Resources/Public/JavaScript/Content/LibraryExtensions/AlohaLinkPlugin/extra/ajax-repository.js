/**
 * Create the Aloha Repositories object.
 */
define(
[
	'aloha', 'jquery'
],
function (
	Aloha,
	$
) {
	'use strict';

	return Aloha.AbstractRepository.extend({
		endpoint: null,
		workspaceName: null,

		_constructor: function(endpoint, workspaceName) {
			this.endpoint = endpoint;
			this.workspaceName = workspaceName;
			this._super('ajax-repository');
		},

		/**
		 * Searches a repository for repository items matching queryString if none found returns null.
		 * The returned repository items must be an array of Aloha.Repository.Object
		 *
		 * @param {object} p params object with properties
		 * @param {function} callback this method must be called with all resulting repository items
		 * @return {void}
		 */
		query: function(p, callback) {
			$.ajax({
				url: this.endpoint,
				data: {
					searchTerm: p.queryString,
					workspaceName: this.workspaceName
				},
				dataType: 'html'
			}).done(function(rawResponse) {
				var convertedResults = [];
				var container = $('<div />').append(rawResponse);
				container.find('li').each(function() {
					var nodeIdentifier = $(this).data('identifier');
					convertedResults.push({
						'id': nodeIdentifier,
						'name': $(this).text(),
						'url': 'node://' + nodeIdentifier,
						'type': 'node',
						'repositoryId': 'ajax-repository'
					});
				});
				callback.call(this, convertedResults);
			});
		},

		/**
		 * Get the repositoryItem with given id
		 * Callback: {Aloha.Repository.Object} item with given id
		 *
		 * @param {string} itemId Id of the repository item to fetch
		 * @param {function} callback Callback function
		 * @return {void}
		 */
		getObjectById: function(itemId, callback) {
			$.ajax({
				url: this.endpoint + '/' + itemId,
				data: {
					workspaceName: this.workspaceName
				},
				dataType: 'html'
			}).done(function(rawResponse) {
				var convertedResult = [];
				var nodeContainer = $('<div />').append(rawResponse).find('div');
				convertedResult.push({
					'id': nodeContainer.data('identifier'),
					'name': nodeContainer.text(),
					'url': 'node://' + nodeContainer.data('identifier'),
					'type': 'node',
					'repositoryId': 'ajax-repository'
				});
				callback.call(this, convertedResult);
			});
		},

		/**
		 * Mark or modify an object as needed by that repository for handling, processing or identification.
		 * Objects can be any DOM object as A, SPAN, ABBR, etc..
		 * (see http://dev.w3.org/html5/spec/elements.html#embedding-custom-non-visible-data)
		 *
		 * @param {object} obj jQuery object to make clean
		 * @param {object} repositoryItem
		 * @return {void}
		 */
		markObject: function(obj, repositoryItem) {
			$(obj).attr('data-ajax-repository-temporary-data', repositoryItem.name);
		},

		/**
		 * Make the given jQuery object (representing an object marked as object of this type)
		 * clean. All attributes needed for handling should be removed.
		 *
		 * @param {object} obj jQuery object to make clean
		 * @return {void}
		 */
		makeClean: function(obj) {
			$(obj).removeAttr('data-ajax-repository-temporary-data');
		}
	});
});