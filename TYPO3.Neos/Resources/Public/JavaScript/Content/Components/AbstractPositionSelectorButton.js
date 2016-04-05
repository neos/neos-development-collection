/**
 * AbstractPositionSelectorButton, used to select position for new and paste
 * operations in the tree and in inline editing handles.
 */
define([
	'emberjs',
	'Shared/I18n',
	'text!./AbstractPositionSelectorButton.html'
],
function (
	Ember,
	I18n,
	template
) {
	return Ember.View.extend({
		template: Ember.Handlebars.compile(template),

		// Private API: set when extending
		desiredPosition: 'after',
		allowedPositions: null,
		title: null,
		iconClass: null,
		triggerAction: Ember.required,

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

		hoverTimer: null,

		isMouseInside: false,

		toggleSelectorOption: function(newPosition) {
			this.set('desiredPosition', newPosition);
			this.triggerAction(this.get('position'));
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

		mouseUp: function(event) {
			clearTimeout(this.get('downTimer'));
			this.set('downTimer', null);
			if (this.get('isActive') === true && this.get('isDisabled') === false && this.get('isExpanded') === false) {
				this.triggerAction(this.get('position'));
			}
		},

		mouseEnter: function(event) {
			if (this.get('isDisabled') === false) {
				var that = this;
				that.set('isMouseInside', true);
				clearTimeout(this.get('hoverTimer'));
				this.set('hoverTimer', setTimeout(function() {
					if (that.get('isMouseInside') === true) {
						that.set('isExpanded', true);
					}
				}, 700));
			}
		},

		mouseLeave: function() {
			this.set('isMouseInside', false);
			this.set('isExpanded', false);
		},

		didInsertElement: function() {
			this.$().tooltip({container: '#neos-application'});
		},

		PositionSelectorOption: Ember.View.extend({
			// Set position to either `into`, `before` or `after` when using
			position: Ember.required(),

			tagName: 'button',

			type: null, // "new" or "paste"

			currentPositionBinding: 'parentView.position',

			allowedPositionsBinding: 'parentView.allowedPositions',

			classNameBindings: [
				'iconClass',
				':neos-button',
				'isActive:neos-active',
				'isDisabled:neos-disabled'
			],

			attributeBindings: ['title'],

			iconClass: function () {
				switch (this.get('position')) {
					case 'before':
						return 'icon-level-up';
					case 'into':
						return 'icon-long-arrow-right';
					case 'after':
						return 'icon-level-down';
				}
				return '';
			}.property('position'),

			title: function () {
				var type = this.get('type');
				switch (this.get('position')) {
					case 'before':
						return I18n.translate(type + 'Before');
					case 'into':
						return I18n.translate(type + 'Into');
					case 'after':
						return I18n.translate(type + 'After');
				}
				return '';
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

			didInsertElement: function() {
				this.$().tooltip({container: '#neos-application', placement: 'right'});
			},

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
