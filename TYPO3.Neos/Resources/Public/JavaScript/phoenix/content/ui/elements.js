/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'jquery',
	'phoenix/content/ui/elements/toolbar',
	'phoenix/content/ui/elements/button',
	'phoenix/content/ui/elements/toggle-button',
	'phoenix/content/ui/elements/popover-button',
	'phoenix/content/ui/elements/contentelement-handles',
	'jquery.popover'
],
function($, Toolbar, Button, ToggleButton, PopoverButton, ContentElementHandle) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('phoenix/content/ui/elements');

	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}

	T3.Content.UI = T3.Content.UI || {};

	/**
	 * T3.Content.UI.Toolbar
	 *
	 * Toolbar which can contain other views. Has two areas, left and right.
	 */
	T3.Content.UI.Toolbar = Toolbar;

	/**
	 * T3.Content.UI.Button
	 *
	 * A simple, styled TYPO3 button.
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	T3.Content.UI.Button = Button;

	/**
	 * T3.Content.UI.Image
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	T3.Content.UI.Image = Ember.View.extend({
		tagName: 'img',
		attributeBindings: ['src']
	});

	/**
	 * T3.Content.UI.ToggleButton
	 *
	 * A button which has a "pressed" state
	 *
	 * TODO: should be moved to T3.Common.UI.Button?
	 */
	T3.Content.UI.ToggleButton = ToggleButton;

	/**
	 * T3.Content.UI.PopoverButton
	 */
	T3.Content.UI.PopoverButton = PopoverButton;

	T3.Content.UI.ContentElementHandle = ContentElementHandle;
});