define(
[
	'emberjs',
	'Library/jquery-with-dependencies',
	'text!./FileUpload.html',
	'Library/plupload',
	'Shared/Notification',
	'Shared/Configuration'
],
function(Ember, $, template, plupload, Notification, Configuration) {
	return Ember.View.extend({
		value: '',

		/**
		 * Label of the file chooser button
		 */
		fileChooserLabel: 'Choose file',
		uploaderLabel: 'Upload',

		// File filters
		allowedFileTypes: null,

		_uploader: null,
		_uploadInProgress: false,
		_containerId: null,
		_browseButtonId: null,
		_uploadButtonShown: false,
		_uploadButtonNotShown: function() {
			return !this.get('_uploadButtonShown');
		}.property('_uploadButtonShown'),

		template: Ember.Handlebars.compile(template),

		init: function() {
			var id = this.get('elementId');
			this.set('_containerId', 'typo3-fileupload-' + id);
			this.set('_browseButtonId', 'typo3-fileupload-browsebutton-' + id);
			return this._super();
		},

		didInsertElement: function() {
			this._initializeUploader();
		},

		_initializeUploader: function() {
			var that = this;
			this._uploader = new plupload.Uploader({
				runtimes : 'html5',
				browse_button : this._browseButtonId,
				container : this._containerId,
				max_file_size : '10mb',
				url : $('link[rel="neos-asset-upload"]').attr('href'),
				multipart_params: {}
			});
			if (this.allowedFileTypes) {
				this._uploader.settings.filters = [{
					title: 'Allowed files',
					extensions: this.allowedFileTypes
				}];
			}

			this._uploader.bind('FilesAdded', function(uploader, files) {
				if (files.length > 0) {
					that.set('_uploadButtonShown', true);
				} else {
					that.set('_uploadButtonShown', false);
				}
			});

			this._uploader.bind('Error', function(uploader, error) {
				that.set('_uploadInProgress', false);
				Notification.error(error.message);
				// FilesAdded gets the unfiltered list, so we have to disable the upload on errors
				if (error.code === plupload.FILE_EXTENSION_ERROR) {
					that.set('_uploadButtonShown', false);
				}
			});

			this._uploader.bind('BeforeUpload', function(uploader, file) {
				uploader.settings.multipart_params['__csrfToken'] = Configuration.get('CsrfToken');
				uploader.settings.multipart_params['asset[type]'] = 'plupload';
				uploader.settings.multipart_params['asset[fileName]'] = file.name;
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
			this.$().find('input[type=file][id^="' + this._uploader.id + '"]').change(function(event) {
				that.filesScheduledForUpload(event.target.files);
			});
		},

		// The "files" is taken from the DOM event when a file changes
		filesScheduledForUpload: function(files) {
			// Template method
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