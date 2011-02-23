/*!
* Aloha Editor
* Author & Copyright (c) 2010 Gentics Software GmbH
* aloha-sales@gentics.com
* Licensed unter the terms of http://www.aloha-editor.com/license.html
*/
/**
 * Register the CropNResize as GENTICS.Aloha.Plugin
 */
GENTICS.Aloha.CropNResize = new GENTICS.Aloha.Plugin('com.gentics.aloha.plugins.CropNResize');

/**
 * Configure the available languages
 */
GENTICS.Aloha.CropNResize.languages = ['en', 'de', 'fr'];

/**
 * The image that is currently edited
 */
GENTICS.Aloha.CropNResize.obj = null;

/**
 * The Jcrop API reference
 * this is needed to be able to destroy the cropping frame later on
 * the variable is linked to the api object whilst cropping, or set to null otherwise
 * strange, but done as documented http://deepliquid.com/content/Jcrop_API.html
 */
GENTICS.Aloha.CropNResize.jcAPI = null;

/**
 * this will contain an image's original properties to be able to undo previous settings
 * 
 * when an image is clicked for the first time, a new object will be added to the array
 * {
 * 		obj : [the image object reference],
 * 		src : [the original src url],
 * 		width : [initial width],
 * 		height : [initial height]
 * }
 * 
 * when an image is clicked the second time, the array will be checked for the image object
 * referenct, to prevent for double entries
 */
GENTICS.Aloha.CropNResize.restoreProps = [];

/**
 * resized callback is triggered right after the user finished resizing the image
 * @param image jquery image object 
 */
GENTICS.Aloha.CropNResize.onResized = function (image) {};

/**
 * crop callback is triggered after the user clicked accept to accept his crop
 * @param image jquery image object reference
 * @param props cropping properties
 */
GENTICS.Aloha.CropNResize.onCropped = function (image, props) {};

/**
 * reset callback is triggered before the internal reset procedure is applied
 * if this function returns true, then the reset has been handled by the callback
 * which means that no other reset will be applied
 * if false is returned the internal reset procedure will be applied
 * @param image jquery image object reference
 * @return true if a reset has been applied, flase otherwise
 */
GENTICS.Aloha.CropNResize.onReset = function (image) { return false; };

/**
 * button references
 */
GENTICS.Aloha.CropNResize.cropButton = null;

/**
 * internal interval reference
 */
GENTICS.Aloha.CropNResize.interval = null;

/**
 * a list of dom object currently attached
 */
GENTICS.Aloha.CropNResize.attachedObjects = [];

/**
 * Initialize the plugin, register the buttons
 */
GENTICS.Aloha.CropNResize.init = function() {
	var that = this;
	
	if (!this.settings.load) {
		this.settings.load = [];
	}
	
	// load additional js libs
	if (this.settings.load.jqueryui !== false && this.settings.load.jqueryui !== 'false') {
		jQuery('head').append('<script type="text/javascript" src="' 
				+ GENTICS.Aloha.settings.base 
				+ 'plugins/com.gentics.aloha.plugins.CropNResize/js/jquery-ui-1.8.7.custom.min.js"></script>');
	}
	if (this.settings.load.jqueryuicss !== false && this.settings.load.jqueryuicss !== 'false') {
		jQuery('head').append('<link rel="stylesheet" href="' 
				+ GENTICS.Aloha.settings.base 
				+ 'plugins/com.gentics.aloha.plugins.CropNResize/css/ui-lightness/jquery-ui-1.8.7.cropnresize.css" />');
	}
	if (this.settings.load.jcrop !== false && this.settings.load.jcrop !== 'false') {
		jQuery('head').append('<script type="text/javascript" src="' 
				+ GENTICS.Aloha.settings.base 
				+ 'plugins/com.gentics.aloha.plugins.CropNResize/js/jquery.Jcrop.min.js"></script>');
	}
	if (this.settings.load.jcropcss !== false && this.settings.load.jcropcss !== 'false') {
		jQuery('head').append('<link rel="stylesheet" href="' 
				+ GENTICS.Aloha.settings.base 
				+ 'plugins/com.gentics.aloha.plugins.CropNResize/css/jquery.Jcrop.css" />');
	}
	jQuery('head').append('<link rel="stylesheet" href="' 
			+ GENTICS.Aloha.settings.base 
			+ 'plugins/com.gentics.aloha.plugins.CropNResize/css/cropnresize.css" />');
	
	// create image scope
	GENTICS.Aloha.FloatingMenu.createScope('GENTICS.Aloha.image', ['GENTICS.Aloha.global']);
	
	/*
	 * init basic settings like callbacks and options
	 */
	if (typeof this.settings.onResized == "function") {
		this.onResized = this.settings.onResized;
	}
	if (typeof this.settings.onCropped == "function") {
		this.onCropped = this.settings.onCropped;
	}
	if (typeof this.settings.onReset == "function") {
		this.onReset = this.settings.onReset;
	}
	if (typeof this.settings.aspectRatio != "boolean") {
		this.settings.aspectRatio = true;
	}
	
	// init attach selectors
	// initialize the root selector used for attach()
	if (typeof this.settings.rootSelector !== 'string') {
		this.settings.rootSelector = '.GENTICS_editable';
	}
	
	// ...and the standard selector
	if (typeof this.settings.selector !== 'string') {
		this.settings.selector = 'img';
	}
	
	/*
	 * image cropping stuff goes here
	 */
	this.cropButton = new GENTICS.Aloha.ui.Button({
		'size' : 'small',
		'tooltip' : this.i18n('Crop'),
		'toggle' : true,
		'iconClass' : 'cnr_crop',
		'onclick' : function (btn, event) {
			if (btn.pressed) {
				that.crop();
			} else {
				that.endCrop();
			}
		}
	});

	// add to floating menu
	GENTICS.Aloha.FloatingMenu.addButton(
		'GENTICS.Aloha.image',
		this.cropButton,
		this.i18n('floatingmenu.tab.image'),
		20
	);
	
	/*
	 * add a reset button
	 */
	GENTICS.Aloha.FloatingMenu.addButton(
		'GENTICS.Aloha.image',
		new GENTICS.Aloha.ui.Button({
			'size' : 'small',
			'tooltip' : this.i18n('Reset'),
			'toggle' : false,
			'iconClass' : 'cnr_reset',
			'onclick' : function (btn, event) {
				that.reset();
			}
		}),
		this.i18n('floatingmenu.tab.image'),
		30
	);
	
	// remove resize handles and cropping status when clicking somewhere else
	GENTICS.Aloha.EventRegistry.subscribe(GENTICS.Aloha, 'selectionChanged', function(event, rangeObject, originalEvent) {
		if (!originalEvent || !originalEvent.target) {
			return;
		}
		if (!jQuery(originalEvent.target).hasClass('ui-resizable-handle')) {
			that.endResize();
		}
	});

	// now attach events to images
	GENTICS.Aloha.EventRegistry.subscribe(GENTICS.Aloha, 'editableCreated', function(event, editable) {
		// attach events to the editable
		that.attach();
	});
};

