Ext.ns("F3.TYPO3.History");
/**
 * @class F3.TYPO3.History.HistoryManager
 * @namespace F3.TYPO3.History
 * @extends Ext.History
 *
 * The history manager allows to use the browser back/forth button
 * and offers convenient methods for adding state to the URL.
 * 
 * @singleton
 */
F3.TYPO3.History.HistoryManager = Ext.apply(Ext.History, {

	/**
	 * The saved state
	 *
	 * @type Object
	 */
	state: {},

	/**
	 * Initialize the history manager and
	 * listen to the change event for updating the saved
	 * state.
	 */
	initialize: function() {
		this.init();
		this.on('change', function(token) {
			this.updateState(token);
		});
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
	 */
	updateState: function(token) {
		token = this.decode(token);
		
		F3.TYPO3.Utils.each(this.state, function(value, key) {
			if (token[key]) {
				this.state[key] = token[key];
				this.fireEvent(key + '-changed', token[key]);
			} else {
				this.fireEvent(key + '-removed', token[value]);
				delete(this.state[key]);
			}
			
			delete(token[key]);
			
		}, this);
		
		F3.TYPO3.Utils.each(token, function(value, key) {
			this.state[key] = value;
			this.fireEvent(key + '-added', value);
		}, this);
		
	},

	/**
	 * Set a state value in the history, this will eventually
	 * fire an event (changed or added).
	 *
	 * @param {String} key The key for the state value
	 * @param {String} value The state value to set
	 * @return {F3.TYPO3.History.HistoryManager} A reference to the history manager
	 */
	set: function(key, value) {
		var newState = F3.TYPO3.Utils.clone(this.state);
		newState[key] = value;
		this.add(this.encode(newState));
		return this;
	},

	/**
	 * Remove a state value from the history, this will eventually
	 * fire an event (removed).
	 *
	 * @param {String} key The key for the state value to remove
	 * @return {F3.TYPO3.History.HistoryManager} A reference to the history manager
	 */
	remove: function(key) {
		var newState = F3.TYPO3.Utils.clone(this.state);
		delete(newState[key]);
		this.add(this.encode(newState));
		return this;
	},

	// private
	encode: function(string) {
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

	// private
	decode: function(string) {
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
