/**
 * Create the Aloha Repositories object.
 */
define(
	[ 'aloha', 'jquery', 'asset-repository/../extra/asset-repository' ],
	function (Aloha, $, AssetRepository) {
		'use strict';

		var Repository = AssetRepository.extend({

			_type: 'node',
			_repositoryIdentifier: 'node-repository',

			workspaceName: null,

			_constructor: function (endpoint, workspaceName) {
				this.workspaceName = workspaceName;
				this._super(endpoint);
			},

			getResultPath: function($result) {
				if ($result.data('path')) {
					return '<br/>' + $result.data('path').replace(/^\/sites\/[^\/]*/, '');
				}
				return '';
			},

			getQueryRequestData: function(searchTerm) {
				return {
					searchTerm: searchTerm,
					workspaceName: this.workspaceName
				}
			},

			getObjectQueryRequestData: function() {
				return {workspaceName: this.workspaceName};
			}

		});

		return Repository;

	});