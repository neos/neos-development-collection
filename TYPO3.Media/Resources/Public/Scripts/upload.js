(function($) {
	$(function() {
		var uploader = new plupload.Uploader({
			runtimes: 'html5,flash',
			container: 'uploader',
			multipart: true,
			url: uploadUrl,
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
				$('#filelist').append('<div class="progress"><div class="bar" style="width: 0%;"></div></div>');
			});

			up.refresh(); // Reposition Flash
			uploader.start();
		});

		uploader.bind('UploadProgress', function(up, file) {
			$('#progress').find('div.progress div.bar').width(file.percent + '%');
		});

		uploader.bind('Error', function(up, err) {
			$('#filelist').html('');
			alert('Error: ' + err.code +
				', Message: ' + err.message +
				(err.file ? ', File: ' + err.file.name : '')
			);

			up.refresh(); // Reposition Flash
		});

		uploader.bind('UploadComplete', function(up, file) {
			location.reload(false);
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
			$('.asset-list').on('click', '.asset', function(e) {
				window.parent.Typo3MediaBrowserCallbacks.assetChosen($(this).attr('data-asset-identifier'));
				e.preventDefault();
			});
		}
	});
})(jQuery);