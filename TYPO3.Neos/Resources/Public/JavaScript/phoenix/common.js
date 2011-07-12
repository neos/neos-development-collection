/**
 * T3.Common
 *
 * Contains JavaScript which is needed in all modules
 */

define(
['phoenix/fixture', 'text!phoenix/common/launcher.html'],
function(fixture, launcherTemplate) {

	var T3 = window.T3 || {};
	T3.Common = {};

	/**
	 * T3.Common.ModulesController
	 *
	 * Contains a list of available modules
	 */
	T3.Common.ModulesController = SC.Object.create({
		availableModules: [],
		filterValue: null,
		filteredModules: [],
		init: function() {
			this.setAvailableModules(fixture.availableModules);
		},
		setAvailableModules: function(modules) {
			var wrappedModules = modules.map(function(module) {
				return SC.Object.create(module);
			});
			this.set('availableModules', wrappedModules);
			this.set('filteredModules', wrappedModules);
		},
		_filterValueChange: function() {
			var lcFilterValue = this.get('filterValue').toLowerCase();
			if (lcFilterValue === '') {
				this.set('filteredModules', this.get('availableModules'));
			} else {
				this.set('filteredModules', this.get('availableModules').filter(function(module) {
					return module.get('label').toLowerCase().indexOf(lcFilterValue) >= 0;
				}, this));
			}
		}.observes('filterValue')
	});

	/**
	 * T3.Common.Launcher
	 *
	 * Implements the quicksilver-like launch bar. Consists of a textfield
	 * and a panel which is opened when the textfield is focussed.
	 */
	T3.Common.Launcher = SC.View.extend({
		tagName: 'div',
		classNames: ['t3-launcher'],
		value: '',
		open: false,
		template: SC.Handlebars.compile(launcherTemplate)
	});

	/**
	 * @internal
	 */
	T3.Common.Launcher.TextField = SC.TextField.extend({
		cancel: function() {
			this.set('value', '');
			this.$().blur();
		},
		focusIn: function() {
			this.set('value', '');
			this.set('open', true);
		},
		focusOut: function() {
			this.set('open', false);
			this._super();
		},
		keyDown: function(event) {
			// TODO Move to controller
			if (event.keyCode === 9) {
				this.$().closest('.t3-launcher').find('.t3-launcher-panel-modules li:first-child a').first().focus();
				return false;
			}
		}
	});

	/**
	 * @internal
	 */
	T3.Common.Launcher.Panel = SC.View.extend({
		tagName: 'div',
		classNames: ['t3-launcher-panel'],
		classNameBindings: ['open'],
		isVisible: false,
		open: false,
		focussed: false,
		templateName: 'launcher-panel',
		_openDidChange: function() {
			var that = this;
			// Delay the execution a bit to give the focus change a chance
			setTimeout(function() {
				var open = that.get('open');
				if (open) {
					that.$().slideDown('fast');
				} else {
					if (that.get('focussed')) return;
					that.$().slideUp('fast');
				}
			}, 50);
		}.observes('open'),
		focusIn: function() {
			this.set('focussed', true);
		},
		focusOut: function() {
			this.set('focussed', false);
		}
	});

	window.T3 = T3;
});