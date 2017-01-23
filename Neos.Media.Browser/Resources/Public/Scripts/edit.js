(function($) {
	$(function() {
		if (window.parent !== window && window.parent.NeosMediaBrowserCallbacks) {
			$('.neos-action-cancel, .neos-button-primary', '.neos-footer').on('click', function(e) {
				if (window.parent.NeosMediaBrowserCallbacks && typeof window.parent.NeosMediaBrowserCallbacks.close === 'function') {
					window.parent.NeosMediaBrowserCallbacks.close();
				}
			});
		}
	});
})(jQuery);
