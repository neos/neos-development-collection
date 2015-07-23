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
				$metaInformation.data('neos-context-workspace-name'),
				$metaInformation.data('neos-context-dimensions'),
				$metaInformation.data('neos-site-node-context-path')
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