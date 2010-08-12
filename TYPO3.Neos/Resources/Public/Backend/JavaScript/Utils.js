Ext.ns('F3.TYPO3');

/**
 * @class F3.TYPO3.Utils
 * @namespace F3.TYPO3
 * @extends Object
 * 
 * Utility functions to use.
 * 
 * @singleton
 */

F3.TYPO3.Utils = {
	/**
	 * Clone Function
	 *
	 * @param {Object/Array} o Object or array to clone
	 * @return {Object/Array} Deep clone of an object or an array
	 * @author Ing. Jozef Sakáloš
	 */
	clone: function(o) {
		if (!o || 'object' !== typeof o) {
			return o;
		}
		if ('function' === typeof o.clone) {
			return o.clone();
		}
		var c = '[object Array]' === Object.prototype.toString.call(o) ? [] : {};
		var p, v;
		for (p in o) {
			if (o.hasOwnProperty(p)) {
				v = o[p];
				if (v && 'object' === typeof v) {
					c[p] = F3.TYPO3.Utils.clone(v);
				} else {
					c[p] = v;
				}
			}
		}
		return c;
	},
	
	each: function(object, callback, scope) {
		var p;
		for (p in object) {
			if (object.hasOwnProperty(p)) {
				v = object[p];
				callback.call(scope, v, p);
			}
		}
	},

	/**
	 * Build an uri for the backend, considering the base URL.
	 * In case you want to build an URL to "typo3/login/index", just pass
	 * "login/index" to the method (everything after "typo3/")
	 * 
	 * @param {String} path The path to build the URI for.
	 * @return {String} the full backend URI.
	 */
	buildBackendUri: function(path) {
		return F3.TYPO3.Configuration.Application.backendBaseUri + path;
	}
};