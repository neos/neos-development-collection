// Modified from version taken -> https://github.com/juggy/jquery-popover

(function($) {
	$.fn.popover = function(options) {
		var KEY_ESC = 27;
		
		// settings stored options and state
	  var settings = $.extend({
	    id: '',						// id for created popover
	    openEvent: null,	// callback function to be called when popup opened
	    closeEvent: null, // callback function to be called when popup closed
	    offsetX: 0,				// fixed offset to correct popup X position
	    offsetY: 0,				// fixed offset to correct popup Y position
			zindex: 100000,		// default z-index value
			padding: 18,			// default settings.padding around popover from document edges
			closeOnEsc: true,	// change to false to disable ESC
			preventLeft: false,		// pass true to prevent left popover
			preventRight: false,	// pass true to prevent right popover
			preventTop: false,		// pass true to prevent top popover
			preventBottom: false	// pass true to prevent bottom popover
	  }, options || {});

	  // HTML popover
	  settings.popover$ = $('<div class="popover" id="' + settings.id + '">'
	        + '<div class="triangle"></div>'
	        + '<div class="header"></div>'
	        + '<div class="content"></div>'
	        + '</div>').appendTo('body');
	  $('.header', settings.popover$).append($(settings.header).detach());
	  $('.content', settings.popover$).append($(settings.content).detach());

	  settings.triangle$ = $('.triangle', settings.popover$);

	  // document click closes active popover
	  $.fn.popover.openedPopup = null;
	  $(document).bind("click", function(event) {
	    if ($.fn.popover.openedPopup != null
	        && ($(event.target).parents(".popover").length == 0)
	        && (!$(event.target).hasClass('popover-button'))) {
	      $.fn.popover.openedPopup.trigger('hidePopover');
	    }
	  });
	
		// document hidePopover causes active popover to close
		$(document).bind("hidePopover", function(event) {
			if ($.fn.popover.openedPopup != null)
				$.fn.popover.openedPopup.trigger('hidePopover');
		});
	
		// keyboard callback
		function keyDown(event) {
			if (!event.altKey && !event.ctrlKey && !event.shiftKey) {
				switch(event.keyCode) {	
					case KEY_ESC:
				    if ($.fn.popover.openedPopup != null) {
				      $.fn.popover.openedPopup.trigger('hidePopover');
				    }
						break;
				}
			}
		}

		function calcPopoverDirPossible(button, coord) {
			var possibleDir = {
				left: false,
				right: false,
				top: false,
				bottom: false
			}
			
	    if (coord.buttonOffset.top + coord.buttonHeight + coord.triangleSize + coord.popoverHeight <= 
									coord.docHeight - settings.padding) {
				possibleDir.bottom = true;
			}

	    if (coord.buttonOffset.top - coord.triangleSize - coord.popoverHeight >= settings.padding) {
				possibleDir.top = true;
			}

	    if (coord.buttonOffset.left + coord.buttonWidth + coord.triangleSize + coord.popoverWidth <= 
									coord.docWidth - settings.padding) {
				possibleDir.right = true;
			}

	    if (coord.buttonOffset.left - coord.triangleSize - coord.popoverWidth >= settings.padding) {
				possibleDir.left = true;
			}
			
			return possibleDir;
		}
		
		function chooseDir(possibleDir) {
			
			// remove directions prevented by settings
			if (settings.preventBottom)
				possibleDir.bottom = false;
			if (settings.preventTop)
				possibleDir.top = false;
			if (settings.preventLeft)
				possibleDir.left = false;
			if (settings.preventRight)
				possibleDir.right = false;

			// determine default direction if nothing works out
			// make sure it is not one of the prevented directions
			var dir = 'right';
			if (settings.preventRight)
				dir = 'bottom';
			if (settings.preventBottom)
				dir = 'top';
			if (settings.preventTop)
				dir = 'left';

			if (possibleDir.right)
				dir = 'right';
			else if (possibleDir.bottom)
					dir = 'bottom';
			else if (possibleDir.left)
					dir = 'left';
			else if (possibleDir.top)
					dir = 'top';
			
			return dir;
		}
	
		function calcPopoverPos(button) {
			
	    // Set this first for the layout calculations to work.
	    settings.popover$.css('display', 'block');
	
			var coord = {
				popoverDir: 'bottom',
		    popoverX: 0,
		    popoverY: 0,
				deltaX: 0,
				deltaY: 0,
				triangleX: 0,
				triangleY: 0,
		    triangleSize: 20, // needs to be updated if triangle changed in css
		    docWidth: $(document).width(),
		    docHeight: $(document).height(),
		    popoverWidth: settings.popover$.outerWidth(),
		    popoverHeight: settings.popover$.outerHeight(),
		    buttonWidth: button.outerWidth(),
		    buttonHeight: button.outerHeight(),
		    buttonOffset: button.offset()
			}
			
			// calculate the possible directions based on popover size and button position
			var possibleDir = calcPopoverDirPossible(button, coord);
				
			// choose selected direction
			coord.popoverDir = chooseDir(possibleDir);

	    // Calculate popover top
			if (coord.popoverDir == 'bottom')
	    	coord.popoverY = coord.buttonOffset.top + coord.buttonHeight + coord.triangleSize;
			else if (coord.popoverDir == 'top')
				coord.popoverY = coord.buttonOffset.top - coord.triangleSize - coord.popoverHeight;
			else // same Y for left & right
				coord.popoverY = coord.buttonOffset.top + (coord.buttonHeight - coord.popoverHeight)/2;

	    // Calculate popover left
			if ((coord.popoverDir == 'bottom') || (coord.popoverDir == 'top')) {
				
		    coord.popoverX = coord.buttonOffset.left + (coord.buttonWidth - coord.popoverWidth)/2;

		    if (coord.popoverX < settings.padding) {
		      // out of the document at left
		      coord.deltaX = coord.popoverX - settings.padding;
		    } else if (coord.popoverX + coord.popoverWidth > coord.docWidth - settings.padding) {
		      // out of the document right
		      coord.deltaX = coord.popoverX + coord.popoverWidth - coord.docWidth + settings.padding;
		    }

				// calc triangle pos
				coord.triangleX = coord.popoverWidth/2 - coord.triangleSize + coord.deltaX;				
				coord.triangleY = 0;	
			}
			else {	// left or right direction
				
				if (coord.popoverDir == 'right')
		    	coord.popoverX = coord.buttonOffset.left + coord.buttonWidth + coord.triangleSize;
				else // left
					coord.popoverX = coord.buttonOffset.left - coord.triangleSize - coord.popoverWidth;
				
		    if (coord.popoverY < settings.padding) {
		      // out of the document at top
		      coord.deltaY = coord.popoverY - settings.padding;
		    } else if (coord.popoverY + coord.popoverHeight > coord.docHeight - settings.padding) {
		      // out of the document bottom
		      coord.deltaY = coord.popoverY + coord.popoverHeight - coord.docHeight + settings.padding;
		    }

				// calc triangle pos
				coord.triangleX = 0;
				coord.triangleY = coord.popoverHeight/2 - coord.triangleSize + coord.deltaY;				
			}
	
			return coord;
		}
		
		function positionPopover(coord) {

			// set the triangle class for it's direction
			settings.triangle$.removeClass("left top right bottom");
			settings.triangle$.addClass(coord.popoverDir);

			if (coord.triangleX > 0) {
				settings.triangle$.css('left', coord.triangleX);
			}
			
			if (coord.triangleY > 0) {
				settings.triangle$.css('top', coord.triangleY);
			}

			// position popover
	    settings.popover$.offset({
	      top: coord.popoverY - coord.deltaY + settings.offsetY,
	      left: coord.popoverX - coord.deltaX + settings.offsetX
	    });
	
			// set popover css and show it
	    settings.popover$.css('z-index', settings.zindex).show();
		}

	  function showPopover(button) {
		
	    // Already open?
	    if ($.fn.popover.openedPopup === button) {
	      $.fn.popover.openedPopup.trigger('hidePopover');
	      return false;
	    } else if($.fn.popover.openedPopup != null){
	      $.fn.popover.openedPopup.trigger('hidePopover');
	    }
	
			// clicking triangle will also close the popover
		  settings.triangle$.click(function() {
	       button.trigger('hidePopover') });
	
			// reset triangle
			settings.triangle$.attr("style", "");
	
			// calculate all the coordinates needed for positioning the popover and position it	
			positionPopover(calcPopoverPos(button));

	    //Timeout for webkit transitions to take effect
	    setTimeout(function() {
	      settings.popover$.addClass("active");
	    }, 0);
	
	    if ($.isFunction(settings.openEvent)) settings.openEvent();
	    $.fn.popover.openedPopup = button;
	    button.addClass('popover-on');
	
			$(document).trigger('popoverOpened');
	
	    return false;
	  }

	  return this.each(function(){
	    var button = $(this);
	    button.addClass("popover-button");
			if (settings.closeOnEsc)
				$(document).bind('keydown', keyDown);
	    button.bind('click', function() { 
				showPopover(button);
				return false;
			});
	    button.bind('showPopover', function() { 
				showPopover(button);
				return false;
			});
	    button.bind('hidePopover', function() {
	      button.removeClass('popover-on');
				$(document).trigger('popoverClosed');
	      settings.popover$.removeClass("active").attr("style", "").hide();
	      if ($.isFunction(settings.closeEvent)) {
	        settings.closeEvent();
	      }
	      $.fn.popover.openedPopup = null;
	      return false;
	    });
	  });
	};
})(jQuery);
