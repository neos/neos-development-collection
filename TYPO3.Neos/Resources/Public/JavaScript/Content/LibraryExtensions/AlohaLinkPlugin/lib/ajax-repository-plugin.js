define( [
	'aloha',
	'jquery',
	'aloha/plugin',
	'ajax-repository/../extra/ajax-repository'
], function ( Aloha, $, Plugin, Repository ) {

	/**
	 * register the plugin with unique name
	 */
	return Plugin.create( 'ajax-repository-plugin', {

		init: function () {
			var endpointLink = $('link[type="application/vnd.typo3.neos.nodes"]');
			var repository = new Repository(endpointLink.attr('href'), endpointLink.data('current-workspace'));
		},

		/**
		* toString method
		* @return string
		*/
		toString: function () {
			return 'ajax-repository-plugin';
		}

	} );

} );