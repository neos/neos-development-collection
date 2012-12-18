/**
 */
define(
[
	'jquery',
	'neos/content/ui/elements/contentelement-handles'
],
function ($, ContentElementHandle) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/section-handles');

	return ContentElementHandle.extend({
		_showRemove: false,
		_showCut: false,
		_showCopy: false,

		_nestedContentElementsAvailable: false,

		didInsertElement: function() {
			this._super();

			if (this.get('_element').find('.t3-contentelement').length > 0) {
				this.set('_nestedContentElementsAvailable', true);
			}
		},

		_isShown: function() {
			if (!this.get('_nestedContentElementsAvailable')) {
				return true;
			}
			return (this.get('_nodePath') === T3.Content.Model.NodeSelection.getPath('selectedNode.nodePath'));
		}.property('T3.Content.Model.NodeSelection.selectedNode', '_nodePath', '_nestedContentElementsAvailable'),

		_toggleVisibilityOnShownChange: function() {
			if (this.get('_isShown')) {
				this.$().css('visibility', 'visible');
			} else {
				this.$().css('visibility', 'hidden');
			}
		}.observes('_isShown')
	});
});