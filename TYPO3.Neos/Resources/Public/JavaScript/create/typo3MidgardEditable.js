define(['jquery', 'jquery-ui', 'createjs'], function($) {
	/**
	 * This jQuery UI widget contains behavior adjustments to midgard editable
	 * such that they work well in a Phoenix environment
	 */
	$.widget('typo3.typo3MidgardEditable', $.Midgard.midgardEditable, {
		findEditableElements: function (callback) {
				// we change the way we find editable elements. Before, $('[property]') was used,
				// which also matches on <script> tags. Furthermore, we have also properties like
				// the HTML source which is only editable through the side panel.

				// Now, we only match every property which is marked as t3-inline-editable; a CSS class being added
				// by the <t3.contentElement.editable> ViewHelper.
			this.vie.service('rdfa').findPredicateElements(this.options.model.id, $('.t3-inline-editable[property]:not(script)', this.element), false).each(callback);
		}
	})
});