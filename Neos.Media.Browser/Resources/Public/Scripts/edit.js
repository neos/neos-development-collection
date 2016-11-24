(function($) {
	$(function() {
		if (window.parent !== window && window.parent.Typo3MediaBrowserCallbacks) {
			$('.neos-action-cancel, .neos-button-primary', '.neos-footer').on('click', function(e) {
				if (window.parent.Typo3MediaBrowserCallbacks && typeof window.parent.Typo3MediaBrowserCallbacks.close === 'function') {
					window.parent.Typo3MediaBrowserCallbacks.close();
				}
			});
		}
	});
})(jQuery);