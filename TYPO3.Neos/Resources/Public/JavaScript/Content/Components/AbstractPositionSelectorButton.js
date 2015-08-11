/**
 * AbstractPositionSelectorButton, used to select position for new and paste
 * operations in the tree and in inline handles.
 */
define([
	'emberjs',
	'text!./AbstractPositionSelectorButton.html'
],
function (
	Ember,
	template
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),

		// Private API: set when extending
		desiredPosition: 'after',
		allowedPositions: null,
		title: null,
		iconClass: null,
		mouseUp: function(event) {},

		isActive: true,

		isExpanded: false,

		attributeBindings: ['title'],

		classNameBindings: [
			':neos-position-selector',
			':neos-button',
			'isActive::neos-disabled',
			'isDisabled:neos-disabled',
			'isExpanded:neos-expanded',
			'activePositionCssClass'
		],

		downTimer: null,

		toggleSelectorOption: function(newPosition) {
			this.set('desiredPosition', newPosition);
		},

		position: function() {
			var allowedPositions = this.get('allowedPositions'),
				desiredPosition = this.get('desiredPosition');
			return allowedPositions.indexOf(desiredPosition) !== -1 ? desiredPosition : allowedPositions[allowedPositions.length - 1];
		}.property('allowedPositions', 'desiredPosition'),

		activePositionCssClass: function() {
			return 'neos-position-selector-node-' + this.get('position');
		}.property('position'),

		isDisabled: function() {
			return this.get('allowedPositions').length === 0;
		}.property('allowedPositions'),

		mouseDown: function(event) {
			if (this.get('isExpanded') === true) {
				this.set('isExpanded', false);
			} else {
				var that = this;
				clearTimeout(this.get('downTimer'));
				this.set('downTimer', setTimeout(function() {
					that.set('isExpanded', true);
				}, 300));
			}
		},

		mouseLeave: function() {
			this.set('isExpanded', false);
		},

		PositionSelectorOption: Ember.View.extend({
			// Set position to either `into`, `before` or `after` when using
			position: Ember.required(),

			tagName: 'button',

			currentPositionBinding: 'parentView.position',

			allowedPositionsBinding: 'parentView.allowedPositions',

			classNameBindings: [
				'iconClass',
				':neos-button',
				'isActive:neos-active',
				'isDisabled:neos-disabled'
			],

			iconClass: function () {
				switch (this.get('position')) {
					case 'before':
						return 'icon-level-up';
					case 'into':
						return 'icon-long-arrow-right';
					case 'after':
						return 'icon-level-down';
					default:
						return '';
				}
			}.property('position'),

			isActive: function() {
				return this.get('isDisabled') === false && this.get('position') === this.get('currentPosition');
			}.property('isDisabled', 'currentPosition'),

			isDisabled: function() {
				if (this.get('allowedPositions')) {
					return this.get('allowedPositions').indexOf(this.get('position')) === -1;
				} else {
					return false;
				}
			}.property('allowedPositions.@each'),

			mouseDown: function(event) {
				if (!this.get('isDisabled')) {
					this.get('parentView').toggleSelectorOption(this.get('position'));
				} else {
					event.stopPropagation();
				}
			}
		})
	});
});