/**
 * attach to elements using the given filter
 * you may call this function subsequently if
 * you've added new images to the content
 * 
 * @param selector optional jQuery selector which defines images to be used.
 * 		the default selector is '.GENTICS_editable img', which you may as well
 * 		override by providing the 'selector' option from the settings:
 * 		
 * 		// make all images in #maincontent editable
 *  	GENTICS.Aloha.settings.plugins["com.gentics.aloha.plugins.CropNResize"].selector = '#maincontent img';
 */
GENTICS.Aloha.CropNResize.attach = function(selector) {
	// if a selector has been provided we'll stick with this one
	if (typeof selector != 'string') {
		selector = this.settings.selector;
	}

	var that = this;
	var attachSelector = this.settings.rootSelector
		+ ' '
		+ selector;
	
	// now attach to objects matched by the selector
	jQuery(attachSelector).each(function() {
		if (!that.isAttached(this)) {
			var o = jQuery(this);

			// we never want to resize edit icons
			if (o.parent().hasClass('GENTICS_editicon')) {
				return true;
			}
			
			o.mouseup(function(e) {
				that.focus(e);
				e.stopPropagation();
			});
		}
	});
	
	try {
		// this will disable mozillas image resizing facilities
		document.execCommand('enableObjectResizing', false, 'false');
	} catch (e) {
		// this is just for internet explorer, who will not support disabling enableObjectResizing
	}
};

/**
 * check whether we've already attached to a dom object
 * @param domobj object to check for
 * @return true if were already attached
 */
GENTICS.Aloha.CropNResize.isAttached = function(domobj) {
	for (var i=0; i<this.attachedObjects.length; i++) {
		if (this.attachedObjects[i] === domobj) {
			return true;
		}
	}
	return false;
};

/**
 * execute cleanup routine
 */
GENTICS.Aloha.CropNResize.reset = function() {
	// TODO implement me
};

/**
 * resets the image to it's initial properties
 */
GENTICS.Aloha.CropNResize.reset = function() {
	this.endCrop();
	this.endResize();
	
	if (this.onReset(this.obj)) {
		// the external reset procedure has already performed a reset, so there is no need to apply an internal reset
		return;
	}
	
	for (var i=0;i<this.restoreProps.length;i++) {
		// restore from restoreProps if there is a match
		if (this.obj.get(0) === this.restoreProps[i].obj) {
			this.obj.attr('src', this.restoreProps[i].src);
			this.obj.width(this.restoreProps[i].width);
			this.obj.height(this.restoreProps[i].height);
			return;
		}
	}
}

/**
 * initialize crop confirm and cancel buttons and move them to the tracker position
 */
