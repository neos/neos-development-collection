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
			new Repository(
				$('link[rel="neos-nodes"]').attr('href'),
				$('meta[name="neos-workspace"]').attr('content')
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