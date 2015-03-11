(function($) {
	$(function() {
		$('#resource').change(function(e) {
			var filename = $(this).val().split('\\').pop();
			$('label[for="resource"]').text(filename);
			var filesize = $(this).get(0).files[0].size;
			if (filesize > maximumFileUploadSize) {
				function readablizeBytes(bytes) {
					var s = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'];
					var e = Math.floor(Math.log(bytes) / Math.log(1024));
					return (bytes / Math.pow(1024, e)).toFixed(2) + " " + s[e];
				}
				var message = 'The file size of ' + readablizeBytes(filesize) + ' exceeds the allowed limit of ' + readablizeBytes(maximumFileUploadSize);
				if (window.Typo3Neos) {
					window.Typo3Neos.Notification.error(message);
				} else {
					alert(message);
				}
				$(this.form).submit(function(e) {
					e.preventDefault();
					var message = 'Cannot upload the file';
					if (window.Typo3Neos) {
						window.Typo3Neos.Notification.warning(message);
					} else {
						alert(message);
					}
				});
			} else {
				$(this.form).unbind('submit');
			}
		});
	});
})(jQuery);