(function ($) {
    if (window.parent !== window && window.parent.NeosMediaBrowserCallbacks) {
        // we are inside iframe
        $('.asset-list').on('click', '[data-asset-identifier]', function(e) {
            if (
                $(e.target).closest('a, button').not('[data-asset-identifier]').length === 0 &&
                window.parent.NeosMediaBrowserCallbacks &&
                typeof window.parent.NeosMediaBrowserCallbacks.assetChosen === 'function'
            ) {
                let localAssetIdentifier = $(this).attr('data-local-asset-identifier');
                if (localAssetIdentifier !== '') {
                    window.parent.NeosMediaBrowserCallbacks.assetChosen(localAssetIdentifier);
                } else {
                    $.post(
                        $('link[rel="neos-media-browser-service-assetproxies-import"]').attr('href'),
                        {
                            assetSourceIdentifier: $(this).attr('data-asset-source-identifier'),
                            assetIdentifier: $(this).attr('data-asset-identifier'),
                            __csrfToken: $('body').attr('data-csrf-token')
                        },
                        function(data) {
                            window.parent.NeosMediaBrowserCallbacks.assetChosen(data.localAssetIdentifier);
                        }
                    );
                }
                e.preventDefault();
            }
        });
    }
})(jQuery);
