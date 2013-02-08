jQuery UI: version 1.9 beta 1
Theme: UI Darkness (from 1.8)

The isLocal() function is patched because otherwise Aloha is broken.

Original isLocal() function:

	function isLocal( anchor ) {
		// clone the node to work around IE 6 not normalizing the href property
		// if it's manually set, i.e., a.href = "#foo" kills the normalization
		anchor = anchor.cloneNode( false );
		return anchor.hash.length > 1 &&
			anchor.href.replace( rhash, "" ) === location.href.replace( rhash, "" );
	}

Patched isLocal() function:
	function isLocal( anchor ) {
		var rhash = /#.*$/,
			currentPage = location.href.replace( rhash, "" );

		return function( anchor ) {
			var href = anchor.href.replace(location.origin + '/', location.href);

			// clone the node to work around IE 6 not normalizing the href property
			// if it's manually set, i.e., a.href = "#foo" kills the normalization
			anchor = anchor.cloneNode( false );
			return anchor.hash.length > 1 &&
				href.replace( rhash, "" ) === currentPage;
		};
	}


jquery popover from https://github.com/harryhorn/jquery-popover
Version 0.2.2
- MODIFIED: see "// TYPO3 SPECIFIC FIX" in jquery.popover.js

line 75
-- $.fn.popover.openedPopup.trigger('hidePopover');
++ //$.fn.popover.openedPopup.trigger('hidePopover');



jquery.dynatree.js
jquery.dynatree.min.js
jQuery Dynatree Version 1.2.1
/Resources/Public/Library/jquery-dynatree/js/jquery.dynatree.js
_setDndStatus: function(){
	--- var pos = $target.offset();
	+++ var pos = $target.position();
}

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
Version 0.9.10 from 28/4/12
- deleted build/
- deleted demos/
- deleted index.html
- deleted js/jquery.min.js


CodeMirror 2.13 from http://codemirror.net/


Chosen 0.9.8 from http://harvesthq.github.com/chosen/
Only the jquery files / needed files are added (skipped coffeescript / prototype / examples)


jQuery Lionbars
http://nikolaydyankov.com/lionbars/ - version 0.2.1
- cleaned and linted by Aske :-)


Spin.js
http://fgnass.github.com/spin.js/ - version 1.2.8


jQuery Hotkeys
https://github.com/jeresig/jquery.hotkeys - version 0.8
- Modularized by Christopher


Twitter Bootstrap
SASS version - https://github.com/jlong/sass-twitter-bootstrap/tree/master/lib (https://github.com/twitter/bootstrap/) - version 2.0.4

* Wrapped in .t3-ui class (bootstrap.scss)
* Changed icon images paths variables $iconSpritePath + $iconWhiteSpritePath (_variables.scss)
* Removed sprites.scss import (bootstrap.scss)


Create.js - https://github.com/bergie/create
* Update using Scripts/update-createjs-to-master.sh

VIE - https://github.com/bergie/VIE
* Update using Scripts/update-vie-to-master.sh

Hallo editor - https://github.com/bergie/hallo
* Update using Scripts/update-hallo-to-master.sh

To execute the update scripts method you'll need:
* NodeJS v0.6.9 or lower
* CoffeeScript (http://jashkenas.github.com/coffee-script/)
* uglify-js (https://npmjs.org/package/uglify-js) - install globally with -g flag for npm
* async (https://npmjs.org/package/async)
* vie (https://npmjs.org:10020/package/vie)

** If you're using OS X you'll need to either ignore or add backup file extension for "sed" commands (http://stackoverflow.com/questions/4247068/sed-command-failing-on-mac-but-works-on-linux),
   which are used in the Cakefile in hallo root dir. Replace "sed -i ..." with "sed -i '' ..." or "sed -ibak ..." to ignore backup files.


Font Awesome v3.0
http://fortawesome.github.com/Font-Awesome/

* Changed $fontAwesomePath in sass/font-awesome.scss
* Wrapped everything in t3-ui sass/font-awesome.scss
* Removed the background of twitter-bootstrap icons before include (style.scss)

Bootstrap Notify
http://nijikokun.github.com/bootstrap-notify/