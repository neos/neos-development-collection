define(['Library/ember'], function(Ember) {

	// We overwrite default Ember.Logger behavior so we can mute logging if needed.
	Ember.Logger.mute = false;

	Ember.Logger.callLogger = function(type, arguments) {
		if (Ember.Logger.mute) {
			return;
		}
		console[type].apply(console, arguments);
	}

	Ember.Logger.log = function() {
		Ember.Logger.callLogger('log', arguments);
	}
	Ember.Logger.warn = function() {
		Ember.Logger.callLogger('warn', arguments);
	}
	Ember.Logger.error = function() {
		Ember.Logger.callLogger('error', arguments);
	}
	Ember.Logger.debug = function() {
		Ember.Logger.callLogger('debug', arguments);
	}

	if (window.localStorage.showDevelopmentFeatures) {
		Ember.ENV = {
			CP_DEFAULT_CACHEABLE: true,
			RAISE_ON_DEPRECATION: true
		};
		// for debugging only - do not ship
		// Ember.LOG_BINDINGS = true;
	}

	return Ember;
});