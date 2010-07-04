Ext.ns("F3.TYPO3.History");
/**
 * @class F3.TYPO3.History.HistoryManager
 * @namespace F3.TYPO3.History
 * @extends Ext.History
 *
 * The history allows to use browser back/forth button
 * 
 * @singleton
 */
F3.TYPO3.History.HistoryManager = Ext.apply(Ext.History, {

	state: {},

	initialize: function() {
		this.init();
		this.on('change', function(hash) {
			this.updateState(hash);
		});
	},
	
	updateState: function(hash) {
		hash = Ext.util.JSON.decode(decodeURIComponent(hash));
		
		F3.TYPO3.Utils.each(this.state, function(value, key) {
			if (hash[key]) {
				this.state[key] = hash[key];
				this.fireEvent(key + '-changed', hash[key]);
			} else {
				this.fireEvent(key + '-removed', hash[value]);
				delete(this.state[key]);
			}
			
			delete(hash[key]);
			
		}, this);
		
		F3.TYPO3.Utils.each(hash, function(value, key) {
			this.state[key] = value;
			this.fireEvent(key + '-added', value);
		}, this);
		
	},
	
	set: function(key, value) {
		var newState = {};
		newState[key] = value;
		this.add( Ext.util.JSON.encode(newState) );
		return this;
	},
	
	remove: function(key) {
		var newState = F3.TYPO3.Utils.clone(this.state);
		delete( newState[key] );
		this.add(newState.toJSON());
		return this;
	}

});
