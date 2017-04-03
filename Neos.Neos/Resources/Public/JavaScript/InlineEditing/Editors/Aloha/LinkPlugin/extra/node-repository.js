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
		siteNode: null,

		_constructor: function(workspaceName, dimensions, siteNode) {
			this.workspaceName = workspaceName;
			this.dimensions = dimensions;
			this.siteNode = siteNode;
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
				dimensions: this.dimensions,
				contextNode: this.siteNode
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
			require({context: 'neos'}, ['Shared/HttpRestClient', 'Shared/NodeTypeService', 'Shared/Utility'], function(HttpRestClient, NodeTypeService, Utility) {
				HttpRestClient.getResource('neos-service-nodes', null, {data: that.getQueryRequestData(params.queryString)}).then(function(result) {
					var convertedResults = [];
					$.each($('.nodes', result.resource).children('li'), function() {
						var nodeIdentifier = $('.node-identifier', this).text();
						convertedResults.push({
							'id': nodeIdentifier,
							'__icon': that.getResultIcon($(this), NodeTypeService),
							'__path': '<br />' + (Utility.removeContextPath($('.node-frontend-uri', this).text().trim()) || '/'),
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
			require({context: 'neos'}, ['Shared/HttpRestClient', 'Shared/NodeTypeService', 'Shared/Utility'], function(HttpRestClient, NodeTypeService, Utility) {
				HttpRestClient.getResource('neos-service-nodes', itemId, {data: that.getObjectQueryRequestData()}).then(function(result) {
					var $node = $('.node', result.resource),
						uri = (Utility.removeContextPath($('.node-frontend-uri', result.resource).text().trim()) || '/');
					callback.call(this, [{
						'id': $('.node-identifier', $node).text(),
						'__icon': that.getResultIcon($node, NodeTypeService),
						'__path': '<br />' + uri,
						'__thumbnail': '',
						'name': $('.node-label', $node).text().trim() + ' (' + uri + ')',
						'url': that._type + '://' + $('.node-identifier', $node).text(),
						'type': that._type,
						'repositoryId': that._repositoryIdentifier
					}]);
				});
			});
		}
	});
});
