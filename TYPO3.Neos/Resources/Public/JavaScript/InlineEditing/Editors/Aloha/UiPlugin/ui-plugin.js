/**
 * The ui/ui-plugin module controls the creation and display of the UI.
 */
define([
	'jquery',
	'aloha',
	'ui/context',
	'ui/container',
	'ui/surface',
	// BEGIN MODIFICATION FOR NEOS
	'./toolbar',
	// END MODIFICATION FOR NEOS
	'ui/scopes',
	'ui/settings',
	'PubSub',
	// Most modules of the ui plugin depend on jquery-ui, but its easy
	// to forget to add the dependency so we do it here.
	'jqueryui'
], function (
	$,
	Aloha,
	Context,
	Container,
	Surface,
	Toolbar,
	Scopes,
	Settings,
	PubSub
	) {
	'use strict';

	var context = new Context(),
		toolbar = new Toolbar(context, getToolbarSettings());

	Aloha.bind('aloha-editable-activated', function (event, alohaEvent) {
		Surface.show(context);
		Container.showContainersForContext(context, event);
	});

	Aloha.bind('aloha-editable-deactivated', function (event, alohaEvent) {
		if (!Surface.suppressHide) {
			Surface.hide(context);
		}
	});

	PubSub.sub('aloha.ui.scope.change', function () {
		Container.showContainersForContext(context);
		primaryScopeForegroundTab(Scopes.getPrimaryScope());
	});

	function getToolbarSettings() {
		var userSettings = Aloha.settings.toolbar,
			defaultSettings = Settings.defaultToolbarSettings;
		if (!userSettings) {
			return defaultSettings.tabs;
		}
		return Settings.combineToolbarSettings(
			userSettings.tabs || [],
			defaultSettings.tabs,
			userSettings.exclude || []
		);
	}

	function primaryScopeForegroundTab() {
		var tabs = toolbar._tabs,
			primaryScope = Scopes.getPrimaryScope(),
			settings,
			i;
		for (i = 0; i < tabs.length; i++) {
			settings = tabs[i].settings;
			if ('object' === $.type(settings.showOn) && settings.showOn.scope === primaryScope) {
				tabs[i].tab.foreground();
				break;
			}
		}
	}

	/**
	 * Adopts a component instance into the UI.
	 *
	 * Usually, the implementation of this method will display the
	 * component, at a position in the UI given by the slot
	 * argument.
	 *
	 * @param slot A position argument that is interpreted by the UI however it likes.
	 * @param component An instance of a component to adopt into the given slot.
	 * @api
	 */
	function adoptInto(slot, component) {
		return toolbar.adoptInto(slot, component);
	}

	/**
	 * Shows the toolbar.
	 *
	 * By default, the toolbar will be hidden when no editable is
	 * activated, and shown when an editable is activated. Calling
	 * this function will show the toolbar regardless of whether an
	 * editable is activated.
	 *
	 * The toolbar will only become visible if tabs are visible as well.
	 * To make tabs visible, set a scope. For example
	 * Scopes.setScope('Aloha.continuoustext');
	 *
	 * Please note that the toolbar will not remain visible if an
	 * editable is subsequently deactivated.
	 *
	 * @param {?Object} event
	 *        An optional event argument that caused the toolbar to be show.
	 *        Will be passed on to Aloha.settings.toolbar.tabs[i].showOn functions.
	 * @api
	 */
	function showToolbar(event) {
		Surface.show(context);
		Container.showContainersForContext(context, event);
	}

	/**
	 * This module is part of the Aloha API.
	 * It is valid to override this module via requirejs to provide a
	 * custom behaviour. An overriding module must implement all API
	 * methods. Every member must have an api annotation. No non-api
	 * members are allowed.
	 * @api
	 */
	return {
		/**
		 * Adopts a component instance into the UI.
		 *
		 * Usually, the implementation of this method will display the
		 * component, at a position in the UI given by the slot
		 * argument.
		 *
		 * @param slot A position argument that is interpreted by the UI however it likes.
		 * @param component An instance of a component to adopt into the given slot.
		 * @api
		 */
		adoptInto: adoptInto,
		showToolbar: showToolbar
	};
});