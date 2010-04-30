Ext.ns('F3.TYPO3');

F3.TYPO3.Utils = {};

/**
 * Clone Function
 *
 * @param {Object/Array} o Object or array to clone
 * @return {Object/Array} Deep clone of an object or an array
 * @author Ing. Jozef Sakáloš
 */
F3.TYPO3.Utils.clone = function(o) {
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
};

/**
 * Build an uri for the backend, considering the base URL.
 * In case you want to build an URL to "typo3/login/index", just pass
 * "login/index" to the method (everything after "typo3/")
 * 
 * @param {String} The path to build the URI for. 
 * @author Sebastian Kurfuerst
 */
F3.TYPO3.Utils.buildBackendUri = function(path) {
	return F3.TYPO3.Configuration.Application.backendBaseUri + path;
}