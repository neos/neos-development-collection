/**
 * Create the Aloha Repositories object.
 */
define(
[
	'aloha',
	'jquery'
], function(
	Aloha,
	$
) {
	return Aloha.AbstractRepository.extend({
		_type: 'node',
		_repositoryIdentifier: 'node-repository',

		workspaceName: null,
		dimensions: null,

		_constructor: function(workspaceName, dimensions) {
			this.workspaceName = workspaceName;
			this.dimensions = dimensions;
			this._super(this._repositoryIdentifier);
		},

		getResultIcon: function($result, NodeTypeService) {
			var iconClass = NodeTypeService.getNodeTypeDefinition($('.node-type', $result).text()).ui.icon;
			return '<i class="' + (iconClass ? iconClass : 'icon-file') + '"></i>';
		},

		getQueryRequestData: function(searchTerm) {
			return {
				searchTerm: searchTerm,
				workspaceName: this.workspaceName,
				dimensions: this.dimensions
			};
		},

		getObjectQueryRequestData: function() {
			return {
				workspaceName: this.workspaceName,
				dimensions: this.dimensions
			};
		},

		/**
		 * Searches a repository for repository items matching queryString if none found returns null.
		 * The returned repository items must be an array of Aloha.Repository.Object
		 *
		 * @param {object} params object with properties
		 * @param {function} callback this method must be called with all resulting repository items
		 */
		query: function(params, callback) {
			var that = this;
			require({context: 'neos'}, ['Shared/HttpRestClient', 'Shared/NodeTypeService'], function(HttpRestClient, NodeTypeService) {
				HttpRestClient.getResource('neos-service-nodes', null, {data: that.getQueryRequestData(params.queryString)}).then(function(result) {
					var convertedResults = [];
					$.each($('.nodes', result.resource).children('li'), function() {
						var nodeIdentifier = $('.node-identifier', this).text();
						convertedResults.push({
							'id': nodeIdentifier,
							'__icon': that.getResultIcon($(this), NodeTypeService),
							'__path': '<br />' + ($('.node-path', this).text().replace(/^\/sites\/[^\/]*/, '') || '/'),
							'__thumbnail': '',
							'name': $('.node-label', this).text().trim(),
							'url': that._type + '://' + nodeIdentifier,
							'type': that._type,
							'repositoryId': that._repositoryIdentifier
						});
					});
					callback.call(this, convertedResults);
				});
			});
		},

		/**
		 * Get the repositoryItem with given id
		 * Callback: {Aloha.Repository.Object} item with given id
		 *
		 * @param itemId {String} id of the repository item to fetch
		 * @param callback {function} callback function
		 */
		getObjectById: function(itemId, callback) {
			var that = this;
			require({context: 'neos'}, ['Shared/HttpRestClient', 'Shared/NodeTypeService'], function(HttpRestClient, NodeTypeService) {
				HttpRestClient.getResource('neos-service-nodes', itemId, {data: that.getObjectQueryRequestData()}).then(function(result) {
					var $node = $('.node', result.resource),
						path = ($('.node-path', $node).text().replace(/^\/sites\/[^\/]*/, '') || '/');
					callback.call(this, [{
						'id': $('.node-identifier', $node).text(),
						'__icon': that.getResultIcon($node, NodeTypeService),
						'__path': '<br />' + path,
						'__thumbnail': '',
						'name': $('.node-label', $node).text().trim() + ' (' + path + ')',
						'url': that._type + '://' + $('.node-identifier', $node).text(),
						'type': that._type,
						'repositoryId': that._repositoryIdentifier
					}]);
				});
			});
		},

		/**
		 * Mark or modify an object as needed by that repository for handling, processing or identification.
		 * Objects can be any DOM object as A, SPAN, ABBR, etc..
		 *
		 * (see http://dev.w3.org/html5/spec/elements.html#embedding-custom-non-visible-data)
		 * @param obj jQuery object to make clean
		 * @return void
		 */
		markObject: function(obj, repositoryItem) {
			$(obj).attr('data-' + this._repositoryIdentifier + '-temporary-data', repositoryItem.name);
		},

		/**
		 * Make the given jQuery object (representing an object marked as object of this type)
		 * clean. All attributes needed for handling should be removed.
		 *
		 * @param {jQuery} obj jQuery object to make clean
		 * @return void
		 */
		makeClean: function(obj) {
			$(obj).removeAttr('data-' + this._repositoryIdentifier + '-temporary-data');
		}
	});
});