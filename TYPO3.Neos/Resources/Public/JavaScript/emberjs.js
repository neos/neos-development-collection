define(['Library/ember'], function(Ember) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('emberjs');

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