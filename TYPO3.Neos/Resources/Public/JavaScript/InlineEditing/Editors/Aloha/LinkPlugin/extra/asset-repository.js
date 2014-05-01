/**
 * Create the Aloha Repositories object.
 */
define(
	[ 'aloha', 'jquery' ],
	function (Aloha, $) {
		'use strict';

		var Repository = Aloha.AbstractRepository.extend({

			_type: 'asset',
			_repositoryIdentifier: 'asset-repository',

			endpoint: null,

			_constructor: function (endpoint) {
				this.endpoint = endpoint;
				this._super(this._repositoryIdentifier);
			},

			getResultIcon: function($result) {
				if ($result.data('icon')) {
					return '<i class="' + $result.data('icon') + '"></i> ';
				}
				return '';
			},

			getResultPath: function($result) {
				return '';
			},

			getQueryRequestData: function(searchTerm) {
				return {
					searchTerm: searchTerm
				}
			},

			getObjectQueryRequestData: function() {
				return {};
			},

			/**
			 * Searches a repository for repository items matching queryString if none found returns null.
			 * The returned repository items must be an array of Aloha.Repository.Object
			 *
			 * @param {object} params object with properties
			 * @param {function} callback this method must be called with all resulting repository items
			 */
			query: function (p, callback) {
				var that = this;

				$.ajax({
					url: this.endpoint,
					data: this.getQueryRequestData(p.queryString),
					dataType: 'html'
				}).done(function(rawResponse) {
					var convertedResults = [],
						container = $('<div />').append(rawResponse);
					container.find('li').each(function() {
						var assetIdentifier = $(this).data('identifier');

						convertedResults.push({
							'id': assetIdentifier,
							'__icon': that.getResultIcon($(this)),
							'__path': that.getResultPath($(this)),
							'name': $(this).text(),
							'url': that._type + '://' + assetIdentifier,
							'type': that._type,
							'repositoryId': that._repositoryIdentifier
						});
					});
					callback.call(this, convertedResults);
				});
			},

			/**
			 * Get the repositoryItem with given id
			 * Callback: {Aloha.Repository.Object} item with given id
			 * @param itemId {String} id of the repository item to fetch
			 * @param callback {function} callback function
			 */
			getObjectById: function (itemId, callback) {
				var that = this;
				$.ajax({
					url: this.endpoint + '/' + itemId,
					data: this.getObjectQueryRequestData(),
					dataType: 'html'
				}).done(function(rawResponse) {
					var convertedResult = [],
						assetContainer = $('<div />').append(rawResponse).find('div');
					convertedResult.push({
						'id': assetContainer.data('identifier'),
						'name': assetContainer.text(),
						'url': that._type + '://' + assetContainer.data('identifier'),
						'type': that._type,
						'repositoryId': that._repositoryIdentifier
					});
					callback.call(this, convertedResult);
				});
			},

			/**
			 * Mark or modify an object as needed by that repository for handling, processing or identification.
			 * Objects can be any DOM object as A, SPAN, ABBR, etc..
			 * (see http://dev.w3.org/html5/spec/elements.html#embedding-custom-non-visible-data)
			 * @param obj jQuery object to make clean
			 * @return void
			 */
			markObject: function (obj, repositoryItem) {
				$(obj).attr('data-' + this._repositoryIdentifier + '-temporary-data', repositoryItem.name);
			},

			/**
			 * Make the given jQuery object (representing an object marked as object of this type)
			 * clean. All attributes needed for handling should be removed.
			 * @param {jQuery} obj jQuery object to make clean
			 * @return void
			 */
			makeClean: function (obj) {
				$(obj).removeAttr('data-' + this._repositoryIdentifier + '-temporary-data');
			}

		});

		return Repository;

	});