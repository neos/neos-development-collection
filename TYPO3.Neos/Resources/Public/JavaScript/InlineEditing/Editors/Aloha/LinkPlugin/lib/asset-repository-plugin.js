define( [
	'aloha',
	'jquery',
	'aloha/plugin',
	'asset-repository/../extra/asset-repository'
], function (Aloha, $, Plugin, AssetRepository) {
	/**
	 * Register the plugin with unique name
	 */
	return Plugin.create('asset-repository-plugin', {
		init: function () {
			new AssetRepository();
		},

		/**
		 * @return string
		 */
		toString: function () {
			return 'asset-repository-plugin';
		}
	});
});