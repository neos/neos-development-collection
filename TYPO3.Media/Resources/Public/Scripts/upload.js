(function($) {
	$(function() {
		var uploader = new plupload.Uploader({
			runtimes: 'html5,flash',
			container: 'uploader',
			multipart: true,
			url: uploadUrl,
			max_file_size: maximumFileUploadSize,
			file_data_name: $('#resource').attr('name'),
			drop_element: 'dropzone'
		});

		uploader.bind('Init', function(up, params) {
			$('#filelist').html('');
		});

		uploader.init();

		uploader.bind('FilesAdded', function(up, files) {
			$('#dropzone').hide();
			$.each(files, function(i, file) {
				$('#filelist').append('<div class="progress" id="' + file.id + '"><div class="bar" style="width: 0%;"></div><div class="label">' + file.name + ' <span>0</span>%</div></div>');
			});

			up.refresh(); // Reposition Flash
			uploader.start();
		});

		uploader.bind('UploadProgress', function(up, file) {
			var $progress = $('#' + file.id);
			$('div.bar', $progress).width(file.percent + '%').find('span').text(file.percent);
			$('div.label span', $progress).text(file.percent);
		});

		var preventReload = false;
		uploader.bind('Error', function(up, err) {
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
					message = 'The file size of ' + readablizeBytes(err.file.size) + ' exceeds the allowed limit of ' + readablizeBytes(maximumFileUploadSize);
					break;
				default:
					message = err.message;
			}
			if (err.file) {
				message += ' for the file ' + err.file.name;
			}
			if (window.Typo3Neos) {
				window.Typo3Neos.Notification.error(message);
			} else {
				alert(message);
			}

			up.refresh(); // Reposition Flash
		});

		uploader.bind('UploadComplete', function(up, file) {
			if (preventReload) {
				$('#filelist').html('');
				var message = 'Only some of the files were successfully uploaded. Refresh the page to see the those.';
				if (window.Typo3Neos) {
					window.Typo3Neos.Notification.warning(message);
				} else {
					alert(message);
				}
			} else {
				location.reload(false);
			}
		});

		// Show the dropzone when dragging files (not folders or page
		// elements). The dropzone is hidden after a timer to prevent
		// flickering to occur as 'dragleave' is fired constantly.
		var dragTimer;
		$(document).on('dragover', function(e) {
			var dataTransfer = e.originalEvent.dataTransfer;
			if(dataTransfer.types != null && (dataTransfer.types.indexOf ? dataTransfer.types.indexOf('Files') != -1 : dataTransfer.types.contains('application/x-moz-file'))) {
				$('#dropzone').show();
				window.clearTimeout(dragTimer);
			}
		});
		$(document).on('dragleave', function(e) {
			dragTimer = window.setTimeout(function() {
				$('#dropzone').hide();
			}, 25);
		});

		if (window.parent !== window && window.parent.Typo3MediaBrowserCallbacks) {
			// we are inside iframe
			$('.asset-list').on('click', '[data-asset-identifier]', function(e) {
				window.parent.Typo3MediaBrowserCallbacks.assetChosen($(this).attr('data-asset-identifier'));
				e.preventDefault();
			});
		}
	});
})(jQuery);