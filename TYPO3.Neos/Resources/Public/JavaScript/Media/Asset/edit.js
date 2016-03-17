(function($) {
	$(function() {
		if (window.parent !== window && window.parent.Typo3MediaBrowserCallbacks) {
			$('.neos-footer a, .neos-footer button').hide();
			$('.neos-footer .neos-button-primary').on('click', function() {
				window.parent.Typo3MediaBrowserCallbacks.close();
			});
		}
	});
})(jQuery);
