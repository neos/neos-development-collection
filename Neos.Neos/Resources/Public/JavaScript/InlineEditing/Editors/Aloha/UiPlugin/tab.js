define([
	'aloha/core',
	'jquery',
	'ui/container',
	'ui/component',
	'PubSub',
	'jqueryui'
], function (
	Aloha,
	$,
	Container,
	Component,
	PubSub
) {
	'use strict';

	var idCounter = 0;
	var slottedComponents = {};

	/**
	 * Defines a Container object that represents a collection of related
	 * component groups to be rendered together on the toolbar. Tabs are
	 * organized by feature and functionality so that related controls can be
	 * brought in and out of view depending on whether they are
	 * appropriate for a given user context.
	 *
	 * Tabs can be defined declaratively in the Aloha configuration in the
	 * following manner:
	 *
	 *    Aloha.settings.toolbar: [
	 *      {
	 *         label: 'Lists',
	 *         showOn: 'ul,ol,*.parent(.aloha-editable ul,.aloha-editable ol)',
	 *         components: [ [ 'orderedList', 'unorderedList' ] ]
	 *      }
	 *    ]
	 *
	 * Alternatively, tabs can also be created imperatively in this way:
	 * new Tab( options, components ).
	 *
	 * @class
	 * @extends {Container}
	 */
	var Tab = Container.extend({

		_elemBySlot: null,
		_groupBySlot: null,
		_groupByComponent: null,
		_settings: null,

		/**
		 * All that this constructor does is save the components array into a
		 * local variable, to be used during instantialization.
		 *
		 * @param {object} settings
		 * @param {Array.<Array<string>>} components
		 * @constructor
		 */
		_constructor: function (context, settings, components) {
			var i, j,
				elem,
				groupedComponents,
				group,
				groupProps,
				componentName;

			this._elemBySlot = {};
			this._groupBySlot = {};
			this._groupByComponent = {};
			this._super(context, settings);

			this._settings = settings;

			this.container = settings.container;
			this.panels = this.container.data('panels');
			this.id = 'tab-ui-container-' + (++idCounter);
			this.panel = $('<div>', {id : this.id, 'unselectable': 'on'});

			for (i = 0; i < components.length; i++) {
				if (typeof components[i] === 'string') {
					if (1 === components[i].length && components[i].charCodeAt(0) === 10) {
						this.panel.append('<span>', {'unselectable': 'on'});
					} else {
						elem = $('<span>', {'unselectable': 'on'});
						this._elemBySlot[components[i]] = elem;
						this.panel.append(elem);
					}
				} else {
					// Hide the group until the first button is adopted into it.
					group = $('<span>', {
						'class': 'neos-aloha-ui-component-group aloha-ui-hidden',
						'unselectable': 'on'
					}).appendTo(this.panel);
					groupProps = {element: group, visibleCounter: 0};
					groupedComponents = components[i];
					for (j = 0; j < groupedComponents.length; j++) {
						this._groupBySlot[groupedComponents[j]] = groupProps;
						if (groupedComponents[j] &&
							1 === groupedComponents[j].length &&
							groupedComponents[j].charCodeAt(0) === 10) {
							group.append($('<span>', {'unselectable': 'on'}));
						} else {
							componentName = groupedComponents[j];
							elem = $('<span>', {'unselectable': 'on'});
							this._elemBySlot[groupedComponents[j]] = elem;
							group.append(elem);
						}
					}
				}
			}

			this.panel.append($('<div>', {'class': 'aloha-ui-clear', 'unselectable': 'on'}));
			this.panel.appendTo(this.panels);
		},

		adoptInto: function(slot, component) {
			var elem = this._elemBySlot[slot],
				group;
			if (!elem) {
				return false;
			}
			slottedComponents[slot] = component;
			component.adoptParent(this);
			elem.append(component.element);
			group = this._groupBySlot[slot];
			if (group) {
				this._groupByComponent[component.id] = group;
				if (component.isVisible()) {
					if (!group.visibleCounter) {
						group.element.removeClass('aloha-ui-hidden');
					}
					group.visibleCounter += 1;
				}
			}
			return true;
		},

		_getSlottedComponents: function() {
			return slottedComponents;
		},

		foreground: function() {
			// We always show all "tabs", so nothing to foreground here
		},

		childForeground: function(childComponent) {
			// We always show all "tabs", so nothing to foreground here
		},

		hasVisibleComponents: function () {
			var siblings = this._elemBySlot;
			var slot;
			for (slot in siblings) {
				if (siblings.hasOwnProperty(slot) && slottedComponents[slot]) {
					if (slottedComponents[slot].visible) {
						return true;
					}
				}
			}
			return false;
		},

		childVisible: function(childComponent, visible) {
			if (visible) {
				childComponent.container.show();
			} else if (!childComponent.container.hasVisibleComponents()) {
				childComponent.container.hide();
			}
			var group = this._groupByComponent[childComponent.id];
			if (!group) {
				return;
			}
			if (visible) {
				if (0 === group.visibleCounter) {
					group.element.removeClass('aloha-ui-hidden');
				}
				group.visibleCounter += 1;
			} else {
				group.visibleCounter -= 1;
				if (0 === group.visibleCounter) {
					group.element.addClass('aloha-ui-hidden');
				}
			}
		},

		/**
		 * @override
		 */
		show: function() {
			this.visible = true;

			// Hiding all tabs may hide the toolbar, so showing the
			// first tab again must also show the toolbar.
			this.panel.show();

			this.foreground();
		},

		/**
		 * @override
		 */
		hide: function() {
			this.panel.hide();
			this.visible = false;
		}

	});

	$.extend(Tab, {
		/**
		 * Creates holding elements for jQuery UI Tabs for a surface.
		 *
		 * @static
		 * @return {jQuery.<HTMLElement>} The holder container on which we
		 *                                invoke jQuery UI Tabs once it is
		 *                                populated with tab containers.
		 */
		createContainer: function () {
			var $container = $('<div>', {'unselectable': 'on'});
			var $panels = $('<div>', {'unselectable': 'on'}).appendTo($container);

			$container
				.data('panels', $panels);

			return $container;
		}
	});

	return Tab;
});
