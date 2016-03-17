(function($) {
    $(function() {
        if (window.parent !== window && window.parent.Typo3MediaBrowserCallbacks) {
            // we are inside iframe
            $('.neos-media-delete').remove();
            $('.asset-list').on('click', '[data-asset-identifier]', function (e) {
                if ($(e.target).closest('button').length === 0) {
                    window.parent.Typo3MediaBrowserCallbacks.assetChosen($(this).attr('data-asset-identifier'));
                    e.preventDefault();
                }
            });
        }
    });
})(jQuery);
