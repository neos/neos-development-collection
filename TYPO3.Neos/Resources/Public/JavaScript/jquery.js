define(['Library/jquery/jquery-1.9.1'], function() {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('jquery');

	// Some parts of our code (Hallo.js) check for jQuery.browser.msie at various points;
	// which has been removed from recent jQuery versions. We just fake it here in order
	// to remove fatal errors.
	// TODO: update the libraries which use jQuery.browser to use feature detection instead.
	window.jQuery.browser = window.jQuery.browser || {};
	return window.jQuery.noConflict();
});