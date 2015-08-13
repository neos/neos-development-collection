define(
['aloha/plugin', 'neosintegration/block', 'block/blockmanager', 'require'],
function(Plugin, block, BlockManager, require) {
	"use strict";

	var NeosLinksPlugin = Plugin.create('neos-links', {
		dependencies: ['block'],

		/**
		 * Aloha Plugin Lifecycle method
		 */
		init: function() {

			new ( Aloha.AbstractRepository.extend( {

				_constructor: function () {
					this._super( 'neosLinksRepository' );
				},

				/**
				 * Searches a repository for object items matching queryString if none found returns null.
				 */
				query: function ( p, callback ) {
					var that = this;
					TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.searchPage(p.queryString, function(result) {
						callback.call(that, result.searchResult);
					});
				},

				/**
				 * Returns all children of a given motherId.
				 *
				 * @param {object} params object with properties
				 * @property {array} objectTypeFilter OPTIONAL Object types that will be returned.
				 * @property {array} filter OPTIONAL Attributes that will be returned.
				 * @property {string} inFolderId OPTIONAL his is a predicate function that tests whether or not a candidate object is a child-object of the folder object identified by the given inFolderId (objectId).
				 * @property {array} orderBy OPTIONAL ex. [{lastModificationDate:’DESC’}, {name:’ASC’}]
				 * @property {Integer} maxItems OPTIONAL number items to return as result
				 * @property {Integer} skipCount OPTIONAL This is tricky in a merged multi repository scenario
				 * @property {array} renditionFilter OPTIONAL Instead of termlist an array of kind or mimetype is expected. If null or array.length == 0 all renditions are returned. See http://docs.oasis-open.org/cmis/CMIS/v1.0/cd04/cmis-spec-v1.0.html#_Ref237323310 for renditionFilter
				 * @param {function} callback this method must be called with all result items
				 */
				getChildren: function ( p, callback ) {
					callback.call( this, []);
				},

				/**
				 * Get the repositoryItem with given id
				 * Callback: {Aloha.Repository.Object} item with given id
				 * @param itemId {String} id of the repository item to fetch
				 * @param callback {function} callback function
				 */
				getObjectById: function ( itemId, callback ) {
					var that = this;
					TYPO3_Neos_Service_ExtDirect_V1_Controller_NodeController.getPageByNodePath(itemId, function(result) {
						result = result.node;
						result.repositoryId = 'neosLinksRepository';
						callback.call(that, [result]);
					});
				}
			}))();
		},

		destroy: function() {
		}
	});
	// We need a global reference here to call the start() method on the neos plugin
	// from the contentmodule bootstrap
	return NeosLinksPlugin;
});