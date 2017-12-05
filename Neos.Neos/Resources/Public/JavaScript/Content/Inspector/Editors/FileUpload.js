define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'text!./FileUpload.html',
	'Library/plupload',
	'Shared/Notification',
	'Shared/Configuration',
	'Shared/I18n'
],
function(Ember, $, template, plupload, Notification, Configuration, I18n) {
	return Ember.View.extend({
		value: '',

		/**
		 * Label of the file chooser button
		 */
		fileChooserLabel: null, // For backwards compatibility
		fileUploadLabel: null,
		_fileUploadLabel: function() {
			return I18n.translate(this.get('fileChooserLabel') || this.get('fileUploadLabel'), 'Upload file');
		}.property(),

		// File filters
		allowedFileTypes: null,

		maximumFileSize: null,

		_uploader: null,
		_uploadInProgress: false,
		_containerId: null,
		_browseButtonId: null,
		_fileDropZoneId: null,
		_uploadButtonShown: false,
		_uploadButtonNotShown: function() {
			return !this.get('_uploadButtonShown');
		}.property('_uploadButtonShown'),

		template: Ember.Handlebars.compile(template),

		init: function() {
			var id = this.get('elementId');
			this.set('_containerId', 'typo3-fileupload-' + id);
			this.set('_browseButtonId', 'typo3-fileupload-browsebutton-' + id);
			this.set('_fileDropZoneId', 'typo3-fileupload-dropzone-' + id);
			return this._super();
		},

		didInsertElement: function() {
			this._initializeUploader();
		},

		_initializeUploader: function() {
			var that = this,
				$fileDropZone = $('#' + this._fileDropZoneId);

			$fileDropZone
				.on('dragenter', function(e) {
					e.dataTransfer.dropEffect = 'copy';
					e.dataTransfer.effectAllowed = 'copy';
					$fileDropZone.addClass('typo3-fileupload-dropzone-hover');
				}).on('dragleave drop dragend', function(e) {
					e.dataTransfer.dropEffect = 'copy';
					e.dataTransfer.effectAllowed = 'copy';
					$fileDropZone.removeClass('typo3-fileupload-dropzone-hover');
				});

			$(document)
				.on('dragover', function(e) {
					$fileDropZone.addClass('typo3-fileupload-dropzone-active');
				}).on('dragleave drop dragend', function(e) {
					$fileDropZone.removeClass('typo3-fileupload-dropzone-active');
				});

			this._uploader = new plupload.Uploader({
				runtimes : 'html5',
				browse_button : this._browseButtonId,
				container : this._containerId,
				drop_element: this._fileDropZoneId,
				max_file_size : this.get('maximumFileSize') ? this.get('maximumFileSize') : Configuration.get('maximumFileUploadSize'),
				url : $('link[rel="neos-asset-upload"]').attr('href'),
				file_data_name: 'asset[resource]',
				multipart_params: {}
			});
			if (this.allowedFileTypes) {
				this._uploader.settings.filters = [{
					title: 'Allowed files',
					extensions: this.allowedFileTypes
				}];
			}

			this._uploader.bind('Error', function(uploader, error) {
				that.set('_uploadInProgress', false);
				that.set('_uploadButtonShown', false);
				Notification.error(error.message);
				uploader.splice();
			});

			this._uploader.bind('BeforeUpload', function(uploader, file) {
				uploader.settings.multipart_params['__csrfToken'] = Configuration.get('CsrfToken');
				uploader.settings.multipart_params['__siteNodeName'] = $('link[rel="neos-site"]').data('node-name');
			});

			this._uploader.bind('FileUploaded', function(uploader, file, response) {
				Notification.ok('Uploaded file "' + file.name + '".');
				that.fileUploaded(response.response);
			});

			this._uploader.init();
			this._uploaderInitialized();
		},

		_uploaderInitialized: function() {
			var that = this;
			this._uploader.bind('FilesAdded', function(uploader) {
				that.filesScheduledForUpload(uploader.files);
			});
		},

		// The "files" is taken from the DOM event when a file changes
		filesScheduledForUpload: function(files) {
			this.upload();
		},

		fileUploaded: function() {
			this.set('_uploadInProgress', false);
			this.set('_uploadButtonShown', false);
		},

		upload: function() {
			this.set('_uploadInProgress', true);
			this._uploader.start();
		}

	});
});
