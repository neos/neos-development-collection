define( [
	'aloha',
	'jquery',
	'aloha/plugin',
	'asset-repository/../extra/asset-repository'
], function ( Aloha, $, Plugin, Repository ) {

	/**
	 * register the plugin with unique name
	 */
	return Plugin.create('asset-repository-plugin', {

		init: function () {
			new Repository(
				$('link[rel="neos-assets"]').attr('href')
			);
		},

		/**
		 * toString method
		 * @return string
		 */
		toString: function () {
			return 'asset-repository-plugin';
		}

	});

});