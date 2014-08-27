define([
	'jquery',
	'aloha/core',
	'ui/surface',
	'./tab',
	'./tabInSecondaryContainer',
	'ui/context',
	'i18n!ui/nls/i18n',
	'jqueryui'
], function (
	$,
	Aloha,
	Surface,
	Tab,
	TabInSecondaryContainer,
	Context,
	i18n
) {
	'use strict';

	/**
	 * The toolbar is configured via `settings.toolbar` and is defined as an
	 * array of tabs with component groups, where the groups are arrays of
	 * controls.
	 *
	 * There are separate components for each context, but only the components
	 * for the active context are shown.
	 *
	 * As a container for tabs, the toolbar serves to group together groups of
	 * control components so that they can be shown and hidden together in their
	 * feature/functional set. For example groups of table controls would be
	 * placed in a table tab, groups of list controls in an image tab, and so
	 * forth.
	 *
	 * Toolbar class and manager
	 *
	 * @class
	 * @extends {Surface}
	 */
	var Toolbar = Surface.extend({
		_moveTimeout: null,
		$_container: null,
		$_secondaryContainer: null,
		_tabBySlot: null,
		_tabs: [],

		/**
		 * Toolbar constructor.
		 *
		 * @param {!Array.<(Object|Array|string)>} tabs
		 * @constructor
		 * @override
		 */
		_constructor: function (context, tabs) {
			var i;
			this._super(context);
			this.$topmenuContainer = $('<div>', {'class': 'aloha-ui aloha-ui-toolbar', 'unselectable': 'on'});
			this.$inspectorContainer = $('<div>', {'class': 'aloha-ui aloha-ui-toolbar', 'unselectable': 'on'});

			this.$_container = Tab.createContainer().appendTo(this.$topmenuContainer);

			// we create an INVISIBLE secondary container for all but the primary tabs; as we
			// render the UI with custom controls instead of the Aloha-based ones. However, because
			// this is not cleanly encapsulated in Aloha, we basically let them render to a detached DOM
			// element and keep them manually in sync with the corresponding Neos controls.
			this.$_secondaryContainer = Tab.createContainer();
			this._tabBySlot = {};

			this._initTab(Tab, context, tabs[0], this.$_container);
			for (var i=1, l=tabs.length; i < l; i++) {
				this._initTab(TabInSecondaryContainer, context, tabs[i], this.$_secondaryContainer);
			}

		},

		_initTab: function(clazz, context, tabSettings, container) {
			var tabInstance = new clazz(context, {
				label: i18n.t(tabSettings.label, tabSettings.label),
				showOn: tabSettings.showOn,
				container: container
			}, tabSettings.components);
			for (var key in tabInstance._elemBySlot) {
				if (tabInstance._elemBySlot.hasOwnProperty(key)) {
					this._tabBySlot[key] = tabInstance;
				}
			}
			this._tabs.push({tab: tabInstance, settings: tabSettings});
		},

		adoptInto: function (slot, component) {
			var tab = this._tabBySlot[slot];
			return tab && tab.adoptInto(slot, component);
		},

		/**
		 * Shows the toolbar.
		 */
		show: function () {
			Toolbar.$topmenuSurfaceContainer.children().detach();
			Toolbar.$topmenuSurfaceContainer.append(this.$topmenuContainer);
			Toolbar.$topmenuSurfaceContainer.show();

			Toolbar.$inspectorSurfaceContainer.children().detach();
			Toolbar.$inspectorSurfaceContainer.append(this.$inspectorContainer);
			Toolbar.$inspectorSurfaceContainer.show();

			$('body').addClass('neos-inline-editing-active');
		},

		/**
		 * Hides the toolbar.
		 */
		hide: function () {
			Toolbar.$topmenuSurfaceContainer.hide();
			Toolbar.$topmenuSurfaceContainer.children().detach();

			Toolbar.$inspectorSurfaceContainer.hide();
			Toolbar.$inspectorSurfaceContainer.children().detach();

			$('body').removeClass('neos-inline-editing-active');
		}
	});

	$.extend(Toolbar, {

		/**
		 * An element on which all toolbar surfaces are to be rendered on the
		 * page.
		 * @type {jQuery.<HTMLElement>}
		 */
		$topmenuSurfaceContainer: null,

		$inspectorSurfaceContainer: null,

		/**
		 * Initializes the toolbar manager. Adds the surface container
		 * element, and sets up floating behaviour settings.
		 */
		init: function () {
			// TODO should use context.js to get the context element
			Toolbar.$topmenuSurfaceContainer = $('<div>', {
				'class': 'aloha aloha-surface aloha-toolbar',
				'unselectable': 'on'
			}).hide();

			Toolbar.$inspectorSurfaceContainer = $('<div>', {
				'class': 'aloha aloha-surface aloha-toolbar',
				'unselectable': 'on'
			}).hide();

			// In the built aloha.js, init will happened before the body has
			// finished loading, so we have to defer appending the element.

			$(function () {
				window.setTimeout(function() {
					Toolbar.$topmenuSurfaceContainer.appendTo('#neos-aloha-topmenu');
					Toolbar.$inspectorSurfaceContainer.appendTo('#neos-aloha-inspectormenu');
					Surface.trackRange(Toolbar.$topmenuSurfaceContainer);
					Surface.trackRange(Toolbar.$inspectorSurfaceContainer);
					Surface.trackRange($('#neos-application'));
				}, 50);
			});
		}
	});

	Toolbar.init();

	return Toolbar;
});