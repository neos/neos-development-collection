define([
	'aloha',
	'jquery',
	'aloha/plugin',
	'node-repository/../extra/node-repository'
], function (Aloha, $, Plugin, NodeRepository) {
	/**
	 * Register the plugin with unique name
	 */
	return Plugin.create('node-repository-plugin', {
		init: function () {
			var $metaInformation = $('#neos-document-metadata');
			new NodeRepository(
				$metaInformation.data('context-__workspacename'),
				$metaInformation.data('context-__dimensions')
			);
		},

		/**
		 * @return string
		 */
		toString: function () {
			return 'node-repository-plugin';
		}
	});
});