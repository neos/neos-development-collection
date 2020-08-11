(function ($) {
    $(function () {
        var $resource = $('#resource');
        $resource.change(function (e) {
            var filename = $(this).val().split('\\').pop();
            $('label[for="resource"]').text(filename);
            var filesize = $(this).get(0).files[0].size;
            if (filesize > maximumFileUploadSize) {
                function readablizeBytes(bytes) {
                    var s = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'];
                    var e = Math.floor(Math.log(bytes) / Math.log(1024));
                    return (bytes / Math.pow(1024, e)).toFixed(2) + " " + s[e];
                }

                var readableFileSize = readablizeBytes(filesize);
                var readableMaximumFileSize = readablizeBytes(maximumFileUploadSize);
                var message = 'The file size of ' + readableFileSize + ' exceeds the allowed limit of ' + readableMaximumFileSize;
                if (window.Typo3Neos) {
                    message = window.Typo3Neos.I18n.translate('fileSizeExceedsAllowedLimit', message, 'Neos.Media.Browser', 'Main', [readableFileSize, readableMaximumFileSize]);
                    window.Typo3Neos.Notification.error(message);
                } else {
                    alert(message);
                }
                $(this.form).on('submit.invalidfile', function (e) {
                    e.preventDefault();
                    var message = 'Cannot upload the file';
                    if (window.Typo3Neos) {
                        message = window.Typo3Neos.I18n.translate('cannotUploadFile', message, 'Neos.Media.Browser');
                        window.Typo3Neos.Notification.warning(message);
                    } else {
                        alert(message);
                    }
                });
            } else {
                $(this.form).off('submit.invalidfile');
            }
        });
        // Fallback validation for Safaris missing required attribute validation
        var isSafari = navigator.userAgent.indexOf('Safari') !== -1 && navigator.userAgent.indexOf('Chrome') === -1;
        if (isSafari) {
            $resource.closest('form').on('submit.safari', function (e) {
                if (!$resource.val()) {
                    e.preventDefault();
                    var message = 'No file selected';
                    if (window.Typo3Neos) {
                        message = window.Typo3Neos.I18n.translate('noFileSelected', message, 'Neos.Media.Browser');
                        window.Typo3Neos.Notification.warning(message);
                    } else {
                        alert(message);
                    }
                }
            });
        }
    });
})(jQuery);
