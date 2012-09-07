define(['Library/emberjs/ember-0.9.7'], function() {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('emberjs');

	if (window.localStorage.showDevelopmentFeatures) {
		Ember.CP_DEFAULT_CACHEABLE = true;
//		Ember.LOG_BINDINGS = true;
		Ember.RAISE_ON_DEPRECATION = true;
	}

	return Ember;
});