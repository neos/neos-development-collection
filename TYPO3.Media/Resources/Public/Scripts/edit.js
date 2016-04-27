(function($) {
	$(function() {
		if (window.parent !== window && window.parent.Typo3MediaBrowserCallbacks) {
			$('.neos-footer .neos-button-primary').on('click', function(e) {
				window.parent.Typo3MediaBrowserCallbacks.close();
			});
		}
	});
})(jQuery);