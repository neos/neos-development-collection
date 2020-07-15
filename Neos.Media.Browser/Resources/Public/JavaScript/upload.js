(function ($) {
    $(function () {
        var uploader = new plupload.Uploader({
            runtimes: 'html5,flash',
            browse_button: 'dropzone',
            container: 'uploader',
            multipart: true,
            url: uploadUrl,
            max_file_size: maximumFileUploadSize,
            filters: {
                mime_types : $('#resource').attr('accept')
            },
            file_data_name: $('#resource').attr('name'),
            drop_element: 'dropzone'
        });

        uploader.bind('Init', function (up, params) {
            $('#filelist').html('');
        });

        uploader.init();

        uploader.bind('FilesAdded', function (up, files) {
            $('#dropzone').hide();
            $.each(files, function (i, file) {
                $('#filelist').append('<div class="progress" id="' + file.id + '"><div class="bar" style="width: 0%;"></div><div class="label">' + file.name + ' <span>0</span>%</div></div>');
            });

            up.refresh(); // Reposition Flash
            uploader.start();
        });

        uploader.bind('UploadProgress', function (up, file) {
            var $progress = $('#' + file.id);
            $('div.bar', $progress).width(file.percent + '%').find('span').text(file.percent);
            $('div.label span', $progress).text(file.percent);
        });

        var preventReload = false;
        uploader.bind('Error', function (up, err) {
            preventReload = true;
            $('#filelist').html('');

            function readablizeBytes(bytes) {
                var s = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'];
                var e = Math.floor(Math.log(bytes) / Math.log(1024));
                return (bytes / Math.pow(1024, e)).toFixed(2) + " " + s[e];
            }

            var message;
            switch (err.code) {
                case -600:
                    var readableFileSize = readablizeBytes(err.file.size);
                    var readableMaximumFileSize = readablizeBytes(maximumFileUploadSize);
                    message = 'The file size of ' + readableFileSize + ' exceeds the allowed limit of ' + readableMaximumFileSize;
                    if (window.Typo3Neos) {
                        message = window.Typo3Neos.I18n.translate('fileSizeExceedsAllowedLimit', message, 'Neos.Media.Browser', 'Main', [readableFileSize, readableMaximumFileSize]);
                    }
                    break;
                default:
                    message = err.message;
            }
            if (err.file) {
                message += ' ';
                if (window.Typo3Neos) {
                    message += window.Typo3Neos.I18n.translate('forTheFile', 'for the file', 'Neos.Media.Browser');
                } else {
                    message += 'for the file';
                }
                message += ' "' + err.file.name + '"';
            }
            if (window.Typo3Neos) {
                window.Typo3Neos.Notification.error(message);
            } else {
                alert(message);
            }

            up.refresh(); // Reposition Flash
        });

        uploader.bind('UploadComplete', function (up, file) {
            if (preventReload) {
                $('#filelist').html('');
                var message = 'Only some of the files were successfully uploaded. Refresh the page to see the those.';
                if (window.Typo3Neos) {
                    message = window.Typo3Neos.I18n.translate('onlySomeFilesWereUploaded', message, 'Neos.Media.Browser');
                    window.Typo3Neos.Notification.warning(message);
                } else {
                    alert(message);
                }
            } else {
                location.reload(false);
            }
        });

        var $fileDropZone = $('#dropzone');
        $fileDropZone
            .on('dragenter dragover', function (e) {
                var dataTransfer = e.originalEvent.dataTransfer;
                dataTransfer.dropEffect = 'copy';
                dataTransfer.effectAllowed = 'copy';
                $fileDropZone.addClass('neos-upload-area-hover');
            }).on('dragleave drop dragend', function (e) {
            var dataTransfer = e.originalEvent.dataTransfer;
            dataTransfer.dropEffect = 'copy';
            dataTransfer.effectAllowed = 'copy';
            $fileDropZone.removeClass('neos-upload-area-hover');
        });

        $(document)
            .on('dragover', function (e) {
                $fileDropZone.addClass('neos-upload-area-active');
            }).on('dragleave drop dragend', function (e) {
            $fileDropZone.removeClass('neos-upload-area-active');
        });
    });
})(jQuery);
