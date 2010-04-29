Ext.ns("F3.TYPO3.Login");

/**
 * @class F3.TYPO3.Login.Bootstrap
 * @namespace F3.TYPO3.Login
 * @extends F3.TYPO3.Application.AbstractBootstrap
 *
 * Bootstrap for the login view
 */

F3.TYPO3.Login.Bootstrap =
	Ext.apply(new F3.TYPO3.Application.AbstractBootstrap, {

		/**
		 * @method initialize
		 * @return void
		 */
		initialize: function() {
			F3.TYPO3.Application.on(
				'F3.TYPO3.Application.afterBootstrap',
				this.initViewport,
				this
			);
		},

		/**
		 * @method initViewport
		 * @return void
		 */
		initViewport: function() {
			F3.TYPO3.Login.viewport = new F3.TYPO3.Login.Layout();
		}
	});

F3.TYPO3.Application.registerBootstrap(F3.TYPO3.Login.Bootstrap);