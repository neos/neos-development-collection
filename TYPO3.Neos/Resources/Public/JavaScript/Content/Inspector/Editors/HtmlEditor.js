define(
[
	'Content/Components/PopoverButton'
],
function(PopoverButton) {
	return PopoverButton.extend({

		_editorInitialized: false,

		_editor: null,

		// TODO: fix the width / height so it relates to the rest of the UI
		$popoverContent: $('<div />').addClass('neos-htmleditor-window'),

		label: 'HTML Editor',

		popoverTitle: 'HTML Editor',

		popoverPosition: 'left',

		classNames: ['neos-primary-editor-action'],

		onPopoverOpen: function() {
			var that = this,
				id = this.get(Ember.GUID_KEY);

				// Initialize CodeMirror editor with a nice html5 canvas demo.
			if (!this._editorInitialized) {

				var $editorContent = $('<textarea />', {
					id: 'typo3-htmleditor-' + id
				}).html(that.get('value'));

				this.$popoverContent.append($editorContent);

				require([
					'Library/codemirror',
				], function(CodeMirror) {
					var editorFullyPopulated = false;

					that._editor = CodeMirror.fromTextArea($editorContent.get(0), {
						mode: 'text/html',
						tabMode: 'indent',
						lineNumbers: true,
						onChange: function() {
							if (that._editor && editorFullyPopulated) {
								that.set('value', that._editor.getValue());
							}
						}
					});

						// We trigger an automatic indentation, which removes all the
						// automatic whitespaces etc...
					var lineCount = that._editor.lineCount();
					for(var i=0; i<lineCount; i++) {
						that._editor.indentLine(i);
					}

					editorFullyPopulated = true;
					that._editorInitialized = true;
				});
			}
		},

		willDestroyElement: function() {
			if (this._editorInitialized) {
				this.$().trigger('hidePopover');
				this._editor.toTextArea();
				$('#typo3-htmleditor-' + this.get(Ember.GUID_KEY)).remove();
				this._editorInitialized = false;
			}
			// TODO: not only hide the popover, but completely remove it from DOM!
		}

	});
});