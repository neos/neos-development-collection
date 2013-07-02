define([
	'./tab'
], function (
	Tab
) {
	'use strict';

	// TODO: retrieve clean Ember reference
	var currentlyShownTabs = Ember.A([]);
	Ember.Object.extend({
		currentlyShownTabs: currentlyShownTabs,
		pushStateToModelIfCurrentlyShownTabsIsZero: function() {
			var that = this;
			if (this.get('currentlyShownTabs.length') === 0 && T3.Content.Model.NodeSelection.get('currentlyShownSecondaryAlohaTabs')) {
				T3.Content.Model.NodeSelection.set('currentlyShownSecondaryAlohaTabs', false);
			}

			if (this.get('currentlyShownTabs.length') > 0 && !T3.Content.Model.NodeSelection.get('currentlyShownSecondaryAlohaTabs')) {
				// execute in next run loop in order to make sure all aloha controls have been updated.
				T3.Content.Model.NodeSelection.set('currentlyShownSecondaryAlohaTabs', that.get('currentlyShownTabs'));
			}
		}.observes('currentlyShownTabs.length')
	}).create();


	var TabInSecondaryContainer = Tab.extend({
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

	return TabInSecondaryContainer;
});
