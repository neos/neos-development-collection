define([
	'./tab',
	'NeosNodeSelection'
], function (
	Tab,
	NodeSelection
) {
	'use strict';

	// TODO: retrieve clean Ember reference
	var currentlyShownTabs = Ember.A([]);
	Ember.Object.extend({
		currentlyShownTabs: currentlyShownTabs,
		pushStateToModelIfCurrentlyShownTabsIsZero: function() {
			if (this.get('currentlyShownTabs.length') === 0 && NodeSelection.get('currentlyShownSecondaryAlohaTabs')) {
				NodeSelection.set('currentlyShownSecondaryAlohaTabs', false);
			}

			if (this.get('currentlyShownTabs.length') > 0 && !NodeSelection.get('currentlyShownSecondaryAlohaTabs')) {
				// execute in next run loop in order to make sure all aloha controls have been updated.
				NodeSelection.set('currentlyShownSecondaryAlohaTabs', this.get('currentlyShownTabs'));
			}
		}.observes('currentlyShownTabs.length')
	}).create();

	return Tab.extend({
		_constructor: function (context, settings, components) {
			this._super(context, settings, components);
		},
		show: function() {
			this._super();
			currentlyShownTabs.addObject(this);
		},
		hide: function() {
			this._super();
			currentlyShownTabs.removeObject(this);
		}
	});
});