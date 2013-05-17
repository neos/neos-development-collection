/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'neos/content/ui/elements/contentelement-handles',
	'neos/content/ui/elements/section-handles'
],
function($, Ember, ContentElementHandle, SectionHandle) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/elements');

	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}

	T3.Content.UI = T3.Content.UI || {};

	/**
	 * T3.Content.UI.Image
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	T3.Content.UI.Image = Ember.View.extend({
		tagName: 'img',
		attributeBindings: ['src']
	});

	T3.Content.UI.ContentElementHandle = ContentElementHandle;

	T3.Content.UI.SectionHandle = SectionHandle;
});