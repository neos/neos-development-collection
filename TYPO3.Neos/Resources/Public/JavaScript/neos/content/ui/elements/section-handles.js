/**
 */
define(
[
	'jquery',
	'vie/entity',
	'neos/content/ui/elements/contentelement-handles'
],
function ($, EntityWrapper, ContentElementHandle) {
	if (window._requirejsLoadingTrace) window._requirejsLoadingTrace.push('neos/content/ui/section-handles');

	return ContentElementHandle.extend({
		/**
		 * @var {Boolean}
		 */
		_showRemove: false,

		/**
		 * @var {Boolean}
		 */
		_showCut: false,

		/**
		 * @var {Boolean}
		 */
		_showCopy: false,

		/**
		 * @var {Boolean}
		 */
		_nestedContentElementsAvailable: false,

		/**
		 * @var {Object} jQuery object for the element to which the handles should be added
		 */
		_element: null,

		/**
		 * @var {Object} A jQuery widget of type typo3.typo3CollectionWidget
		 */
		_collection: null,

		/**
		 * @var {Integer} The position in the collection on which paste / new actions should place the new entity
		 */
		_entityCollectionIndex: null,

		/**
		 * @return {void}
		 */
		didInsertElement: function() {
			this._super();

			this._areNestedContentElementsAvailable();

			this.getPath('_collection.options.collection').on('change', this._areNestedContentElementsAvailable, this);
		},

		/**
		 * @return {void}
		 */
		_areNestedContentElementsAvailable: function() {
			var availableContentElements = 0;
			this.getPath('_collection.options.collection.models').forEach(function(entity) {
				var attributes = EntityWrapper.extractAttributesFromVieEntity(entity);
				if (attributes['_removed'] !== true) {
					availableContentElements++;
				}
			}, this);
			this.set('_nestedContentElementsAvailable', availableContentElements > 0 ? true : false);
		},

		/**
		 * @return {Boolean}
		 */
		_isShown: function() {
			if (!this.get('_nestedContentElementsAvailable')) {
				return true;
			}
			return (this.get('_nodePath') === T3.Content.Model.NodeSelection.getPath('selectedNode.nodePath'));
		}.property('T3.Content.Model.NodeSelection.selectedNode', '_nodePath', '_nestedContentElementsAvailable'),

		/**
		 * @return {void}
		 */
		_toggleVisibilityOnShownChange: function() {
			if (this.get('_isShown')) {
				this.$().show();
			} else {
				this.$().hide();
			}
		}.observes('_isShown')
	});
});