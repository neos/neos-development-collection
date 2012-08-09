jQuery UI: version 1.8.14
- UI Core: all
- Interactions: all
- Widgets: all
- Effects: core, fade, highlight
Theme: UI Darkness


jquery popover from https://github.com/harryhorn/jquery-popover
Version 0.2.2
- MODIFIED: see "// TYPO3 SPECIFIC FIX" in jquery.popover.js


jquery.dynatree.js
jquery.dynatree.min.js
jQuery Dynatree Version 1.2.1
/Resources/Public/Library/jquery-dynatree/js/jquery.dynatree.js
_setDndStatus: function(){
	--- var pos = $target.offset();
	+++ var pos = $target.position();
}

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


jCrop from http://deepliquid.com/content/Jcrop.html
Version 0.9.9 from 6/7/11
- deleted build/
- deleted demo/
- deleted index.html
- deleted js/jquery.min.js

Sproutcore 2.0 from Github repository https://github.com/sproutcore/sproutcore20
Commit: d7b29372f128b22d8a962dfa48c96499154e2160


CodeMirror 2.13 from http://codemirror.net/


Chosen 0.9.8 from http://harvesthq.github.com/chosen/
Only the jquery files / needed files are added (skipped coffeescript / prototype / examples)


jQuery Lionbars
http://nikolaydyankov.com/lionbars/ - version 0.2.1
- cleaned and linted by Aske :-)


Canvas Indicator
http://toydestroyer.com/ - version 1.0


jQuery Hotkeys
https://github.com/jeresig/jquery.hotkeys - version 0.8
- Modularized by Christopher


Twitter Bootstrap
SASS version - https://github.com/jlong/sass-twitter-bootstrap/tree/master/lib (https://github.com/twitter/bootstrap/) - version 2.0.4

* Wrapped in .t3-ui class (bootstrap.scss)
* Changed icon images paths variables $iconSpritePath + $iconWhiteSpritePath (_variables.scss)