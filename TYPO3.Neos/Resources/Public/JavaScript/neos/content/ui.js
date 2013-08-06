/**
 * T3.Content.UI
 *
 * Contains UI elements for the Content Module
 */

define(
[
	'Library/jquery-with-dependencies',
	'emberjs',
	'text!neos/templates/content/ui/saveIndicator.html',
	'neos/content/ui/elements'
],

function($, Ember, saveIndicatorTemplate) {
	if (window._requirejsLoadingTrace) {
		window._requirejsLoadingTrace.push('neos/content/ui');
	}

	var T3 = window.T3 || {};
	if (typeof T3.Content === 'undefined') {
		T3.Content = {};
	}
	T3.Content.UI = T3.Content.UI || {};

	/**
	 * =====================
	 * SECTION: TREE PANEL
	 * =====================
	 */

		// Is necessary otherwise a button has always the class 'btn-mini'
	T3.Content.UI.ButtonDialog = Ember.View.extend(Ember.TargetActionSupport, {
		tagName: 'button',
		attributeBindings: ['disabled'],
		label: '',
		disabled: false,
		visible: true,
		icon: '',
		template: Ember.Handlebars.compile('{{#if view.icon}}<i class="{{unbound view.icon}}"></i> {{/if}}{{view.label}}'),

		click: function() {
			this.triggerAction();
		}
	});

	/**
	 * =====================
	 * SECTION: INSPECT TREE
	 * =====================
	 * - Inspect TreeButton
	 */

	T3.Content.UI.SaveIndicator = Ember.View.extend({
		saveRunning: false,
		lastSuccessfulTransfer: null,

		template: Ember.Handlebars.compile(saveIndicatorTemplate),

		lastSuccessfulTransferLabel: function() {
			var date = this.get('lastSuccessfulTransfer');
			if (date !== null) {
				function pad(n) {
					return n < 10 ? '0' + n : n;
				}
				return 'Saved at ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds())
			}
			return '';
		}.property('lastSuccessfulTransfer')
	});

	/**
	 * ================
	 * SECTION: UTILITY
	 * ================
	 * - Content Element Handle Utilities
	 */
	T3.Content.UI.Util = T3.Content.UI.Util || {};

	T3.Content.UI.Util.AddNotInlineEditableOverlay = function($element, entity) {
		var setOverlaySizeFn = function() {
				// We use a timeout here to make sure the browser has re-drawn; thus $element
				// has a possibly updated size
			window.setTimeout(function() {
				$element.find('> .neos-contentelement-overlay').css({
					'width': $element.width(),
					'height': $element.height()
				});
			}, 10);
		};

			// Add overlay to content elements without inline editable properties and no sub-elements
		if ($element.hasClass('neos-not-inline-editable')) {
			var overlay = $('<div />', {
				'class': 'neos-contentelement-overlay'
			}).prependTo($element);

			var nodeTypeConfiguration = entity.get('@type').pop().metadata;

			$('<span />', {'class': 'neos-contentelement-overlay-icon'}).append($('<i />', {class: nodeTypeConfiguration.ui.icon})).appendTo(overlay);

			setOverlaySizeFn();

			entity.on('change', function() {
					// If the entity changed, it might happen that the size changed as well; thus we need to reload the overlay size
				setOverlaySizeFn();
			});
		}
	};
});
