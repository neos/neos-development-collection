Ext.ns('TYPO3.TYPO3.Core');

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
 * @class TYPO3.TYPO3.Core.HistoryManager
 *
 * The history manager allows to use the browser back/forth button
 * and offers convenient methods for adding state to the URL.
 *
 * @namespace TYPO3.TYPO3.History
 * @extends Ext.History
 * @singleton
 */
TYPO3.TYPO3.Core.HistoryManager = Ext.apply(Ext.History, {

	/**
	 * The saved state
	 *
	 * @type Object
	 * @private
	 */
	_state: {},

	/**
	 * @event emptyToken Fired when there is no history token set.
	 */

	/**
	 * Initialize the history manager and
	 * listen to the change event for updating the saved
	 * state.
	 *
	 * @param {TYPO3.TYPO3.Core.Application} application the application instance
	 */
	initialize: function(application) {
		this.init();
		this.on('change', function(token) {
			this._updateState(token);
		});
	},


	/**
	 * Update the HistoryManager token after the bootstrap is done.
	 *
	 * @return {void}
	 * @private
	 */
	start: function() {
		var token = this.getToken();
		if (token) {
			this._updateState(token);
		} else {
			this.fireEvent('emptyToken');
		}
	},

	/**
	 * Decode the token and fire events for changed,
	 * removed and added state values. The state
	 * property will hold the current state.
	 *
	 * The events will be the key suffixed
	 * with "-changed", "-removed" and "-added".
	 *
	 * @param {String} token The new token to process (URL fragment)
	 * @private
	 */
	_updateState: function(token) {
		token = this._decode(token);

		TYPO3.TYPO3.Utils.each(this._state, function(value, key) {
			if (token[key]) {
				this._state[key] = token[key];
				this.fireEvent(key + '-changed', token[key]);
			} else {
				this.fireEvent(key + '-removed', token[value]);
				delete(this._state[key]);
			}
			delete(token[key]);
		}, this);

		TYPO3.TYPO3.Utils.each(token, function(value, key) {
			this._state[key] = value;
			this.fireEvent(key + '-added', value);
		}, this);
	},

	/**
	 * Set a state value in the history, this will eventually
	 * fire an event (changed or added).
	 *
	 * @param {String} key The key for the state value
	 * @param {String} value The state value to set
	 * @return {TYPO3.TYPO3.Core.HistoryManager} A reference to the history manager
	 */
	set: function(key, value) {
		var newState = TYPO3.TYPO3.Utils.clone(this._state);
		newState[key] = value;
		this.add(this._encode(newState));
		return this;
	},

	/**
	 * Remove a state value from the history, this will eventually
	 * fire an event (removed).
	 *
	 * @param {String} key The key for the state value to remove
	 * @return {TYPO3.TYPO3.Core.HistoryManager} A reference to the history manager
	 */
	remove: function(key) {
		var newState = TYPO3.TYPO3.Utils.clone(this._state);
		delete(newState[key]);
		this.add(this._encode(newState));
		return this;
	},

	/**
	 * Encode a state value to store it in the URL
	 *
	 * @param {String} The value to encode
	 * @return {String} The encoded value
	 * @private
	 */
	_encode: function(string) {
		string = Ext.util.JSON.encode(string);
		// TODO remove outer { and } as it's always needed and can be auto-attached
		string = string.substr(1, string.length-2);

		string = string.replace(/"/g, '');
		string = string.replace(/\{/g, '!');
		string = string.replace(/\}/g, '$');
		string = string.replace(/:/g, '=');
		string = string.replace(/,/g, '&');

		return string;
	},

	/**
	 * Decode a state value from the URL
	 *
	 * @param {String} The value to decode
	 * @return {String} The decoded value
	 * @private
	 */
	_decode: function(string) {
		string = decodeURIComponent(string);
		string = string.replace(/\=!/g, '":{"');
		string = string.replace(/\$&/g, '"},"');

		string = string.replace(/!/g, '{"');
		string = string.replace(/\$/g, '"}');
		string = string.replace(/\=/g, '":"');
		string = string.replace(/&/g, '","');

		string = '{"' + string + '"}';
		string = Ext.util.JSON.decode(string);

		return string;
	}
});