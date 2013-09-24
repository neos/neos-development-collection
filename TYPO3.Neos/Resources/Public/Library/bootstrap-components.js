/* ==========================================================
 * bootstrap-alert.js v2.2.2
 * http://twitter.github.com/bootstrap/javascript.html#alerts
 * ==========================================================
 * Copyright 2012 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */


!function ($) {

  "use strict"; // jshint ;_;


 /* ALERT CLASS DEFINITION
  * ====================== */

  var dismiss = '[data-dismiss="alert"]'
    , Alert = function (el) {
        $(el).on('click', dismiss, this.close)
      }

  Alert.prototype.close = function (e) {
    var $this = $(this)
      , selector = $this.attr('data-target')
      , $parent

    if (!selector) {
      selector = $this.attr('href')
      selector = selector && selector.replace(/.*(?=#[^\s]*$)/, '') //strip for ie7
    }

    $parent = $(selector)

    e && e.preventDefault()

    $parent.length || ($parent = $this.hasClass('neos-alert') ? $this : $this.parent())

    $parent.trigger(e = $.Event('close'))

    if (e.isDefaultPrevented()) return

    $parent.removeClass('neos-in')

    function removeElement() {
      $parent
        .trigger('closed')
        .remove()
    }

    $.support.transition && $parent.hasClass('neos-fade') ?
      $parent.on($.support.transition.end, removeElement) :
      removeElement()
  }


 /* ALERT PLUGIN DEFINITION
  * ======================= */

  var old = $.fn.alert

  $.fn.alert = function (option) {
    return this.each(function () {
      var $this = $(this)
        , data = $this.data('alert')
      if (!data) $this.data('alert', (data = new Alert(this)))
      if (typeof option == 'string') data[option].call($this)
    })
  }

  $.fn.alert.Constructor = Alert


 /* ALERT NO CONFLICT
  * ================= */

  $.fn.alert.noConflict = function () {
    $.fn.alert = old
    return this
  }


 /* ALERT DATA-API
  * ============== */

  $(document).on('click.neos-alert.data-api', dismiss, Alert.prototype.close)

}(window.jQuery);
/* ============================================================
 * bootstrap-dropdown.js v2.3.1
 * http://twitter.github.com/bootstrap/javascript.html#dropdowns
 * ============================================================
 * Copyright 2012 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================ */


!function ($) {

	"use strict"; // jshint ;_;


	/* DROPDOWN CLASS DEFINITION
	 * ========================= */

	var toggle = '[data-toggle=dropdown]'
		, Dropdown = function (element) {
			var $el = $(element).on('click.neos-dropdown.data-api', this.toggle)
			$('html').on('click.neos-dropdown.data-api', function () {
				$el.parent().removeClass('neos-open')
			})
		}

	Dropdown.prototype = {

		constructor: Dropdown

		, toggle: function (e) {
			var $this = $(this)
				, $parent
				, isActive

			if ($this.is('.neos-disabled, :disabled')) return

			$parent = getParent($this)

			isActive = $parent.hasClass('neos-open')

			clearMenus()

			if (!isActive) {
				$parent.toggleClass('neos-open')
			}

			$this.focus()

			return false
		}

		, keydown: function (e) {
			var $this
				, $items
				, $active
				, $parent
				, isActive
				, index

			if (!/(38|40|27)/.test(e.keyCode)) return

			$this = $(this)

			e.preventDefault()
			e.stopPropagation()

			if ($this.is('.neos-disabled, :disabled')) return

			$parent = getParent($this)

			isActive = $parent.hasClass('neos-open')

			if (!isActive || (isActive && e.keyCode == 27)) {
				if (e.which == 27) $parent.find(toggle).focus()
				return $this.click()
			}

			$items = $('[role=menu] li:not(.neos-divider):visible a', $parent)

			if (!$items.length) return

			index = $items.index($items.filter(':focus'))

			if (e.keyCode == 38 && index > 0) index--                                        // up
			if (e.keyCode == 40 && index < $items.length - 1) index++                        // down
			if (!~index) index = 0

			$items
				.eq(index)
				.focus()
		}

	}

	function clearMenus() {
		$(toggle).each(function () {
			getParent($(this)).removeClass('neos-open')
		})
	}

	function getParent($this) {
		var selector = $this.attr('data-target')
			, $parent

		if (!selector) {
			selector = $this.attr('href')
			selector = selector && /#/.test(selector) && selector.replace(/.*(?=#[^\s]*$)/, '') //strip for ie7
		}

		$parent = selector && $(selector)

		if (!$parent || !$parent.length) $parent = $this.parent()

		return $parent
	}


	/* DROPDOWN PLUGIN DEFINITION
	 * ========================== */

	var old = $.fn.dropdown

	$.fn.dropdown = function (option) {
		return this.each(function () {
			var $this = $(this)
				, data = $this.data('dropdown')
			if (!data) $this.data('dropdown', (data = new Dropdown(this)))
			if (typeof option == 'string') data[option].call($this)
		})
	}

	$.fn.dropdown.Constructor = Dropdown


	/* DROPDOWN NO CONFLICT
	 * ==================== */

	$.fn.dropdown.noConflict = function () {
		$.fn.dropdown = old
		return this
	}


	/* APPLY TO STANDARD DROPDOWN ELEMENTS
	 * =================================== */

	$(document)
		.on('click.neos-dropdown.data-api', clearMenus)
		.on('click.neos-dropdown.data-api', '.neos-dropdown form', function (e) { e.stopPropagation() })
		.on('click.neos-dropdown-menu', function (e) { e.stopPropagation() })
		.on('click.neos-dropdown.data-api'  , toggle, Dropdown.prototype.toggle)
		.on('keydown.neos-dropdown.data-api', toggle + ', [role=menu]' , Dropdown.prototype.keydown)

}(window.jQuery);
/* ===========================================================
 * bootstrap-tooltip.js v2.3.2
 * http://twitter.github.com/bootstrap/javascript.html#tooltips
 * Inspired by the original jQuery.tipsy by Jason Frame
 * ===========================================================
 * Copyright 2012 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */


!function ($) {

	"use strict"; // jshint ;_;


	/* TOOLTIP PUBLIC CLASS DEFINITION
	 * =============================== */

	var Tooltip = function (element, options) {
		this.init('tooltip', element, options)
	}

	Tooltip.prototype = {

		constructor: Tooltip

		, init: function (type, element, options) {
			var eventIn
				, eventOut
				, triggers
				, trigger
				, i

			this.type = type
			this.$element = $(element)
			this.options = this.getOptions(options)
			this.enabled = true

			triggers = this.options.trigger.split(' ')

			for (i = triggers.length; i--;) {
				trigger = triggers[i]
				if (trigger == 'click') {
					this.$element.on('click.neos-' + this.type, this.options.selector, $.proxy(this.toggle, this))
				} else if (trigger != 'manual') {
					eventIn = trigger == 'hover' ? 'mouseenter' : 'focus'
					eventOut = trigger == 'hover' ? 'mouseleave' : 'blur'
					this.$element.on(eventIn + '.' + this.type, this.options.selector, $.proxy(this.enter, this))
					this.$element.on(eventOut + '.' + this.type, this.options.selector, $.proxy(this.leave, this))
				}
			}

			this.options.selector ?
				(this._options = $.extend({}, this.options, { trigger: 'manual', selector: '' })) :
				this.fixTitle()
		}

		, getOptions: function (options) {
			options = $.extend({}, $.fn[this.type].defaults, this.$element.data(), options)

			if (options.delay && typeof options.delay == 'number') {
				options.delay = {
					show: options.delay
					, hide: options.delay
				}
			}

			return options
		}

		, enter: function (e) {
			var defaults = $.fn[this.type].defaults
				, options = {}
				, self

			this._options && $.each(this._options, function (key, value) {
				if (defaults[key] != value) options[key] = value
			}, this)

			self = $(e.currentTarget)[this.type](options).data(this.type)

			if (!self.options.delay || !self.options.delay.show) return self.show()

			clearTimeout(this.timeout)
			self.hoverState = 'in'
			this.timeout = setTimeout(function() {
				if (self.hoverState == 'in') self.show()
			}, self.options.delay.show)
		}

		, leave: function (e) {
			var self = $(e.currentTarget)[this.type](this._options).data(this.type)

			if (this.timeout) clearTimeout(this.timeout)
			if (!self.options.delay || !self.options.delay.hide) return self.hide()

			self.hoverState = 'out'
			this.timeout = setTimeout(function() {
				if (self.hoverState == 'out') self.hide()
			}, self.options.delay.hide)
		}

		, show: function () {
			var $tip
				, pos
				, actualWidth
				, actualHeight
				, placement
				, tp
				, e = $.Event('show')

			if (this.hasContent() && this.enabled) {
				this.$element.trigger(e)
				if (e.isDefaultPrevented()) return
				$tip = this.tip()
				this.setContent()

				if (this.options.animation) {
					$tip.addClass('neos-fade')
				}

				placement = typeof this.options.placement == 'function' ?
					this.options.placement.call(this, $tip[0], this.$element[0]) :
					this.options.placement

				$tip
					.detach()
					.css({ top: 0, left: 0, display: 'block' })

				this.options.container ? $tip.appendTo(this.options.container) : $tip.insertAfter(this.$element)

				pos = this.getPosition()

				actualWidth = $tip[0].offsetWidth
				actualHeight = $tip[0].offsetHeight

				switch (placement) {
					case 'bottom':
						tp = {top: pos.top + pos.height, left: pos.left + pos.width / 2 - actualWidth / 2}
						break
					case 'top':
						tp = {top: pos.top - actualHeight, left: pos.left + pos.width / 2 - actualWidth / 2}
						break
					case 'left':
						tp = {top: pos.top + pos.height / 2 - actualHeight / 2, left: pos.left - actualWidth}
						break
					case 'right':
						tp = {top: pos.top + pos.height / 2 - actualHeight / 2, left: pos.left + pos.width}
						break
				}

				this.applyPlacement(tp, placement)
				this.$element.trigger('shown')
			}
		}

		, applyPlacement: function(offset, placement){
			var $tip = this.tip()
				, width = $tip[0].offsetWidth
				, height = $tip[0].offsetHeight
				, actualWidth
				, actualHeight
				, delta
				, replace

			$tip
				.offset(offset)
				.addClass('neos-' + placement)
				.addClass('neos-in')

			actualWidth = $tip[0].offsetWidth
			actualHeight = $tip[0].offsetHeight

			if (placement == 'top' && actualHeight != height) {
				offset.top = offset.top + height - actualHeight
				replace = true
			}

			if (placement == 'bottom' || placement == 'top') {
				delta = 0

				if (offset.left < 0){
					delta = offset.left * -2
					offset.left = 0
					$tip.offset(offset)
					actualWidth = $tip[0].offsetWidth
					actualHeight = $tip[0].offsetHeight
				}

				this.replaceArrow(delta - width + actualWidth, actualWidth, 'left')
			} else {
				this.replaceArrow(actualHeight - height, actualHeight, 'top')
			}

			if (replace) $tip.offset(offset)
		}

		, replaceArrow: function(delta, dimension, position){
			this
				.arrow()
				.css(position, delta ? (50 * (1 - delta / dimension) + "%") : '')
		}

		, setContent: function () {
			var $tip = this.tip()
				, title = this.getTitle()

			$tip.find('.neos-tooltip-inner')[this.options.html ? 'html' : 'text'](title)
			$tip.removeClass('neos-fade neos-in neos-top neos-bottom neos-left neos-right')
		}

		, hide: function () {
			var that = this
				, $tip = this.tip()
				, e = $.Event('hide')

			this.$element.trigger(e)
			if (e.isDefaultPrevented()) return

			$tip.removeClass('neos-in')

			function removeWithAnimation() {
				var timeout = setTimeout(function () {
					$tip.off($.support.transition.end).detach()
				}, 500)

				$tip.one($.support.transition.end, function () {
					clearTimeout(timeout)
					$tip.detach()
				})
			}

			$.support.transition && this.$tip.hasClass('neos-fade') ?
				removeWithAnimation() :
				$tip.detach()

			this.$element.trigger('hidden')

			return this
		}

		, fixTitle: function () {
			var $e = this.$element
			if ($e.attr('title') || typeof($e.attr('data-original-title')) != 'string') {
				$e.attr('data-original-title', $e.attr('title') || '').attr('title', '')
			}
		}

		, hasContent: function () {
			return this.getTitle()
		}

		, getPosition: function () {
			var el = this.$element[0]
			return $.extend({}, (typeof el.getBoundingClientRect == 'function') ? el.getBoundingClientRect() : {
				width: el.offsetWidth
				, height: el.offsetHeight
			}, this.$element.offset())
		}

		, getTitle: function () {
			var title
				, $e = this.$element
				, o = this.options

			title = $e.attr('data-original-title')
				|| (typeof o.title == 'function' ? o.title.call($e[0]) :  o.title)

			return title
		}

		, tip: function () {
			return this.$tip = this.$tip || $(this.options.template)
		}

		, arrow: function(){
			return this.$arrow = this.$arrow || this.tip().find(".neos-tooltip-arrow")
		}

		, validate: function () {
			if (!this.$element[0].parentNode) {
				this.hide()
				this.$element = null
				this.options = null
			}
		}

		, enable: function () {
			this.enabled = true
		}

		, disable: function () {
			this.enabled = false
		}

		, toggleEnabled: function () {
			this.enabled = !this.enabled
		}

		, toggle: function (e) {
			var self = e ? $(e.currentTarget)[this.type](this._options).data(this.type) : this
			self.tip().hasClass('neos-in') ? self.hide() : self.show()
		}

		, destroy: function () {
			this.hide().$element.off('.' + this.type).removeData(this.type)
		}

	}


	/* TOOLTIP PLUGIN DEFINITION
	 * ========================= */

	var old = $.fn.tooltip

	$.fn.tooltip = function ( option ) {
		return this.each(function () {
			var $this = $(this)
				, data = $this.data('tooltip')
				, options = typeof option == 'object' && option
			if (!data) $this.data('tooltip', (data = new Tooltip(this, options)))
			if (typeof option == 'string') data[option]()
		})
	}

	$.fn.tooltip.Constructor = Tooltip

	$.fn.tooltip.defaults = {
		animation: true
		, placement: 'top'
		, selector: false
		, template: '<div class="neos-tooltip"><div class="neos-tooltip-arrow"></div><div class="neos-tooltip-inner"></div></div>'
		, trigger: 'hover focus'
		, title: ''
		, delay: 0
		, html: false
		, container: false
	}


	/* TOOLTIP NO CONFLICT
	 * =================== */

	$.fn.tooltip.noConflict = function () {
		$.fn.tooltip = old
		return this
	}

}(window.jQuery);
/**
 * bootstrap-notify.js v1.0.0
 * --
 * Copyright 2012 Nijiko Yonskai <nijikokun@gmail.com>
 * Copyright 2012 Goodybag, Inc.
 * --
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

(function ($) {
  var Notification = function (element, options) {
    // Element collection
    this.$element = $(element);
    this.$note    = $('<div class="neos-alert"></div>');
    this.options  = $.extend(true, $.fn.notify.defaults, options, this.$element.data());

    // Setup from options
    if(this.options.transition)
      if(this.options.transition == 'fade')
        this.$note.addClass('neos-in').addClass(this.options.transition);
      else this.$note.addClass(this.options.transition);
    else this.$note.addClass('neos-fade').addClass('neos-in');

    if(this.options.type)
      this.$note.addClass('neos-alert-' + this.options.type);
    else this.$note.addClass('neos-alert-success');

    if(this.options.message)
      if(typeof this.options.message === 'string')
        this.$note.html(this.options.message);
      else if(typeof this.options.message === 'object')
        if(this.options.message.html)
          this.$note.html(this.options.message.html);
        else if(this.options.message.text)
          this.$note.text(this.options.message.text);

    if(this.options.closable)
      var link = $('<a class="neos-close neos-pull-right">&times;</a>');
      $(link).on('click', $.proxy(onClose, this));
      this.$note.prepend(link);

    return this;
  };

  onClose = function() {
    this.options.onClose();
    $(this.$note).remove();
    this.options.onClosed();
  };

  Notification.prototype.show = function () {
    if(this.options.fadeOut.enabled)
      this.$note.delay(this.options.fadeOut.delay || 3000).fadeOut('slow', $.proxy(onClose, this));

    this.$element.append(this.$note);
    this.$note.alert();
  };

  Notification.prototype.hide = function () {
    if(this.options.fadeOut.enabled)
      this.$note.delay(this.options.fadeOut.delay || 3000).fadeOut('slow', $.proxy(onClose, this));
    else onClose.call(this);
  };

  $.fn.notify = function (options) {
    return new Notification(this, options);
  };

  $.fn.notify.defaults = {
    type: 'success',
    closable: true,
    transition: 'fade',
    fadeOut: {
      enabled: true,
      delay: 3000
    },
    message: null,
    onClose: function () {},
    onClosed: function () {}
  }
})(window.jQuery);