GENTICS.Aloha.CropNResize.initCropButtons = function() {
	jQuery('body').append(
			'<div id="GENTICS_CropNResize_btns">' + 
			'<button class="cnr_crop_apply" title="' + this.i18n('Accept') + 
				'" onclick="GENTICS.Aloha.CropNResize.acceptCrop();">&#10004;</button>' +
			'<button class="cnr_crop_cancel" title="' + this.i18n('Cancel') + 
				'" onclick="GENTICS.Aloha.CropNResize.endCrop();">&#10006;</button>' + 
			'</div>'
	);
	
	var btns = jQuery('#GENTICS_CropNResize_btns');
	var oldLeft = 0;
	var oldTop = 0;
	this.interval = setInterval(function () {
		var jt = jQuery('.jcrop-tracker:first');
		var off = jt.offset();
		if (jt.css('height') != '0px' && jt.css('width') != '0px') {
			btns.fadeIn('slow');
		}
		
		// move the icons to the bottom right side
		off.top = parseInt(off.top + jt.height() + 3);
		off.left = parseInt(off.left + jt.width() - 55);
		
		// comparison to old values hinders flickering bug in FF
		if (oldLeft != off.left || oldTop != off.top) {
			btns.offset(off);
		}
		
		oldLeft = off.left;
		oldTop = off.top;
	}, 10);
};

/**
 * destroy crop confirm and cancel buttons
 */
GENTICS.Aloha.CropNResize.destroyCropButtons = function () {
	jQuery('#GENTICS_CropNResize_btns').remove();
	clearInterval(this.interval);
};

/**
 * this will be called, when the crop button is pressed, and cropping starts
 */
GENTICS.Aloha.CropNResize.crop = function () {
	var that = this;
	
	this.endResize();
	this.initCropButtons();

	this.jcAPI = jQuery.Jcrop(this.obj, {
		onSelect : function () {
			// ugly hack to keep scope :( 
			setTimeout(function () {
				GENTICS.Aloha.FloatingMenu.setScope('GENTICS.Aloha.image');
			}, 10);
		}
	});
};

/**
 * end cropping
 * will toggle buttons accordingly and remove all cropping markup
 */
GENTICS.Aloha.CropNResize.endCrop = function () {
	if (this.jcAPI) {
		this.jcAPI.destroy();
		this.jcAPI = null;
	}
	
	this.destroyCropButtons();
	this.cropButton.extButton.toggle(false);
	this.resize();
};

/**
 * accept the current cropping area and apply the crop
 */
GENTICS.Aloha.CropNResize.acceptCrop = function () {
	/*
	 * this.jcAPI.tellSelect()
Object
h: 218
w: 296
x: 45
x2: 341
y: 36
y2: 254
__proto__: Object
	 */
	this.onCropped(this.obj, this.jcAPI.tellSelect());
	this.endCrop();
	this.resize();
};
/**
 * start resizing
 * 
 * uses a load of jQueryUI.resizable() option, which can be passed through via the plugin's settings object
 * 		aspectRatio
 * 		maxHeight
 * 		minHeight
 * 		maxWidth
 * 		minWidth
 *  	grid
 */
GENTICS.Aloha.CropNResize.resize = function () {
	var that = this;
	
	this.obj.resizable({
		stop : function (event, ui) {
			that.onResized(that.obj);
			
			// this is so ugly, but I could'nt figure out how to do it better... 
			setTimeout(function () {
				GENTICS.Aloha.FloatingMenu.setScope('GENTICS.Aloha.image');
				that.done(event);
			}, 10);
		},
		// the rest of the settings is directly set through the plugin settings object
		aspectRatio : that.settings.aspectRatio,
 		maxHeight : that.settings.maxHeight,
 		minHeight : that.settings.minHeight,
 		maxWidth : that.settings.maxWidth,
 		minWidth : that.settings.minWidth,
 		grid : that.settings.grid
	});
	
	// this will prevent the user from resizing an image
	// using IE's resize handles
	// however I could not manage to hide them completely
	jQuery('.ui-wrapper')
		.attr('contentEditable', false)
		.bind('resizestart', function (e) {
			e.preventDefault();
		});
};

/**
 * end resizing
 * will toggle buttons accordingly and remove all markup that has been added for cropping
 */
GENTICS.Aloha.CropNResize.endResize = function () {
	if (this.obj) {
		this.obj.resizable("destroy");
	}
};

/**
 * an image has been clicked
 */
GENTICS.Aloha.CropNResize.focus = function (e) {
	GENTICS.Aloha.FloatingMenu.setScope('GENTICS.Aloha.image');
	this.obj = jQuery(e.target);
	this.restoreProps.push({
		obj : e.srcElement,
		src : this.obj.attr('src'),
		width : this.obj.width(),
		height : this.obj.height()
	});
	this.resize(); // init resizing by default
	this.updateFM();
};

/**
 * this is called when the cropping or resizing process has finished
 */
GENTICS.Aloha.CropNResize.done = function (e) {
	this.updateFM();
};

/**
 * reposition the floating menu
 */
GENTICS.Aloha.CropNResize.updateFM = function () {
	var o = this.obj.offset();
	GENTICS.Aloha.FloatingMenu.floatTo({
		x : o.left,
		y : (o.top - 100)
	});	
};