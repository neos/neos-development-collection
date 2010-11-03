Ext.ns('F3.TYPO3');

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @class F3.TYPO3.Utils
 * 
 * Utility functions to use.
 *
 * @namespace F3.TYPO3
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

	/**
	 * Iterate over all properties of an object
	 *
	 * @param {Object} object
	 * @param {Function} callback
	 * @param {Object} scope
	 * @return {void}
	 */
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
	},

	/**
	 * Checks if an object is an empty object
	 *
	 * @param {Object}
	 * @return {Boolean} true if empty object, false if no object or non empty object
	 */
	isEmptyObject: function(object) {
		var i;
		for (i in object) {
			return false;
		}
		return true;
	}
};