define([
	'aloha',
	'jquery',
	'aloha/plugin',
	'node-repository/../extra/node-repository'
], function ( Aloha, $, Plugin, Repository ) {

	/**
	 * Register the plugin with unique name
	 */
	return Plugin.create('node-repository-plugin', {

		init: function () {
			$metaInformation = $('#neos-document-metadata');
			new Repository(
				$('link[rel="neos-service-nodes"]').attr('href'),
				$metaInformation.attr('data-context-__workspacename'),
				$metaInformation.data('context-__dimensions')
			);
		},

		/**
		 * toString method
		 * @return string
		 */
		toString: function () {
			return 'node-repository-plugin';
		}

	});

});