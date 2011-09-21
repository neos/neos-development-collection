jQuery UI: version 1.8.14
- UI Core: all
- Interactions: all
- Widgets: all
- Effects: core, fade, highlight
Theme: UI Darkness


jquery popover from https://github.com/harryhorn/jquery-popover
Version 0.2.2


jQuery Notice from http://code.google.com/p/jquery-notice/
Version 1.0

plupload from http://plupload.com
Version 1.5b (2011-09-11)
- deleted "examples" directory
- forked plupload.html5.js:
	--- a/Resources/Public/Library/plupload/js/plupload.html5.js
	+++ b/Resources/Public/Library/plupload/js/plupload.html5.js
	@@ -341,7 +341,11 @@
											addSelectedFiles(this.files);

											// Clearing the value enables the user to select the same file again if they want to
	-                                       this.value = '';
	+/**
	+ * TYPO3: We change this line. To be able to process the File objects in a FileReader we need the event to bubble up
	+ * with the files still in it's value
	+ */
	+//                                     this.value = '';
									};

									/* Since we have to place input[type=file] on top of the browse_button for some browsers (FF, Opera),