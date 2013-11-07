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
			var repository = new Repository(
				$('link[rel="neos-nodes"]').attr('href'),
				$('meta[name="neos-workspace"]').attr('content')
			);
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