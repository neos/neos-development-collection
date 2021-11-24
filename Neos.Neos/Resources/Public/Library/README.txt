jQuery UI: version 1.10.3
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
Version 0.9.12 from 2/2/13
- deleted demos/
- deleted index.html
- deleted js/jquery.min.js


CodeMirror 3.24.0 from http://codemirror.net/


Chosen 0.12.1 from http://harvesthq.github.io/chosen/
Only the jQuery files / needed files are added (skipped prototype / examples / docs / sprites)


Antiscroll
https://github.com/jrolfs/antiscroll - version 22738867613c255734b9bc420f42f2b40f916bb8


jQuery Mousewheel
https://github.com/brandonaaron/jquery-mousewheel - version 3.0.6


Spin.js
http://fgnass.github.com/spin.js/ - version 1.2.8


jQuery Hotkeys
https://github.com/jeresig/jquery.hotkeys - version 0.8
- Modularized by Christopher


Twitter Bootstrap
SASS version - https://github.com/jlong/sass-twitter-bootstrap/tree/master/lib (https://github.com/twitter/bootstrap/) - version 2.3.1


Create.js - https://github.com/bergie/create
* Update using Scripts/update-createjs-to-master.sh


VIE - https://github.com/bergie/VIE
* Update using Scripts/update-vie-to-master.sh


Hallo editor - https://github.com/bergie/hallo
* Update using Scripts/update-hallo-to-master.sh


Mousetrap - http://craig.is/killing/mice
Version 1.4.5
Apache 2.0 license

To execute the update scripts method you'll need:
* NodeJS v0.6.9 or lower
* CoffeeScript (http://jashkenas.github.com/coffee-script/)
* uglify-js (https://npmjs.org/package/uglify-js) - install globally with -g flag for npm
* async (https://npmjs.org/package/async)
* vie (https://npmjs.org:10020/package/vie)

** If you're using OS X you'll need to either ignore or add backup file extension for "sed" commands (http://stackoverflow.com/questions/4247068/sed-command-failing-on-mac-but-works-on-linux),
   which are used in the Cakefile in hallo root dir. Replace "sed -i ..." with "sed -i '' ..." or "sed -ibak ..." to ignore backup files.


Font Awesome v5.2.0
http://fontawesome.io

In Neos 4.0, Fontawesome 5 was introduced, enabling the usage of all free Fontawesome icons:
https://fontawesome.com/icons?d=gallery&m=free
Those can still be referenced via "icon-[name]", as the UI includes a fallback to the "fas"
prefix-classes. To be sure which icon will be used, they can also be referenced by their
icon-classes, e.g. "fas fa-check"


Bootstrap Notify
http://nijikokun.github.com/bootstrap-notify/


XRegExp 2.0.0
* Including Unicode Base 1.0.0 & Unicode Categories 1.2.0
http://xregexp.com/
MIT License


iso8601-js-period 0.2
https://github.com/nezasa/iso8601-js-period
Apache 2.0 license


DateTime Picker - http://www.malot.fr/bootstrap-datetimepicker/
Apache 2.0 license

Select2 - http://ivaynberg.github.io/select2/
Version 3.4.5
Apache 2.0 or GPL 2.0 License


Requirejs - https://github.com/jrburke/requirejs
new BSD, and MIT
Version 2.1.9

Requirejs text - https://github.com/requirejs/text
new BSD, and MIT
Version 2.0.10


Fixed-sticky - https://github.com/filamentgroup/fixed-sticky
MIT license
Version 0.1.3


NProgress - https://github.com/rstacruz/nprogress
Version 0.1.2


Sly - https://github.com/darsain/sly
MIT license
Version 1.2.3


Ember-i18n - https://github.com/jamesarosen/ember-i18n
Apache License, Version 2.0
Version 1.6.3
Kept the lib/i18n.js and the vendor/cldr-1.0.0.js


CLDR.js - https://github.com/jamesarosen/CLDR.js
Apache License, Version 2.0
Version 1.0.0
Used file from the Ember-i18n library


jQuery Cookie - https://github.com/carhartl/jquery-cookie
MIT license
Version 1.3.1


d3 - http://d3js.org/
BSD license
Version 3.4.13
